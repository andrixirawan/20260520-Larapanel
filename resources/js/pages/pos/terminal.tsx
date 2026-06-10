import { Form, Head, Link, router } from '@inertiajs/react';
import {
    Banknote,
    Boxes,
    Clock,
    Minus,
    Plus,
    ReceiptText,
    Search,
    ShoppingCart,
} from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import type { PosProduct, PosShift, RecentSale } from './types';

type CartItem = PosProduct & {
    quantity: number;
};

const currency = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

export default function PosTerminal({
    products,
    openShift,
    paymentMethods,
    recentSales,
}: {
    products: PosProduct[];
    openShift: PosShift | null;
    paymentMethods: Record<string, string>;
    recentSales: RecentSale[];
}) {
    const [search, setSearch] = useState('');
    const [cart, setCart] = useState<CartItem[]>([]);
    const [paymentMethod, setPaymentMethod] = useState('cash');
    const [receivedAmount, setReceivedAmount] = useState('');
    const [processing, setProcessing] = useState(false);

    const filteredProducts = products.filter((product) => {
        const value = `${product.name} ${product.sku ?? ''}`.toLowerCase();

        return value.includes(search.toLowerCase());
    });

    const subtotal = cart.reduce(
        (total, item) => total + item.price * item.quantity,
        0,
    );
    const received =
        paymentMethod === 'cash' ? Number(receivedAmount || 0) : subtotal;
    const change = Math.max(received - subtotal, 0);
    const canCheckout =
        Boolean(openShift) &&
        cart.length > 0 &&
        (paymentMethod !== 'cash' || received >= subtotal);

    const addToCart = (product: PosProduct) => {
        if (!product.product_variant_id) {
            return;
        }

        setCart((items) => {
            const existing = items.find((item) => item.id === product.id);

            if (existing) {
                return items.map((item) =>
                    item.id === product.id
                        ? { ...item, quantity: item.quantity + 1 }
                        : item,
                );
            }

            return [...items, { ...product, quantity: 1 }];
        });
    };

    const updateQuantity = (productId: number, delta: number) => {
        setCart((items) =>
            items
                .map((item) =>
                    item.id === productId
                        ? { ...item, quantity: item.quantity + delta }
                        : item,
                )
                .filter((item) => item.quantity > 0),
        );
    };

    const checkout = () => {
        if (!canCheckout) {
            return;
        }

        router.post(
            '/pos/sales',
            {
                items: cart.map((item) => ({
                    product_variant_id: item.product_variant_id,
                    quantity: item.quantity,
                })),
                payment_method: paymentMethod,
                received_amount: received,
            },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onSuccess: () => {
                    setCart([]);
                    setReceivedAmount('');
                },
            },
        );
    };

    return (
        <>
            <Head title="POS Terminal" />

            <div className="flex h-full flex-1 flex-col gap-5 overflow-x-auto p-4">
                <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <Heading
                        title="POS Terminal"
                        description="Cashier workspace for shift-based sales and invoice creation."
                    />

                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline">
                            <Link href="/pos/sales">
                                <ReceiptText />
                                Sales
                            </Link>
                        </Button>
                        <Button asChild variant="outline">
                            <Link href="/pos/products">
                                <Boxes />
                                Products
                            </Link>
                        </Button>
                    </div>
                </div>

                {!openShift ? (
                    <Card className="border-amber-200 bg-amber-50/60 dark:border-amber-900/60 dark:bg-amber-950/20">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Clock className="size-5" />
                                Open shift required
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Form
                                action="/pos/shifts"
                                method="post"
                                className="grid gap-3 md:grid-cols-[220px_1fr_auto]"
                            >
                                {({ processing: opening }) => (
                                    <>
                                        <Input
                                            type="number"
                                            name="opening_cash"
                                            min="0"
                                            step="0.01"
                                            placeholder="Opening cash"
                                            required
                                        />
                                        <Input
                                            name="notes"
                                            placeholder="Opening note, optional"
                                        />
                                        <Button type="submit" disabled={opening}>
                                            Open shift
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardContent className="flex flex-col gap-4 p-4 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <div className="text-sm text-muted-foreground">
                                    Active shift
                                </div>
                                <div className="font-medium">
                                    #{openShift.id} opened with{' '}
                                    {currency.format(openShift.opening_cash)}
                                </div>
                            </div>

                            <Form
                                action={`/pos/shifts/${openShift.id}/close`}
                                method="post"
                                className="grid gap-2 sm:grid-cols-[180px_1fr_auto]"
                            >
                                {({ processing: closing }) => (
                                    <>
                                        <input
                                            type="hidden"
                                            name="_method"
                                            value="patch"
                                        />
                                        <Input
                                            type="number"
                                            name="counted_cash"
                                            min="0"
                                            step="0.01"
                                            placeholder="Counted cash"
                                            required
                                        />
                                        <Input
                                            name="notes"
                                            placeholder="Closing note"
                                        />
                                        <Button
                                            type="submit"
                                            variant="outline"
                                            disabled={closing}
                                        >
                                            Close shift
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                )}

                <div className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_420px]">
                    <section className="space-y-4">
                        <div className="relative">
                            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                placeholder="Search product or SKU"
                                className="pl-9"
                            />
                        </div>

                        <div className="grid gap-3 sm:grid-cols-2 2xl:grid-cols-3">
                            {filteredProducts.length ? (
                                filteredProducts.map((product) => (
                                    <button
                                        key={product.id}
                                        type="button"
                                        onClick={() => addToCart(product)}
                                        disabled={
                                            !openShift ||
                                            (product.track_inventory &&
                                                product.stock <= 0)
                                        }
                                        className="group rounded-xl border bg-card p-4 text-left shadow-xs transition hover:-translate-y-0.5 hover:border-primary/50 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <div className="flex items-start justify-between gap-3">
                                            <div>
                                                <div className="font-semibold">
                                                    {product.name}
                                                </div>
                                                <div className="text-xs text-muted-foreground">
                                                    {product.sku ?? 'No SKU'}
                                                </div>
                                            </div>
                                            <Badge variant="outline">
                                                {currency.format(product.price)}
                                            </Badge>
                                        </div>
                                        <div className="mt-4 flex items-center justify-between text-sm">
                                            <span className="text-muted-foreground">
                                                Stock
                                            </span>
                                            <span
                                                className={
                                                    product.track_inventory &&
                                                    product.stock <= 0
                                                        ? 'font-medium text-destructive'
                                                        : 'font-medium'
                                                }
                                            >
                                                {product.track_inventory
                                                    ? product.stock
                                                    : 'Not tracked'}
                                            </span>
                                        </div>
                                    </button>
                                ))
                            ) : (
                                <div className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground sm:col-span-2 2xl:col-span-3">
                                    No products match your search.
                                </div>
                            )}
                        </div>
                    </section>

                    <aside className="space-y-4">
                        <Card className="xl:sticky xl:top-4">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <ShoppingCart className="size-5" />
                                    Cart
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {cart.length ? (
                                    <div className="space-y-3">
                                        {cart.map((item) => (
                                            <div
                                                key={item.id}
                                                className="rounded-lg border p-3"
                                            >
                                                <div className="flex justify-between gap-3">
                                                    <div>
                                                        <div className="font-medium">
                                                            {item.name}
                                                        </div>
                                                        <div className="text-xs text-muted-foreground">
                                                            {currency.format(
                                                                item.price,
                                                            )}
                                                        </div>
                                                    </div>
                                                    <div className="font-semibold">
                                                        {currency.format(
                                                            item.price *
                                                                item.quantity,
                                                        )}
                                                    </div>
                                                </div>
                                                <div className="mt-3 flex items-center gap-2">
                                                    <Button
                                                        type="button"
                                                        size="icon-sm"
                                                        variant="outline"
                                                        onClick={() =>
                                                            updateQuantity(
                                                                item.id,
                                                                -1,
                                                            )
                                                        }
                                                    >
                                                        <Minus />
                                                    </Button>
                                                    <span className="w-10 text-center font-medium">
                                                        {item.quantity}
                                                    </span>
                                                    <Button
                                                        type="button"
                                                        size="icon-sm"
                                                        variant="outline"
                                                        onClick={() =>
                                                            updateQuantity(
                                                                item.id,
                                                                1,
                                                            )
                                                        }
                                                    >
                                                        <Plus />
                                                    </Button>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
                                        Add products to start a sale.
                                    </div>
                                )}

                                <div className="space-y-2 border-t pt-4">
                                    <div className="flex justify-between text-sm">
                                        <span>Subtotal</span>
                                        <span>{currency.format(subtotal)}</span>
                                    </div>
                                    <div className="flex justify-between text-lg font-semibold">
                                        <span>Total</span>
                                        <span>{currency.format(subtotal)}</span>
                                    </div>
                                </div>

                                <div className="space-y-3">
                                    <select
                                        value={paymentMethod}
                                        onChange={(event) =>
                                            setPaymentMethod(event.target.value)
                                        }
                                        className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    >
                                        {Object.entries(paymentMethods).map(
                                            ([value, label]) => (
                                                <option
                                                    key={value}
                                                    value={value}
                                                >
                                                    {label}
                                                </option>
                                            ),
                                        )}
                                    </select>

                                    {paymentMethod === 'cash' && (
                                        <Input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            value={receivedAmount}
                                            onChange={(event) =>
                                                setReceivedAmount(
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="Cash received"
                                        />
                                    )}

                                    <div className="flex items-center justify-between rounded-lg bg-muted px-3 py-2 text-sm">
                                        <span>Change</span>
                                        <span className="font-semibold">
                                            {currency.format(change)}
                                        </span>
                                    </div>

                                    <Button
                                        type="button"
                                        size="lg"
                                        className="w-full"
                                        disabled={!canCheckout || processing}
                                        onClick={checkout}
                                    >
                                        <Banknote />
                                        Complete sale
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Recent invoices</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                {recentSales.length ? (
                                    recentSales.map((sale) => (
                                        <Link
                                            key={sale.id}
                                            href={`/pos/sales/${sale.id}`}
                                            className="flex items-center justify-between rounded-lg border p-3 text-sm hover:bg-muted"
                                        >
                                            <span>{sale.invoice_number}</span>
                                            <span className="font-medium">
                                                {currency.format(sale.total)}
                                            </span>
                                        </Link>
                                    ))
                                ) : (
                                    <div className="text-sm text-muted-foreground">
                                        No sales in this browser session yet.
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </aside>
                </div>
            </div>
        </>
    );
}

PosTerminal.layout = {
    breadcrumbs: [
        {
            title: 'POS',
            href: '/pos',
        },
    ],
};
