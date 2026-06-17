# Laravel Daily Task — Game-Style Guide

> Stack: Laravel 12 · ULID public_id · Inertia.js · React · shadcn/ui · Tailwind CSS

---

## 1. Konsep Inti

Setiap **Task** bisa diulang pada hari-hari tertentu. Setiap hari sistem men-*generate* **Task Instance** berdasarkan jadwal task tersebut. Saat task dihapus, instance yang sudah terbentuk di hari-hari sebelumnya tetap ada di histori — hanya tidak akan di-*generate* lagi untuk hari ini dan ke depan.

---

## 2. Fitur

### Task Management
- Buat, edit, hapus task
- Set nama, deskripsi, ikon/emoji, poin reward (game feel)
- Set warna atau kategori task

### Recurrence (Pengulangan)
Pilihan jadwal pengulangan:
- **Daily** — setiap hari
- **Specific days** — pilih hari (Sen, Sel, Rab, … Min)
- **One-time** — hanya 1 tanggal tertentu
- **X days** — hanya N hari ke depan sejak dibuat (misalnya 7 hari)
- **Date range** — antara tanggal A sampai tanggal B

### Task Instance & Completion
- Task Instance di-*generate* otomatis setiap hari (via Scheduler)
- User bisa centang ✅ / uncentang task instance hari ini
- Instance masa lalu tidak bisa diubah (read-only)
- Soft-delete task: task berhenti di-*generate*, tapi instance lama tetap ada

### Histori
- Lihat task apa saja yang sudah diselesaikan per tanggal
- Filter histori per kategori / task
- Streak counter (berapa hari berturut-turut menyelesaikan task)
- Poin total yang sudah dikumpulkan (gamifikasi)

### Dashboard / Stats
- Poin hari ini vs target
- Completion rate mingguan
- Streak terpanjang
- XP progress bar (level up feel)

---

## 3. Database Design

### `users`
```
id              — bigint, PK
public_id       — ulid, unique, indexed
name
email
email_verified_at
password
timezone        — varchar (default: 'UTC')
total_points    — int default 0
current_streak  — int default 0
longest_streak  — int default 0
last_active_date— date, nullable
timestamps
```

### `task_categories`
```
id              — bigint, PK
public_id       — ulid, unique
user_id         — FK → users.id
name            — varchar
color           — varchar (hex, e.g. #6366f1)
icon            — varchar (emoji atau icon name)
timestamps
```

### `tasks`
```
id              — bigint, PK
public_id       — ulid, unique, indexed
user_id         — FK → users.id
category_id     — FK → task_categories.id, nullable
name            — varchar
description     — text, nullable
icon            — varchar (emoji), nullable
color           — varchar (hex), nullable
points          — int default 10

recurrence_type — enum: daily | specific_days | one_time | x_days | date_range

-- kolom kondisional (nullable, diisi sesuai recurrence_type)
recurrence_days     — json nullable   → ["Mon","Wed","Fri"]
recurrence_ends_at  — date nullable   → batas akhir (untuk x_days & date_range)
recurrence_starts_at— date nullable   → batas awal (untuk date_range & one_time)

is_active       — boolean default true
deleted_at      — timestamp nullable  → soft delete
timestamps
```

> **Catatan:** `deleted_at` di-set saat user "hapus" task. Instance lama tetap ada, generate berhenti.

### `task_instances`
```
id              — bigint, PK
public_id       — ulid, unique, indexed
task_id         — FK → tasks.id
user_id         — FK → users.id (denormalized, untuk query histori lebih mudah)
scheduled_date  — date
completed_at    — timestamp nullable
points_awarded  — int nullable (snapshot poin saat completed)
notes           — text nullable (opsional catatan per instance)
timestamps

UNIQUE (task_id, scheduled_date)
INDEX  (user_id, scheduled_date)
```

