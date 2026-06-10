import { router } from '@inertiajs/react';
import {
    flexRender,
    getFilteredRowModel,
    getCoreRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    useReactTable,
} from '@tanstack/react-table';
import type { ColumnDef, SortingState } from '@tanstack/react-table';
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    ChevronLeft,
    ChevronRight,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import { SearchInput } from '@/components/search-input';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { DEFAULT_TABLE_PER_PAGE, TABLE_PER_PAGE_OPTIONS } from '@/constants/app';
import { cn } from '@/lib/utils';
import type { Paginated, TableFilters } from '@/types/pagination';

type DataTableProps<TData> = {
    columns: ColumnDef<TData>[];
    data: Paginated<TData>;
    filters: TableFilters;
    route: string;
    searchPlaceholder?: string;
    emptyMessage?: string;
    totalLabel: string;
    getRowId?: (row: TData) => string;
};

type ClientDataTableProps<TData> = {
    columns: ColumnDef<TData>[];
    data: TData[];
    searchPlaceholder?: string;
    emptyMessage?: string;
    totalLabel: string;
    getRowId?: (row: TData) => string;
};

function cleanQuery(params: Record<string, string | number | undefined>) {
    return Object.fromEntries(
        Object.entries(params).filter(([, value]) => value !== undefined && value !== ''),
    );
}

function pageNumbers(currentPage: number, lastPage: number) {
    const start = Math.max(1, currentPage - 2);
    const end = Math.min(lastPage, currentPage + 2);

    return Array.from({ length: end - start + 1 }, (_, index) => start + index);
}

