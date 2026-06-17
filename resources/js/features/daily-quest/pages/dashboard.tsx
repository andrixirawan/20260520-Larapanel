import { Head } from '@inertiajs/react';
import { CheckCircle2, Flame, Sparkles, Target } from 'lucide-react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    XAxis,
    YAxis,
} from 'recharts';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import StatsCard from '@/features/daily-quest/components/stats-card';
import XPProgressBar from '@/features/daily-quest/components/xp-progress-bar';
import type { DashboardStats } from '@/features/daily-quest/types';
import {
    calculateXp,
    formatCompactDate,
    weeklyChartPeak,
} from '@/features/daily-quest/utils';

type DashboardProps = {
    stats: DashboardStats;
    date_range: {
        today: string;
        week_start: string;
        week_end: string;
    };
};

const chartConfig = {
    completed_tasks: {
        label: 'Task selesai',
        color: '#0ea5e9',
    },
    points_earned: {
        label: 'Poin',
        color: '#f59e0b',
    },
};

export default function DailyQuestDashboard({
    stats,
    date_range,
}: DashboardProps) {
    const xp = calculateXp(stats.points.total);
    const chartPeak = weeklyChartPeak(stats);

    return (
        <>
            <Head title="Dashboard" />

            <div className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 px-4 py-5 sm:px-6">
                <Heading
                    title="Daily Quest Dashboard"
                    description="Ringkasan streak, progres mingguan, dan momentum poin untuk memastikan kebiasaan yang penting tetap bergerak."
                />

                <Card className="overflow-hidden border-0 bg-[linear-gradient(135deg,#f59e0b_0%,#fb7185_55%,#0ea5e9_100%)] py-0 text-white shadow-xl">
                    <CardContent className="flex flex-col gap-4 px-5 py-5 sm:px-6">
                        <div className="flex flex-wrap items-start justify-between gap-4">
                            <div className="space-y-1">
                                <p className="text-sm uppercase tracking-[0.18em] text-white/70">
                                    Rentang mingguan
                                </p>
                                <p className="text-3xl font-semibold">
                                    {formatCompactDate(date_range.week_start)} -{' '}
                                    {formatCompactDate(date_range.week_end)}
                                </p>
                                <p className="text-sm text-white/80">
                                    Pantau konsistensi harian dan lihat di mana pola mulai melemah.
                                </p>
                            </div>

                            <Badge className="rounded-full bg-white/15 px-4 py-2 text-white hover:bg-white/15">
                                Hari ini: {stats.points.today ?? 0} pts
                            </Badge>
                        </div>

                        <XPProgressBar {...xp} />
                    </CardContent>
                </Card>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <StatsCard
                        title="Streak aktif"
                        value={stats.streak.current}
                        description={`Rekor terbaik ${stats.streak.longest} hari.`}
                        icon={Flame}
                        tone="warm"
                    />
                    <StatsCard
                        title="Total poin"
                        value={stats.points.total}
                        description="Akumulasi seluruh reward yang sudah diamankan."
                        icon={Sparkles}
                        tone="cool"
                    />
                    <StatsCard
                        title="Progress hari ini"
                        value={`${stats.today?.completed_tasks ?? 0}/${stats.today?.total_tasks ?? 0}`}
                        description="Task yang berhasil diselesaikan pada hari ini."
                        icon={CheckCircle2}
                    />
                    <StatsCard
                        title="Weekly completion"
                        value={`${stats.weekly_completion_rate}%`}
                        description="Rata-rata penyelesaian task untuk 7 hari terakhir."
                        icon={Target}
                    />
                </section>

                <Card className="rounded-[2rem] border bg-card/90 py-0 shadow-sm">
                    <CardHeader className="px-5 py-5 sm:px-6">
                        <CardTitle>Aktivitas mingguan</CardTitle>
                        <p className="text-sm text-muted-foreground">
                            Bandingkan jumlah task selesai dan poin yang dihasilkan setiap hari.
                        </p>
                    </CardHeader>
                    <CardContent className="px-2 pb-4 sm:px-4">
                        <ChartContainer
                            config={chartConfig}
                            className="min-h-[320px] w-full"
                        >
                            <BarChart data={stats.weekly_chart} barCategoryGap={16}>
                                <CartesianGrid vertical={false} strokeDasharray="3 3" />
                                <XAxis
                                    dataKey="date"
                                    tickFormatter={formatCompactDate}
                                    tickLine={false}
                                    axisLine={false}
                                />
                                <YAxis
                                    width={36}
                                    domain={[0, chartPeak]}
                                    tickLine={false}
                                    axisLine={false}
                                />
                                <ChartTooltip
                                    content={
                                        <ChartTooltipContent
                                            labelFormatter={(label) =>
                                                typeof label === 'string'
                                                    ? formatCompactDate(label)
                                                    : label
                                            }
                                        />
                                    }
                                />
                                <Bar
                                    dataKey="completed_tasks"
                                    fill="var(--color-completed_tasks)"
                                    radius={[10, 10, 0, 0]}
                                />
                                <Bar
                                    dataKey="points_earned"
                                    fill="var(--color-points_earned)"
                                    radius={[10, 10, 0, 0]}
                                />
                            </BarChart>
                        </ChartContainer>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
