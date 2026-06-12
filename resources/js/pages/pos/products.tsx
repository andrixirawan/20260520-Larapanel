import { Head, Link, router, usePage } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import {
    ArrowUpDown,
    Boxes,
    ClipboardList,
    Loader2,
    Siren,
    PackagePlus,
    Pencil,
    Trash2,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import { toast } from 'sonner';
import { DataTable } from '@/components/data-table';
import {
    Alert,
    AlertDescription,
    AlertTitle,
} from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { Auth } from '@/types';
import type { TableFilters } from '@/types/pagination';
import type { Paginated, PosProductRow } from './types';
import { firstErrorMessage, posCurrency } from './utils';

type ProductFormState = {
    name: string;
    sku: string;
    price: string;
    cost_price: string;
    initial_quantity: string;
    low_stock_threshold: string;
    description: string;
    track_inventory: boolean;
    allow_backorder: boolean;
    status: 'active' | 'inactive';
};

const initialProductForm: ProductFormState = {
    name: '',
    sku: '',
    price: '',
    cost_price: '',
    initial_quantity: '',
    low_stock_threshold: '0',
    description: '',
    track_inventory: true,
    allow_backorder: false,
    status: 'active',
};

export default function PosProducts({
    products,
    filters,
}: {
    products: Paginated<PosProductRow>;
    filters: TableFilters;
}) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const canManageProducts = auth.permissions['pos.products.manage'];
    const canManageInventory = auth.permissions['pos.inventory.manage'];
    const [createDialogOpen, setCreateDialogOpen] = useState(false);
    const [adjustDialogOpen, setAdjustDialogOpen] = useState(false);
    const [stockOpnameDialogOpen, setStockOpnameDialogOpen] = useState(false);
    const [editDialogOpen, setEditDialogOpen] = useState(false);
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [productForm, setProductForm] =
        useState<ProductFormState>(initialProductForm);
    const [editForm, setEditForm] = useState<ProductFormState>(initialProductForm);
    const [adjustment, setAdjustment] = useState({
        quantity_delta: '',
        notes: '',
    });
    const [stockOpnameNotes, setStockOpnameNotes] = useState('');
    const [stockOpnameCounts, setStockOpnameCounts] = useState<Record<string, string>>(
        {},
    );
    const [selectedProduct, setSelectedProduct] = useState<PosProductRow | null>(
        null,
    );
    const [isCreating, setIsCreating] = useState(false);
    const [isAdjusting, setIsAdjusting] = useState(false);
    const [isUpdating, setIsUpdating] = useState(false);
    const [isDeleting, setIsDeleting] = useState(false);
    const [isSubmittingStockOpname, setIsSubmittingStockOpname] = useState(false);
    const columns = useMemo<ColumnDef<PosProductRow>[]>(
        () => [
            {
                id: 'name',
                accessorKey: 'name',
                header: 'Product',
                cell: ({ row }) => (
                    <>
                        <div className="font-medium">{row.original.name}</div>
                        <div className="text-xs text-muted-foreground">
                            {row.original.sku ?? 'No SKU'}
                        </div>
                    </>
                ),
            },
            {
                id: 'price',
                header: 'Price',
                enableSorting: false,
                cell: ({ row }) => (
                    <>
                        <div className="font-medium">
                            {posCurrency.format(row.original.price)}
                        </div>
                        <div className="text-xs text-muted-foreground">
                            Cost:{' '}
                            {row.original.cost_price === null
                                ? '-'
                                : posCurrency.format(row.original.cost_price)}
                        </div>
                        <div className="text-xs text-muted-foreground">
                            Threshold: {row.original.low_stock_threshold}
                        </div>
                    </>
                ),
            },
            {
                id: 'stock',
                header: 'Stock',
                enableSorting: false,
                cell: ({ row }) =>
                    row.original.track_inventory ? (
                        <Badge
                            variant={
                                row.original.stock <= 0
                                    ? 'destructive'
                                    : row.original.is_low_stock
                                      ? 'secondary'
                                    : 'outline'
                            }
                        >
                            {row.original.stock}
                        </Badge>
                    ) : (
                        <Badge variant="secondary">Not tracked</Badge>
                    ),
            },
            {
                id: 'status',
                accessorKey: 'status',
                header: 'Status',
                cell: ({ row }) => (
                    <Badge
                        variant={
                            row.original.status === 'active'
                                ? 'default'
                                : 'secondary'
                        }
                    >
                        {row.original.status}
                    </Badge>
                ),
            },
            {
                id: 'action',
                header: 'Action',
                enableSorting: false,
                meta: {
                    headerClassName: 'text-right',
                    cellClassName: 'text-right',
                },
                cell: ({ row }) =>
                    (
                        <div className="flex justify-end gap-2">
                            {canManageInventory &&
                            row.original.product_variant_public_id ? (
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() => {
                                        setSelectedProduct(row.original);
                                        setAdjustDialogOpen(true);
                                    }}
                                >
                                    Adjust stock
                                </Button>
                            ) : null}
                            {canManageProducts ? (
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() => {
                                        setSelectedProduct(row.original);
                                        setEditForm({
                                            name: row.original.name,
                                            sku: row.original.sku ?? '',
                                            price: String(row.original.price),
                                            cost_price:
                                                row.original.cost_price === null
                                                    ? ''
                                                    : String(row.original.cost_price),
                                            initial_quantity: '',
                                            low_stock_threshold: String(
                                                row.original.low_stock_threshold,
                                            ),
                                            description: row.original.description ?? '',
                                            track_inventory: row.original.track_inventory,
                                            allow_backorder:
                                                row.original.allow_backorder,
                                            status:
                                                row.original.status === 'inactive'
                                                    ? 'inactive'
                                                    : 'active',
                                        });
                                        setEditDialogOpen(true);
                                    }}
                                >
                                    <Pencil />
                                    Edit
                                </Button>
                            ) : null}
                            {canManageProducts ? (
                                <Button
                                    size="sm"
                                    variant="destructive"
                                    disabled={row.original.has_sales}
                                    onClick={() => {
                                        setSelectedProduct(row.original);
                                        setDeleteDialogOpen(true);
                                    }}
                                >
                                    <Trash2 />
                                    Delete
                                </Button>
                            ) : null}
                        </div>
                    ),
            },
        ],
        [canManageInventory, canManageProducts],
    );

    const totalVisibleStock = products.data.reduce(
        (sum, product) => sum + (product.track_inventory ? product.stock : 0),
        0,
    );
    const activeCount = products.data.filter(
        (product) => product.status === 'active',
    ).length;
    const lowStockCount = products.data.filter(
        (product) => product.is_low_stock,
    ).length;

    const createProduct = () => {
        const loadingToast = toast.loading('Creating product...');

        router.post(
            '/pos/products',
            {
                ...productForm,
                track_inventory: productForm.track_inventory ? 1 : 0,
                allow_backorder: productForm.allow_backorder ? 1 : 0,
            },
            {
                preserveScroll: true,
                onStart: () => setIsCreating(true),
                onError: (errors) =>
                    toast.error(firstErrorMessage(errors), {
                        id: loadingToast,
                    }),
                onSuccess: () => {
                    setCreateDialogOpen(false);
                    setProductForm(initialProductForm);
                },
                onFinish: () => {
                    setIsCreating(false);
                    toast.dismiss(loadingToast);
                },
            },
        );
    };

    const adjustStock = () => {
        if (!selectedProduct?.product_variant_public_id) {
            return;
        }

        const loadingToast = toast.loading('Applying stock adjustment...');

        router.post(
            `/pos/product-variants/${selectedProduct.product_variant_public_id}/stock-adjustments`,
            adjustment,
            {
                preserveScroll: true,
                onStart: () => setIsAdjusting(true),
                onError: (errors) =>
                    toast.error(firstErrorMessage(errors), {
                        id: loadingToast,
                    }),
                onSuccess: () => {
                    setAdjustDialogOpen(false);
                    setSelectedProduct(null);
                    setAdjustment({ quantity_delta: '', notes: '' });
                },
                onFinish: () => {
                    setIsAdjusting(false);
                    toast.dismiss(loadingToast);
                },
            },
        );
    };

    const updateProduct = () => {
        if (!selectedProduct) {
            return;
        }

        const loadingToast = toast.loading('Updating product...');

        router.patch(
            `/pos/products/${selectedProduct.public_id}`,
            {
                ...editForm,
                track_inventory: editForm.track_inventory ? 1 : 0,
                allow_backorder: editForm.allow_backorder ? 1 : 0,
            },
            {
                preserveScroll: true,
                onStart: () => setIsUpdating(true),
                onError: (errors) =>
                    toast.error(firstErrorMessage(errors), {
                        id: loadingToast,
                    }),
                onSuccess: () => {
                    setEditDialogOpen(false);
                    setSelectedProduct(null);
                    setEditForm(initialProductForm);
                },
                onFinish: () => {
                    setIsUpdating(false);
                    toast.dismiss(loadingToast);
                },
            },
        );
    };

    const deleteProduct = () => {
        if (!selectedProduct) {
            return;
        }

        const loadingToast = toast.loading('Deleting product...');

        router.delete(`/pos/products/${selectedProduct.public_id}`, {
            preserveScroll: true,
            onStart: () => setIsDeleting(true),
            onError: (errors) =>
                toast.error(firstErrorMessage(errors), {
                    id: loadingToast,
                }),
            onSuccess: () => {
                setDeleteDialogOpen(false);
                setSelectedProduct(null);
            },
            onFinish: () => {
                setIsDeleting(false);
                toast.dismiss(loadingToast);
            },
        });
    };

    const submitStockOpname = () => {
        const items = products.data
            .filter((product) => product.product_variant_public_id)
            .map((product) => ({
                product_variant_public_id: product.product_variant_public_id,
                counted_quantity:
                    stockOpnameCounts[product.public_id] ?? String(product.stock),
            }));

        const loadingToast = toast.loading('Saving stock opname...');

        router.post(
            '/pos/stock-opname',
            {
                items,
                notes: stockOpnameNotes,
            },
            {
                preserveScroll: true,
                onStart: () => setIsSubmittingStockOpname(true),
                onError: (errors) =>
                    toast.error(firstErrorMessage(errors), {
                        id: loadingToast,
                    }),
                onSuccess: () => {
                    setStockOpnameDialogOpen(false);
                    setStockOpnameNotes('');
                    setStockOpnameCounts({});
                },
                onFinish: () => {
                    setIsSubmittingStockOpname(false);
                    toast.dismiss(loadingToast);
                },
            },
        );
    };

    return (
        <>
            <Head title="POS Products" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
                <Card className="relative overflow-hidden border-none bg-[linear-gradient(135deg,rgba(17,24,39,1),rgba(8,145,178,0.92),rgba(249,115,22,0.86))] text-white shadow-xl">
                    <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(255,255,255,0.16),transparent_28%),radial-gradient(circle_at_bottom_right,rgba(255,255,255,0.14),transparent_26%)]" />
                    <CardContent className="relative p-6 md:p-7">
                        <div className="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                            <div className="space-y-3">
                                <Badge className="bg-white/14 text-white hover:bg-white/14">
                                    Catalog and inventory desk
                                </Badge>
                                <div>
                                    <h1 className="text-2xl font-semibold tracking-tight md:text-3xl">
                                        Product and inventory control
                                    </h1>
                                    <p className="mt-2 max-w-2xl text-sm leading-6 text-white/76">
                                        Seluruh produk POS saat ini dibuat sebagai
                                        single default variant. Struktur ini
                                        sengaja dijaga agar nanti size, color,
                                        atau variant lain bisa masuk tanpa
                                        memecah histori transaksi.
                                    </p>
                                </div>
                            </div>

                            <div className="flex flex-wrap gap-2">
                                {canManageProducts && (
                                    <Button
                                        variant="secondary"
                                        onClick={() => setCreateDialogOpen(true)}
                                    >
                                        <PackagePlus />
                                        New product
                                    </Button>
                                )}
                                {canManageInventory && (
                                    <Button
                                        variant="secondary"
                                        onClick={() => {
                                            setStockOpnameCounts(
                                                Object.fromEntries(
                                                    products.data.map((product) => [
                                                        product.public_id,
                                                        String(product.stock),
                                                    ]),
                                                ),
                                            );
                                            setStockOpnameDialogOpen(true);
                                        }}
                                    >
                                        <ClipboardList />
                                        Batch stock opname
                                    </Button>
                                )}
                                <Button
                                    asChild
                                    variant="outline"
                                    className="border-white/20 bg-transparent text-white hover:bg-white/10 hover:text-white"
                                >
                                    <Link href="/pos">
                                        <Boxes />
                                        Open terminal
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-4 md:grid-cols-4">
                    {[
                        {
                            icon: ClipboardList,
                            label: 'Visible products',
                            value: `${products.total}`,
                        },
                        {
                            icon: Boxes,
                            label: 'Active on this page',
                            value: `${activeCount}/${products.data.length}`,
                        },
                        {
                            icon: ArrowUpDown,
                            label: 'Tracked stock visible',
                            value: `${totalVisibleStock}`,
                        },
                        {
                            icon: Siren,
                            label: 'Low stock on page',
                            value: `${lowStockCount}`,
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

                <Alert>
                    <Boxes />
                    <AlertTitle>Single-variant mode</AlertTitle>
                    <AlertDescription>
                        Produk baru masih dibuat sebagai default variant.
                        Inventory, pricing, dan historinya sudah disiapkan agar
                        nanti mudah naik ke multi-variant.
                    </AlertDescription>
                </Alert>

                <Alert>
                    <ClipboardList />
                    <AlertTitle>Product maintenance</AlertTitle>
                    <AlertDescription>
                        Produk yang sudah pernah masuk transaksi tidak boleh dihapus
                        agar histori tetap aman. Untuk kasus seperti SKU salah,
                        gunakan edit. Jika produk sudah tidak dipakai, ubah status
                        ke inactive agar hilang dari terminal.
                    </AlertDescription>
                </Alert>

                {lowStockCount ? (
                    <Alert className="border-amber-200 bg-amber-50 text-amber-950">
                        <Siren />
                        <AlertTitle>Low stock alert</AlertTitle>
                        <AlertDescription>
                            {products.data
                                .filter((product) => product.is_low_stock)
                                .map(
                                    (product) =>
                                        `${product.name} (${product.stock}/${product.low_stock_threshold})`,
                                )
                                .join(', ')}
                        </AlertDescription>
                    </Alert>
                ) : null}

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_300px]">
                    <Card>
                        <CardHeader className="flex flex-col gap-4 border-b sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <CardTitle>Catalog table</CardTitle>
                                <p className="text-sm text-muted-foreground">
                                    Search, review stock, and trigger adjustments.
                                </p>
                            </div>
                        </CardHeader>
                        <CardContent className="p-4">
                            <DataTable
                                columns={columns}
                                data={products}
                                filters={filters}
                                route="/pos/products"
                                searchPlaceholder="Search products"
                                emptyMessage="No POS products"
                                totalLabel="products"
                            />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Current scope</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4 text-sm text-muted-foreground">
                            <div className="rounded-2xl border bg-muted/40 p-4">
                                Administrator membuat produk, mengatur stok,
                                dan menyiapkan katalog untuk cashier.
                            </div>
                            <div className="rounded-2xl border bg-muted/40 p-4">
                                Flow adjustment stock menggunakan ledger,
                                bukan overwrite quantity langsung.
                            </div>
                            <div className="rounded-2xl border bg-muted/40 p-4">
                                Split role inventory manager bisa ditambah
                                nanti tanpa mengubah UI dasar terminal cashier.
                            </div>
                        </CardContent>
                    </Card>
                </div>

            </div>

            <Dialog open={createDialogOpen} onOpenChange={setCreateDialogOpen}>
                <DialogContent className="sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>Create POS product</DialogTitle>
                        <DialogDescription>
                            Produk akan dibuat bersama default variant dan siap
                            dipakai di terminal. SKU boleh dikosongkan dan akan
                            digenerate otomatis dengan format `PRD-000001`.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Input
                                value={productForm.name}
                                onChange={(event) =>
                                    setProductForm((state) => ({
                                        ...state,
                                        name: event.target.value,
                                    }))
                                }
                                placeholder="Product name"
                            />
                            <p className="text-xs text-muted-foreground">
                                Nama produk yang muncul di terminal cashier dan laporan.
                            </p>
                        </div>
                        <div className="space-y-2">
                            <Input
                                value={productForm.sku}
                                onChange={(event) =>
                                    setProductForm((state) => ({
                                        ...state,
                                        sku: event.target.value,
                                    }))
                                }
                                placeholder="SKU, optional"
                            />
                            <p className="text-xs text-muted-foreground">
                                Kosongkan jika ingin sistem generate SKU otomatis
                                dengan format `PRD-000001`.
                            </p>
                        </div>
                        <div className="space-y-2">
                            <Input
                                type="number"
                                min="0.01"
                                step="0.01"
                                value={productForm.price}
                                onChange={(event) =>
                                    setProductForm((state) => ({
                                        ...state,
                                        price: event.target.value,
                                    }))
                                }
                                placeholder="Selling price"
                            />
                            <p className="text-xs text-muted-foreground">
                                Harga jual ke customer.
                            </p>
                        </div>
                        <div className="space-y-2">
                            <Input
                                type="number"
                                min="0"
                                step="0.01"
                                value={productForm.cost_price}
                                onChange={(event) =>
                                    setProductForm((state) => ({
                                        ...state,
                                        cost_price: event.target.value,
                                    }))
                                }
                                placeholder="Cost price / HPP"
                            />
                            <p className="text-xs text-muted-foreground">
                                HPP atau modal per unit. Dipakai untuk histori dan analisis margin.
                            </p>
                        </div>
                        <div className="space-y-2">
                            <Input
                                type="number"
                                min="0"
                                step="0.001"
                                value={productForm.initial_quantity}
                                onChange={(event) =>
                                    setProductForm((state) => ({
                                        ...state,
                                        initial_quantity: event.target.value,
                                    }))
                                }
                                placeholder="Initial stock"
                            />
                            <p className="text-xs text-muted-foreground">
                                Stok awal saat produk dibuat. Bisa `0` jika stok diisi nanti.
                            </p>
                        </div>
                        <div className="space-y-2">
                            <Input
                                type="number"
                                min="0"
                                step="0.001"
                                value={productForm.low_stock_threshold}
                                onChange={(event) =>
                                    setProductForm((state) => ({
                                        ...state,
                                        low_stock_threshold: event.target.value,
                                    }))
                                }
                                placeholder="Low stock threshold"
                            />
                            <p className="text-xs text-muted-foreground">
                                Alert akan aktif jika stok {'<='} threshold ini. Isi `0` untuk nonaktif.
                            </p>
                        </div>
                        <div className="grid gap-3 rounded-xl border p-4">
                            <label className="flex items-center gap-3 text-sm font-medium">
                                <Checkbox
                                    checked={productForm.track_inventory}
                                    onCheckedChange={(checked) =>
                                        setProductForm((state) => ({
                                            ...state,
                                            track_inventory: Boolean(checked),
                                        }))
                                    }
                                />
                                Track inventory
                            </label>
                            <p className="text-xs text-muted-foreground">
                                Aktifkan jika stok produk harus dihitung dan berkurang saat sale.
                                Nonaktifkan untuk jasa atau item yang tidak perlu stok.
                            </p>
                            <label className="flex items-center gap-3 text-sm font-medium">
                                <Checkbox
                                    checked={productForm.allow_backorder}
                                    onCheckedChange={(checked) =>
                                        setProductForm((state) => ({
                                            ...state,
                                            allow_backorder: Boolean(checked),
                                        }))
                                    }
                                />
                                Allow backorder
                            </label>
                            <p className="text-xs text-muted-foreground">
                                Jika aktif, transaksi tetap boleh jalan meski stok minus.
                                Biasanya untuk pre-order atau barang menyusul.
                            </p>
                        </div>
                        <div className="md:col-span-2">
                            <div className="space-y-2">
                                <Textarea
                                    value={productForm.description}
                                    onChange={(event) =>
                                        setProductForm((state) => ({
                                            ...state,
                                            description: event.target.value,
                                        }))
                                    }
                                    placeholder="Description, optional"
                                />
                                <p className="text-xs text-muted-foreground">
                                    Isi dengan catatan internal singkat seperti ukuran, merek, isi paket,
                                    atau pembeda produk. Boleh dikosongkan jika nama produk sudah cukup jelas.
                                </p>
                            </div>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setCreateDialogOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            onClick={createProduct}
                            disabled={!productForm.name || !productForm.price || isCreating}
                        >
                            {isCreating ? (
                                <Loader2 className="animate-spin" />
                            ) : (
                                <PackagePlus />
                            )}
                            Create product
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={editDialogOpen} onOpenChange={setEditDialogOpen}>
                <DialogContent className="sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>Edit POS product</DialogTitle>
                        <DialogDescription>
                            Perubahan nama, SKU, harga, dan status tidak akan
                            mengubah snapshot histori item yang sudah terjual.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Input
                                value={editForm.name}
                                onChange={(event) =>
                                    setEditForm((state) => ({
                                        ...state,
                                        name: event.target.value,
                                    }))
                                }
                                placeholder="Product name"
                            />
                        </div>
                        <div className="space-y-2">
                            <Input
                                value={editForm.sku}
                                onChange={(event) =>
                                    setEditForm((state) => ({
                                        ...state,
                                        sku: event.target.value,
                                    }))
                                }
                                placeholder="SKU"
                            />
                        </div>
                        <div className="space-y-2">
                            <Input
                                type="number"
                                min="0.01"
                                step="0.01"
                                value={editForm.price}
                                onChange={(event) =>
                                    setEditForm((state) => ({
                                        ...state,
                                        price: event.target.value,
                                    }))
                                }
                                placeholder="Selling price"
                            />
                        </div>
                        <div className="space-y-2">
                            <Input
                                type="number"
                                min="0"
                                step="0.01"
                                value={editForm.cost_price}
                                onChange={(event) =>
                                    setEditForm((state) => ({
                                        ...state,
                                        cost_price: event.target.value,
                                    }))
                                }
                                placeholder="Cost price / HPP"
                            />
                        </div>
                        <div className="space-y-2">
                            <Input
                                type="number"
                                min="0"
                                step="0.001"
                                value={editForm.low_stock_threshold}
                                onChange={(event) =>
                                    setEditForm((state) => ({
                                        ...state,
                                        low_stock_threshold: event.target.value,
                                    }))
                                }
                                placeholder="Low stock threshold"
                            />
                        </div>
                        <div className="space-y-2">
                            <Select
                                value={editForm.status}
                                onValueChange={(value: 'active' | 'inactive') =>
                                    setEditForm((state) => ({
                                        ...state,
                                        status: value,
                                    }))
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="active">Active</SelectItem>
                                    <SelectItem value="inactive">
                                        Inactive
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p className="text-xs text-muted-foreground">
                                Inactive akan menyembunyikan produk dari terminal POS.
                            </p>
                        </div>
                        <div className="grid gap-3 rounded-xl border p-4">
                            <label className="flex items-center gap-3 text-sm font-medium">
                                <Checkbox
                                    checked={editForm.track_inventory}
                                    onCheckedChange={(checked) =>
                                        setEditForm((state) => ({
                                            ...state,
                                            track_inventory: Boolean(checked),
                                        }))
                                    }
                                />
                                Track inventory
                            </label>
                            <label className="flex items-center gap-3 text-sm font-medium">
                                <Checkbox
                                    checked={editForm.allow_backorder}
                                    onCheckedChange={(checked) =>
                                        setEditForm((state) => ({
                                            ...state,
                                            allow_backorder: Boolean(checked),
                                        }))
                                    }
                                />
                                Allow backorder
                            </label>
                        </div>
                        <div className="md:col-span-2">
                            <Textarea
                                value={editForm.description}
                                onChange={(event) =>
                                    setEditForm((state) => ({
                                        ...state,
                                        description: event.target.value,
                                    }))
                                }
                                placeholder="Description"
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setEditDialogOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            onClick={updateProduct}
                            disabled={!editForm.name || !editForm.price || isUpdating}
                        >
                            {isUpdating ? (
                                <Loader2 className="animate-spin" />
                            ) : (
                                <Pencil />
                            )}
                            Save changes
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete product</DialogTitle>
                        <DialogDescription>
                            Produk hanya bisa dihapus jika belum pernah dipakai
                            di transaksi.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="rounded-xl border bg-muted/40 p-4 text-sm">
                        {selectedProduct ? (
                            <>
                                <div className="font-medium">
                                    {selectedProduct.name}
                                </div>
                                <div className="mt-1 text-muted-foreground">
                                    SKU: {selectedProduct.sku ?? '-'}
                                </div>
                            </>
                        ) : null}
                    </div>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setDeleteDialogOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={deleteProduct}
                            disabled={isDeleting}
                        >
                            {isDeleting ? (
                                <Loader2 className="animate-spin" />
                            ) : (
                                <Trash2 />
                            )}
                            Delete product
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={adjustDialogOpen} onOpenChange={setAdjustDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Adjust stock</DialogTitle>
                        <DialogDescription>
                            {selectedProduct
                                ? `Apply stock movement for ${selectedProduct.name}.`
                                : 'Select a product to adjust.'}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        {selectedProduct && (
                            <div className="rounded-xl border bg-muted/40 p-4 text-sm">
                                <div className="font-medium">
                                    {selectedProduct.name}
                                </div>
                                <div className="mt-1 text-muted-foreground">
                                    Current stock: {selectedProduct.stock}
                                </div>
                            </div>
                        )}
                        <Input
                            type="number"
                            step="0.001"
                            value={adjustment.quantity_delta}
                            onChange={(event) =>
                                setAdjustment((state) => ({
                                    ...state,
                                    quantity_delta: event.target.value,
                                }))
                            }
                            placeholder="+/- quantity"
                        />
                        <Textarea
                            value={adjustment.notes}
                            onChange={(event) =>
                                setAdjustment((state) => ({
                                    ...state,
                                    notes: event.target.value,
                                }))
                            }
                            placeholder="Reason for adjustment"
                        />
                    </div>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setAdjustDialogOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            onClick={adjustStock}
                            disabled={!adjustment.quantity_delta || isAdjusting}
                        >
                            {isAdjusting ? (
                                <Loader2 className="animate-spin" />
                            ) : (
                                <ArrowUpDown />
                            )}
                            Apply adjustment
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog
                open={stockOpnameDialogOpen}
                onOpenChange={setStockOpnameDialogOpen}
            >
                <DialogContent className="sm:max-w-3xl">
                    <DialogHeader>
                        <DialogTitle>Batch stock opname</DialogTitle>
                        <DialogDescription>
                            Masukkan jumlah fisik aktual untuk produk pada halaman ini.
                            Sistem akan membuat movement hanya untuk item yang berubah.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div className="max-h-[420px] space-y-3 overflow-y-auto pr-1">
                            {products.data
                                .filter(
                                    (product) =>
                                        product.track_inventory &&
                                        product.product_variant_public_id,
                                )
                                .map((product) => (
                                    <div
                                        key={product.public_id}
                                        className="grid gap-3 rounded-xl border p-4 md:grid-cols-[minmax(0,1fr)_180px]"
                                    >
                                        <div>
                                            <div className="font-medium">
                                                {product.name}
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                {product.sku ?? 'No SKU'} • Current {product.stock}
                                            </div>
                                        </div>
                                        <Input
                                            type="number"
                                            min="0"
                                            step="0.001"
                                            value={
                                                stockOpnameCounts[product.public_id] ??
                                                String(product.stock)
                                            }
                                            onChange={(event) =>
                                                setStockOpnameCounts((state) => ({
                                                    ...state,
                                                    [product.public_id]:
                                                        event.target.value,
                                                }))
                                            }
                                            placeholder="Counted quantity"
                                        />
                                    </div>
                                ))}
                        </div>
                        <Textarea
                            value={stockOpnameNotes}
                            onChange={(event) =>
                                setStockOpnameNotes(event.target.value)
                            }
                            placeholder="Catatan stock opname, optional"
                        />
                    </div>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setStockOpnameDialogOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            onClick={submitStockOpname}
                            disabled={isSubmittingStockOpname}
                        >
                            {isSubmittingStockOpname ? (
                                <Loader2 className="animate-spin" />
                            ) : (
                                <ClipboardList />
                            )}
                            Save stock opname
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

PosProducts.layout = {
    breadcrumbs: [
        {
            title: 'POS Products',
            href: '/pos/products',
        },
    ],
};
