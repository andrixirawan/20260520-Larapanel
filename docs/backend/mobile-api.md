# Mobile API Authentication

API ini dibuat untuk React Native/Expo TypeScript dengan Bearer token stateless. Route web Laravel/Fortify tetap berjalan seperti biasa; mobile memakai prefix `/api/mobile`.

## Base URL

Development lokal:

```text
http://<ip-komputer-laravel>:8000/api/mobile
```

Catatan React Native:

- Android emulator biasanya memakai `http://10.0.2.2:8000/api/mobile`.
- iOS simulator bisa memakai `http://localhost:8000/api/mobile`.
- Device fisik harus memakai IP LAN komputer, misalnya `http://192.168.1.10:8000/api/mobile`.
- Selalu kirim header `Accept: application/json`.

## Auth Header

Endpoint protected memakai:

```http
Authorization: Bearer <access_token>
Accept: application/json
Content-Type: application/json
```

Token disimpan hashed di database (`mobile_auth_tokens.token_hash`) dan token asli hanya muncul sekali saat `login` atau `register`. Default expiry: 30 hari lewat `AUTH_MOBILE_TOKEN_EXPIRE_MINUTES`.

## Route List

| Method | Route | Auth | Kegunaan |
| --- | --- | --- | --- |
| POST | `/api/mobile/auth/register` | No | Register user dan langsung mendapat token. |
| POST | `/api/mobile/auth/login` | No | Login dengan email/password, mendukung 2FA. |
| POST | `/api/mobile/auth/google` | No | Login/register dengan Google ID token dari React Native. |
| POST | `/api/mobile/auth/forgot-password` | No | Kirim email reset password. Response dibuat aman dari user enumeration. |
| POST | `/api/mobile/auth/reset-password` | No | Reset password memakai token dari email. Semua token mobile user dicabut. |
| GET | `/api/mobile/user` | Bearer | Ambil data user yang sedang login. |
| POST | `/api/mobile/auth/logout` | Bearer | Cabut token yang sedang dipakai. |
| POST | `/api/mobile/auth/logout-all` | Bearer | Cabut semua token mobile milik user. |
| POST | `/api/mobile/email/verification-notification` | Bearer | Kirim ulang email verifikasi. |

## Request And Response

### Register

```http
POST /api/mobile/auth/register
```

Body:

```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "password": "password",
  "password_confirmation": "password",
  "device_name": "iPhone 15"
}
```

Response `201`:

```json
{
  "message": "Authenticated.",
  "token_type": "Bearer",
  "access_token": "plain-text-token",
  "expires_at": "2026-07-06T13:00:00.000000Z",
  "user": {
    "id": 1,
    "name": "Jane Doe",
    "email": "jane@example.com",
    "avatar": null,
    "has_custom_avatar": false,
    "email_verified_at": null,
    "is_email_verified": false,
    "two_factor_enabled": false,
    "created_at": "2026-06-06T13:00:00.000000Z",
    "updated_at": "2026-06-06T13:00:00.000000Z"
  }
}
```

### Login

```http
POST /api/mobile/auth/login
```

Body:

```json
{
  "email": "jane@example.com",
  "password": "password",
  "device_name": "Pixel 9"
}
```

Jika 2FA aktif dan belum mengirim kode:

```json
{
  "message": "Two-factor authentication is required.",
  "code": "two_factor_required",
  "two_factor_required": true
}
```

Kirim ulang login dengan salah satu:

```json
{
  "email": "jane@example.com",
  "password": "password",
  "code": "123456"
}
```

atau:

```json
{
  "email": "jane@example.com",
  "password": "password",
  "recovery_code": "xxxx-yyyy"
}
```

### Google Login

```http
POST /api/mobile/auth/google
```

Body:

```json
{
  "id_token": "google-id-token-dari-react-native",
  "device_name": "Pixel 9"
}
```

Response `200` sama seperti login email/password:

```json
{
  "message": "Authenticated.",
  "token_type": "Bearer",
  "access_token": "plain-text-token",
  "expires_at": "2026-07-06T13:00:00.000000Z",
  "user": {
    "id": 1,
    "name": "Jane Doe",
    "email": "jane@example.com",
    "avatar": "https://lh3.googleusercontent.com/a/example",
    "has_custom_avatar": false,
    "email_verified_at": "2026-06-06T13:00:00.000000Z",
    "is_email_verified": true,
    "two_factor_enabled": false,
    "created_at": "2026-06-06T13:00:00.000000Z",
    "updated_at": "2026-06-06T13:00:00.000000Z"
  }
}
```

Backend akan:

- Verifikasi `id_token` ke Google lewat endpoint `https://oauth2.googleapis.com/tokeninfo`.
- Menolak token jika `aud` tidak ada di `GOOGLE_MOBILE_CLIENT_IDS`.
- Menolak token jika email Google belum terverifikasi.
- Membuat user baru jika email belum ada, atau menautkan akun lama berdasarkan `google_id`/`email`.
- Mengembalikan Bearer token mobile biasa untuk dipakai di endpoint protected.

Konfigurasi Laravel:

```env
GOOGLE_CLIENT_ID=web-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=secret-untuk-login-web
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"

# Pisahkan dengan koma jika aplikasi mobile punya lebih dari satu client ID.
GOOGLE_MOBILE_CLIENT_IDS="web-client-id.apps.googleusercontent.com,android-client-id.apps.googleusercontent.com,ios-client-id.apps.googleusercontent.com"
```

