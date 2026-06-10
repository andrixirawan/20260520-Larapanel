import type { Paginated } from '@/types/pagination';

export type Post = {
    public_id: string;
    title: string;
    slug: string;
    cover: string | null;
    cover_url: string | null;
    body: string;
    author: string;
    created_at: string;
    updated_at: string;
};

export type PostFilters = {
    search: string;
    author: string;
    sort: string;
    direction: 'asc' | 'desc';
    per_page: number;
};

export type { Paginated };
