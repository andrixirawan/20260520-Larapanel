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

export type TableFilters = {
    search: string;
    sort: string;
    direction: 'asc' | 'desc';
    per_page: number;
} & Record<string, string | number | undefined>;
