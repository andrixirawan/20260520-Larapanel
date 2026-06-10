import type { Paginated } from '@/types/pagination';

export type { Paginated };

export type PosProduct = {
    public_id: string;
    name: string;
    sku: string | null;
    product_variant_public_id: string | null;
    price: number;
    track_inventory: boolean;
    stock: number;
};

export type PosProductRow = PosProduct & {
    status: string;
    cost_price: number | null;
    allow_backorder: boolean;
    created_at: string | null;
};

export type PosShift = {
    public_id: string;
    opening_cash: number;
    opened_at: string | null;
};

export type PosSaleListItem = {
    public_id: string;
    invoice_number: string;
    cashier: string | null;
    status: string;
    payment_status: string;
    payment_method: string | null;
    total: number;
    created_at: string | null;
};

export type RecentSale = {
    public_id: string;
    invoice_number: string;
    total: number;
    payment_method: string | null;
    created_at: string | null;
};
