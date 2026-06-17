# Post Feature PRD

## MVP Progress

- [x] Public web post list di homepage
- [x] Public web post detail by slug
- [x] Web dashboard CRUD post
- [x] Mobile API CRUD post terautentikasi
- [x] Public API list post
- [x] Public API read post by slug
- [x] Search post untuk web list
- [x] Search post untuk mobile/public API list
- [x] Pagination untuk web list
- [x] Pagination untuk mobile/public API list
- [x] Sorting untuk web list
- [x] Sorting untuk mobile/public API list
- [x] Filter by author untuk web list
- [x] Filter by author untuk mobile/public API list
- [x] Cover image upload
- [x] Cover image replace
- [x] Cover image remove
- [x] Cover image served via Laravel route
- [x] Authorization: admin hanya bisa akses post miliknya
- [x] Test coverage untuk web CRUD
- [x] Test coverage untuk mobile API CRUD
- [x] Test coverage untuk public API list/detail
- [x] OpenAPI spec `openapi.yaml`
- [ ] Cursor pagination / API contract khusus infinite query mobile
- [ ] Public API documentation yang final untuk integrasi external/mobile

## Purpose

Feature `post` adalah MVP untuk publishing post sederhana yang melayani 3 flow:

- public web untuk listing dan detail post
- dashboard web untuk author/admin mengelola post
- API untuk mobile app, termasuk public read endpoint dan authenticated CRUD endpoint

Dokumen ini dipakai sebagai local PRD, checklist progres, dan sumber referensi sebelum membuat `openapi.yaml`.

## Current Scope

### User goals

- visitor bisa melihat daftar post publik dari homepage
- visitor bisa membuka detail post publik via slug
- admin/author bisa membuat, melihat, mengubah, dan menghapus post dari web
- mobile app terautentikasi bisa mengambil semua post lewat protected API
- mobile app terautentikasi bisa melihat list post miliknya sendiri lewat protected API
- mobile app terautentikasi bisa CRUD post milik user
- semua flow list bisa memakai search, pagination, sorting, dan filter author

### Current constraints

- public API list/detail saat ini memakai `PostResource` yang sama dengan mobile authenticated API
- list API masih `LengthAwarePaginator`, belum cursor-based
- public API list belum punya contract yang secara eksplisit dioptimalkan untuk infinite query, walau sudah bisa dipakai dengan page-based pagination
- mobile authenticated list memakai query `scope=all|mine`
- route cover image tetap lewat Laravel route, bukan direct public storage URL

## Implemented Routes

### Web Routes

#### Protected

- `GET /posts`
  fungsi: dashboard list semua post
  url route/endpoint: `/posts`
- `GET /posts/mine`
  fungsi: dashboard list post milik user login
  url route/endpoint: `/posts/mine`
- `GET /posts/create`
  fungsi: form create post
  url route/endpoint: `/posts/create`
- `POST /posts`
  fungsi: simpan post baru
  url route/endpoint: `/posts`
- `GET /posts/{post}`
  fungsi: detail post di dashboard
  url route/endpoint: `/posts/{public_id}`
- `GET /posts/{post}/edit`
  fungsi: form edit post
  url route/endpoint: `/posts/{public_id}/edit`
- `PUT/PATCH /posts/{post}`
  fungsi: update post
  url route/endpoint: `/posts/{public_id}`
- `DELETE /posts/{post}`
  fungsi: hapus post
  url route/endpoint: `/posts/{public_id}`

#### Public

- `GET /blog`
  fungsi: list public blog posts
  url route/endpoint: `/blog`
- `GET /blog/{post:slug}`
  fungsi: detail public blog post
  url route/endpoint: `/blog/{slug}`
- `GET /posts/{post}/cover`
  fungsi: serve cover image post
  url route/endpoint: `/posts/{public_id}/cover`

### API Routes

#### Protected

- `GET /api/mobile/posts`
  fungsi: list semua post atau post milik user login via query `scope=all|mine`
  url route/endpoint: `/api/mobile/posts`
- `POST /api/mobile/posts`
  fungsi: create post dari mobile
  url route/endpoint: `/api/mobile/posts`
- `GET /api/mobile/posts/{post}`
  fungsi: detail post milik user login
  url route/endpoint: `/api/mobile/posts/{public_id}`
- `PUT/PATCH /api/mobile/posts/{post}`
  fungsi: update post milik user login
  url route/endpoint: `/api/mobile/posts/{public_id}`
- `DELETE /api/mobile/posts/{post}`
  fungsi: hapus post milik user login
  url route/endpoint: `/api/mobile/posts/{public_id}`

#### Public

- `GET /api/posts`
  fungsi: list public posts
  url route/endpoint: `/api/posts`
