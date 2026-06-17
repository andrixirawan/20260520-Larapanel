# Daily Task — UI Design & Build Checklist

> Platform: Web (Inertia + React + shadcn) · Mobile (Flutter / React Native Expo)
> Approach: Mobile-first, SPA-like feel, semua platform tujuannya mirip secara UX

---

## BAGIAN 1 — UI/UX Design Philosophy

### Prinsip Utama

**1. Clean Index, Complexity Hidden**
Halaman utama hanya menampilkan task hari ini. Tidak ada form inline, tidak ada panel tersembunyi. Semua aksi keluar via overlay (modal/sheet/popover). User harus merasa seperti membuka app native, bukan website.

**2. Overlay-First untuk Aksi Sederhana**
Aksi yang butuh ≤3 input → pakai overlay (bottom sheet / modal / popover).
Aksi yang lebih kompleks → dedicated page/screen. Jangan campurkan keduanya.

| Aksi                       | Web                              | Mobile                   |
| -------------------------- | -------------------------------- | ------------------------ |
| Toggle complete task       | Tap langsung (no overlay)        | Tap langsung             |
| Tambah task cepat          | Bottom sheet dari FAB            | Bottom sheet dari FAB    |
| Edit task lengkap          | Navigate ke halaman baru         | Push screen baru         |
| Konfirmasi hapus           | Modal/AlertDialog                | Bottom sheet konfirmasi  |
| Pilih kategori             | Popover / Select dropdown        | Bottom sheet list picker |
| Tambah catatan ke instance | Inline expand atau popover kecil | Bottom sheet             |
| Filter history             | Popover / slide-in panel         | Bottom sheet             |

**3. FAB Selalu Ada di Halaman List**
Setiap halaman list punya satu entry point utama: tombol `+` yang mengambang di kanan bawah. Jangan ada tombol tambah di header atau tengah halaman kosong (ghost button kosong boleh sebagai secondary).

**4. Feedback Instan (Optimistic UI)**
Toggle task complete tidak perlu tunggu server. UI berubah duluan, server sync di background. Jika server error, UI rollback dengan notifikasi kecil.

**5. Satu Bottom Navigation**
4 item saja: **Today · Tasks · History · Profile**. Tidak lebih. Tidak ada nested tab di bottom nav.

---

### Peta Halaman / Screen

```
App
├── Today (Home)
│   └── [Sheet] Quick Add Task
│   └── [Sheet] Task Item Options (pause, catatan, skip)
│
├── Tasks
│   ├── [Sheet] Quick Add (nama + recurrence cepat)
│   ├── [Page/Screen] Create Task (form lengkap)
│   └── [Page/Screen] Edit Task (form lengkap)
│
├── History
│   └── [Page/Screen] Detail Hari (tap dari kalender)
│
└── Profile / Settings
    ├── [Sheet] Edit Display Name
    ├── [Sheet] Pilih Tema / Warna Aksen
    └── [Page/Screen] Account Settings
```

---

### Anatomi Per Halaman

---

#### TODAY (Halaman Utama)

**Tujuan:** User buka app → langsung lihat apa yang harus dikerjakan hari ini.

```
┌────────────────────────────────┐
│  Selasa, 17 Jun     🔥 5 hari  │
│  ████████░░  4 / 6  •  60 pts │
├────────────────────────────────┤
│                                │
│  [ ] 🏃 Olahraga pagi   10✨  │
│  [✓] 📚 Baca buku       15✨  │
│  [ ] 💧 Minum air        5✨  │
│  [✓] 🧘 Meditasi        10✨  │
│                                │
│  ── Selesai (2) ───────────── │
│  [✓] 📓 Jurnal          20✨  │
│  [✓] 🃏 Flashcard       10✨  │
│                                │
├────────────────────────────────┤
│                        [ + ]   │
└────────────────────────────────┘
```

