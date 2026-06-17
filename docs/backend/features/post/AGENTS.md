# Post Feature

## Purpose

Feature `post` menangani:

- public post listing di homepage
- public post detail by slug
- CRUD post untuk web Inertia
- CRUD post untuk API mobile
- upload, replace, remove, dan serve cover image

## Local Storage

Upload cover image `post` disimpan di disk `public` Laravel.

```txt
storage/app/public/uploads/posts/covers/YYYY/MM/<hashed-file-name>
```

Contoh:

```txt
storage/app/public/uploads/posts/covers/2026/06/abc123def456.webp
```

Response URL tetap diserve lewat route Laravel:

```txt
/posts/{public_id}/cover
```

Placeholder image bawaan feature `post` disimpan di:

```txt
public/images/placeholders/post/placeholder-16x9.avif
```

## Folder Structure

```txt
app/
├── Actions/
│   └── Post/
│       ├── CreatePostAction.php
│       ├── DeletePostAction.php
│       └── UpdatePostAction.php
├── Data/
│   └── Post/
│       └── PostData.php
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   └── Mobile/
│   │   │       └── Post/
│   │   │           └── PostController.php
│   │   └── Post/
│   │       └── PostController.php
│   ├── Requests/
│   │   └── Post/
│   │       ├── StorePostRequest.php
│   │       └── UpdatePostRequest.php
│   └── Resources/
│       └── Post/
│           └── PostResource.php
├── Models/
│   └── Post/
│       └── Post.php
├── Queries/
│   └── Post/
│       └── PostIndexQuery.php
└── Services/
    └── Post/
        ├── PostCoverService.php
        └── PostSlugService.php

database/
├── migrations/
│   └── post/
│       └── 2026_05_21_000000_create_posts_table.php
├── seeders/
│   ├── Post/
│   │   └── PostSeeder.php
│   └── User/
│       └── UserSeeder.php
└── factories/
    └── Post/
        └── PostFactory.php

public/
└── images/
    └── placeholders/
        └── post/
            └── placeholder-16x9.avif

routes/
├── api/
│   └── post.php
├── web/
│   └── post.php
├── api.php
└── web.php

resources/
└── js/
    ├── features/
    │   └── post/
    │       ├── pages/
    │       │   ├── create.tsx
    │       │   ├── edit.tsx
    │       │   ├── index.tsx
    │       │   ├── public-show.tsx
    │       │   └── show.tsx
    │       └── types.ts
    └── pages/
        ├── posts/
        │   ├── create.tsx
        │   ├── edit.tsx
        │   ├── index.tsx
        │   ├── show.tsx
        │   └── types.ts
        └── public-posts/
            └── show.tsx

tests/
└── Feature/
    ├── Api/
    │   └── MobilePostCrudTest.php
    └── PostCrudTest.php
```

## Notes

- `resources/js/features/post/*` adalah source utama UI feature `post`.
- `resources/js/pages/posts/*` dan `resources/js/pages/public-posts/show.tsx` adalah shim Inertia agar page name lama tetap jalan.
- `app/Http/Controllers/Post/PostController.php` dipakai untuk web/public flow.
- `app/Http/Controllers/Api/Mobile/Post/PostController.php` dipakai untuk mobile API flow.
- `PostData` dipakai untuk shape data internal/web.
- `PostResource` dipakai untuk response JSON API mobile.
- subfolder di `database/migrations/*` tetap terbaca oleh `php artisan migrate` karena didaftarkan di `AppServiceProvider`.
- cover image `post` dipisahkan per folder bulan upload di bawah `uploads/posts/covers/YYYY/MM`.
- placeholder asset ratio `16:9` untuk feature `post` disimpan terpisah di `public/images/placeholders/post`.
- user seeder menyediakan akun untuk semua role dengan email:
  `superadmin@gmail.com`, `administrator@gmail.com`, `cashier@gmail.com`, dan `subscriber@gmail.com`.
- password user seeder adalah `!Password12345` dan semua akun dalam kondisi verified.
