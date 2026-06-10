import { Head, Link, router } from '@inertiajs/react';
import {
    Banknote,
    Boxes,
    Clock3,
    CreditCard,
    Loader2,
    Minus,
    PackageSearch,
    Plus,
    ReceiptText,
    ShieldCheck,
    ShoppingBag,
    ShoppingCart,
    Wallet,
} from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import { SearchInput } from '@/components/search-input';
import {
    Alert,
    AlertDescription,
    AlertTitle,
} from '@/components/ui/alert';
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
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { Input } from '@/components/ui/input';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import type { PosProduct, PosShift, RecentSale } from './types';
import { usePosTerminalStore } from './use-pos-terminal-store';
import { firstErrorMessage, formatPosDateTime, posCurrency } from './utils';

function CartContent({
    cart,
    paymentMethod,
    receivedAmount,
    paymentMethods,
    subtotal,
    change,
    isSubmitting,
    onPaymentMethodChange,
    onReceivedAmountChange,
    onQuantityChange,
    onCheckout,
}: {
    cart: ReturnType<typeof usePosTerminalStore.getState>['cart'];
    paymentMethod: string;
    receivedAmount: string;
    paymentMethods: Record<string, string>;
    subtotal: number;
    change: number;
    isSubmitting: boolean;
    onPaymentMethodChange: (value: string) => void;
    onReceivedAmountChange: (value: string) => void;
    onQuantityChange: (productPublicId: string, delta: number) => void;
    onCheckout: () => void;
}) {
    const canCheckout =
        cart.length > 0 &&
        (paymentMethod !== 'cash' || Number(receivedAmount || 0) >= subtotal);

    return (
        <div className="flex h-full flex-col">
            <ScrollArea className="h-[320px] pr-1 xl:h-[420px]">
                <div className="space-y-3">
                    {cart.length ? (
                        cart.map((item) => (
                            <div
                                key={item.public_id}
                                className="rounded-2xl border bg-card/70 p-4 shadow-xs"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <div className="font-medium">
                                            {item.name}
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                            {item.sku ?? 'No SKU'}
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <div className="font-semibold">
                                            {posCurrency.format(
                                                item.price * item.quantity,
                                            )}
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                            {posCurrency.format(item.price)} each
                                        </div>
                                    </div>
                                </div>
                                <div className="mt-4 flex items-center gap-2">
                                    <Button
                                        type="button"
                                        size="icon-sm"
                                        variant="outline"
                                        onClick={() =>
                                            onQuantityChange(item.public_id, -1)
                                        }
                                    >
                                        <Minus />
                                    </Button>
                                    <div className="min-w-10 text-center text-sm font-semibold">
                                        {item.quantity}
                                    </div>
                                    <Button
                                        type="button"
                                        size="icon-sm"
                                        variant="outline"
                                        onClick={() =>
                                            onQuantityChange(item.public_id, 1)
                                        }
                                    >
                                        <Plus />
                                    </Button>
                                </div>
                            </div>
                        ))
                    ) : (
                        <Empty className="min-h-[260px] border border-dashed">
                            <EmptyHeader>
                                <EmptyMedia variant="icon">
                                    <ShoppingCart />
                                </EmptyMedia>
                                <EmptyTitle>Cart is empty</EmptyTitle>
                                <EmptyDescription>
                                    Pilih produk dari katalog untuk mulai
                                    transaksi.
                                </EmptyDescription>
                            </EmptyHeader>
                        </Empty>
                    )}
                </div>
            </ScrollArea>

            <div className="mt-4 space-y-4 border-t pt-4">
                <div className="rounded-2xl border bg-muted/40 p-4">
                    <div className="flex items-center justify-between text-sm text-muted-foreground">
                        <span>Items</span>
                        <span>{cart.length}</span>
                    </div>
                    <div className="mt-2 flex items-center justify-between text-sm text-muted-foreground">
                        <span>Subtotal</span>
                        <span>{posCurrency.format(subtotal)}</span>
                    </div>
                    <div className="mt-3 flex items-center justify-between text-lg font-semibold">
                        <span>Total</span>
                        <span>{posCurrency.format(subtotal)}</span>
                    </div>
                </div>

                <div className="space-y-3">
                    <Select
                        value={paymentMethod}
                        onValueChange={onPaymentMethodChange}
                    >
                        <SelectTrigger className="w-full">
                            <SelectValue placeholder="Payment method" />
                        </SelectTrigger>
                        <SelectContent>
                            {Object.entries(paymentMethods).map(
                                ([value, label]) => (
                                    <SelectItem key={value} value={value}>
                                        {label}
                                    </SelectItem>
                                ),
                            )}
                        </SelectContent>
                    </Select>

                    {paymentMethod === 'cash' && (
                        <Input
                            type="number"
                            min="0"
                            step="0.01"
                            value={receivedAmount}
                            onChange={(event) =>
                                onReceivedAmountChange(event.target.value)
                            }
                            placeholder="Cash received"
                        />
                    )}

                    <div className="flex items-center justify-between rounded-xl bg-muted px-4 py-3 text-sm">
                        <span className="text-muted-foreground">Change</span>
                        <span className="font-semibold">
                            {posCurrency.format(change)}
                        </span>
                    </div>
                </div>
            </div>

            <Button
                type="button"
                size="lg"
                className="mt-4 w-full"
                disabled={!canCheckout || isSubmitting}
                onClick={onCheckout}
            >
                {isSubmitting ? <Loader2 className="animate-spin" /> : <Wallet />}
                Complete sale
            </Button>
        </div>
    );
}

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
    const {
        cart,
        paymentMethod,
        receivedAmount,
        cartSheetOpen,
        checkoutOpen,
        closeShiftOpen,
        setPaymentMethod,
        setReceivedAmount,
        setCartSheetOpen,
        setCheckoutOpen,
        setCloseShiftOpen,
        addToCart,
        updateQuantity,
        clearCart,
        resetPayment,
    } = usePosTerminalStore();

    const [search, setSearch] = useState('');
    const [openShiftDialogOpen, setOpenShiftDialogOpen] = useState(false);
    const [openingCash, setOpeningCash] = useState('');
    const [openingNotes, setOpeningNotes] = useState('');
    const [countedCash, setCountedCash] = useState('');
    const [closingNotes, setClosingNotes] = useState('');
    const [isOpeningShift, setIsOpeningShift] = useState(false);
    const [isClosingShift, setIsClosingShift] = useState(false);
    const [isSubmittingSale, setIsSubmittingSale] = useState(false);

    const filteredProducts = products.filter((product) =>
        `${product.name} ${product.sku ?? ''}`
            .toLowerCase()
            .includes(search.toLowerCase()),
    );

    const subtotal = cart.reduce(
        (total, item) => total + item.price * item.quantity,
        0,
    );
    const received =
        paymentMethod === 'cash' ? Number(receivedAmount || 0) : subtotal;
    const change = Math.max(received - subtotal, 0);
    const availableCount = products.filter(
        (product) => !product.track_inventory || product.stock > 0,
    ).length;

    const openShiftAction = () => {
        const loadingToast = toast.loading('Opening shift...');

        router.post(
            '/pos/shifts',
            {
                opening_cash: openingCash,
                notes: openingNotes,
            },
            {
                preserveScroll: true,
                onStart: () => setIsOpeningShift(true),
                onError: (errors) =>
                    toast.error(firstErrorMessage(errors), {
                        id: loadingToast,
                    }),
                onSuccess: () => {
                    setOpenShiftDialogOpen(false);
                    setOpeningCash('');
                    setOpeningNotes('');
                },
                onFinish: () => {
                    setIsOpeningShift(false);
                    toast.dismiss(loadingToast);
                },
            },
        );
    };

    const closeShiftAction = () => {
        if (!openShift) {
            return;
        }

        const loadingToast = toast.loading('Closing shift...');

        router.patch(
            `/pos/shifts/${openShift.public_id}/close`,
            {
                counted_cash: countedCash,
                notes: closingNotes,
            },
            {
                preserveScroll: true,
                onStart: () => setIsClosingShift(true),
                onError: (errors) =>
                    toast.error(firstErrorMessage(errors), {
                        id: loadingToast,
                    }),
                onSuccess: () => {
                    setCloseShiftOpen(false);
                    setCountedCash('');
                    setClosingNotes('');
                    clearCart();
                    resetPayment();
                },
                onFinish: () => {
                    setIsClosingShift(false);
                    toast.dismiss(loadingToast);
                },
            },
        );
    };

    const checkout = () => {
        const loadingToast = toast.loading('Submitting sale...');

        router.post(
            '/pos/sales',
            {
                items: cart.map((item) => ({
                    product_variant_public_id: item.product_variant_public_id,
                    quantity: item.quantity,
                })),
                payment_method: paymentMethod,
                received_amount: received,
            },
            {
                preserveScroll: true,
                onStart: () => setIsSubmittingSale(true),
                onError: (errors) =>
                    toast.error(firstErrorMessage(errors), {
                        id: loadingToast,
                    }),
                onSuccess: () => {
                    setCheckoutOpen(false);
                    setCartSheetOpen(false);
                    clearCart();
                    resetPayment();
                },
                onFinish: () => {
                    setIsSubmittingSale(false);
                    toast.dismiss(loadingToast);
                },
            },
        );
    };

    if (!openShift) {
        return (
            <>
                <Head title="POS Terminal" />

                <div className="flex h-full flex-1 items-center justify-center overflow-hidden p-4">
                    <div className="grid w-full max-w-5xl gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
                        <Card className="relative overflow-hidden border-none bg-[linear-gradient(135deg,rgba(15,23,42,1),rgba(14,116,144,0.9),rgba(34,197,94,0.72))] text-white shadow-2xl">
                            <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(255,255,255,0.18),transparent_32%),radial-gradient(circle_at_bottom_right,rgba(255,255,255,0.14),transparent_28%)]" />
                            <CardContent className="relative flex min-h-[560px] flex-col justify-between p-8 md:p-10">
                                <div className="space-y-8">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Badge className="bg-white/14 text-white hover:bg-white/14">
                                            Cash drawer secured
                                        </Badge>
                                        <Badge className="bg-white/14 text-white hover:bg-white/14">
                                            Shift-gated checkout
                                        </Badge>
                                    </div>

                                    <div className="max-w-2xl space-y-4">
                                        <h1 className="text-3xl font-semibold tracking-tight md:text-5xl">
                                            Mulai shift dulu, baru terminal POS
                                            dibuka penuh.
                                        </h1>
                                        <p className="max-w-xl text-sm leading-6 text-white/78 md:text-base">
                                            Flow ini sengaja memisahkan operasional
                                            pembukaan kas dengan transaksi agar
                                            kasir tidak langsung melihat cart,
                                            payment, dan katalog sebelum shift
                                            valid.
                                        </p>
                                    </div>

                                    <div className="grid gap-3 md:grid-cols-3">
                                        {[
                                            {
                                                icon: Wallet,
                                                title: 'Input opening cash',
                                                description:
                                                    'Kas awal disimpan sebagai baseline rekonsiliasi shift.',
                                            },
                                            {
                                                icon: ShieldCheck,
                                                title: 'Lock operational scope',
                                                description:
                                                    'Transaksi cash hanya boleh jalan saat shift open.',
                                            },
                                            {
                                                icon: ReceiptText,
                                                title: 'Trace all activities',
                                                description:
                                                    'Invoice, payment, inventory, dan finance tetap terhubung.',
                                            },
                                        ].map((item) => (
                                            <div
                                                key={item.title}
                                                className="rounded-2xl border border-white/15 bg-white/8 p-4 backdrop-blur-sm"
                                            >
                                                <item.icon className="size-5" />
                                                <div className="mt-3 font-medium">
                                                    {item.title}
                                                </div>
                                                <div className="mt-1 text-sm text-white/72">
                                                    {item.description}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>

                                <div className="flex flex-wrap gap-3">
                                    <Button
                                        size="lg"
                                        variant="secondary"
                                        onClick={() =>
                                            setOpenShiftDialogOpen(true)
                                        }
                                    >
                                        <Clock3 />
                                        Open shift
                                    </Button>
                                    <Button
                                        asChild
                                        size="lg"
                                        variant="outline"
                                        className="border-white/20 bg-transparent text-white hover:bg-white/10 hover:text-white"
                                    >
                                        <Link href="/pos/shifts">
                                            <ReceiptText />
                                            View shift history
                                        </Link>
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>

                        <div className="space-y-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Before opening shift</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4 text-sm text-muted-foreground">
                                    <Alert>
                                        <ShieldCheck />
                                        <AlertTitle>Operational guardrail</AlertTitle>
                                        <AlertDescription>
                                            UI terminal disembunyikan penuh sampai
                                            shift aktif agar alur cashier tidak
                                            tercampur dengan setup kas.
                                        </AlertDescription>
                                    </Alert>
                                    <div className="rounded-2xl border bg-muted/40 p-4">
                                        <div className="text-xs uppercase tracking-[0.22em] text-muted-foreground">
                                            Checklist
                                        </div>
                                        <div className="mt-3 space-y-3 text-foreground">
                                            <div>1. Verifikasi uang kas awal.</div>
                                            <div>2. Pastikan drawer dan printer siap.</div>
                                            <div>3. Baru masuk ke terminal transaksi.</div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle>Last invoices</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    {recentSales.length ? (
                                        recentSales.map((sale) => (
                                            <Link
                                                key={sale.public_id}
                                                href={`/pos/sales/${sale.public_id}`}
                                                className="flex items-center justify-between rounded-xl border p-3 text-sm transition hover:bg-muted/40"
                                            >
                                                <div>
                                                    <div className="font-medium">
                                                        {sale.invoice_number}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {formatPosDateTime(
                                                            sale.created_at,
                                                        )}
                                                    </div>
                                                </div>
                                                <div className="text-right">
                                                    <div className="font-semibold">
                                                        {posCurrency.format(
                                                            sale.total,
                                                        )}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {sale.payment_method ??
                                                            '-'}
                                                    </div>
                                                </div>
                                            </Link>
                                        ))
                                    ) : (
                                        <Empty className="min-h-[180px] border border-dashed">
                                            <EmptyHeader>
                                                <EmptyMedia variant="icon">
                                                    <ReceiptText />
                                                </EmptyMedia>
                                                <EmptyTitle>
                                                    No recent invoices
                                                </EmptyTitle>
                                                <EmptyDescription>
                                                    Riwayat invoice akan muncul
                                                    setelah transaksi pertama.
                                                </EmptyDescription>
                                            </EmptyHeader>
                                        </Empty>
                                    )}
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>

                <Dialog
                    open={openShiftDialogOpen}
                    onOpenChange={setOpenShiftDialogOpen}
                >
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Open shift</DialogTitle>
                            <DialogDescription>
                                Catat uang kas awal sebelum terminal transaksi
                                diaktifkan.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-4">
                            <Input
                                type="number"
                                min="0"
                                step="0.01"
                                value={openingCash}
                                onChange={(event) =>
                                    setOpeningCash(event.target.value)
                                }
                                placeholder="Opening cash"
                            />
                            <Textarea
                                value={openingNotes}
                                onChange={(event) =>
                                    setOpeningNotes(event.target.value)
                                }
                                placeholder="Opening notes, optional"
                            />
                        </div>
                        <DialogFooter>
                            <Button
                                variant="outline"
                                onClick={() => setOpenShiftDialogOpen(false)}
                            >
                                Cancel
                            </Button>
                            <Button
                                onClick={openShiftAction}
                                disabled={!openingCash || isOpeningShift}
                            >
                                {isOpeningShift ? (
                                    <Loader2 className="animate-spin" />
                                ) : (
                                    <Clock3 />
                                )}
                                Open shift
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </>
        );
    }

    return (
        <>
            <Head title="POS Terminal" />

            <div className="flex h-full flex-1 flex-col gap-5 overflow-hidden p-4">
                <div className="grid gap-4 xl:grid-cols-[minmax(0,1.6fr)_minmax(320px,0.8fr)]">
                    <Card className="relative overflow-hidden border-none bg-[linear-gradient(140deg,rgba(15,23,42,1),rgba(30,41,59,1),rgba(8,145,178,0.92))] text-white shadow-xl">
                        <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.14),transparent_28%),radial-gradient(circle_at_bottom_left,rgba(255,255,255,0.12),transparent_25%)]" />
                        <CardContent className="relative p-6 md:p-7">
                            <div className="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                                <div className="space-y-3">
                                    <div className="flex flex-wrap gap-2">
                                        <Badge className="bg-white/14 text-white hover:bg-white/14">
                                            Shift {openShift.public_id.slice(-8)}
                                        </Badge>
                                        <Badge className="bg-white/14 text-white hover:bg-white/14">
                                            Opened {formatPosDateTime(openShift.opened_at)}
                                        </Badge>
                                    </div>
                                    <div>
                                        <h1 className="text-2xl font-semibold tracking-tight md:text-3xl">
                                            POS terminal is live
                                        </h1>
                                        <p className="mt-2 max-w-xl text-sm leading-6 text-white/76">
                                            Katalog, cart, payment, dan invoice
                                            sekarang aktif di dalam shift yang
                                            sedang berjalan.
                                        </p>
                                    </div>
                                </div>

                                <div className="flex flex-wrap gap-2">
                                    <Button
                                        variant="secondary"
                                        onClick={() =>
                                            setCloseShiftOpen(true)
                                        }
                                    >
                                        <Clock3 />
                                        Close shift
                                    </Button>
                                    <Button
                                        asChild
                                        variant="outline"
                                        className="border-white/20 bg-transparent text-white hover:bg-white/10 hover:text-white"
                                    >
                                        <Link href="/pos/sales">
                                            <ReceiptText />
                                            Sales history
                                        </Link>
                                    </Button>
                                    <Button
                                        asChild
                                        variant="outline"
                                        className="border-white/20 bg-transparent text-white hover:bg-white/10 hover:text-white"
                                    >
                                        <Link href="/pos/products">
                                            <Boxes />
                                            Products
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="grid gap-4 sm:grid-cols-3 xl:grid-cols-1">
                        {[
                            {
                                icon: Wallet,
                                label: 'Opening cash',
                                value: posCurrency.format(openShift.opening_cash),
                            },
                            {
                                icon: PackageSearch,
                                label: 'Available products',
                                value: `${availableCount}/${products.length}`,
                            },
                            {
                                icon: ShoppingBag,
                                label: 'Cart total',
                                value: posCurrency.format(subtotal),
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
                </div>

                <div className="grid min-h-0 flex-1 gap-5 xl:grid-cols-[minmax(0,1fr)_400px]">
                    <section className="min-h-0 rounded-3xl border bg-card">
                        <div className="flex flex-col gap-4 border-b p-5">
                            <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                <Heading
                                    title="Catalog"
                                    description="Tambah item ke cart dari produk aktif yang siap dijual."
                                    variant="small"
                                />

                                <div className="flex items-center gap-2">
                                    <SearchInput
                                        value={search}
                                        onValueChange={setSearch}
                                        placeholder="Search product or SKU"
                                        className="flex-1 lg:w-72"
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="xl:hidden"
                                        onClick={() => setCartSheetOpen(true)}
                                    >
                                        <ShoppingCart />
                                        Cart ({cart.length})
                                    </Button>
                                </div>
                            </div>

                            <Alert>
                                <ShieldCheck />
                                <AlertTitle>Terminal mode</AlertTitle>
                                <AlertDescription>
                                    Produk habis stok tetap terlihat untuk
                                    observabilitas, tetapi tidak bisa ditambahkan
                                    ke cart jika inventory tracking aktif.
                                </AlertDescription>
                            </Alert>
                        </div>

                        <ScrollArea className="h-[calc(100vh-25rem)] xl:h-[calc(100vh-21rem)]">
                            <div className="grid gap-4 p-5 sm:grid-cols-2 2xl:grid-cols-3">
                                {filteredProducts.length ? (
                                    filteredProducts.map((product) => {
                                        const disabled =
                                            product.track_inventory &&
                                            product.stock <= 0;

                                        return (
                                            <button
                                                key={product.public_id}
                                                type="button"
                                                onClick={() => addToCart(product)}
                                                disabled={disabled}
                                                className={cn(
                                                    'group rounded-3xl border p-4 text-left transition hover:-translate-y-0.5 hover:shadow-md',
                                                    disabled
                                                        ? 'cursor-not-allowed border-dashed bg-muted/30 opacity-60'
                                                        : 'bg-[linear-gradient(180deg,rgba(255,255,255,0.9),rgba(248,250,252,0.6))] hover:border-primary/40 dark:bg-[linear-gradient(180deg,rgba(15,23,42,0.4),rgba(15,23,42,0.2))]',
                                                )}
                                            >
                                                <div className="flex items-start justify-between gap-3">
                                                    <div className="space-y-1">
                                                        <div className="font-semibold">
                                                            {product.name}
                                                        </div>
                                                        <div className="text-xs text-muted-foreground">
                                                            {product.sku ?? 'No SKU'}
                                                        </div>
                                                    </div>
                                                    <Badge variant="outline">
                                                        {posCurrency.format(
                                                            product.price,
                                                        )}
                                                    </Badge>
                                                </div>

                                                <div className="mt-6 flex items-end justify-between gap-3">
                                                    <div>
                                                        <div className="text-xs uppercase tracking-[0.2em] text-muted-foreground">
                                                            Inventory
                                                        </div>
                                                        <div
                                                            className={cn(
                                                                'mt-1 text-sm font-medium',
                                                                disabled &&
                                                                    'text-destructive',
                                                            )}
                                                        >
                                                            {product.track_inventory
                                                                ? `${product.stock} ready`
                                                                : 'Not tracked'}
                                                        </div>
                                                    </div>
                                                    <div className="rounded-full bg-primary/8 px-3 py-1 text-xs font-medium text-primary">
                                                        Add to cart
                                                    </div>
                                                </div>
                                            </button>
                                        );
                                    })
                                ) : (
                                    <Empty className="min-h-[320px] border border-dashed sm:col-span-2 2xl:col-span-3">
                                        <EmptyHeader>
                                            <EmptyMedia variant="icon">
                                                <PackageSearch />
                                            </EmptyMedia>
                                            <EmptyTitle>
                                                No matching products
                                            </EmptyTitle>
                                            <EmptyDescription>
                                                Ubah kata kunci pencarian atau
                                                tambahkan produk baru dari halaman
                                                products.
                                            </EmptyDescription>
                                        </EmptyHeader>
                                        <EmptyContent>
                                            <Button asChild variant="outline">
                                                <Link href="/pos/products">
                                                    <Boxes />
                                                    Manage products
                                                </Link>
                                            </Button>
                                        </EmptyContent>
                                    </Empty>
                                )}
                            </div>
                        </ScrollArea>
                    </section>

                    <aside className="hidden min-h-0 xl:block">
                        <Card className="h-full rounded-3xl">
                            <CardHeader className="border-b">
                                <CardTitle className="flex items-center gap-2">
                                    <ShoppingCart className="size-5" />
                                    Active cart
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="flex h-[calc(100%-4.5rem)] flex-col p-5">
                                <CartContent
                                    cart={cart}
                                    paymentMethod={paymentMethod}
                                    receivedAmount={receivedAmount}
                                    paymentMethods={paymentMethods}
                                    subtotal={subtotal}
                                    change={change}
                                    isSubmitting={isSubmittingSale}
                                    onPaymentMethodChange={setPaymentMethod}
                                    onReceivedAmountChange={setReceivedAmount}
                                    onQuantityChange={updateQuantity}
                                    onCheckout={() => setCheckoutOpen(true)}
                                />
                            </CardContent>
                        </Card>
                    </aside>
                </div>

                <div className="fixed right-4 bottom-4 z-30 xl:hidden">
                    <Button
                        size="lg"
                        className="shadow-lg"
                        onClick={() => setCartSheetOpen(true)}
                    >
                        <ShoppingCart />
                        Cart {cart.length ? `(${cart.length})` : ''}
                    </Button>
                </div>

                <Sheet open={cartSheetOpen} onOpenChange={setCartSheetOpen}>
                    <SheetContent side="right" className="w-full sm:max-w-md">
                        <SheetHeader>
                            <SheetTitle>Cart</SheetTitle>
                            <SheetDescription>
                                Review item, payment method, dan total transaksi.
                            </SheetDescription>
                        </SheetHeader>
                        <div className="flex-1 overflow-hidden px-4">
                            <CartContent
                                cart={cart}
                                paymentMethod={paymentMethod}
                                receivedAmount={receivedAmount}
                                paymentMethods={paymentMethods}
                                subtotal={subtotal}
                                change={change}
                                isSubmitting={isSubmittingSale}
                                onPaymentMethodChange={setPaymentMethod}
                                onReceivedAmountChange={setReceivedAmount}
                                onQuantityChange={updateQuantity}
                                onCheckout={() => setCheckoutOpen(true)}
                            />
                        </div>
                        <SheetFooter />
                    </SheetContent>
                </Sheet>

                <Dialog open={closeShiftOpen} onOpenChange={setCloseShiftOpen}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Close shift</DialogTitle>
                            <DialogDescription>
                                Rekonsiliasi kas aktual sebelum shift ditutup.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-4">
                            <Input
                                type="number"
                                min="0"
                                step="0.01"
                                value={countedCash}
                                onChange={(event) =>
                                    setCountedCash(event.target.value)
                                }
                                placeholder="Counted cash"
                            />
                            <Textarea
                                value={closingNotes}
                                onChange={(event) =>
                                    setClosingNotes(event.target.value)
                                }
                                placeholder="Closing notes, optional"
                            />
                        </div>
                        <DialogFooter>
                            <Button
                                variant="outline"
                                onClick={() => setCloseShiftOpen(false)}
                            >
                                Cancel
                            </Button>
                            <Button
                                onClick={closeShiftAction}
                                disabled={!countedCash || isClosingShift}
                            >
                                {isClosingShift ? (
                                    <Loader2 className="animate-spin" />
                                ) : (
                                    <Clock3 />
                                )}
                                Close shift
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <AlertDialog
                    open={checkoutOpen}
                    onOpenChange={setCheckoutOpen}
                >
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogMedia>
                                <CreditCard />
                            </AlertDialogMedia>
                            <AlertDialogTitle>
                                Confirm checkout
                            </AlertDialogTitle>
                            <AlertDialogDescription>
                                Invoice akan dibuat, stok akan dikurangi, dan
                                payment akan dicatat ke finance ledger.
                            </AlertDialogDescription>
                        </AlertDialogHeader>

                        <div className="rounded-2xl border bg-muted/40 p-4 text-sm">
                            <div className="flex items-center justify-between">
                                <span className="text-muted-foreground">
                                    Items
                                </span>
                                <span>{cart.length}</span>
                            </div>
                            <div className="mt-2 flex items-center justify-between">
                                <span className="text-muted-foreground">
                                    Payment
                                </span>
                                <span>{paymentMethods[paymentMethod]}</span>
                            </div>
                            <div className="mt-2 flex items-center justify-between font-semibold">
                                <span>Total</span>
                                <span>{posCurrency.format(subtotal)}</span>
                            </div>
                        </div>

                        <AlertDialogFooter>
                            <AlertDialogCancel>Back</AlertDialogCancel>
                            <AlertDialogAction
                                onClick={checkout}
                                disabled={isSubmittingSale}
                            >
                                {isSubmittingSale ? (
                                    <Loader2 className="animate-spin" />
                                ) : (
                                    <Banknote />
                                )}
                                Confirm sale
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
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
