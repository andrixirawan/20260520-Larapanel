import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type SaleDetail = {
    id: number;
    invoice_number: string;
    status: string;
    payment_status: string;
    subtotal: string;
    total: string;
    paid_total: string;
    change_total: string;
    created_at: string;
    cashier?: { id: number; name: string };
    items: Array<{
        id: number;
        name_snapshot: string;
        sku_snapshot: string | null;
        quantity: string;
        unit_price: string;
        line_total: string;
    }>;
    payments: Array<{
        id: number;
        method: string;
        status: string;
        amount: string;
        received_amount: string | null;
        change_amount: string;
        provider_reference: string | null;
    }>;
};

const currency = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

export default function PosSaleShow({ sale }: { sale: SaleDetail }) {
    return (
        <>
            <Head title={sale.invoice_number} />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        title={sale.invoice_number}
                        description="Invoice snapshot, payment, and sold items."
                    />

                    <Button asChild variant="outline" className="w-fit">
                        <Link href="/pos/sales">Back to sales</Link>
                    </Button>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle>Status</CardTitle>
                        </CardHeader>
                        <CardContent className="flex gap-2">
                            <Badge>{sale.status}</Badge>
                            <Badge variant="secondary">
                                {sale.payment_status}
                            </Badge>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Cashier</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {sale.cashier?.name ?? '-'}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Total</CardTitle>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">
                            {currency.format(Number(sale.total))}
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Items</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Product</TableHead>
                                    <TableHead>Qty</TableHead>
                                    <TableHead>Price</TableHead>
                                    <TableHead className="text-right">
                                        Line total
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {sale.items.map((item) => (
                                    <TableRow key={item.id}>
                                        <TableCell>
                                            <div className="font-medium">
                                                {item.name_snapshot}
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                {item.sku_snapshot ?? 'No SKU'}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {Number(item.quantity)}
                                        </TableCell>
                                        <TableCell>
                                            {currency.format(
                                                Number(item.unit_price),
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right font-medium">
                                            {currency.format(
                                                Number(item.line_total),
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Payments</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {sale.payments.map((payment) => (
                            <div
                                key={payment.id}
                                className="grid gap-2 rounded-lg border p-3 text-sm md:grid-cols-4"
                            >
                                <div>
                                    <span className="text-muted-foreground">
                                        Method
                                    </span>
                                    <div className="font-medium">
                                        {payment.method}
                                    </div>
                                </div>
                                <div>
                                    <span className="text-muted-foreground">
                                        Amount
                                    </span>
                                    <div className="font-medium">
                                        {currency.format(
                                            Number(payment.amount),
                                        )}
                                    </div>
                                </div>
                                <div>
                                    <span className="text-muted-foreground">
                                        Change
                                    </span>
                                    <div className="font-medium">
                                        {currency.format(
                                            Number(payment.change_amount),
                                        )}
                                    </div>
                                </div>
                                <div>
                                    <span className="text-muted-foreground">
                                        Reference
                                    </span>
                                    <div className="font-medium">
                                        {payment.provider_reference ?? '-'}
                                    </div>
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

PosSaleShow.layout = {
    breadcrumbs: [
        {
            title: 'POS Sales',
            href: '/pos/sales',
        },
    ],
};
