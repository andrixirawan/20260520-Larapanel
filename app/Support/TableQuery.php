<?php

namespace App\Support;

use Illuminate\Http\Request;

final class TableQuery
{
    public const DEFAULT_PER_PAGE = 10;

    public const PER_PAGE_OPTIONS = [5, 10, 15, 25, 50];

    public static function search(Request $request): string
    {
        return trim($request->string('search')->toString());
    }

    public static function perPage(Request $request, int $default = self::DEFAULT_PER_PAGE): int
    {
        $perPage = $request->integer('per_page', $default);

        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : $default;
    }

    /**
     * @param  array<string, string>  $allowed
     */
    public static function sort(Request $request, array $allowed, string $default): string
    {
        $sort = $request->string('sort')->toString();

        return array_key_exists($sort, $allowed) ? $sort : $default;
    }

    public static function direction(Request $request, string $default = 'desc'): string
    {
        $direction = strtolower($request->string('direction')->toString());

        return in_array($direction, ['asc', 'desc'], true) ? $direction : $default;
    }
}