- Header: tanggal + streak badge + progress bar completion + poin hari ini
- List task dikelompokkan: **Belum selesai** di atas, **Selesai** di bawah (collapsible)
- Tap checkbox → complete/uncomplete, animasi centang, poin counter naik
- Long press / swipe kiri item → bottom sheet opsi (lihat detail, skip, tambah catatan)
- FAB `+` → bottom sheet "Tambah Task Cepat" (nama + recurrence 1 klik)
- Jika semua selesai → full-screen celebration moment (konfetti ringan, pesan motivasi)
- Jika tidak ada task → empty state dengan ilustrasi + tombol "Tambah Task Pertama"

---

#### TASKS (Manajemen Task)

**Tujuan:** Lihat, kelola, dan atur semua task yang dimiliki user.

```
┌────────────────────────────────┐
│  Tasks               [⊞] [⋮]  │
│  [Aktif]  [Dijeda]  [Arsip]   │
├────────────────────────────────┤
│  🏃 Olahraga pagi             │
│     Daily  •  10✨  •  🟢      │
│                                │
│  📚 Baca buku                 │
│     Sen Rab Jum  •  15✨       │
│                                │
│  💧 Minum air 8 gelas         │
│     Daily  •  5✨              │
├────────────────────────────────┤
│                        [ + ]   │
└────────────────────────────────┘
```

- Tab: Aktif / Dijeda / Arsip (soft-deleted)
- Tiap card menampilkan ikon, nama, recurrence summary, poin, status aktif
- Tap card → navigasi ke **Edit Task Screen** (dedicated)
- Long press / swipe → bottom sheet: Edit · Jeda · Duplikat · Hapus
- Hapus → konfirmasi via modal/dialog kecil di atas bottom sheet
- FAB `+` → langsung navigate ke **Create Task Screen** (form kompleks, dedicated)
- View toggle: list vs grid (opsional, tersimpan di preference)

---

#### CREATE / EDIT TASK (Screen Dedicated)

**Tujuan:** Buat atau edit task dengan kontrol penuh atas recurrence.

```
┌────────────────────────────────┐
│  ←  Buat Task Baru            │
├────────────────────────────────┤
│                                │
│  [😀]  Nama task *            │
│         [_____________________]│
│                                │
│  Kategori      Poin            │
│  [Kesehatan ▾] [  10  ] ✨    │
│                                │
│  Warna aksen                  │
│  ● ○ ○ ○ ○ ○ ○               │
│                                │
│  ─── Jadwal Pengulangan ─────  │
│  ○ Setiap hari                 │
│  ● Hari tertentu               │
│    S  M  T  W  T  F  S       │
│    ○  ●  ○  ●  ○  ●  ○       │
│  ○ Hanya sekali    [tgl ___]  │
│  ○ N hari ke depan [  7  ]   │
│  ○ Rentang tanggal [A] → [B] │
│                                │
│  [        Simpan Task        ] │
└────────────────────────────────┘
```

- Satu screen scroll, tidak ada tab/step (form tidak sepanjang itu)
- Pilih ikon → bottom sheet / modal emoji picker
- Pilih warna → popover color swatch
- Pilih kategori → bottom sheet searchable list
- Recurrence section animated: pilih type → sub-input muncul dengan animasi
- Validasi inline, tidak perlu submit dulu untuk tahu ada error
- Tombol simpan fixed di bottom (tidak ikut scroll)

---

#### HISTORY

**Tujuan:** Review apa yang sudah dikerjakan di hari-hari sebelumnya.

```
┌────────────────────────────────┐
│  History             [Filter▾]│
├────────────────────────────────┤
│  ← Juni 2025 →               │
│                               │
│  M  T  W  T  F  S  S         │
│  ●  ●  ◕  ●  ○  ·  ·        │
│  ●  ◕  ●  ●  ●  ·  ·        │
│  (● penuh, ◕ sebagian, ○ nol) │
├────────────────────────────────┤
│  Rabu, 17 Jun                 │
│  ✓ Olahraga pagi     +10 ✨  │
│  ✓ Baca buku         +15 ✨  │
│  ✗ Minum air          —      │
│                               │
│  Selasa, 16 Jun               │
│  ✓ (semua 5 task)    +60 ✨  │
│  [Lihat detail →]             │
└────────────────────────────────┘
```

