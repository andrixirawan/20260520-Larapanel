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
    low_stock_threshold: number;
    is_low_stock: boolean;
};

export type PosProductRow = PosProduct & {
    status: string;
    cost_price: number | null;
    allow_backorder: boolean;
    low_stock_threshold: number;
    is_low_stock: boolean;
    description: string | null;
    has_sales: boolean;
    created_at: string | null;
};

export type PosShift = {
    public_id: string;
    opening_cash: number;
    cash_sales_total: number;
    drawer_cash_in_total: number;
    drawer_cash_out_total: number;
    net_cash_movement_total: number;
    expected_cash: number;
    opened_at: string | null;
    handover_to_cashier?: string | null;
};

export type PosOpeningGuide = {
    recommended_opening_cash: number;
    source_shift_public_id: string;
    source_closed_at: string | null;
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

export type PosCashierOption = {
    public_id: string;
    name: string;
};
