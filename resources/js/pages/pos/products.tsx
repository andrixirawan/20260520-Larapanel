import { Form, Head, Link, usePage } from '@inertiajs/react';
import { Boxes, Plus, Search } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { Auth } from '@/types';
import type { Paginated, PosProductRow } from './types';

const currency = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

export default function PosProducts({
    products,
    filters,
}: {
    products: Paginated<PosProductRow>;
    filters: { search: string };
}) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const canManageProducts = auth.permissions['pos.products.manage'];
    const canManageInventory = auth.permissions['pos.inventory.manage'];

    return (
        <>
            <Head title="POS Products" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
                <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <Heading
                        title="POS Products"
                        description="Manage saleable products and inventory stock for POS."
                    />

                    <Button asChild variant="outline" className="w-fit">
                        <Link href="/pos">
                            <Boxes />
                            Open terminal
                        </Link>
                    </Button>
                </div>

                {canManageProducts && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Plus className="size-5" />
                                New product
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Form
                                action="/pos/products"
                                method="post"
                                className="grid gap-3 lg:grid-cols-6"
                            >
                                {({ processing }) => (
                                    <>
                                        <Input
                                            name="name"
                                            placeholder="Product name"
                                            className="lg:col-span-2"
                                            required
                                        />
                                        <Input name="sku" placeholder="SKU" />
                                        <Input
                                            type="number"
                                            name="price"
                                            min="0.01"
                                            step="0.01"
                                            placeholder="Price"
                                            required
                                        />
                                        <Input
                                            type="number"
                                            name="cost_price"
                                            min="0"
                                            step="0.01"
                                            placeholder="Cost"
                                        />
                                        <Input
                                            type="number"
                                            name="initial_quantity"
                                            min="0"
                                            step="0.001"
                                            placeholder="Initial stock"
                                        />
                                        <label className="flex items-center gap-2 rounded-md border px-3 py-2 text-sm">
                                            <input
                                                type="checkbox"
                                                name="track_inventory"
                                                value="1"
                                                defaultChecked
                                            />
                                            Track stock
                                        </label>
                                        <label className="flex items-center gap-2 rounded-md border px-3 py-2 text-sm">
                                            <input
                                                type="checkbox"
                                                name="allow_backorder"
                                                value="1"
                                            />
                                            Backorder
                                        </label>
                                        <Input
                                            name="description"
                                            placeholder="Description"
                                            className="lg:col-span-3"
                                        />
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="lg:col-span-1"
                                        >
                                            Create
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                )}

                <form
                    action="/pos/products"
                    method="get"
                    className="grid gap-3 rounded-lg border bg-card p-4 sm:grid-cols-[1fr_auto]"
                >
                    <div className="relative">
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            name="search"
                            defaultValue={filters.search}
                            placeholder="Search products, SKU, or barcode"
                            className="pl-9"
                        />
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit">Search</Button>
                        {filters.search && (
                            <Button asChild variant="ghost">
                                <Link href="/pos/products">Reset</Link>
                            </Button>
                        )}
                    </div>
                </form>

                <div className="overflow-hidden rounded-lg border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Product</TableHead>
                                <TableHead>Price</TableHead>
                                <TableHead>Stock</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="w-[320px]">
                                    Inventory adjustment
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {products.data.length ? (
                                products.data.map((product) => (
                                    <TableRow key={product.id}>
                                        <TableCell>
                                            <div className="font-medium">
                                                {product.name}
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                {product.sku ?? 'No SKU'}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {currency.format(product.price)}
                                        </TableCell>
                                        <TableCell>
                                            {product.track_inventory ? (
                                                <Badge
                                                    variant={
                                                        product.stock <= 0
                                                            ? 'destructive'
                                                            : 'outline'
                                                    }
                                                >
                                                    {product.stock}
                                                </Badge>
                                            ) : (
                                                <Badge variant="secondary">
                                                    Not tracked
                                                </Badge>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    product.status === 'active'
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {product.status}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {canManageInventory &&
                                            product.product_variant_id ? (
                                                <Form
                                                    action={`/pos/product-variants/${product.product_variant_id}/stock-adjustments`}
                                                    method="post"
                                                    className="grid gap-2 sm:grid-cols-[110px_1fr_auto]"
                                                >
                                                    {({ processing }) => (
                                                        <>
                                                            <Input
                                                                type="number"
                                                                name="quantity_delta"
                                                                step="0.001"
                                                                placeholder="+/- qty"
                                                                required
                                                            />
                                                            <Input
                                                                name="notes"
                                                                placeholder="Reason"
                                                            />
                                                            <Button
                                                                type="submit"
                                                                size="sm"
                                                                variant="outline"
                                                                disabled={
                                                                    processing
                                                                }
                                                            >
                                                                Apply
                                                            </Button>
                                                        </>
                                                    )}
                                                </Form>
                                            ) : (
                                                <span className="text-sm text-muted-foreground">
                                                    No access
                                                </span>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
                                        className="h-32 text-center text-muted-foreground"
                                    >
                                        No POS products yet.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                <div className="flex flex-col gap-3 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                    <span>
                        Showing {products.from ?? 0} to {products.to ?? 0} of{' '}
                        {products.total} products
                    </span>
                    <div className="flex gap-2">
                        <Button
                            asChild={Boolean(products.prev_page_url)}
                            variant="outline"
                            size="sm"
                            disabled={!products.prev_page_url}
                        >
                            {products.prev_page_url ? (
                                <Link href={products.prev_page_url}>
                                    Previous
                                </Link>
                            ) : (
                                <span>Previous</span>
                            )}
                        </Button>
                        <Button
                            asChild={Boolean(products.next_page_url)}
                            variant="outline"
                            size="sm"
                            disabled={!products.next_page_url}
                        >
                            {products.next_page_url ? (
                                <Link href={products.next_page_url}>Next</Link>
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

PosProducts.layout = {
    breadcrumbs: [
        {
            title: 'POS Products',
            href: '/pos/products',
        },
    ],
};
