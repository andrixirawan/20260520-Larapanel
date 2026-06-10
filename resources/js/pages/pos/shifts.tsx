import { Head, Link } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { Clock3, ShieldAlert, Wallet } from 'lucide-react';
import { useMemo } from 'react';
import { DataTable } from '@/components/data-table';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { TableFilters } from '@/types/pagination';
import type { Paginated } from './types';
import { formatPosDateTime, posCurrency } from './utils';

type ShiftRow = {
    public_id: string;
    cashier: string | null;
    opened_by: string | null;
    closed_by: string | null;
    status: string;
    opening_cash: number;
    expected_cash: number | null;
    counted_cash: number | null;
    cash_difference: number | null;
    opened_at: string | null;
    closed_at: string | null;
};

export default function PosShifts({
    shifts,
    filters,
}: {
    shifts: Paginated<ShiftRow>;
    filters: TableFilters;
}) {
    const openCount = shifts.data.filter((shift) => shift.status === 'open').length;
    const diffCount = shifts.data.filter(
        (shift) => shift.cash_difference !== null && shift.cash_difference !== 0,
    ).length;
    const totalOpeningCash = shifts.data.reduce(
        (sum, shift) => sum + shift.opening_cash,
        0,
    );
    const columns = useMemo<ColumnDef<ShiftRow>[]>(
        () => [
            {
                id: 'id',
                accessorKey: 'id',
                header: 'Shift',
                cell: ({ row }) => (
                    <>
                        <div className="font-medium">
                            {row.original.public_id.slice(-8)}
                        </div>
                        <Badge
                            variant={
                                row.original.status === 'open'
                                    ? 'default'
                                    : 'secondary'
                            }
                        >
                            {row.original.status}
                        </Badge>
                    </>
                ),
            },
            {
                id: 'cashier',
                header: 'Cashier',
                enableSorting: false,
                cell: ({ row }) => (
                    <>
                        <div>{row.original.cashier ?? '-'}</div>
                        <div className="text-xs text-muted-foreground">
                            Opened by {row.original.opened_by ?? '-'}
                        </div>
                        <div className="text-xs text-muted-foreground">
                            Closed by {row.original.closed_by ?? '-'}
                        </div>
                    </>
                ),
            },
            {
                id: 'opening_cash',
                accessorKey: 'opening_cash',
                header: 'Cash snapshot',
                cell: ({ row }) => (
                    <>
                        <div>
                            Opening:{' '}
                            {posCurrency.format(row.original.opening_cash)}
                        </div>
                        <div className="text-xs text-muted-foreground">
                            Expected:{' '}
                            {row.original.expected_cash === null
                                ? '-'
                                : posCurrency.format(row.original.expected_cash)}
                        </div>
                        <div className="text-xs text-muted-foreground">
                            Counted:{' '}
                            {row.original.counted_cash === null
                                ? '-'
                                : posCurrency.format(row.original.counted_cash)}
                        </div>
                    </>
                ),
            },
            {
                id: 'cash_difference',
                accessorKey: 'cash_difference',
                header: 'Difference',
                cell: ({ row }) =>
                    row.original.cash_difference === null ? (
                        <span className="text-muted-foreground">-</span>
                    ) : (
                        <Badge
                            variant={
                                row.original.cash_difference === 0
                                    ? 'outline'
                                    : 'destructive'
                            }
                        >
                            {posCurrency.format(row.original.cash_difference)}
                        </Badge>
                    ),
            },
            {
                id: 'opened_at',
                accessorKey: 'opened_at',
                header: 'Timeline',
                cell: ({ row }) => (
                    <>
                        <div className="text-xs">
                            Opened: {formatPosDateTime(row.original.opened_at)}
                        </div>
                        <div className="text-xs text-muted-foreground">
                            Closed: {formatPosDateTime(row.original.closed_at)}
                        </div>
                    </>
                ),
            },
        ],
        [],
    );

    return (
        <>
            <Head title="POS Shifts" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <Heading
                        title="POS Shifts"
                        description="Cash opening, closing, and reconciliation snapshots per operator."
                    />
                    <Button asChild variant="outline" className="w-fit">
                        <Link href="/pos">
                            <Clock3 />
                            Open terminal
                        </Link>
                    </Button>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    {[
                        {
                            icon: Clock3,
                            label: 'Open shifts',
                            value: `${openCount}`,
                        },
                        {
                            icon: Wallet,
                            label: 'Visible opening cash',
                            value: posCurrency.format(totalOpeningCash),
                        },
                        {
                            icon: ShieldAlert,
                            label: 'Shifts with difference',
                            value: `${diffCount}`,
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
                        <CardTitle>Shift register</CardTitle>
                    </CardHeader>
                    <CardContent className="p-4">
                        <DataTable
                            columns={columns}
                            data={shifts}
                            filters={filters}
                            route="/pos/shifts"
                            searchPlaceholder="Search shifts"
                            emptyMessage="No shifts yet"
                            totalLabel="shifts"
                        />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

PosShifts.layout = {
    breadcrumbs: [
        {
            title: 'POS Shifts',
            href: '/pos/shifts',
        },
    ],
};
