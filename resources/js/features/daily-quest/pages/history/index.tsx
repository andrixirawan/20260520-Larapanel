import { Head, router } from '@inertiajs/react';
import {
    CalendarRange,
    CheckCircle2,
    ChevronRight,
    Filter,
    Sparkles,
} from 'lucide-react';
import { useMemo, useRef, useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Popover,
    PopoverContent,
    PopoverDescription,
    PopoverHeader,
    PopoverTitle,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Progress } from '@/components/ui/progress';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import CalendarHeatmap from '@/features/daily-quest/components/calendar-heatmap';
import DatePicker from '@/features/daily-quest/components/date-picker';
import type {
    DailyQuestTask,
    HistoryDay,
    HistoryFilters,
    TaskCategory,
    TaskInstance,
} from '@/features/daily-quest/types';
import {
    dailyQuestId,
    formatHistoryDayLabel,
    historyDaySummaries,
    historyMonthOptions,
    toDateLabel,
} from '@/features/daily-quest/utils';

type HistoryIndexProps = {
    days: HistoryDay[];
    filters: HistoryFilters;
    tasks: DailyQuestTask[];
    categories: TaskCategory[];
};

type HistoryFilterDraft = {
    task_id: string;
    category_id: string;
    from: string;
    to: string;
};

function normalizeFilters(filters: HistoryFilters): HistoryFilterDraft {
    return {
        task_id: filters.task_id ?? '',
        category_id: filters.category_id ?? '',
        from: filters.from ?? '',
        to: filters.to ?? '',
    };
}

