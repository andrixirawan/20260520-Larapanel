import { format } from 'date-fns';
import { ChevronLeft, ChevronRight, ExternalLink } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { HistoryDaySummary } from '@/features/daily-quest/types';
import {
    buildHeatmapMonthDays,
    completionTone,
    formatHistoryDayLabel,
    formatHistoryMonthLabel,
    isWithinVisibleMonth,
} from '@/features/daily-quest/utils';
import { cn } from '@/lib/utils';

type CalendarHeatmapProps = {
    summaries: HistoryDaySummary[];
    selectedDate: string | null;
    selectedMonth: string;
    availableMonths: string[];
    onMonthChange: (month: string) => void;
    onSelectDate: (date: string) => void;
    onOpenDate: (date: string) => void;
};

const weekdayLabels = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

export default function CalendarHeatmap({
    summaries,
    selectedDate,
    selectedMonth,
    availableMonths,
    onMonthChange,
    onSelectDate,
    onOpenDate,
}: CalendarHeatmapProps) {
    const summaryMap = new Map(
        summaries
            .filter((summary) => summary.date !== null)
            .map((summary) => [summary.date as string, summary]),
    );
    const monthDays = buildHeatmapMonthDays(selectedMonth);
    const selectedSummary =
        selectedDate && summaryMap.has(selectedDate)
            ? summaryMap.get(selectedDate) ?? null
            : null;
    const currentIndex = availableMonths.indexOf(selectedMonth);
    const previousMonth =
        currentIndex >= 0 && currentIndex < availableMonths.length - 1
            ? availableMonths[currentIndex + 1]
            : null;
    const nextMonth =
        currentIndex > 0 ? availableMonths[currentIndex - 1] : null;

    return (
        <Card className="rounded-[2rem] border bg-card/90 py-0 shadow-sm">
            <CardHeader className="gap-4 px-5 py-5 sm:px-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="space-y-1">
                        <CardTitle className="text-xl">
                            {formatHistoryMonthLabel(selectedMonth)}
                        </CardTitle>
                        <p className="text-sm text-muted-foreground">
                            Tap tanggal untuk scroll ke detail hari tersebut.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            className="rounded-full"
                            disabled={! previousMonth}
                            onClick={() =>
                                previousMonth ? onMonthChange(previousMonth) : null
                            }
                        >
                            <ChevronLeft className="size-4" />
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            className="rounded-full"
                            disabled={! nextMonth}
                            onClick={() =>
                                nextMonth ? onMonthChange(nextMonth) : null
                            }
                        >
                            <ChevronRight className="size-4" />
                        </Button>
                    </div>
                </div>
            </CardHeader>

            <CardContent className="space-y-5 px-5 pb-5 sm:px-6">
                <div className="grid grid-cols-7 gap-2">
                    {weekdayLabels.map((label) => (
                        <div
                            key={label}
                            className="text-center text-xs font-medium uppercase tracking-[0.18em] text-muted-foreground"
                        >
                            {label}
                        </div>
                    ))}

                    {monthDays.map((date) => {
                        const isoDate = format(date, 'yyyy-MM-dd');
                        const summary = summaryMap.get(isoDate) ?? null;
                        const isSelected = isoDate === selectedDate;
                        const isInMonth = isWithinVisibleMonth(date, selectedMonth);

                        return (
                            <button
                                key={isoDate}
                                type="button"
                                disabled={! summary}
                                className={cn(
                                    'min-h-18 rounded-2xl border p-2 text-left transition',
                                    ! isInMonth &&
                                        'border-dashed border-slate-200 bg-slate-50/50 text-slate-400 dark:border-slate-800 dark:bg-slate-950/20',
                                    isInMonth &&
                                        ! summary &&
                                        'border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-950/30',
                                    summary &&
                                        'border-slate-200 bg-white hover:-translate-y-0.5 hover:shadow-sm dark:border-slate-800 dark:bg-slate-950',
                                    isSelected &&
                                        'ring-2 ring-amber-400 ring-offset-2 ring-offset-background',
                                )}
                                onClick={() => (summary ? onSelectDate(isoDate) : null)}
                            >
                                <div className="flex h-full flex-col justify-between gap-2">
                                    <span
                                        className={cn(
                                            'text-xs font-medium',
                                            ! isInMonth && 'opacity-60',
                                        )}
                                    >
                                        {format(date, 'd')}
                                    </span>

                                    {summary ? (
                                        <div className="space-y-1">
                                            <div
                                                className={cn(
                                                    'h-2.5 rounded-full',
                                                    completionTone(summary),
                                                )}
                                            />
                                            <p className="text-[10px] text-muted-foreground">
                                                {summary.completed_tasks}/
                                                {summary.total_tasks}
                                            </p>
                                        </div>
                                    ) : null}
                                </div>
                            </button>
                        );
                    })}
                </div>

                {selectedSummary ? (
                    <div className="flex flex-wrap items-center justify-between gap-3 rounded-3xl border bg-background/90 p-4">
                        <div>
                            <p className="font-medium">
                                {formatHistoryDayLabel(selectedSummary.date ?? '')}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {selectedSummary.completed_tasks}/
                                {selectedSummary.total_tasks} task selesai,{' '}
                                {selectedSummary.points_earned} poin.
                            </p>
                        </div>

                        <Button
                            type="button"
                            className="rounded-full"
                            onClick={() =>
                                selectedSummary.date
                                    ? onOpenDate(selectedSummary.date)
                                    : null
                            }
                        >
                            <ExternalLink className="size-4" />
                            Buka detail hari
                        </Button>
                    </div>
                ) : null}
            </CardContent>
        </Card>
    );
}
