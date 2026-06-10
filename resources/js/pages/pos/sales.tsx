import { Head, Link } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { ReceiptText, Wallet } from 'lucide-react';
import { useMemo } from 'react';
import { DataTable } from '@/components/data-table';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { TableFilters } from '@/types/pagination';
import type { Paginated, PosSaleListItem } from './types';
import { formatPosDateTime, posCurrency } from './utils';

export default function PosSales({
    sales,
    filters,
}: {
    sales: Paginated<PosSaleListItem>;
    filters: TableFilters;
}) {
    const grossVisible = sales.data.reduce((sum, sale) => sum + sale.total, 0);
    const paidCount = sales.data.filter(
        (sale) => sale.payment_status === 'paid',
    ).length;
    const columns = useMemo<ColumnDef<PosSaleListItem>[]>(
        () => [
            {
                id: 'invoice_number',
                accessorKey: 'invoice_number',
                header: 'Invoice',
                cell: ({ row }) => (
                    <>
                        <Link
                            href={`/pos/sales/${row.original.public_id}`}
                            className="font-medium hover:underline"
                        >
                            {row.original.invoice_number}
                        </Link>
                        <div className="text-xs text-muted-foreground">
                            {formatPosDateTime(row.original.created_at)}
                        </div>
                    </>
                ),
            },
            {
                id: 'cashier',
                header: 'Cashier',
                enableSorting: false,
                cell: ({ row }) => row.original.cashier ?? '-',
            },
            {
                id: 'payment_method',
                header: 'Payment',
                enableSorting: false,
                cell: ({ row }) => (
                    <Badge variant="outline">
                        {row.original.payment_method ?? '-'}
                    </Badge>
                ),
            },
            {
                id: 'status',
                accessorKey: 'status',
                header: 'Status',
                cell: ({ row }) => (
                    <div className="flex flex-wrap gap-2">
                        <Badge>{row.original.status}</Badge>
                        <Badge variant="secondary">
                            {row.original.payment_status}
                        </Badge>
                    </div>
                ),
            },
            {
                id: 'total',
                accessorKey: 'total',
                header: 'Total',
                meta: {
                    headerClassName: 'text-right',
                    cellClassName: 'text-right font-semibold',
                },
                cell: ({ row }) => posCurrency.format(row.original.total),
            },
        ],
        [],
    );

    return (
        <>
            <Head title="POS Sales" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
                <div className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <Heading
                        title="POS Sales"
                        description="Invoice history, payment snapshots, and cashier traceability."
                    />

                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline">
                            <Link href="/pos">
                                <ReceiptText />
                                Open terminal
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    {[
                        {
                            icon: ReceiptText,
                            label: 'Visible invoices',
                            value: `${sales.data.length}`,
                        },
                        {
                            icon: Wallet,
                            label: 'Visible gross',
                            value: posCurrency.format(grossVisible),
                        },
                        {
                            icon: Wallet,
                            label: 'Paid status',
                            value: `${paidCount}/${sales.data.length}`,
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
                    <CardHeader className="flex flex-col gap-4 border-b sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <CardTitle>Invoice register</CardTitle>
                            <p className="text-sm text-muted-foreground">
                                Search by invoice number and inspect the final
                                transaction snapshot.
                            </p>
                        </div>
                    </CardHeader>
                    <CardContent className="p-4">
                        <DataTable
                            columns={columns}
                            data={sales}
                            filters={filters}
                            route="/pos/sales"
                            searchPlaceholder="Search invoice number"
                            emptyMessage="No sales found"
                            totalLabel="sales"
                        />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

PosSales.layout = {
    breadcrumbs: [
        {
            title: 'POS Sales',
            href: '/pos/sales',
        },
    ],
};
