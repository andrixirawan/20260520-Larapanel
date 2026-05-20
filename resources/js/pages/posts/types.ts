export type Post = {
    id: number;
    title: string;
    slug: string;
    cover: string | null;
    cover_url: string | null;
    body: string;
    author: string;
    created_at: string;
    updated_at: string;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    from: number | null;
    last_page: number;
    next_page_url: string | null;
    per_page: number;
    prev_page_url: string | null;
    to: number | null;
    total: number;
};
