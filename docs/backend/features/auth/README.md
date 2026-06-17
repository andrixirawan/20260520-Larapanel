# Authentication Module

## Metadata

| Field | Value |
| --- | --- |
| Overall status | `MOBILE_IN_PROGRESS` |
| Backend API | `API_READY` |
| Flutter | Implemented |
| React Native | `MOBILE_IN_PROGRESS` |
| Detailed contract | [`API.md`](API.md) |

## Outcome

User dapat membuat account, login, menyelesaikan 2FA bila aktif, memulihkan
password, memuat session, dan logout secara aman pada kedua mobile app.

## Capability Backend

- Register dan issue mobile bearer token.
- Email/password login.
- Two-factor code atau recovery code challenge.
- Google OAuth melalui system browser dan deep-link callback.
- Current user, roles, dan permission map.
- Update nama dan avatar current user.
- Forgot/reset password.
- Logout current token dan logout all tokens.
- Resend email verification.

## API Summary

| Capability | Endpoint |
| --- | --- |
| Register | `POST /api/mobile/auth/register` |
| Login dan 2FA | `POST /api/mobile/auth/login` |
| Current user | `GET /api/mobile/user` |
| Update profile | `POST /api/mobile/user/profile` |
| Forgot password | `POST /api/mobile/auth/forgot-password` |
| Reset password | `POST /api/mobile/auth/reset-password` |
| Logout | `POST /api/mobile/auth/logout` |
| Logout all devices | `POST /api/mobile/auth/logout-all` |
| Resend verification | `POST /api/mobile/email/verification-notification` |

Request, response, Google OAuth, error format, dan implementation reference
berada di [`API.md`](API.md).

## Implementation Progress

| Repo | Status | Evidence | Next action |
| --- | --- | --- | --- |
| `be` | `API_READY` | Mobile bearer auth, 2FA, Google OAuth, recovery, profile name/avatar update, roles/permissions, dan feature tests tersedia. | Jaga contract dan test tetap sinkron. |
| `fl` | Implemented | Auth utama, secure storage, deep link Google callback, dan profile screen edit nama/avatar sudah terhubung ke `POST /api/mobile/user/profile`. | Verifikasi upload avatar, hapus avatar custom, dan session restore pada device nyata. |
| `rn` | `MOBILE_IN_PROGRESS` | Auth utama sudah implemented; profile screen masih read-only. | Tambahkan FormData client/context dan form edit profile. |

## Suggested Screens

| Screen | API/behavior |
| --- | --- |
| Auth loading / splash gate | Baca secure token lalu `GET /user`. |
| Login | `POST /auth/login`, tampilkan 2FA input saat code khusus diterima. |
| Register | `POST /auth/register`. |
| Forgot password | `POST /auth/forgot-password`. |
| Reset password | `POST /auth/reset-password`. |
| Google callback | Simpan token callback lalu `GET /user`. |
| Profile/session | Tampilkan current user, edit nama/avatar, verification state, dan logout actions. |

## Platform-Neutral Pseudocode

```text
APP_START:
  token = secureStorage.read("mobile_access_token")

  if token is empty:
    navigateToLogin()
    stop

  response = api.GET("/user", bearer=token)

  if response.status == 200:
    authState.setUser(response.data)
    navigateToProtectedHome()
  else if response.status == 401:
    secureStorage.delete("mobile_access_token")
    authState.clear()
    navigateToLogin()
  else:
    showRetryableSessionError()
```

```text
LOGIN(email, password, secondFactor?):
  response = api.POST("/auth/login", {
    email,
    password,
    device_name,
    code: secondFactor.code,
    recovery_code: secondFactor.recoveryCode
  })

  if response.status == 409
     and response.code == "two_factor_required":
    showTwoFactorStep()
    stop

  if response.status == 422:
    showFieldErrors(response.errors)
    stop

  secureStorage.write("mobile_access_token", response.access_token)
  authState.setUser(response.user)
  navigateToProtectedHome()
```

```text
UPDATE_PROFILE(name, avatar?, removeAvatar?):
  form = multipartFormData({
    name,
    avatar,
    remove_avatar: removeAvatar
  })

  response = api.POST("/user/profile", form, bearer=token)

  if response.status == 200:
    authState.setUser(response.data)
    refreshAvatar(response.data.avatar)
  else if response.status == 422:
    showFieldErrors(response.errors)
```

```text
LOGOUT:
  try:
    api.POST("/auth/logout")
  finally:
    secureStorage.delete("mobile_access_token")
    authState.clear()
    navigateToLogin()
```

## Business Rules

- Plain token hanya diterima saat token dibuat.
- Token tidak boleh disimpan di storage non-secure.
- Invalid/expired token mengakhiri local session.
- Google secret hanya ada di backend.
- Client memakai `public_id` sebagai user identifier.
- Profile upload memakai `multipart/form-data`; client tidak mengatur boundary
  `Content-Type` secara manual.
- `avatar` dari mobile API berupa absolute URL dan dapat langsung dipakai oleh
  image widget.
- Permission map dipakai untuk UX gating, tetapi backend tetap melakukan
  authorization.

## Required States

- checking session;
- submitting;
- authenticated;
- unauthenticated;
- 2FA required;
- validation error;
- invalid credentials;
- rate limited;
- offline/server error.

## Acceptance Criteria

Backend:

- Semua endpoint pada contract tersedia.
- Token tersimpan hashed dan memiliki expiry.
- Rate limit serta validation aktif.
- Critical auth feature tests lulus.

Flutter dan React Native:

- Token memakai secure storage.
- Restart app dapat memulihkan valid session.
- `401` membersihkan session.
- 2FA code dan recovery code dapat dipakai.
- Forgot/reset password menampilkan state yang benar.
- Google login kembali ke app melalui deep link.
- Nama dan avatar dapat diubah, diganti, serta avatar custom dapat dihapus.
- Logout selalu membersihkan local token.
