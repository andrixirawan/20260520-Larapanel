import { create } from 'zustand';
import type { PosProduct } from './types';

export type PosCartItem = PosProduct & {
    quantity: number;
};

type PosTerminalStore = {
    cart: PosCartItem[];
    paymentMethod: string;
    receivedAmount: string;
    cartSheetOpen: boolean;
    checkoutOpen: boolean;
    closeShiftOpen: boolean;
    setPaymentMethod: (value: string) => void;
    setReceivedAmount: (value: string) => void;
    setCartSheetOpen: (value: boolean) => void;
    setCheckoutOpen: (value: boolean) => void;
    setCloseShiftOpen: (value: boolean) => void;
    addToCart: (product: PosProduct) => void;
    updateQuantity: (productPublicId: string, delta: number) => void;
    clearCart: () => void;
    resetPayment: () => void;
};

export const usePosTerminalStore = create<PosTerminalStore>((set) => ({
    cart: [],
    paymentMethod: 'cash',
    receivedAmount: '',
    cartSheetOpen: false,
    checkoutOpen: false,
    closeShiftOpen: false,
    setPaymentMethod: (value) => set({ paymentMethod: value }),
    setReceivedAmount: (value) => set({ receivedAmount: value }),
    setCartSheetOpen: (value) => set({ cartSheetOpen: value }),
    setCheckoutOpen: (value) => set({ checkoutOpen: value }),
    setCloseShiftOpen: (value) => set({ closeShiftOpen: value }),
    addToCart: (product) =>
        set((state) => {
            if (!product.product_variant_public_id) {
                return state;
            }

            const existing = state.cart.find(
                (item) => item.public_id === product.public_id,
            );

            if (existing) {
                return {
                    cart: state.cart.map((item) =>
                        item.public_id === product.public_id
                            ? { ...item, quantity: item.quantity + 1 }
                            : item,
                    ),
                };
            }

            return {
                cart: [...state.cart, { ...product, quantity: 1 }],
            };
        }),
    updateQuantity: (productPublicId, delta) =>
        set((state) => ({
            cart: state.cart
                .map((item) =>
                    item.public_id === productPublicId
                        ? { ...item, quantity: item.quantity + delta }
                        : item,
                )
                .filter((item) => item.quantity > 0),
        })),
    clearCart: () => set({ cart: [] }),
    resetPayment: () => set({ paymentMethod: 'cash', receivedAmount: '' }),
}));
