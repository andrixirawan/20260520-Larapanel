<?php

namespace App\Queries\Post;

use App\Models\Post\Post;
use App\Models\User;
use App\Support\TableQuery;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

final class PostIndexQuery
{
    /**
     * @return array{search: string, author: string, sort: string, direction: string, per_page: int}
     */
    public function webFilters(Request $request): array
    {
        $sortOptions = $this->webSortOptions();

        return [
            'search' => TableQuery::search($request),
            'author' => trim($request->string('author')->toString()),
            'sort' => TableQuery::sort($request, $sortOptions, 'created_at'),
            'direction' => TableQuery::direction($request),
            'per_page' => TableQuery::perPage($request),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function webSortOptions(): array
    {
        return [
            'id' => __('ID'),
            'title' => __('Title'),
            'author' => __('Author'),
            'created_at' => __('Created at'),
        ];
    }

    /**
     * @return LengthAwarePaginator<int, Post>
     */
    public function paginateForWeb(Request $request, ?User $owner = null): LengthAwarePaginator
    {
        $filters = $this->webFilters($request);
        $sortColumns = [
            'id' => 'id',
            'title' => 'title',
            'author' => 'author',
            'created_at' => 'created_at',
        ];

        return $this->baseQuery($filters['search'], $filters['author'], $owner)
            ->orderBy($sortColumns[$filters['sort']], $filters['direction'])
            ->orderByDesc('id')
            ->paginate($filters['per_page'])
            ->withQueryString();
    }

    public function mobileScope(Request $request): string
    {
        $scope = trim($request->string('scope')->toString());

        return in_array($scope, ['all', 'mine'], true) ? $scope : 'mine';
    }

    /**
     * @return array{search: string, author: string, sort: string, per_page: int}
     */
    public function mobileFilters(Request $request): array
    {
        $sort = $request->string('sort')->toString();
        $perPage = $request->integer('per_page', 10);

        return [
            'search' => trim($request->string('search')->toString()),
            'author' => trim($request->string('author')->toString()),
            'sort' => array_key_exists($sort, $this->mobileSortOptions()) ? $sort : 'latest',
            'per_page' => in_array($perPage, [5, 10, 15, 25], true) ? $perPage : 10,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function mobileSortOptions(): array
    {
        return [
            'latest' => 'Newest first',
            'oldest' => 'Oldest first',
            'title' => 'Title A-Z',
            'author' => 'Author A-Z',
        ];
    }

    /**
     * @return LengthAwarePaginator<int, Post>
     */
    public function paginateForMobile(Request $request, ?User $owner = null): LengthAwarePaginator
    {
        $filters = $this->mobileFilters($request);

        return $this->baseQuery($filters['search'], $filters['author'], $owner)
            ->when(
                $filters['sort'] === 'oldest',
                fn ($query) => $query->oldest(),
                fn ($query) => $query->when(
                    $filters['sort'] === 'title',
                    fn ($query) => $query->orderBy('title')->orderByDesc('id'),
                    fn ($query) => $query->when(
                        $filters['sort'] === 'author',
                        fn ($query) => $query->orderBy('author')->orderByDesc('id'),
                        fn ($query) => $query->latest(),
                    ),
                ),
            )
            ->paginate($filters['per_page'])
            ->withQueryString();
    }

    private function baseQuery(string $search, string $author, ?User $owner = null)
    {
        return Post::query()
            ->with('user:id,name')
            ->when($owner, fn ($query) => $query->where('user_id', $owner->id))
            ->when($search, function ($query, string $value) {
                $query->where(function ($query) use ($value) {
                    $query
                        ->where('title', 'like', "%{$value}%")
                        ->orWhere('slug', 'like', "%{$value}%")
                        ->orWhere('author', 'like', "%{$value}%")
                        ->orWhere('body', 'like', "%{$value}%");
                });
            })
            ->when($author, fn ($query, string $value) => $query->where('author', 'like', "%{$value}%"));
    }
}
