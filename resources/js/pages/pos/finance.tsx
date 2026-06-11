import { Head } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { Landmark, Wallet } from 'lucide-react';
import { useMemo } from 'react';
import { DataTable } from '@/components/data-table';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { TableFilters } from '@/types/pagination';
import type { Paginated } from './types';
import { formatPosDateTime, posCurrency } from './utils';

type FinanceEntryRow = {
    public_id: string;
    entry_date: string | null;
    shift_public_id: string | null;
    type: string;
    direction: string;
    payment_method: string | null;
    amount: number;
    created_by: string | null;
    notes: string | null;
    created_at: string | null;
};

export default function PosFinance({
    entries,
    filters,
}: {
    entries: Paginated<FinanceEntryRow>;
    filters: TableFilters;
}) {
    const creditVisible = entries.data
        .filter((entry) => entry.direction === 'credit')
        .reduce((sum, entry) => sum + entry.amount, 0);
    const columns = useMemo<ColumnDef<FinanceEntryRow>[]>(
        () => [
            {
                id: 'entry_date',
                accessorKey: 'entry_date',
                header: 'Date',
                cell: ({ row }) => (
                    <>
                        <div>{row.original.entry_date ?? '-'}</div>
                        <div className="text-xs text-muted-foreground">
                            {formatPosDateTime(row.original.created_at)}
                        </div>
                    </>
                ),
            },
            {
                id: 'type',
                accessorKey: 'type',
                header: 'Type',
                cell: ({ row }) => (
                    <>
                        <div className="font-medium">{row.original.type}</div>
                        <Badge variant="secondary">
                            {row.original.direction}
                        </Badge>
                        <div className="mt-1 text-xs text-muted-foreground">
                            {row.original.notes ?? '-'}
                        </div>
                    </>
                ),
            },
            {
                id: 'shift_id',
                header: 'Source',
                enableSorting: false,
                cell: ({ row }) => (
                    <>
                        <div>
                            Shift{' '}
                            {row.original.shift_public_id
                                ? row.original.shift_public_id.slice(-8)
                                : '-'}
                        </div>
                        <div className="text-xs text-muted-foreground">
                            By {row.original.created_by ?? '-'}
                        </div>
                    </>
                ),
            },
            {
                id: 'payment_method',
                accessorKey: 'payment_method',
                header: 'Method',
                cell: ({ row }) => (
                    <Badge variant="outline">
                        {row.original.payment_method ?? '-'}
                    </Badge>
                ),
            },
            {
                id: 'amount',
                accessorKey: 'amount',
                header: 'Amount',
                meta: {
                    headerClassName: 'text-right',
                    cellClassName: 'text-right font-semibold',
                },
                cell: ({ row }) => posCurrency.format(row.original.amount),
            },
        ],
        [],
    );

    return (
        <>
            <Head title="POS Finance" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
                <Heading
                    title="POS Finance"
                    description="Ledger entries generated from POS payments and shift-based activity."
                />

                <div className="grid gap-4 md:grid-cols-3">
                    {[
                        {
                            icon: Landmark,
                            label: 'Visible entries',
                            value: `${entries.data.length}`,
                        },
                        {
                            icon: Wallet,
                            label: 'Visible credit',
                            value: posCurrency.format(creditVisible),
                        },
                        {
                            icon: Wallet,
                            label: 'Paginated total rows',
                            value: `${entries.total}`,
                        },
                    ].map((item) => (
                        <Card key={item.label}>
                            <CardContent className="flex items-center gap-4 p-5">
                                <div className="rounded-2xl bg-muted p-3">
                                    <item.icon className="size-5" />
                                </div>
                                <div>
                                    <div className="text-sm text-muted-foreground">
                                        {item.label}
                                    </div>
                                    <div className="text-lg font-semibold">
                                        {item.value}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Finance ledger</CardTitle>
                    </CardHeader>
                    <CardContent className="p-4">
                        <DataTable
                            columns={columns}
                            data={entries}
                            filters={filters}
                            route="/pos/finance"
                            searchPlaceholder="Search finance entries"
                            emptyMessage="No finance entries"
                            totalLabel="entries"
                        />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

PosFinance.layout = {
    breadcrumbs: [
        {
            title: 'POS Finance',
            href: '/pos/finance',
        },
    ],
};