function HistoryFiltersPopover({
    filters,
    tasks,
    categories,
}: {
    filters: HistoryFilters;
    tasks: DailyQuestTask[];
    categories: TaskCategory[];
}) {
    const [draftFilters, setDraftFilters] = useState<HistoryFilterDraft>(() =>
        normalizeFilters(filters),
    );
    const activeFilterCount = Object.values(draftFilters).filter(
        (value) => value.trim() !== '',
    ).length;

    const applyFilters = () => {
        router.get(
            '/history',
            {
                task_id: draftFilters.task_id || undefined,
                category_id: draftFilters.category_id || undefined,
                from: draftFilters.from || undefined,
                to: draftFilters.to || undefined,
            },
            {
                preserveScroll: true,
                preserveState: true,
            },
        );
    };

    const resetFilters = () => {
        const nextFilters = normalizeFilters({});
        setDraftFilters(nextFilters);
        router.get(
            '/history',
            {},
            { preserveScroll: true, preserveState: true },
        );
    };

    return (
        <Popover>
            <PopoverTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    className="rounded-full"
                >
                    <Filter className="size-4" />
                    Filter
                    {activeFilterCount > 0 ? (
                        <Badge variant="secondary" className="rounded-full">
                            {activeFilterCount}
                        </Badge>
                    ) : null}
                </Button>
            </PopoverTrigger>
            <PopoverContent align="end" className="w-[22rem] space-y-4">
                <PopoverHeader>
                    <PopoverTitle>Filter history</PopoverTitle>
                    <PopoverDescription>
                        Batasi data berdasarkan task, kategori, atau rentang
                        tanggal.
                    </PopoverDescription>
                </PopoverHeader>

                <div className="space-y-4">
                    <div className="space-y-2">
                        <p className="text-sm font-medium">Task</p>
                        <Select
                            value={draftFilters.task_id || 'all'}
                            onValueChange={(value) =>
                                setDraftFilters((current) => ({
                                    ...current,
                                    task_id: value === 'all' ? '' : value,
                                }))
                            }
                        >
                            <SelectTrigger className="w-full rounded-2xl">
                                <SelectValue placeholder="Semua task" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua task</SelectItem>
                                {tasks.map((task) => (
                                    <SelectItem
                                        key={dailyQuestId(task)}
                                        value={dailyQuestId(task)}
                                    >
                                        {task.icon ? `${task.icon} ` : ''}
                                        {task.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-2">
                        <p className="text-sm font-medium">Kategori</p>
                        <Select
                            value={draftFilters.category_id || 'all'}
                            onValueChange={(value) =>
                                setDraftFilters((current) => ({
                                    ...current,
                                    category_id: value === 'all' ? '' : value,
                                }))
                            }
                        >
                            <SelectTrigger className="w-full rounded-2xl">
                                <SelectValue placeholder="Semua kategori" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    Semua kategori
                                </SelectItem>
                                {categories.map((category) => (
                                    <SelectItem
                                        key={dailyQuestId(category)}
                                        value={dailyQuestId(category)}
                                    >
                                        {category.icon
                                            ? `${category.icon} `
                                            : ''}
                                        {category.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="grid gap-3 sm:grid-cols-2">
                        <div className="space-y-2">
                            <p className="text-sm font-medium">Dari</p>
                            <DatePicker
                                value={draftFilters.from}
                                onChange={(value) =>
                                    setDraftFilters((current) => ({
                                        ...current,
                                        from: value,
                                    }))
                                }
                                placeholder="Pilih tanggal"
                            />
                        </div>
                        <div className="space-y-2">
                            <p className="text-sm font-medium">Sampai</p>
                            <DatePicker
                                value={draftFilters.to}
                                onChange={(value) =>
                                    setDraftFilters((current) => ({
                                        ...current,
                                        to: value,
                                    }))
                                }
                                placeholder="Pilih tanggal"
                            />
                        </div>
                    </div>

                    <div className="flex gap-2">
                        <Button
                            type="button"
                            className="flex-1 rounded-full"
                            onClick={applyFilters}
                        >
                            Terapkan
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            className="rounded-full"
                            onClick={resetFilters}
                        >
                            Reset
                        </Button>
                    </div>
                </div>
            </PopoverContent>
        </Popover>
    );
}

function HistoryInstanceRow({ instance }: { instance: TaskInstance }) {
    const task = instance.task;
    const completed = instance.completed_at !== null;

    return (
        <div className="flex items-start gap-3 rounded-2xl border bg-background/80 p-3">
            <div
                className="flex size-11 shrink-0 items-center justify-center rounded-2xl text-lg text-white shadow-sm"
                style={{
                    backgroundColor:
                        task?.color ?? task?.category?.color ?? '#0f172a',
                }}
            >
                {task?.icon ?? task?.category?.icon ?? '📝'}
            </div>

            <div className="min-w-0 flex-1 space-y-1">
                <div className="flex flex-wrap items-center gap-2">
                    <p className="text-sm font-semibold">
                        {task?.name ?? 'Deleted task'}
                    </p>
                    <Badge
                        variant={completed ? 'secondary' : 'outline'}
                        className="rounded-full"
                    >
                        {completed ? 'Selesai' : 'Belum selesai'}
                    </Badge>
                    {task?.deleted_at ? (
                        <Badge variant="outline" className="rounded-full">
                            dihapus
                        </Badge>
                    ) : null}
                </div>

                <p className="text-sm text-muted-foreground">
                    {task?.category
                        ? `${task.category.icon ? `${task.category.icon} ` : ''}${task.category.name}`
                        : (task?.recurrence_summary ?? 'Task')}
                </p>

                {instance.notes ? (
                    <p className="text-xs text-muted-foreground">
                        {instance.notes}
                    </p>
                ) : null}
            </div>

            <Badge
                className="rounded-full"
                variant={completed ? 'default' : 'outline'}
            >
                <Sparkles className="size-3" />
                {instance.points_awarded ?? task?.points ?? 0}
            </Badge>
        </div>
    );
}

export default function DailyQuestHistoryIndex({
    days,
    filters,
    tasks,
    categories,
}: HistoryIndexProps) {
    const sectionRefs = useRef<Record<string, HTMLDivElement | null>>({});
    const [selectedDate, setSelectedDate] = useState<string | null>(
        days[0]?.summary.date ?? null,
    );
    const summaries = useMemo(() => historyDaySummaries(days), [days]);
    const monthOptions = useMemo(() => historyMonthOptions(days), [days]);
    const [selectedMonth, setSelectedMonth] = useState<string>(
        monthOptions[0] ?? new Date().toISOString().slice(0, 7),
    );
    const normalizedFilters = useMemo(
        () => normalizeFilters(filters),
        [filters],
    );
    const effectiveSelectedMonth = monthOptions.includes(selectedMonth)
        ? selectedMonth
        : (monthOptions[0] ?? new Date().toISOString().slice(0, 7));
    const effectiveSelectedDate =
        selectedDate && selectedDate.slice(0, 7) === effectiveSelectedMonth
            ? selectedDate
            : null;
    const overallStats = useMemo(() => {
        const totalTasks = summaries.reduce(
            (sum, item) => sum + item.total_tasks,
            0,
        );
        const completedTasks = summaries.reduce(
            (sum, item) => sum + item.completed_tasks,
            0,
        );
        const points = summaries.reduce(
            (sum, item) => sum + item.points_earned,
            0,
        );

        return {
            days: summaries.length,
            totalTasks,
            completedTasks,
            points,
            completionRate:
                totalTasks > 0
                    ? Math.round((completedTasks / totalTasks) * 100)
                    : 0,
        };
    }, [summaries]);

    const scrollToDate = (date: string) => {
        setSelectedDate(date);
        const target = sectionRefs.current[date];
        target?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    return (
        <>
            <Head title="History" />

            <div className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 px-4 py-5 sm:px-6">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <Heading
                        title="History"
                        description="Lihat ritme harian, buka titik lemah, dan telusuri task yang selesai maupun yang terlewat."
                    />

                    <HistoryFiltersPopover
                        key={JSON.stringify(normalizedFilters)}
                        filters={filters}
                        tasks={tasks}
                        categories={categories}
                    />
                </div>

                <Card className="overflow-hidden border-0 bg-[linear-gradient(135deg,#0f172a_0%,#1e293b_45%,#0ea5e9_100%)] py-0 text-white shadow-xl">
                    <CardContent className="grid gap-4 px-5 py-5 sm:grid-cols-4 sm:px-6">
                        <div className="space-y-1">
                            <p className="text-xs tracking-[0.18em] text-white/65 uppercase">
                                Hari tercatat
                            </p>
                            <p className="text-3xl font-semibold">
                                {overallStats.days}
                            </p>
                        </div>
                        <div className="space-y-1">
                            <p className="text-xs tracking-[0.18em] text-white/65 uppercase">
                                Completion
                            </p>
                            <p className="text-3xl font-semibold">
                                {overallStats.completionRate}%
                            </p>
                            <Progress
                                value={overallStats.completionRate}
                                className="h-2 bg-white/15 [&_[data-slot=progress-indicator]]:bg-white"
                            />
                        </div>
                        <div className="space-y-1">
                            <p className="text-xs tracking-[0.18em] text-white/65 uppercase">
                                Task selesai
                            </p>
                            <p className="text-3xl font-semibold">
                                {overallStats.completedTasks}
                            </p>
                        </div>
                        <div className="space-y-1">
                            <p className="text-xs tracking-[0.18em] text-white/65 uppercase">
                                Poin terkumpul
                            </p>
                            <p className="text-3xl font-semibold">
                                {overallStats.points}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                {days.length === 0 ? (
                    <Card className="rounded-[2rem] border border-dashed py-0 shadow-sm">
                        <CardContent className="flex flex-col items-start gap-3 px-5 py-8 sm:px-6">
                            <CalendarRange className="size-6 text-muted-foreground" />
                            <div>
                                <p className="text-lg font-semibold">
                                    Belum ada history yang cocok
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    Ubah filter atau selesaikan beberapa task
                                    dulu agar heatmap dan daftar hari mulai
                                    terisi.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                ) : (
                    <>
                        <CalendarHeatmap
                            summaries={summaries}
                            selectedDate={effectiveSelectedDate}
                            selectedMonth={effectiveSelectedMonth}
                            availableMonths={monthOptions}
                            onMonthChange={setSelectedMonth}
                            onSelectDate={scrollToDate}
                            onOpenDate={(date) =>
                                router.visit(`/history/${date}`)
                            }
                        />

                        <section className="space-y-4">
                            {days.map((day) => {
                                const date = day.summary.date;

                                if (!date) {
                                    return null;
                                }

                                return (
                                    <div
                                        key={date}
                                        ref={(element) => {
                                            sectionRefs.current[date] = element;
                                        }}
                                        className="scroll-mt-24"
                                    >
                                        <Card className="rounded-[2rem] border bg-card/90 py-0 shadow-sm">
                                            <CardContent className="space-y-5 px-5 py-5 sm:px-6">
                                                <div className="flex flex-wrap items-start justify-between gap-3">
                                                    <div className="space-y-1">
                                                        <p className="text-lg font-semibold capitalize">
                                                            {formatHistoryDayLabel(
                                                                date,
                                                            )}
                                                        </p>
                                                        <p className="text-sm text-muted-foreground">
                                                            {
                                                                day.summary
                                                                    .completed_tasks
                                                            }
                                                            /
                                                            {
                                                                day.summary
                                                                    .total_tasks
                                                            }{' '}
                                                            task selesai •{' '}
                                                            {
                                                                day.summary
                                                                    .points_earned
                                                            }{' '}
                                                            poin
                                                        </p>
                                                    </div>

                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <Badge
                                                            variant="secondary"
                                                            className="rounded-full"
                                                        >
                                                            <CheckCircle2 className="size-3" />
                                                            {
                                                                day.summary
                                                                    .completion_rate
                                                            }
                                                            %
                                                        </Badge>
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            className="rounded-full"
                                                            onClick={() =>
                                                                router.visit(
                                                                    `/history/${date}`,
                                                                )
                                                            }
                                                        >
                                                            Detail hari
                                                            <ChevronRight className="size-4" />
                                                        </Button>
                                                    </div>
                                                </div>

                                                <Progress
                                                    value={
                                                        day.summary
                                                            .completion_rate
                                                    }
                                                    className="h-2.5"
                                                />

                                                <div className="space-y-3">
                                                    {day.instances.map(
                                                        (instance) => (
                                                            <HistoryInstanceRow
                                                                key={dailyQuestId(
                                                                    instance,
                                                                )}
                                                                instance={
                                                                    instance
                                                                }
                                                            />
                                                        ),
                                                    )}
                                                </div>
                                            </CardContent>
                                        </Card>
                                    </div>
                                );
                            })}
                        </section>
                    </>
                )}

                {days.length > 0 ? (
                    <div className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                        <span>Range aktif:</span>
                        <Badge variant="outline" className="rounded-full">
                            {normalizedFilters.from
                                ? toDateLabel(normalizedFilters.from)
                                : 'Awal'}
                            {' - '}
                            {normalizedFilters.to
                                ? toDateLabel(normalizedFilters.to)
                                : 'Sekarang'}
                        </Badge>
                    </div>
                ) : null}
            </div>
        </>
    );
}

DailyQuestHistoryIndex.layout = {
    breadcrumbs: [
        {
            title: 'History',
            href: '/history',
        },
    ],
};