Catatan praktis:

- Jika library React Native dikonfigurasi memakai `webClientId`, biasanya `aud` ID token adalah Web Client ID.
- Jika flow menghasilkan ID token dengan Android/iOS Client ID sebagai audience, masukkan juga client ID itu ke `GOOGLE_MOBILE_CLIENT_IDS`.
- Setelah mengubah env production, jalankan `php artisan config:clear` atau rebuild cache config.

### Current User

```http
GET /api/mobile/user
Authorization: Bearer <access_token>
```

Response:

```json
{
  "data": {
    "id": 1,
    "name": "Jane Doe",
    "email": "jane@example.com",
    "avatar": null,
    "has_custom_avatar": false,
    "email_verified_at": null,
    "is_email_verified": false,
    "two_factor_enabled": false,
    "created_at": "2026-06-06T13:00:00.000000Z",
    "updated_at": "2026-06-06T13:00:00.000000Z"
  }
}
```

### Forgot Password

```http
POST /api/mobile/auth/forgot-password
```

Body:

```json
{
  "email": "jane@example.com"
}
```

Response selalu generik untuk email valid format:

```json
{
  "message": "If an account exists for this email, a password reset link has been sent."
}
```

### Reset Password

```http
POST /api/mobile/auth/reset-password
```

Body:

```json
{
  "email": "jane@example.com",
  "token": "token-dari-email",
  "password": "new-password",
  "password_confirmation": "new-password"
}
```

Saat sukses, semua token mobile user dicabut agar session lama tidak tetap aktif.

## Error Format

Validation error `422`:

```json
{
  "message": "The email field is required.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

Unauthorized `401`:

```json
{
  "message": "Unauthenticated."
}
```

Rate limited `429`:

```json
{
  "message": "Too many login attempts. Please try again in 60 seconds.",
  "errors": {
    "email": ["Too many login attempts. Please try again in 60 seconds."]
  }
}
```

## React Native TypeScript Example

Gunakan `expo-secure-store` untuk token. Hindari AsyncStorage untuk token auth.

```ts
import * as SecureStore from "expo-secure-store";

const API_URL = process.env.EXPO_PUBLIC_API_URL;
const TOKEN_KEY = "mobile_access_token";

export type MobileUser = {
  id: number;
  name: string;
  email: string;
  avatar: string | null;
  has_custom_avatar: boolean;
  email_verified_at: string | null;
  is_email_verified: boolean;
  two_factor_enabled: boolean;
  created_at: string | null;
  updated_at: string | null;
};

export class ApiError extends Error {
  constructor(
    message: string,
    public status: number,
    public errors?: Record<string, string[]>,
    public code?: string,
  ) {
    super(message);
  }
}

async function request<T>(path: string, init: RequestInit = {}): Promise<T> {
  if (!API_URL) {
    throw new Error("EXPO_PUBLIC_API_URL is not configured");
  }

  const token = await SecureStore.getItemAsync(TOKEN_KEY);

  const response = await fetch(`${API_URL}${path}`, {
    ...init,
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...init.headers,
    },
  });

  const json = await response.json().catch(() => ({}));

  if (!response.ok) {
    throw new ApiError(
      json.message ?? "Request failed",
      response.status,
      json.errors,
      json.code,
    );
  }

  return json as T;
}

export async function login(input: {
  email: string;
  password: string;
  device_name?: string;
  code?: string;
  recovery_code?: string;
}) {
  const data = await request<{
    access_token: string;
    token_type: "Bearer";
    expires_at: string | null;
    user: MobileUser;
  }>("/auth/login", {
    method: "POST",
    body: JSON.stringify(input),
  });

  await SecureStore.setItemAsync(TOKEN_KEY, data.access_token);

  return data.user;
}

export async function loginWithGoogle(input: {
  id_token: string;
  device_name?: string;
}) {
  const data = await request<{
    access_token: string;
    token_type: "Bearer";
    expires_at: string | null;
    user: MobileUser;
  }>("/auth/google", {
    method: "POST",
    body: JSON.stringify(input),
  });

  await SecureStore.setItemAsync(TOKEN_KEY, data.access_token);

  return data.user;
}

export async function currentUser() {
  const data = await request<{ data: MobileUser }>("/user");

  return data.data;
}

export async function logout() {
  await request<{ message: string }>("/auth/logout", { method: "POST" });
  await SecureStore.deleteItemAsync(TOKEN_KEY);
}
```

`.env` di React Native/Expo:

```env
EXPO_PUBLIC_API_URL=http://10.0.2.2:8000/api/mobile
```

## Backend Notes

- Jalankan migration: `php artisan migrate`.
- Token expiry bisa diubah dengan `AUTH_MOBILE_TOKEN_EXPIRE_MINUTES=43200`.
- Google login mobile butuh `GOOGLE_MOBILE_CLIENT_IDS`; default `.env.example` memakai `GOOGLE_CLIENT_ID` agar web client ID bisa langsung dipakai.
- Gunakan HTTPS di production agar Bearer token tidak bocor di jaringan.
- Untuk endpoint protected baru, pakai middleware `mobile.auth`.
- Jika perlu membatasi fitur per token nanti, kolom `abilities` sudah tersedia.