- Kalender heatmap di atas (1 bulan, swipe untuk bulan lain)
- Tap tanggal di kalender → scroll ke section tanggal tersebut
- Section per hari: list task instance, status, poin
- Task yang sudah dihapus tetap muncul dengan label kecil _(dihapus)_
- Hari dengan semua task selesai → dot hijau penuh; sebagian → kuning; nol → merah/abu

---

#### PROFILE / SETTINGS

```
┌────────────────────────────────┐
│  [Avatar]                      │
│  Rafi Wicaksono               │
│  Level 4  •  420 XP / 500 XP  │
│  ████████████░░░░             │
├────────────────────────────────┤
│  🔥 Streak terpanjang   12 hr │
│  ✨ Total poin         1.240  │
│  ✅ Task diselesaikan    342  │
├────────────────────────────────┤
│  Pengaturan                   │
│  › Kategori task              │
│  › Notifikasi                 │
│  › Tema & warna aksen         │
│  › Zona waktu                 │
│  › Akun & privasi             │
├────────────────────────────────┤
│  [Keluar]                      │
└────────────────────────────────┘
```

- Stats ringkas di header profile
- Tiap baris pengaturan → bottom sheet atau dedicated screen sesuai kompleksitas
- Kelola Kategori → dedicated screen (CRUD sederhana)

---

### Catatan Perbedaan Per Platform

| Konsep           | Web (shadcn)                    | Flutter                          | React Native Expo                        |
| ---------------- | ------------------------------- | -------------------------------- | ---------------------------------------- |
| Bottom sheet     | `Sheet` (shadcn)                | `showModalBottomSheet`           | `@gorhom/bottom-sheet`                   |
| Modal/Alert      | `AlertDialog`, `Dialog`         | `showDialog` + `AlertDialog`     | `Modal` + custom                         |
| FAB              | `Button` fixed position         | `FloatingActionButton`           | `TouchableOpacity` absolute              |
| Navigation       | Inertia `router.visit`          | `Navigator.push` / `go_router`   | `expo-router` push                       |
| Toast / snackbar | `Sonner` / shadcn `Toast`       | `ScaffoldMessenger.showSnackBar` | `react-native-toast-message`             |
| Swipe aksi       | CSS transform + event           | `Dismissible` widget             | `react-native-gesture-handler` Swipeable |
| Animasi list     | Framer Motion / CSS             | `AnimatedList`                   | `Animated` API / Reanimated              |
| Progress bar     | `Progress` (shadcn)             | `LinearProgressIndicator`        | custom atau `expo-progress`              |
| Toggle/checkbox  | `Checkbox` (shadcn)             | `Checkbox` / `Switch`            | `Checkbox` dari expo                     |
| Bottom nav       | custom atau `NavigationMenu`    | `BottomNavigationBar`            | `Tabs` dari expo-router                  |
| Heatmap kalender | custom dengan shadcn `Calendar` | custom `GridView`                | custom `FlatList` grid                   |

> **Tujuannya sama:** User experience yang mirip di ketiga platform. Nama widget berbeda, tapi behavior dan visual hierarchy identik.

---

---

## BAGIAN 2 — Build Progress Checklist

> Urutan: Core dulu (tanpa ini app tidak jalan) → Fitur penting → MVP → Polish

---

### 🗄️ BACKEND — Laravel

#### Phase 1 — Foundation

- [x] Buat trait `HasPublicId` dengan ULID auto-generate
- [x] Migration: `users` (tambah kolom `timezone`, `total_points`, `current_streak`, `longest_streak`, `last_active_date`)
- [x] Migration: `task_categories` (id, public_id, user_id, name, color, icon)
- [x] Migration: `tasks` (id, public_id, user_id, category_id, name, description, icon, color, points, recurrence_type, recurrence_days, recurrence_ends_at, recurrence_starts_at, is_active, deleted_at)
- [x] Migration: `task_instances` (id, public_id, task_id, user_id, scheduled_date, completed_at, points_awarded, notes, + unique constraint)
- [x] Migration: `user_daily_stats` (id, user_id, date, total_tasks, completed_tasks, points_earned)
- [x] Model `Task` (SoftDeletes, HasPublicId, casts, relasi)
- [x] Model `TaskInstance` (HasPublicId, relasi dengan `withTrashed()`)
- [x] Model `TaskCategory` (HasPublicId, relasi)
- [x] Update Model `User` (relasi, helper method streak)