### `user_daily_stats`
```
id              — bigint, PK
user_id         — FK → users.id
date            — date
total_tasks     — int default 0   → berapa instance di-generate hari ini
completed_tasks — int default 0
points_earned   — int default 0
timestamps

UNIQUE (user_id, date)
```

---

## 4. Relasi Eloquent

```php
// User
public function tasks(): HasMany          { return $this->hasMany(Task::class); }
public function taskInstances(): HasMany  { return $this->hasMany(TaskInstance::class); }
public function categories(): HasMany     { return $this->hasMany(TaskCategory::class); }

// Task  (dengan SoftDeletes)
public function user(): BelongsTo        { return $this->belongsTo(User::class); }
public function category(): BelongsTo    { return $this->belongsTo(TaskCategory::class); }
public function instances(): HasMany     { return $this->hasMany(TaskInstance::class); }

// TaskInstance
public function task(): BelongsTo        { return $this->belongsTo(Task::class)->withTrashed(); }
public function user(): BelongsTo        { return $this->belongsTo(User::class); }
```

> Gunakan `withTrashed()` di relasi `TaskInstance → Task` agar instance lama masih bisa mengakses data task walau task sudah di-soft-delete.

---

## 5. Logic: Generate Task Instances

```php
// app/Services/DailyQuest/TaskSchedulerService.php

public function generateForDate(User $user, Carbon $date): void
{
    $tasks = Task::where('user_id', $user->id)
        ->where('is_active', true)
        ->whereNull('deleted_at')
        ->get();

    foreach ($tasks as $task) {
        if (! $this->shouldRunOn($task, $date)) continue;

        TaskInstance::firstOrCreate(
            ['task_id' => $task->id, 'scheduled_date' => $date->toDateString()],
            ['user_id' => $user->id, 'public_id' => (string) Str::ulid()]
        );
    }
}

private function shouldRunOn(Task $task, Carbon $date): bool
{
    return match ($task->recurrence_type) {
        'daily'         => true,
        'specific_days' => in_array($date->format('D'), $task->recurrence_days),
        'one_time'      => $task->recurrence_starts_at?->isSameDay($date),
        'x_days'        => $date->between($task->created_at, $task->recurrence_ends_at),
        'date_range'    => $date->between($task->recurrence_starts_at, $task->recurrence_ends_at),
        default         => false,
    };
}
```

```php
// app/Console/Commands/GenerateDailyTasks.php
// Scheduled: setiap hari pukul 00:05 server time, lalu generate berdasarkan timezone user

Schedule::command('tasks:generate-daily')->dailyAt('00:05');
```

---

## 6. Route Structure

```php
// routes/web.php

Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Today's Tasks
    Route::get('/today', [TodayController::class, 'index'])->name('today');
    Route::patch('/instances/{instance:public_id}/complete', [TaskInstanceController::class, 'complete'])->name('instances.complete');
    Route::patch('/instances/{instance:public_id}/uncomplete', [TaskInstanceController::class, 'uncomplete'])->name('instances.uncomplete');

    // Tasks (CRUD)
    Route::resource('tasks', TaskController::class)->parameters(['tasks' => 'task:public_id']);

    // History
    Route::get('/history', [HistoryController::class, 'index'])->name('history');
    Route::get('/history/{date}', [HistoryController::class, 'show'])->name('history.show');

    // Categories
    Route::resource('categories', CategoryController::class)->parameters(['categories' => 'category:public_id']);
});
```

Current implementation in this repo uses a dedicated route file:

```php
// routes/web/daily-quest.php

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('today', [TodayController::class, 'index'])->name('today');
    Route::patch('instances/{instance}/complete', [TaskInstanceController::class, 'complete'])->name('instances.complete');
    Route::patch('instances/{instance}/uncomplete', [TaskInstanceController::class, 'uncomplete'])->name('instances.uncomplete');
    Route::resource('tasks', TaskController::class);
    Route::get('history', [HistoryController::class, 'index'])->name('history');
    Route::get('history/{date}', [HistoryController::class, 'show'])->name('history.show');
    Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
});
```

