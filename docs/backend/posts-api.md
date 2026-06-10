# Mobile Posts API

CRUD posts untuk React Native memakai Bearer token dari mobile auth Laravel. Base URL production:

```text
https://demo.shendro.cloud/api/mobile
```

Semua endpoint posts membutuhkan header:

```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer <mobile_access_token>
```

## Endpoints

| Method | Path | Permission | Kegunaan |
| --- | --- | --- | --- |
| GET | `/posts` | `posts.view` | List posts, pagination, search, filter, sort. |
| POST | `/posts` | `posts.create` | Create post. |
| GET | `/posts/{public_id}` | `posts.view` | Detail post. |
| PATCH | `/posts/{public_id}` | `posts.update` | Update post. |
| DELETE | `/posts/{public_id}` | `posts.delete` | Delete post. |

## Identifier Rules

Posts API tidak mengekspos numeric database `id`. Mobile app harus memakai `public_id`:

- Simpan `public_id` dari response list/detail/create.
- Pakai `public_id` untuk route detail, update, dan delete.
- Jangan memakai numeric `id` di navigation params, cache key, atau request API.
- `cover_url` juga memakai `public_id` di URL backend.

## List Posts

```http
GET /api/mobile/posts?search=laravel&author=Rani&sort=latest&per_page=10&page=1
```

Query:

| Name | Type | Values |
| --- | --- | --- |
| `search` | string | title, slug, author, body search. |
| `author` | string | author contains filter. |
| `sort` | string | `latest`, `oldest`, `title`, `author`. |
| `per_page` | number | `5`, `10`, `15`, `25`. |
| `page` | number | Laravel paginator page. |

Response:

```json
{
  "data": [
    {
      "public_id": "01HZPOSTPUBLICID1234567890",
      "title": "Laravel API Guide",
      "slug": "laravel-api-guide",
      "cover": "uploads/posts/covers/hashed-cover.jpg",
      "cover_url": "https://demo.shendro.cloud/posts/01HZPOSTPUBLICID1234567890/cover",
      "body": "Post content",
      "author": "Rani",
      "created_at": "2026-06-08T08:00:00.000000Z",
      "updated_at": "2026-06-08T08:00:00.000000Z"
    }
  ],
  "links": {},
  "meta": {},
  "filters": {
    "search": "laravel",
    "author": "",
    "sort": "latest",
    "per_page": 10
  },
  "sort_options": {
    "latest": "Newest first",
    "oldest": "Oldest first",
    "title": "Title A-Z",
    "author": "Author A-Z"
  }
}
```

## Create Post

```http
POST /api/mobile/posts
```

JSON body:

```json
{
  "title": "Mobile CRUD Post",
  "slug": "",
  "body": "Created from the mobile app."
}
```

Notes:

- `slug` boleh kosong; Laravel akan generate dari `title`.
- `author` otomatis diisi dari nama user login. Client tidak perlu dan tidak boleh mengirim field `author`.
- `cover` optional untuk multipart upload (`jpg`, `jpeg`, `png`, `webp`, max 2 MB).

Response `201`:

```json
{
  "message": "Post created.",
  "data": {
    "public_id": "01HZPOSTPUBLICID1234567890",
    "title": "Mobile CRUD Post",
    "slug": "mobile-crud-post",
    "cover": null,
    "cover_url": null,
    "body": "Created from the mobile app.",
    "author": "Mobile User",
    "created_at": "2026-06-08T08:00:00.000000Z",
    "updated_at": "2026-06-08T08:00:00.000000Z"
  }
}
```

## Update Post

```http
PATCH /api/mobile/posts/01HZPOSTPUBLICID1234567890
```

JSON body:

```json
{
  "title": "Updated Mobile Post",
  "slug": "updated-mobile-post",
  "body": "Updated from the mobile app."
}
```

Untuk update cover image dari mobile, kirim multipart sebagai `POST /api/mobile/posts/01HZPOSTPUBLICID1234567890` dengan field `_method=PATCH`, karena file upload multipart perlu diproses sebagai POST di PHP:

| Field | Required | Notes |
| --- | --- | --- |
| `title` | yes | string, max 255 |
| `slug` | no | nullable, alpha dash, generated from title when empty |
| `body` | yes | string |
| `cover` | no | image file, `jpg`, `jpeg`, `png`, `webp`, max 2 MB |
| `remove_cover` | no | boolean, removes existing cover |

## Delete Post

```http
DELETE /api/mobile/posts/01HZPOSTPUBLICID1234567890
```

Response:

```json
{
  "message": "Post deleted."
}
```

## Validation Errors

Laravel returns `422`:

```json
{
  "message": "The title field is required.",
  "errors": {
    "title": ["The title field is required."]
  }
}
```

## Swagger

OpenAPI spec tersedia di:

```text
docs/backend/posts-openapi.yaml
```
