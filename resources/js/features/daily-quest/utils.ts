import { addDays, format, parseISO } from 'date-fns';
import type {
    DailyQuestTask,
    RecurrenceType,
    TaskCategory,
    TaskStatusTab,
} from '@/features/daily-quest/types';

export const recurrenceDayOptions = [
    { value: 'Mon', shortLabel: 'S', label: 'Senin' },
    { value: 'Tue', shortLabel: 'S', label: 'Selasa' },
    { value: 'Wed', shortLabel: 'R', label: 'Rabu' },
    { value: 'Thu', shortLabel: 'K', label: 'Kamis' },
    { value: 'Fri', shortLabel: 'J', label: 'Jumat' },
    { value: 'Sat', shortLabel: 'S', label: 'Sabtu' },
    { value: 'Sun', shortLabel: 'M', label: 'Minggu' },
] as const;

export const taskStatusMeta: Record<
    TaskStatusTab,
    { label: string; description: string }
> = {
    active: {
        label: 'Aktif',
        description: 'Task yang masih dijalankan dan akan terus digenerate.',
    },
    paused: {
        label: 'Dijeda',
        description: 'Task berhenti digenerate sampai diaktifkan lagi.',
    },
    archived: {
        label: 'Arsip',
        description: 'Task yang sudah dihapus secara soft delete.',
    },
};

export const taskColors = [
    '#f59e0b',
    '#ef4444',
    '#ec4899',
    '#8b5cf6',
    '#3b82f6',
    '#06b6d4',
    '#10b981',
    '#84cc16',
    '#f97316',
    '#64748b',
] as const;

export const taskEmojiGroups = [
    ['🔥', '✨', '🎯', '🚀', '🏃', '💧', '📚', '🧘', '💪', '🧠'],
    ['🥗', '🍎', '☕', '💼', '📝', '🎧', '🛌', '🧹', '🎨', '🎸'],
    ['🌿', '🧾', '📈', '🏆', '🎮', '🧪', '🧼', '🪴', '🧩', '🚴'],
] as const;

export function toDateLabel(date: string | null): string {
    if (! date) {
        return '-';
    }

    return format(parseISO(date), 'dd MMM yyyy');
}

export function buildTaskRecurrenceSummary(values: {
    recurrence_type: RecurrenceType;
    recurrence_days: string[];
    recurrence_starts_at: string;
    recurrence_ends_at: string;
    x_days_span: string;
}): string {
    switch (values.recurrence_type) {
        case 'daily':
            return 'Daily';
        case 'specific_days':
            return values.recurrence_days.length > 0
                ? values.recurrence_days.join(', ')
                : 'Pilih hari';
        case 'one_time':
            return values.recurrence_starts_at
                ? `One time: ${values.recurrence_starts_at}`
                : 'One time';
        case 'x_days': {
            const startDate = values.recurrence_starts_at || todayDate();

            if (! values.x_days_span) {
                return `Mulai ${startDate}`;
            }

            const endDate = format(
                addDays(parseISO(startDate), Math.max(Number(values.x_days_span) - 1, 0)),
                'yyyy-MM-dd',
            );

            return `${startDate} until ${endDate}`;
        }
        case 'date_range':
            return values.recurrence_starts_at && values.recurrence_ends_at
                ? `${values.recurrence_starts_at} to ${values.recurrence_ends_at}`
                : 'Tentukan rentang tanggal';
        default:
            return '-';
    }
}

export function todayDate(): string {
    return format(new Date(), 'yyyy-MM-dd');
}

export function buildTaskRedirectPath(status: TaskStatusTab, search = ''): string {
    const params = new URLSearchParams({ status });

    if (search.trim() !== '') {
        params.set('search', search);
    }

    return `/tasks?${params.toString()}`;
}

export function inferTaskStatus(task: DailyQuestTask): TaskStatusTab {
    if (task.deleted_at) {
        return 'archived';
    }

    return task.is_active ? 'active' : 'paused';
}

export function categoryLabel(category: TaskCategory | null): string {
    if (! category) {
        return 'Tanpa kategori';
    }

    return `${category.icon ? `${category.icon} ` : ''}${category.name}`;
}
