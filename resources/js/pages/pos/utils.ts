import {
    APP_CURRENCY,
    APP_LOCALE,
    DATE_TIME_FORMAT_OPTIONS,
} from '@/constants/app';

export const posCurrency = new Intl.NumberFormat(APP_LOCALE, {
    style: 'currency',
    currency: APP_CURRENCY,
    maximumFractionDigits: 0,
});

export function formatPosDateTime(value: string | null | undefined): string {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleString(APP_LOCALE, DATE_TIME_FORMAT_OPTIONS);
}

export function firstErrorMessage(
    errors: Record<string, string | string[]>,
): string {
    const entry = Object.values(errors)[0];

    if (Array.isArray(entry)) {
        return entry[0] ?? 'Request failed.';
    }

    return entry ?? 'Request failed.';
}