---

## 7. UI Pages & Components

> **Prinsip:** Mobile-first, SPA feel. Gunakan Drawer untuk aksi sederhana di mobile, Modal untuk konfirmasi/form pendek, dedicated page hanya untuk form kompleks. Semua navigasi via Inertia Link tanpa full reload.

---

### 7.1 Layout Shell

```
┌─────────────────────────────────┐
│  BottomNav (mobile)             │
│  [Today] [Tasks] [History] [Me] │
└─────────────────────────────────┘

Sidebar (desktop, collapsible):
Today | Tasks | History | Profile
```

- `AppLayout.tsx` — sidebar di desktop, bottom nav di mobile
- Persistent header kecil: streak badge 🔥 dan total poin ✨
- Warna aksen sesuai user (bisa dikustomisasi)

---

### 7.2 `/today` — Today Page (Home)

**Tujuan:** Tampilkan dan selesaikan task hari ini.

```
┌──────────────────────────────────┐
│ Selasa, 17 Juni              🔥5 │
│ ████████░░ 4/6 selesai   60 pts │
├──────────────────────────────────┤
│ ○ Olahraga pagi          +10 ✨ │
│ ✓ Baca buku              +15 ✨ │
│ ○ Minum air 8 gelas      +5  ✨ │
│ ✓ Meditasi               +10 ✨ │
│ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─  │
│ Selesai (2)                      │
│ ✓ Jurnal harian          +20 ✨ │
│ ✓ Review flashcard       +10 ✨ │
├──────────────────────────────────┤
│                          [+ FAB] │
└──────────────────────────────────┘
```

**Interaksi:**
- Tap item → toggle complete (optimistic update via Inertia)
- Long-press / swipe kiri → sheet opsi (lihat detail, skip dengan catatan)
- FAB `+` → buka **Create Task Drawer** (form ringkas)
- Progress bar animasi saat task selesai
- Animasi konfetti kecil saat semua task selesai

**Komponen shadcn:**
- `Checkbox` (custom animasi centang)
- `Progress` (completion bar)
- `Sheet` (bottom drawer untuk quick action)
- `Badge` (poin per task)

---

### 7.3 `/tasks` — Task List Page

**Tujuan:** Kelola semua task (aktif & arsip).

```
┌──────────────────────────────────┐
│ My Tasks          [Filter▾] [⋮] │
├──────────────────────────────────┤
│ Tab: [Aktif]  [Arsip]           │
├──────────────────────────────────┤
│ 🏃 Olahraga pagi                │
│    Daily · 10 pts               │
│ 📚 Baca buku                    │
│    Sen Rab Jum · 15 pts         │
│ 💧 Minum air 8 gelas            │
│    Daily · 5 pts                │
├──────────────────────────────────┤
│                          [+ FAB] │
└──────────────────────────────────┘
```

**Interaksi:**
- FAB `+` → navigasi ke `/tasks/create` (dedicated page, form kompleks)
- Tap item → navigasi ke `/tasks/{id}/edit` (dedicated page)
- Swipe kiri item → Sheet dengan opsi:
  - **Pause** (set `is_active = false`) — tidak generate tapi tidak dihapus
  - **Hapus** — konfirmasi via `AlertDialog` shadcn
  - **Duplikat**
- Tab Arsip → task yang sudah di-soft-delete

**Komponen shadcn:**
- `Tabs`
- `AlertDialog` (konfirmasi hapus)
- `Sheet` (swipe action)
- `DropdownMenu` (filter)
- `Badge` (label recurrence)

---

### 7.4 `/tasks/create` & `/tasks/{id}/edit` — Task Form Page

**Tujuan:** Buat atau edit task (dedicated page karena form kompleks).

