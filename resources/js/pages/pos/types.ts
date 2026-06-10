import type { Paginated } from '@/types/pagination';

export type { Paginated };

export type PosProduct = {
    id: number;
    name: string;
    sku: string | null;
    product_variant_id: number | null;
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
    id: number;
    opening_cash: number;
    opened_at: string | null;
};

export type PosSaleListItem = {
    id: number;
    invoice_number: string;
    cashier: string | null;
    status: string;
    payment_status: string;
    payment_method: string | null;
    total: number;
    created_at: string | null;
};

export type RecentSale = {
    id: number;
    invoice_number: string;
    total: number;
    payment_method: string | null;
    created_at: string | null;
};
