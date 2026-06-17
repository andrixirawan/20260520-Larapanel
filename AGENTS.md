# AGENTS.md

## Project

Laravel 13 + Inertia + React + TypeScript + shadcn/ui.

This project is for learning Laravel and building random independent features. Features may be unrelated, so group files by feature inside Laravel's default folders.

## Main Pattern

Use Laravel-default structure with feature-based subfolders.

Default flow:

```txt
Route → Controller → FormRequest → Action/Service → Model → Resource/Data → Inertia Page/API Response
```

Use these patterns only when needed:

```txt
Validation      → FormRequest
Business logic  → Action
Reusable logic  → Service
Index/filter    → Query
Authorization   → Policy
Response shape  → Resource/Data
Shared helper   → Support
```

Do not create Repository classes by default. Use Eloquent directly unless there is a strong reason.

## Backend Structure

Group backend files by feature inside Laravel's default folders.

```txt
app/
├── Models/
│   └── FeatureName/
│
├── Http/
│   ├── Controllers/
│   │   └── FeatureName/
│   ├── Requests/
│   │   └── FeatureName/
│   └── Resources/
│       └── FeatureName/
│
├── Actions/
│   └── FeatureName/
│
├── Queries/
│   └── FeatureName/
│
├── Services/
│   └── FeatureName/
│
├── Policies/
│   └── FeatureName/
│
├── Data/
│   └── FeatureName/
│
└── Support/
```

Example:

```txt
app/Models/Post/Post.php
app/Http/Controllers/Post/PostController.php
app/Http/Requests/Post/StorePostRequest.php
app/Actions/Post/CreatePostAction.php
app/Queries/Post/PostIndexQuery.php
app/Services/Post/PostCoverService.php
app/Data/Post/PostData.php
```

## Route Structure

Routes must be separated by feature/module.

```txt
routes/
├── web.php
├── api.php
├── web/
│   ├── post.php
│   ├── pos.php
│   └── settings.php
└── api/
    ├── post.php
    ├── pos.php
    └── mobile.php
```

Use `routes/web/*.php` for Inertia/web routes.

Use `routes/api/*.php` for API/mobile/external routes.

Main route files should load feature route files:

```php
// routes/web.php
require __DIR__ . '/web/post.php';
require __DIR__ . '/web/pos.php';
require __DIR__ . '/web/settings.php';
```

```php
// routes/api.php
require __DIR__ . '/api/post.php';
require __DIR__ . '/api/pos.php';
require __DIR__ . '/api/mobile.php';
```

Do not rely on implicit route-model binding for mutable feature routes. For routes
that update, delete, pause, complete, duplicate, or otherwise mutate data, accept
the route parameter as a string in the controller and explicitly resolve the
record through the authenticated user's scoped relationship or an explicit query.
Return 404 when the scoped query cannot find the record.

## Database Structure

Group database files by feature.

```txt
database/
├── migrations/
│   └── feature_name/
├── seeders/
│   └── FeatureName/
└── factories/
    └── FeatureName/
```

Example:

```txt
database/migrations/post/
database/seeders/Post/
database/factories/Post/
```

## Frontend Structure

Group frontend files by feature.

```txt
resources/js/
├── features/
│   └── feature-name/
│       ├── pages/
│       ├── components/
│       ├── forms/
│       ├── hooks/
│       └── types.ts
│
├── components/
│   └── ui/
├── layouts/
├── lib/
└── types/
```

Only reusable global UI goes into shared `components`.

## Rule

Keep the structure close to Laravel defaults, but group each feature inside subfolders.

Do not create a root `app/Features` folder unless explicitly requested.