```
┌──────────────────────────────────┐
│ ← Buat Task Baru                │
├──────────────────────────────────┤
│ Nama task *                      │
│ [________________________]       │
│                                  │
│ Ikon  Warna   Kategori           │
│ [😀]  [████]  [Kesehatan ▾]     │
│                                  │
│ Poin reward                      │
│ [  10  ] pts                     │
│                                  │
│ Pengulangan *                    │
│ ○ Setiap hari                    │
│ ● Hari tertentu                  │
│   [S] [M] [T] [W] [T] [F] [S]  │
│ ○ Hanya sekali                   │
│ ○ Beberapa hari (N hari ke depan)│
│ ○ Rentang tanggal                │
│                                  │
│ [Simpan Task]                    │
└──────────────────────────────────┘
```

**Interaksi:**
- Pilihan recurrence menampilkan sub-form secara kondisional (animate height)
- Klik ikon → `Dialog` Emoji Picker
- Klik warna → `Popover` color palette
- Submit via Inertia `router.post` / `router.put`

**Komponen shadcn:**
- `Input`, `Textarea`
- `RadioGroup` (recurrence type)
- `Toggle` / `ToggleGroup` (pilih hari)
- `DatePicker` (Calendar + Popover)
- `Dialog` (emoji picker)
- `Select` (kategori)
- `Slider` atau `Input` (poin)

---

### 7.5 `/history` — History Page

**Tujuan:** Lihat task yang sudah diselesaikan per hari.

```
┌──────────────────────────────────┐
│ History              [Filter▾]  │
├──────────────────────────────────┤
│ ← Jun 2025 →                    │
│                                  │
│ [Calendar heatmap]               │
│  S  M  T  W  T  F  S           │
│  ·  ●  ●  ◕  ●  ○  ·           │
│  (warna = completion rate)       │
├──────────────────────────────────┤
│ Selasa, 17 Jun                  │
│ ✓ Olahraga pagi        +10 pts │
│ ✓ Baca buku            +15 pts │
│ ─ ─                             │
│ Senin, 16 Jun                   │
│ ✓ Olahraga pagi        +10 pts │
│ ✗ Baca buku            —       │
└──────────────────────────────────┘
```

**Interaksi:**
- Tap tanggal di heatmap → scroll ke section tanggal tersebut
- Task yang di-soft-delete tetap muncul di histori dengan label *"(dihapus)"*
- Filter: kategori, rentang tanggal

**Komponen shadcn:**
- `Calendar` (custom heatmap style)
- `Badge` (poin, status)
- `Select` / `Popover` (filter)

---

### 7.6 `/` — Dashboard

**Tujuan:** Overview statistik dan motivasi.

```
┌──────────────────────────────────┐
│ Selamat pagi, Rafi! ☀️          │
├──────────────────────────────────┤
│  🔥 Streak    ✨ Total   📈 Rate │
│    5 hari     420 pts    83%    │
├──────────────────────────────────┤
│ XP Progress             Lv. 4   │
│ ████████████░░░░  420/500 XP   │
├──────────────────────────────────┤
│ Hari ini: 4/6 task selesai      │
│ [Lihat Task Hari Ini →]         │
├──────────────────────────────────┤
│ Minggu ini                       │
│ [Bar chart completion per hari] │
└──────────────────────────────────┘
```

**Komponen shadcn:**
- `Card`
- `Progress`
- Chart via `recharts` (bar chart mingguan)

---

## 8. Model Conventions

```php
// Semua model pakai ULID sebagai public_id
trait HasPublicId
{
    protected static function bootHasPublicId(): void
    {
        static::creating(function ($model) {
            $model->public_id ??= (string) Str::ulid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id'; // Route model binding pakai public_id
    }
}
```

```php
// app/Models/Task.php
class Task extends Model
{
    use SoftDeletes, HasPublicId;

    protected $casts = [
        'recurrence_days'      => 'array',
        'recurrence_ends_at'   => 'date',
        'recurrence_starts_at' => 'date',
        'is_active'            => 'boolean',
    ];
}
```