#### Phase 2 — Core Logic

- [x] `TaskSchedulerService::generateForDate(User, Carbon)` — logika recurrence semua tipe
- [x] `TaskSchedulerService::shouldRunOn(Task, Carbon)` — cek apakah task perlu di-generate
- [x] Artisan command `tasks:generate-daily` (loop semua user, generate hari ini)
- [x] Register scheduler command di `routes/console.php` (daily 00:05)
- [x] Logic catch-up: saat user login, generate hari yang terlewat (maks 7 hari ke belakang)
- [x] `UpdateUserStatsJob` — update `total_points`, `current_streak`, `last_active_date` setelah complete/uncomplete

#### Phase 3 — API / Controller

- [x] `TaskCategoryController` (index, store, update, destroy)
- [x] `TaskController` (index, create, store, show, edit, update, destroy dengan soft-delete)
- [x] `TodayController@index` — ambil instances hari ini beserta stats
- [x] `TaskInstanceController@complete` — set `completed_at`, award points
- [x] `TaskInstanceController@uncomplete` — unset `completed_at`, revoke points
- [x] `HistoryController@index` — list tanggal + summary stats per hari
- [x] `HistoryController@show` — detail instances per tanggal
- [x] `DashboardController@index` — stats streak, poin, chart mingguan

#### Phase 4 — API Resources & Validation

- [ ] `TaskResource` (public_id, name, icon, color, points, recurrence summary, category)
- [ ] `TaskInstanceResource` (public_id, task, scheduled_date, completed_at, points_awarded)
- [ ] `TaskCategoryResource`
- [ ] Form Request: `StoreTaskRequest` (validasi recurrence kondisional)
- [ ] Form Request: `UpdateTaskRequest`
- [ ] Form Request: `StoreTaskCategoryRequest`

---

### 🌐 FRONTEND — Web (Inertia + React + shadcn)

#### Phase 1 — Setup

- [x] All Done

#### Phase 2 — Today Page

- [ ] Halaman `Today/Index.tsx` (render list instances)
- [ ] Komponen `TaskItem.tsx` (checkbox, nama, ikon, poin, animasi centang)
- [ ] Toggle complete/uncomplete dengan optimistic update
- [ ] Grup "Belum selesai" dan "Selesai" (collapsible section)
- [ ] Progress bar + stats header (completion, poin, streak)
- [ ] FAB `+` yang memanggil bottom sheet
- [ ] Bottom sheet "Tambah Task Cepat" (nama + recurrence satu klik)
- [ ] Bottom sheet opsi item (long press / swipe): catatan, skip, lihat detail
- [ ] Empty state jika tidak ada task hari ini
- [ ] Celebration state jika semua task selesai

#### Phase 3 — Task Management Pages

- [ ] Halaman `Tasks/Index.tsx` (list task dengan tab Aktif/Dijeda/Arsip)
- [ ] Komponen `TaskCard.tsx` (nama, ikon, recurrence summary, poin, status)
- [ ] Swipe/long-press → bottom sheet: Edit, Jeda, Duplikat, Hapus
- [ ] `AlertDialog` konfirmasi hapus task
- [ ] FAB `+` navigate ke Create page
- [ ] Halaman `Tasks/Create.tsx` (form lengkap)
- [ ] Halaman `Tasks/Edit.tsx` (form sama, pre-filled)
- [ ] Komponen `RecurrenceForm.tsx` (reusable, animated kondisional sub-input)
- [ ] Komponen `EmojiPicker.tsx` (dalam Dialog / bottom sheet)
- [ ] Komponen `ColorPicker.tsx` (dalam Popover)
- [ ] Komponen `CategoryPicker.tsx` (dalam bottom sheet searchable)
- [ ] `DatePicker` untuk recurrence one-time / date range