- `GET /api/posts/{post:slug}`
  fungsi: detail public post
  url route/endpoint: `/api/posts/{slug}`

## API Contract Notes

### Public API list and read

Target public API untuk fase berikutnya:

- `GET /api/posts`
- `GET /api/posts/{slug}`

Kebutuhan yang sudah terpenuhi sekarang:

- bisa list post
- bisa read detail post
- support searching lewat `search`
- support pagination lewat `per_page` dan `page`
- support sorting lewat `sort`
- support filter author lewat `author`

Kebutuhan mobile yang perlu diperhatikan saat bikin `openapi.yaml`:

- mobile app memakai endpoint protected `GET /api/mobile/posts` untuk tab `all` dan `mine`
- pemilihan dataset mobile dilakukan via query `scope=all|mine`
- mobile app akan memakai infinite query
- contract sekarang page-based, jadi masih usable untuk infinite query berbasis `page + meta + links`
- bila nanti ingin lebih efisien untuk feed panjang, pertimbangkan endpoint atau mode pagination baru berbasis cursor

### Current response shape

Response item saat ini berisi:

- `public_id`
- `title`
- `slug`
- `cover`
- `cover_url`
- `body`
- `author`
- `is_mine`
- `can_edit`
- `can_delete`
- `created_at`
- `updated_at`

Response list saat ini juga membawa:

- `data`
- `links`
- `meta`
- `filters`
- `sort_options`
- `scope` untuk authenticated mobile list dengan nilai `all` atau `mine`

Catatan penting:

- untuk public API, field `is_mine`, `can_edit`, `can_delete` akan selalu bernilai `false` bila request guest
- saat membuat `openapi.yaml`, perlu diputuskan apakah public API tetap memakai shape yang sama, atau dibuat resource khusus public supaya contract lebih bersih

## Search, Filter, Sort, Pagination

Query support saat ini berasal dari `App\Queries\Post\PostIndexQuery`.

### Web list

- `search`:
  cari di `title`, `slug`, `author`, `body`
- `author`:
  filter `author like`
- `sort`:
  `id`, `title`, `author`, `created_at`
- `direction`:
  `asc` atau `desc`
- `per_page`:
  mengikuti `TableQuery::perPage()`

### API list

- `search`:
  cari di `title`, `slug`, `author`, `body`
- `author`:
  filter `author like`
- `sort`:
  `latest`, `oldest`, `title`, `author`
- `per_page`:
  valid values saat ini `5`, `10`, `15`, `25`
- `page`:
  pagination default Laravel

## Authorization Notes

- authenticated dashboard list `mine` hanya menampilkan post milik user yang login
- authenticated mobile list `scope=mine` hanya menampilkan post milik user yang login
- authenticated mobile list `scope=all` menampilkan semua post, tetapi policy edit/delete tetap mengikuti ownership/role
- admin biasa tidak bisa view, edit, update, atau delete post milik admin lain
- superadmin bisa edit dan delete post milik user lain pada web dashboard
- public route dan public API tidak butuh autentikasi

## Cover Image Notes

Upload cover image `post` disimpan di disk `public` Laravel.

```txt
storage/app/public/uploads/posts/covers/YYYY/MM/<hashed-file-name>
```

Contoh:

```txt
storage/app/public/uploads/posts/covers/2026/06/abc123def456.webp
```

Cover diserve lewat route Laravel:

```txt
/posts/{public_id}/cover
```

Placeholder image bawaan feature `post`:

```txt
public/images/placeholders/post/placeholder-16x9.avif
```

## Source of Truth in Code

File utama feature saat ini:

- `app/Http/Controllers/Post/PostController.php`
- `app/Http/Controllers/Api/Post/PublicPostController.php`
- `app/Http/Controllers/Api/Mobile/Post/PostController.php`
- `app/Queries/Post/PostIndexQuery.php`
- `app/Http/Resources/Post/PostResource.php`
- `app/Data/Post/PostData.php`
- `routes/web/post.php`
- `routes/api/post.php`

Test yang merepresentasikan behaviour saat ini:

- `tests/Feature/PostCrudTest.php`
- `tests/Feature/Api/PublicPostApiTest.php`
- `tests/Feature/Api/MobilePostCrudTest.php`

## Next Documentation Tasks

- definisikan final public API contract untuk `GET /api/posts` dan `GET /api/posts/{slug}` sebagai web/external read API
- putuskan apakah public API tetap memakai `PostResource` yang sama atau resource khusus public
- dokumentasikan query params: `search`, `author`, `sort`, `per_page`, `page`, `scope`
- dokumentasikan pagination response untuk kebutuhan infinite query mobile
- buat `openapi.yaml`
