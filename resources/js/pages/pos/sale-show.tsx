import { Head, Link, router, usePage } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import {
    BadgeCheck,
    Loader2,
    Printer,
    ReceiptText,
    ShoppingBasket,
    ShieldAlert,
    Undo2,
    WalletCards,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import { ClientDataTable } from '@/components/data-table';
import Heading from '@/components/heading';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogMedia,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import type { Auth } from '@/types';
import { formatPosDateTime, posCurrency } from './utils';

type SaleDetail = {
    public_id: string;
    invoice_number: string;
    status: string;
    payment_status: string;
    subtotal: number;
    total: number;
    paid_total: number;
    change_total: number;
    created_at: string;
    voided_at?: string | null;
    void_reason?: string | null;
    cashier?: { name: string };
    voided_by?: { name: string } | null;
    shift?: { public_id: string; opened_at: string | null };
    items: Array<{
        public_id: string;
        name_snapshot: string;
        sku_snapshot: string | null;
        quantity: number;
        unit_price: number;
        line_total: number;
    }>;
    payments: Array<{
        public_id: string;
        method: string;
        status: string;
        amount: number;
        received_amount: number | null;
        change_amount: number;
        provider_reference: string | null;
    }>;
};

export default function PosSaleShow({ sale }: { sale: SaleDetail }) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const canVoidSale = auth.permissions['pos.sales.void'];
    const [voidDialogOpen, setVoidDialogOpen] = useState(false);
    const [voidReason, setVoidReason] = useState('');
    const [isVoiding, setIsVoiding] = useState(false);
    const itemColumns = useMemo<ColumnDef<SaleDetail['items'][number]>[]>(
        () => [
            {
                id: 'name_snapshot',
                accessorKey: 'name_snapshot',
                header: 'Product',
                cell: ({ row }) => (
                    <>
                        <div className="font-medium">
                            {row.original.name_snapshot}
                        </div>
                        <div className="text-xs text-muted-foreground">
                            {row.original.sku_snapshot ?? 'No SKU'}
                        </div>
                    </>
                ),
            },
            {
                id: 'quantity',
                accessorKey: 'quantity',
                header: 'Qty',
                cell: ({ row }) => Number(row.original.quantity),
            },
            {
                id: 'unit_price',
                accessorKey: 'unit_price',
                header: 'Price',
                cell: ({ row }) =>
                    posCurrency.format(Number(row.original.unit_price)),
            },
            {
                id: 'line_total',
                accessorKey: 'line_total',
                header: 'Line total',
                meta: {
                    headerClassName: 'text-right',
                    cellClassName: 'text-right font-medium',
                },
                cell: ({ row }) =>
                    posCurrency.format(Number(row.original.line_total)),
            },
        ],
        [],
    );

    const voidSale = () => {
        router.patch(
            `/pos/sales/${sale.public_id}/void`,
            { reason: voidReason },
            {
                preserveScroll: true,
                onStart: () => setIsVoiding(true),
                onSuccess: () => {
                    setVoidDialogOpen(false);
                    setVoidReason('');
                },
                onFinish: () => setIsVoiding(false),
            },
        );
    };

    return (
        <>
            <Head title={sale.invoice_number} />

            <div className="pos-receipt-print flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
                <Card className="relative overflow-hidden border-none bg-[linear-gradient(135deg,rgba(15,23,42,1),rgba(6,95,70,0.94),rgba(8,145,178,0.86))] text-white shadow-xl">
                    <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(255,255,255,0.15),transparent_30%),radial-gradient(circle_at_bottom_right,rgba(255,255,255,0.12),transparent_28%)]" />
                    <CardContent className="relative flex flex-col gap-5 p-6 md:p-7 lg:flex-row lg:items-end lg:justify-between">
                        <div className="space-y-3">
                            <div className="flex flex-wrap gap-2">
                                <Badge className="bg-white/14 text-white hover:bg-white/14">
                                    {sale.invoice_number}
                                </Badge>
                                <Badge className="bg-white/14 text-white hover:bg-white/14">
                                    {formatPosDateTime(sale.created_at)}
                                </Badge>
                            </div>
                            <Heading
                                title={sale.invoice_number}
                                description="Invoice snapshot, payment trace, and sold item history."
                            />
                        </div>

                        <Button
                            className="no-print"
                            variant="secondary"
                            onClick={() => window.print()}
                        >
                            <Printer />
                            Print receipt
                        </Button>
                        <Button
                            asChild
                            variant="outline"
                            className="no-print border-white/20 bg-transparent text-white hover:bg-white/10 hover:text-white"
                        >
                            <Link href="/pos/sales">Back to sales</Link>
                        </Button>
                        {canVoidSale && sale.status !== 'voided' ? (
                            <Button
                                className="no-print"
                                variant="destructive"
                                onClick={() => setVoidDialogOpen(true)}
                            >
                                <Undo2 />
                                Void sale
                            </Button>
                        ) : null}
                    </CardContent>
                </Card>

                <div className="grid gap-4 md:grid-cols-4">
                    {[
                        {
                            icon: BadgeCheck,
                            label: 'Status',
                            value: sale.status,
                            extra: sale.payment_status,
                        },
                        {
                            icon: ReceiptText,
                            label: 'Cashier',
                            value: sale.cashier?.name ?? '-',
                            extra: formatPosDateTime(sale.created_at),
                        },
                        {
                            icon: ShoppingBasket,
                            label: 'Items',
                            value: `${sale.items.length}`,
                            extra: posCurrency.format(Number(sale.subtotal)),
                        },
                        {
                            icon: WalletCards,
                            label: 'Total',
                            value: posCurrency.format(Number(sale.total)),
                            extra: `Change ${posCurrency.format(Number(sale.change_total))}`,
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
                                    <div className="text-xs text-muted-foreground">
                                        {item.extra}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.75fr)]">
                    <Card>
                        <CardHeader>
                            <CardTitle>Sold items</CardTitle>
                        </CardHeader>
                        <CardContent className="p-4">
                            <ClientDataTable
                                columns={itemColumns}
                                data={sale.items}
                                searchPlaceholder="Search sold items"
                                emptyMessage="No sold items"
                                totalLabel="items"
                            />
                        </CardContent>
                    </Card>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Payment records</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {sale.payments.map((payment) => (
                                    <div
                                        key={payment.public_id}
                                        className="rounded-2xl border bg-muted/30 p-4 text-sm"
                                    >
                                        <div className="flex items-start justify-between gap-3">
                                            <div>
                                                <div className="font-medium">
                                                    {payment.method}
                                                </div>
                                                <div className="text-xs text-muted-foreground">
                                                    {payment.status}
                                                </div>
                                            </div>
                                            <Badge variant="outline">
                                                {posCurrency.format(
                                                    Number(payment.amount),
                                                )}
                                            </Badge>
                                        </div>
                                        <div className="mt-4 space-y-2 text-muted-foreground">
                                            <div className="flex items-center justify-between">
                                                <span>Received</span>
                                                <span>
                                                    {payment.received_amount
                                                        ? posCurrency.format(
                                                              Number(
                                                                  payment.received_amount,
                                                              ),
                                                          )
                                                        : '-'}
                                                </span>
                                            </div>
                                            <div className="flex items-center justify-between">
                                                <span>Change</span>
                                                <span>
                                                    {posCurrency.format(
                                                        Number(
                                                            payment.change_amount,
                                                        ),
                                                    )}
                                                </span>
                                            </div>
                                            <div className="flex items-center justify-between">
                                                <span>Reference</span>
                                                <span className="font-medium text-foreground">
                                                    {payment.provider_reference ??
                                                        '-'}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Invoice summary</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                <div className="flex items-center justify-between">
                                    <span className="text-muted-foreground">
                                        Subtotal
                                    </span>
                                    <span>
                                        {posCurrency.format(
                                            Number(sale.subtotal),
                                        )}
                                    </span>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span className="text-muted-foreground">
                                        Paid
                                    </span>
                                    <span>
                                        {posCurrency.format(
                                            Number(sale.paid_total),
                                        )}
                                    </span>
                                </div>
                                <div className="flex items-center justify-between text-base font-semibold">
                                    <span>Total</span>
                                    <span>
                                        {posCurrency.format(Number(sale.total))}
                                    </span>
                                </div>
                                {sale.voided_at ? (
                                    <>
                                        <div className="flex items-center justify-between">
                                            <span className="text-muted-foreground">
                                                Voided at
                                            </span>
                                            <span>
                                                {formatPosDateTime(sale.voided_at)}
                                            </span>
                                        </div>
                                        <div className="flex items-center justify-between">
                                            <span className="text-muted-foreground">
                                                Voided by
                                            </span>
                                            <span>{sale.voided_by?.name ?? '-'}</span>
                                        </div>
                                        <div className="rounded-xl border bg-muted/30 p-3">
                                            <div className="text-xs text-muted-foreground">
                                                Void reason
                                            </div>
                                            <div className="mt-1 font-medium">
                                                {sale.void_reason ?? '-'}
                                            </div>
                                        </div>
                                    </>
                                ) : null}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            <AlertDialog open={voidDialogOpen} onOpenChange={setVoidDialogOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogMedia>
                            <ShieldAlert />
                        </AlertDialogMedia>
                        <AlertDialogTitle>Void sale</AlertDialogTitle>
                        <AlertDialogDescription>
                            Aksi ini hanya untuk administrator. Stok akan dikembalikan,
                            finance entry reversal akan dibuat, dan reason wajib disimpan.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <Textarea
                        value={voidReason}
                        onChange={(event) => setVoidReason(event.target.value)}
                        placeholder="Reason for void"
                    />
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={voidSale}
                            disabled={!voidReason.trim() || isVoiding}
                        >
                            {isVoiding ? (
                                <Loader2 className="animate-spin" />
                            ) : (
                                <Undo2 />
                            )}
                            Confirm void
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
            <style>{`
                @media print {
                    body * {
                        visibility: hidden;
                    }

                    .pos-receipt-print,
                    .pos-receipt-print * {
                        visibility: visible;
                    }

                    .pos-receipt-print {
                        position: absolute;
                        inset: 0;
                        width: 100%;
                        padding: 0;
                        color: #111827;
                        background: white;
                    }

                    .no-print {
                        display: none !important;
                    }
                }
            `}</style>
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