#### Phase 4 — History Page

- [ ] Halaman `History/Index.tsx`
- [ ] Komponen `CalendarHeatmap.tsx` (custom grid dengan dot warna completion)
- [ ] Scroll ke section tanggal saat tap di kalender
- [ ] List group per tanggal (instances + status + poin)
- [ ] Filter history (kategori, rentang) via popover
- [ ] Halaman `History/Show.tsx` (detail satu hari, navigasi dari heatmap)

#### Phase 5 — Dashboard & Profile

- [ ] Halaman `Dashboard/Index.tsx` (stats card, XP bar, chart mingguan)
- [ ] Komponen `StatsCard.tsx` (streak, total poin, completion rate)
- [ ] Komponen `XPProgressBar.tsx` (level + progress)
- [ ] Chart mingguan (bar chart dengan recharts)
- [ ] Halaman `Profile/Index.tsx` (avatar, stats, menu pengaturan)
- [ ] Bottom sheet edit display name
- [ ] Halaman `Categories/Index.tsx` (CRUD kategori)

---

### 📱 FRONTEND — Mobile (Flutter atau React Native Expo)

> Checklist ini berlaku untuk keduanya. Nama komponen disesuaikan per platform.

#### Phase 1 — Setup

- [ ] Init project (Flutter: `flutter create` / Expo: `npx create-expo-app`)
- [ ] Setup navigation: tab-based bottom nav (4 tab)
- [ ] Setup state management (Flutter: Riverpod/Bloc · RN: Zustand/Jotai)
- [ ] Setup HTTP client + auth token storage
- [ ] Buat model/class: `Task`, `TaskInstance`, `TaskCategory`, `User`
- [ ] Buat shared theme: warna, typography, spacing sesuai design tokens
- [ ] Setup global snackbar/toast

#### Phase 2 — Today Screen

- [ ] Screen `TodayScreen` (list instances hari ini)
- [ ] Widget/komponen `TaskItem` (checkbox, nama, ikon, poin)
- [ ] Toggle complete/uncomplete (optimistic update)
- [ ] Grup pending vs selesai (collapsible)
- [ ] Header stats (streak, progress, poin)
- [ ] FAB → bottom sheet "Quick Add Task"
- [ ] Long press / swipe item → bottom sheet opsi
- [ ] Empty state
- [ ] Celebration feedback (haptic + animasi ringan)

#### Phase 3 — Task Management Screens

- [ ] Screen `TaskListScreen` (tab Aktif / Dijeda / Arsip)
- [ ] Widget `TaskCard`
- [ ] Swipe gesture → bottom sheet aksi
- [ ] Bottom sheet konfirmasi hapus
- [ ] FAB → navigate ke `CreateTaskScreen`
- [ ] Screen `CreateTaskScreen` (form scroll, semua field)
- [ ] Screen `EditTaskScreen` (pre-filled, sama layout)
- [ ] Widget `RecurrenceSelector` (animated, kondisional)
- [ ] Bottom sheet emoji picker
- [ ] Bottom sheet color picker
- [ ] Bottom sheet category picker (searchable list)

#### Phase 4 — History Screen

- [ ] Screen `HistoryScreen` (kalender heatmap + list)
- [ ] Widget `CalendarHeatmap` (grid custom)
- [ ] Tap tanggal → scroll ke section / navigate ke detail
- [ ] Screen `HistoryDetailScreen` (detail satu hari)
- [ ] Bottom sheet filter

#### Phase 5 — Profile & Settings

- [ ] Screen `ProfileScreen` (stats, menu)
- [ ] Bottom sheet edit nama
- [ ] Screen `CategoryScreen` (CRUD)
- [ ] Screen `SettingsScreen` (notifikasi, tema, timezone)
- [ ] Push notification setup (opsional)

---

### ✅ CAPABILITY CHECKLIST (Feature-level)

> "User bisa..." — mulai dari paling core ke MVP

#### Auth

- [ ] User bisa daftar akun baru
- [ ] User bisa login
- [ ] User bisa logout
- [ ] Sesi tetap aktif (remember me / token refresh)

