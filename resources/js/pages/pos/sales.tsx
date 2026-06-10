import { Head, Link } from '@inertiajs/react';
import { ReceiptText, Search } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { Paginated, PosSaleListItem } from './types';

const currency = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

export default function PosSales({
    sales,
    filters,
}: {
    sales: Paginated<PosSaleListItem>;
    filters: { search: string };
}) {
    return (
        <>
            <Head title="POS Sales" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        title="POS Sales"
                        description="Shift-bound invoices and payment history."
                    />

                    <Button asChild variant="outline" className="w-fit">
                        <Link href="/pos">
                            <ReceiptText />
                            Open terminal
                        </Link>
                    </Button>
                </div>

                <form
                    action="/pos/sales"
                    method="get"
                    className="grid gap-3 rounded-lg border bg-card p-4 sm:grid-cols-[1fr_auto]"
                >
                    <div className="relative">
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            name="search"
                            defaultValue={filters.search}
                            placeholder="Search invoice number"
                            className="pl-9"
                        />
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit">Search</Button>
                        {filters.search && (
                            <Button asChild variant="ghost">
                                <Link href="/pos/sales">Reset</Link>
                            </Button>
                        )}
                    </div>
                </form>

                <div className="overflow-hidden rounded-lg border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Invoice</TableHead>
                                <TableHead>Cashier</TableHead>
                                <TableHead>Payment</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">
                                    Total
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {sales.data.length ? (
                                sales.data.map((sale) => (
                                    <TableRow key={sale.id}>
                                        <TableCell>
                                            <Link
                                                href={`/pos/sales/${sale.id}`}
                                                className="font-medium hover:underline"
                                            >
                                                {sale.invoice_number}
                                            </Link>
                                            <div className="text-xs text-muted-foreground">
                                                {sale.created_at
                                                    ? new Date(
                                                          sale.created_at,
                                                      ).toLocaleString('id-ID')
                                                    : '-'}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {sale.cashier ?? '-'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="outline">
                                                {sale.payment_method ?? '-'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex flex-wrap gap-2">
                                                <Badge>{sale.status}</Badge>
                                                <Badge variant="secondary">
                                                    {sale.payment_status}
                                                </Badge>
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-right font-semibold">
                                            {currency.format(sale.total)}
                                        </TableCell>
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
                                        className="h-32 text-center text-muted-foreground"
                                    >
                                        No sales yet.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                <div className="flex flex-col gap-3 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                    <span>
                        Showing {sales.from ?? 0} to {sales.to ?? 0} of{' '}
                        {sales.total} sales
                    </span>
                    <div className="flex gap-2">
                        <Button
                            asChild={Boolean(sales.prev_page_url)}
                            variant="outline"
                            size="sm"
                            disabled={!sales.prev_page_url}
                        >
                            {sales.prev_page_url ? (
                                <Link href={sales.prev_page_url}>Previous</Link>
                            ) : (
                                <span>Previous</span>
                            )}
                        </Button>
                        <Button
                            asChild={Boolean(sales.next_page_url)}
                            variant="outline"
                            size="sm"
                            disabled={!sales.next_page_url}
                        >
                            {sales.next_page_url ? (
                                <Link href={sales.next_page_url}>Next</Link>
                            ) : (
                                <span>Next</span>
                            )}
                        </Button>
                    </div>
                </div>
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
