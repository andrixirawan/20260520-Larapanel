export const APP_LOCALE = 'id-ID';

export const APP_CURRENCY = 'IDR';

export const APP_TIME_ZONE = 'Asia/Jakarta';

export const TABLE_PER_PAGE_OPTIONS = [5, 10, 15, 25, 50] as const;

export const DEFAULT_TABLE_PER_PAGE = 10;

export const DATE_TIME_FORMAT_OPTIONS: Intl.DateTimeFormatOptions = {
    dateStyle: 'medium',
    timeStyle: 'short',
    timeZone: APP_TIME_ZONE,
};