#### Task — Core

- [ ] User bisa membuat task dengan nama
- [ ] User bisa memilih task berulang setiap hari (daily)
- [ ] User bisa memilih task berulang di hari-hari tertentu (Senin, Rabu, Jumat, dll)
- [ ] User bisa membuat task yang hanya terjadi sekali (one-time)
- [ ] User bisa membuat task yang aktif N hari ke depan
- [ ] User bisa membuat task dengan rentang tanggal tertentu
- [ ] User bisa menambah poin reward ke task
- [ ] User bisa menghapus task (soft-delete)
- [ ] Task yang dihapus berhenti muncul di hari ini dan ke depan
- [ ] Task yang dihapus masih terlihat di histori hari-hari sebelumnya

#### Task — Additional

- [ ] User bisa mengedit task yang sudah ada
- [ ] User bisa menjeda task (pause — tidak digenerate tapi tidak dihapus)
- [ ] User bisa mengaktifkan kembali task yang dijeda
- [ ] User bisa menduplikat task
- [ ] User bisa menambah ikon/emoji ke task
- [ ] User bisa memilih warna aksen task
- [ ] User bisa menambah deskripsi task

#### Kategori

- [ ] User bisa membuat kategori task
- [ ] User bisa memberi warna dan ikon ke kategori
- [ ] User bisa mengelompokkan task ke dalam kategori
- [ ] User bisa mengedit dan menghapus kategori

#### Today / Task Instance

- [ ] User melihat list task yang harus dikerjakan hari ini
- [ ] User bisa mencentang task sebagai selesai
- [ ] User bisa membatalkan centang task
- [ ] Task yang selesai terpisah dari yang belum (di bawah, collapsed)
- [ ] User melihat progress completion hari ini (berapa dari total)
- [ ] User melihat poin yang sudah dikumpulkan hari ini
- [ ] User bisa menambah catatan pada task instance hari ini
- [ ] Task instance di hari lalu tidak bisa diubah (read-only)

#### Histori

- [ ] User bisa melihat task apa saja yang dikerjakan di tanggal tertentu
- [ ] User bisa melihat status (selesai/tidak) per task per tanggal
- [ ] User bisa melihat kalender dengan indikator completion rate per hari
- [ ] Task yang sudah dihapus tetap terlihat di histori
- [ ] User bisa memfilter histori per kategori

#### Gamifikasi

- [ ] User mendapat poin setiap task selesai
- [ ] Poin direvoke jika task di-uncomplete
- [ ] User memiliki streak harian (berapa hari berturut-turut ada task yang selesai)
- [ ] Streak reset jika hari itu tidak ada task yang diselesaikan (dengan toleransi konfigurasi)
- [ ] User memiliki total poin kumulatif
- [ ] User melihat XP progress menuju level berikutnya
- [ ] Ada feedback visual saat semua task hari ini selesai

#### Stats

- [ ] User melihat streak saat ini dan streak terpanjang
- [ ] User melihat total poin sepanjang waktu
- [ ] User melihat completion rate (minggu ini)
- [ ] User melihat grafik activity mingguan

#### Profile & Settings

- [ ] User bisa mengubah nama tampilan
- [ ] User bisa mengatur timezone
- [ ] User bisa memilih tema (light/dark)
- [ ] User bisa memilih warna aksen aplikasi

---

### 🚀 MVP Definition

MVP tercapai ketika semua item berikut sudah berfungsi end-to-end:

- [ ] Auth (daftar, login, logout)
- [ ] Buat task dengan recurrence: daily + specific days
- [ ] Generate task instance otomatis setiap hari
- [ ] Today screen: lihat dan centang task
- [ ] Poin counter harian sederhana
- [ ] Hapus task (soft-delete, tidak muncul lagi tapi histori aman)
- [ ] History screen: lihat task per tanggal
- [ ] Streak counter sederhana

---

_Setelah MVP, lanjut ke: kategori, gamifikasi lengkap (XP/level), notifikasi push, statistik lanjutan, dan duplikat task._