export function DataTable<TData>({
    columns,
    data,
    filters,
    route,
    searchPlaceholder = 'Search',
    emptyMessage = 'No data found.',
    totalLabel,
    getRowId,
}: DataTableProps<TData>) {
    const [search, setSearch] = useState(filters.search);
    const sorting = useMemo<SortingState>(
        () => (filters.sort ? [{ id: filters.sort, desc: filters.direction === 'desc' }] : []),
        [filters.direction, filters.sort],
    );

    const visit = (query: Record<string, string | number | undefined>) => {
        router.get(
            route,
            cleanQuery({
                search: filters.search,
                sort: filters.sort,
                direction: filters.direction,
                per_page: filters.per_page,
                page: data.current_page,
                ...query,
            }),
            {
                preserveScroll: true,
                preserveState: true,
            },
        );
    };

    // eslint-disable-next-line react-hooks/incompatible-library
    const table = useReactTable({
        data: data.data,
        columns,
        getCoreRowModel: getCoreRowModel(),
        manualPagination: true,
        manualSorting: true,
        pageCount: data.last_page,
        rowCount: data.total,
        state: {
            sorting,
            pagination: {
                pageIndex: Math.max(data.current_page - 1, 0),
                pageSize: filters.per_page,
            },
        },
        getRowId,
        onSortingChange: (updater) => {
            const nextSorting =
                typeof updater === 'function' ? updater(sorting) : updater;
            const nextSort = nextSorting[0];

            visit({
                sort: nextSort?.id ?? filters.sort,
                direction: nextSort?.desc ? 'desc' : 'asc',
                page: 1,
            });
        },
    });

    const hasActiveQuery =
        Boolean(filters.search) ||
        data.current_page > 1 ||
        filters.per_page !== DEFAULT_TABLE_PER_PAGE;

    return (
        <div className="space-y-4">
            <div className="flex flex-col gap-3 rounded-lg border bg-card p-4 sm:flex-row sm:items-center sm:justify-between">
                <form
                    className="flex w-full gap-2 sm:max-w-md"
                    onSubmit={(event) => {
                        event.preventDefault();
                        visit({ search: search.trim(), page: 1 });
                    }}
                >
                    <SearchInput
                        value={search}
                        onValueChange={setSearch}
                        placeholder={searchPlaceholder}
                        className="flex-1"
                    />
                    <Button type="submit" disabled={!search.trim()}>
                        Search
                    </Button>
                </form>

                <div className="flex items-center gap-2">
                    {hasActiveQuery ? (
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={() => {
                                setSearch('');
                                visit({
                                    search: undefined,
                                    page: 1,
                                    per_page: DEFAULT_TABLE_PER_PAGE,
                                });
                            }}
                        >
                            Reset
                        </Button>
                    ) : null}
                    <select
                        value={filters.per_page}
                        onChange={(event) =>
                            visit({
                                per_page: Number(event.target.value),
                                page: 1,
                            })
                        }
                        className="h-9 rounded-md border border-input bg-background px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    >
                        {TABLE_PER_PAGE_OPTIONS.map((value) => (
                            <option key={value} value={value}>
                                {value} / page
                            </option>
                        ))}
                    </select>
                </div>
            </div>

            <div className="overflow-hidden rounded-lg border">
                <Table>
                    <TableHeader>
                        {table.getHeaderGroups().map((headerGroup) => (
                            <TableRow key={headerGroup.id}>
                                {headerGroup.headers.map((header) => {
                                    const sorted = header.column.getIsSorted();

                                    return (
                                        <TableHead
                                            key={header.id}
                                            className={cn(
                                                header.column.columnDef.meta?.headerClassName,
                                            )}
                                        >
                                            {header.isPlaceholder ? null : header.column.getCanSort() ? (
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    className="-ml-3 h-8 px-3"
                                                    onClick={header.column.getToggleSortingHandler()}
                                                >
                                                    {flexRender(
                                                        header.column.columnDef.header,
                                                        header.getContext(),
                                                    )}
                                                    {sorted === 'asc' ? (
                                                        <ArrowUp />
                                                    ) : sorted === 'desc' ? (
                                                        <ArrowDown />
                                                    ) : (
                                                        <ArrowUpDown />
                                                    )}
                                                </Button>
                                            ) : (
                                                flexRender(
                                                    header.column.columnDef.header,
                                                    header.getContext(),
                                                )
                                            )}
                                        </TableHead>
                                    );
                                })}
                            </TableRow>
                        ))}
                    </TableHeader>
                    <TableBody>
                        {table.getRowModel().rows.length ? (
                            table.getRowModel().rows.map((row) => (
                                <TableRow key={row.id}>
                                    {row.getVisibleCells().map((cell) => (
                                        <TableCell
                                            key={cell.id}
                                            className={cn(
                                                cell.column.columnDef.meta?.cellClassName,
                                            )}
                                        >
                                            {flexRender(
                                                cell.column.columnDef.cell,
                                                cell.getContext(),
                                            )}
                                        </TableCell>
                                    ))}
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell
                                    colSpan={columns.length}
                                    className="h-32 text-center text-muted-foreground"
                                >
                                    {emptyMessage}
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>

            <div className="flex flex-col gap-3 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                <span>
                    Showing {data.from ?? 0} to {data.to ?? 0} of {data.total}{' '}
                    {totalLabel}
                </span>
                <div className="flex flex-wrap items-center gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        disabled={data.current_page <= 1}
                        onClick={() => visit({ page: data.current_page - 1 })}
                    >
                        <ChevronLeft />
                        Previous
                    </Button>
                    {pageNumbers(data.current_page, data.last_page).map((page) => (
                        <Button
                            key={page}
                            type="button"
                            variant={page === data.current_page ? 'default' : 'outline'}
                            size="sm"
                            onClick={() => visit({ page })}
                        >
                            {page}
                        </Button>
                    ))}
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        disabled={data.current_page >= data.last_page}
                        onClick={() => visit({ page: data.current_page + 1 })}
                    >
                        Next
                        <ChevronRight />
                    </Button>
                </div>
            </div>
        </div>
    );
}

export function ClientDataTable<TData>({
    columns,
    data,
    searchPlaceholder = 'Search',
    emptyMessage = 'No data found.',
    totalLabel,
    getRowId,
}: ClientDataTableProps<TData>) {
    const [search, setSearch] = useState('');
    const [sorting, setSorting] = useState<SortingState>([]);
    const [pagination, setPagination] = useState({
        pageIndex: 0,
        pageSize: DEFAULT_TABLE_PER_PAGE,
    });

    // eslint-disable-next-line react-hooks/incompatible-library
    const table = useReactTable({
        data,
        columns,
        getCoreRowModel: getCoreRowModel(),
        getFilteredRowModel: getFilteredRowModel(),
        getPaginationRowModel: getPaginationRowModel(),
        getSortedRowModel: getSortedRowModel(),
        globalFilterFn: 'includesString',
        state: {
            globalFilter: search,
            sorting,
            pagination,
        },
        getRowId,
        onGlobalFilterChange: setSearch,
        onSortingChange: setSorting,
        onPaginationChange: setPagination,
    });

    return (
        <div className="space-y-4">
            <div className="flex flex-col gap-3 rounded-lg border bg-card p-4 sm:flex-row sm:items-center sm:justify-between">
                <form
                    className="flex w-full gap-2 sm:max-w-md"
                    onSubmit={(event) => event.preventDefault()}
                >
                    <SearchInput
                        value={search}
                        onValueChange={setSearch}
                        placeholder={searchPlaceholder}
                        className="flex-1"
                    />
                    <Button type="submit" disabled={!search.trim()}>
                        Search
                    </Button>
                </form>

                <select
                    value={pagination.pageSize}
                    onChange={(event) => {
                        table.setPageSize(Number(event.target.value));
                    }}
                    className="h-9 rounded-md border border-input bg-background px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                >
                    {TABLE_PER_PAGE_OPTIONS.map((value) => (
                        <option key={value} value={value}>
                            {value} / page
                        </option>
                    ))}
                </select>
            </div>

            <div className="overflow-hidden rounded-lg border">
                <Table>
                    <TableHeader>
                        {table.getHeaderGroups().map((headerGroup) => (
                            <TableRow key={headerGroup.id}>
                                {headerGroup.headers.map((header) => {
                                    const sorted = header.column.getIsSorted();

                                    return (
                                        <TableHead
                                            key={header.id}
                                            className={cn(
                                                header.column.columnDef.meta?.headerClassName,
                                            )}
                                        >
                                            {header.isPlaceholder ? null : header.column.getCanSort() ? (
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    className="-ml-3 h-8 px-3"
                                                    onClick={header.column.getToggleSortingHandler()}
                                                >
                                                    {flexRender(
                                                        header.column.columnDef.header,
                                                        header.getContext(),
                                                    )}
                                                    {sorted === 'asc' ? (
                                                        <ArrowUp />
                                                    ) : sorted === 'desc' ? (
                                                        <ArrowDown />
                                                    ) : (
                                                        <ArrowUpDown />
                                                    )}
                                                </Button>
                                            ) : (
                                                flexRender(
                                                    header.column.columnDef.header,
                                                    header.getContext(),
                                                )
                                            )}
                                        </TableHead>
                                    );
                                })}
                            </TableRow>
                        ))}
                    </TableHeader>
                    <TableBody>
                        {table.getRowModel().rows.length ? (
                            table.getRowModel().rows.map((row) => (
                                <TableRow key={row.id}>
                                    {row.getVisibleCells().map((cell) => (
                                        <TableCell
                                            key={cell.id}
                                            className={cn(
                                                cell.column.columnDef.meta?.cellClassName,
                                            )}
                                        >
                                            {flexRender(
                                                cell.column.columnDef.cell,
                                                cell.getContext(),
                                            )}
                                        </TableCell>
                                    ))}
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell
                                    colSpan={columns.length}
                                    className="h-32 text-center text-muted-foreground"
                                >
                                    {emptyMessage}
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>

            <div className="flex flex-col gap-3 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                <span>
                    Showing{' '}
                    {table.getFilteredRowModel().rows.length
                        ? pagination.pageIndex * pagination.pageSize + 1
                        : 0}{' '}
                    to{' '}
                    {Math.min(
                        (pagination.pageIndex + 1) * pagination.pageSize,
                        table.getFilteredRowModel().rows.length,
                    )}{' '}
                    of {table.getFilteredRowModel().rows.length} {totalLabel}
                </span>
                <div className="flex items-center gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        disabled={!table.getCanPreviousPage()}
                        onClick={() => table.previousPage()}
                    >
                        <ChevronLeft />
                        Previous
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        disabled={!table.getCanNextPage()}
                        onClick={() => table.nextPage()}
                    >
                        Next
                        <ChevronRight />
                    </Button>
                </div>
            </div>
        </div>
    );
}

declare module '@tanstack/react-table' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface ColumnMeta<TData, TValue> {
        headerClassName?: string;
        cellClassName?: string;
    }
}