---

## 9. Inertia Resource Shape

Contoh data yang dikirim controller ke React:

```php
// TodayController@index
return Inertia::render('Today/Index', [
    'date'      => today()->toDateString(),
    'instances' => TaskInstanceResource::collection($instances),
    'stats'     => [
        'total'     => $instances->count(),
        'completed' => $instances->whereNotNull('completed_at')->count(),
        'points'    => $instances->whereNotNull('completed_at')->sum('points_awarded'),
    ],
    'streak'    => auth()->user()->current_streak,
]);
```

Current repo implementation serializes this payload through:

- `app/Http/Resources/DailyQuest/TaskResource.php`
- `app/Http/Resources/DailyQuest/TaskInstanceResource.php`
- `app/Http/Resources/DailyQuest/TaskCategoryResource.php`

Task and category mutations are validated through:

- `app/Http/Requests/DailyQuest/StoreTaskRequest.php`
- `app/Http/Requests/DailyQuest/UpdateTaskRequest.php`
- `app/Http/Requests/DailyQuest/StoreTaskCategoryRequest.php`

```ts
// types/task.ts
export interface TaskInstance {
  public_id: string
  task: {
    public_id: string
    name: string
    icon: string | null
    color: string | null
    points: number
    category: { name: string; color: string } | null
  }
  scheduled_date: string
  completed_at: string | null
  points_awarded: number | null
  notes: string | null
}
```

---

## 10. Scheduler & Queue

```php
// Artisan command: php artisan tasks:generate-daily
// Loop semua user, generate instance untuk hari ini per timezone user

// Juga bisa trigger on-demand saat user login
// via listener Login + TaskSchedulerService::catchUpForUser()
// jika last_active_date < today → generate missed days (maks 7 hari ke belakang)
```

```php
// app/Jobs/DailyQuest/UpdateUserStatsJob.php
// Streak update (job, dipanggil setelah complete/uncomplete)
class UpdateUserStatsJob implements ShouldQueue
{
    public function handle(): void
    {
        // Cek apakah semua task hari ini selesai
        // Jika ya dan kemarin juga selesai → streak + 1
        // Jika tidak ada task selesai kemarin → reset streak
    }
}
```

---

## 11. Key UX Rules (Ringkasan)

| Aksi | Cara |
|---|---|
| Complete/uncomplete task | Optimistic update — checkbox langsung berubah, sync ke server di background |
| Buat task cepat | FAB → Drawer form mini (nama + recurrence) |
| Buat task lengkap | FAB → Drawer → tombol "Opsi lanjutan" → dedicated page |
| Hapus task | Swipe/long-press → Sheet → AlertDialog konfirmasi |
| Lihat detail histori | Tap tanggal di calendar heatmap |
| Edit task | Tap dari list → dedicated edit page |
| Pause task | Sheet aksi → toggle pause (tidak delete, tidak generate) |

---

## 12. Folder Structure (Frontend)

```
resources/js/
├── Components/
│   ├── TaskItem.tsx          # Item di Today list
│   ├── TaskCard.tsx          # Item di Task list
│   ├── RecurrenceForm.tsx    # Sub-form pengulangan (reusable)
│   ├── CalendarHeatmap.tsx   # History heatmap
│   ├── StatsBar.tsx          # Streak / poin / rate
│   └── ProgressXP.tsx        # XP bar
├── Pages/
│   ├── Dashboard/Index.tsx
│   ├── Today/Index.tsx
│   ├── Tasks/
│   │   ├── Index.tsx
│   │   ├── Create.tsx
│   │   └── Edit.tsx
│   └── History/
│       ├── Index.tsx
│       └── Show.tsx
├── Layouts/
│   └── AppLayout.tsx
└── types/
    └── task.ts
```

---

*Generate dengan: `php artisan make:model Task -mrc --api` lalu sesuaikan controller dengan Inertia.*
