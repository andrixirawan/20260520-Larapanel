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
- [ ] OpenAPI spec `openapi.yaml`
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
- mobile app bisa mengambil list post publik dan detail post publik
- mobile app terautentikasi bisa CRUD post milik user
- semua flow list bisa memakai search, pagination, sorting, dan filter author

### Current constraints

- public API list/detail saat ini memakai `PostResource` yang sama dengan mobile authenticated API
- list API masih `LengthAwarePaginator`, belum cursor-based
- public API list belum punya contract yang secara eksplisit dioptimalkan untuk infinite query, walau sudah bisa dipakai dengan page-based pagination
- mobile authenticated list dibatasi ke post milik user yang login
- route cover image tetap lewat Laravel route, bukan direct public storage URL

## Implemented Routes

### Web Routes

### `GET /`

- Route name: `home`
- Controller: `App\Http\Controllers\Post\PostController@home`
- Fungsi:
  menampilkan public post listing di homepage Inertia `welcome`
- Query yang sudah didukung:
  `search`, `author`, `sort`, `direction`, `per_page`

### `GET /posts/{post}/cover`

- Route name: `posts.cover`
- Controller: `App\Http\Controllers\Post\PostController@cover`
- Fungsi:
  serve file cover image post lewat Laravel response stream

### `GET /p/{post:slug}`

- Route name: `public.posts.show`
- Controller: `App\Http\Controllers\Post\PostController@publicShow`
- Fungsi:
  menampilkan detail post publik berdasarkan slug

### `GET /posts`

- Route name: `posts.index`
- Middleware: `auth`, `verified`, permission `posts.view`
- Controller: `App\Http\Controllers\Post\PostController@index`
- Fungsi:
  menampilkan dashboard list post milik user yang login
- Query yang sudah didukung:
  `search`, `author`, `sort`, `direction`, `per_page`

### `GET /posts/create`

- Route name: `posts.create`
- Middleware: `auth`, `verified`, permission `posts.create`
- Controller: `App\Http\Controllers\Post\PostController@create`
- Fungsi:
  menampilkan form create post di web

### `POST /posts`

- Route name: `posts.store`
- Middleware: `auth`, `verified`, permission `posts.create`
- Controller: `App\Http\Controllers\Post\PostController@store`
- Fungsi:
  membuat post baru dari web dashboard

### `GET /posts/{post}`

- Route name: `posts.show`
- Middleware: `auth`, `verified`, permission `posts.view`, policy `view`
- Controller: `App\Http\Controllers\Post\PostController@show`
- Fungsi:
  menampilkan detail post di dashboard

### `GET /posts/{post}/edit`

- Route name: `posts.edit`
- Middleware: `auth`, `verified`, permission `posts.update`, policy `update`
- Controller: `App\Http\Controllers\Post\PostController@edit`
- Fungsi:
  menampilkan form edit post

### `PUT/PATCH /posts/{post}`

- Route name: `posts.update`
- Middleware: `auth`, `verified`, permission `posts.update`, policy `update`
- Controller: `App\Http\Controllers\Post\PostController@update`
- Fungsi:
  update data post, termasuk replace atau remove cover

### `DELETE /posts/{post}`

- Route name: `posts.destroy`
- Middleware: `auth`, `verified`, permission `posts.delete`, policy `delete`
- Controller: `App\Http\Controllers\Post\PostController@destroy`
- Fungsi:
  menghapus post dan cover file terkait

### API Routes

#### Public API

### `GET /api/posts`

- Route name: `api.posts.index`
- Controller: `App\Http\Controllers\Api\Post\PublicPostController@index`
- Fungsi:
  mengembalikan daftar post publik untuk konsumsi mobile app atau client external
- Query yang sudah didukung:
  `search`, `author`, `sort`, `per_page`, `page`
- Sort yang tersedia:
  `latest`, `oldest`, `title`, `author`
- Catatan:
  ini kandidat utama untuk dijadikan public API list post

### `GET /api/posts/{post:slug}`

- Route name: `api.posts.show`
- Controller: `App\Http\Controllers\Api\Post\PublicPostController@show`
- Fungsi:
  mengembalikan detail 1 post publik berdasarkan slug
- Catatan:
  ini kandidat utama untuk public API read post

#### Mobile Authenticated API

### `GET /api/mobile/posts`

- Route name: `api.mobile.posts.index`
- Middleware: `mobile.auth`, permission `posts.view`
- Controller: `App\Http\Controllers\Api\Mobile\Post\PostController@index`
- Fungsi:
  mengembalikan daftar post milik user yang login
- Query yang sudah didukung:
  `search`, `author`, `sort`, `per_page`, `page`

### `POST /api/mobile/posts`

- Route name: `api.mobile.posts.store`
- Middleware: `mobile.auth`, permission `posts.create`
- Controller: `App\Http\Controllers\Api\Mobile\Post\PostController@store`
- Fungsi:
  membuat post baru dari mobile app

### `GET /api/mobile/posts/{post}`

- Route name: `api.mobile.posts.show`
- Middleware: `mobile.auth`, permission `posts.view`, policy `view`
- Controller: `App\Http\Controllers\Api\Mobile\Post\PostController@show`
- Fungsi:
  melihat detail post milik user dari mobile app

### `PUT/PATCH /api/mobile/posts/{post}`

- Route name: `api.mobile.posts.update`
- Middleware: `mobile.auth`, permission `posts.update`, policy `update`
- Controller: `App\Http\Controllers\Api\Mobile\Post\PostController@update`
- Fungsi:
  update post milik user dari mobile app

### `DELETE /api/mobile/posts/{post}`

- Route name: `api.mobile.posts.destroy`
- Middleware: `mobile.auth`, permission `posts.delete`, policy `delete`
- Controller: `App\Http\Controllers\Api\Mobile\Post\PostController@destroy`
- Fungsi:
  menghapus post milik user dari mobile app

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

- authenticated dashboard list dan mobile list hanya menampilkan post milik user yang login
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

- definisikan final public API contract untuk `GET /api/posts` dan `GET /api/posts/{slug}`
- putuskan apakah public API tetap memakai `PostResource` yang sama atau resource khusus public
- dokumentasikan query params: `search`, `author`, `sort`, `per_page`, `page`
- dokumentasikan pagination response untuk kebutuhan infinite query mobile
- buat `openapi.yaml`
