import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, Clock3, Sparkles } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import type {
    HistoryDaySummary,
    TaskInstance,
} from '@/features/daily-quest/types';
import { formatHistoryDayLabel } from '@/features/daily-quest/utils';

type HistoryShowProps = {
    date: string;
    summary: HistoryDaySummary;
    instances: TaskInstance[];
};

function DetailInstanceRow({ instance }: { instance: TaskInstance }) {
    const task = instance.task;
    const completed = instance.completed_at !== null;

    return (
        <div className="flex items-start gap-4 rounded-[1.6rem] border bg-background/80 p-4">
            <div
                className="flex size-12 shrink-0 items-center justify-center rounded-2xl text-xl text-white"
                style={{
                    backgroundColor:
                        task?.color ?? task?.category?.color ?? '#0f172a',
                }}
            >
                {task?.icon ?? task?.category?.icon ?? '📝'}
            </div>

            <div className="min-w-0 flex-1 space-y-2">
                <div className="flex flex-wrap items-center gap-2">
                    <p className="text-base font-semibold">
                        {task?.name ?? 'Deleted task'}
                    </p>
                    <Badge
                        variant={completed ? 'secondary' : 'outline'}
                        className="rounded-full"
                    >
                        {completed ? (
                            <CheckCircle2 className="size-3" />
                        ) : (
                            <Clock3 className="size-3" />
                        )}
                        {completed ? 'Selesai' : 'Tidak selesai'}
                    </Badge>
                    {task?.deleted_at ? (
                        <Badge variant="outline" className="rounded-full">
                            dihapus
                        </Badge>
                    ) : null}
                </div>

                <p className="text-sm text-muted-foreground">
                    {task?.description ||
                        task?.recurrence_summary ||
                        'Task harian'}
                </p>

                <div className="flex flex-wrap items-center gap-2">
                    {task?.category ? (
                        <Badge variant="outline" className="rounded-full">
                            {task.category.icon ? `${task.category.icon} ` : ''}
                            {task.category.name}
                        </Badge>
                    ) : null}
                    <Badge variant="outline" className="rounded-full">
                        <Sparkles className="size-3" />
                        {instance.points_awarded ?? task?.points ?? 0} poin
                    </Badge>
                </div>

                {instance.notes ? (
                    <p className="rounded-2xl bg-muted/60 px-3 py-2 text-sm text-muted-foreground">
                        {instance.notes}
                    </p>
                ) : null}
            </div>
        </div>
    );
}

export default function DailyQuestHistoryShow({
    date,
    summary,
    instances,
}: HistoryShowProps) {
    return (
        <>
            <Head title="History Detail" />

            <div className="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-6 px-4 py-5 sm:px-6">
                <Button
                    asChild
                    type="button"
                    variant="ghost"
                    className="w-fit rounded-full"
                >
                    <Link href="/history">
                        <ArrowLeft className="size-4" />
                        Kembali ke history
                    </Link>
                </Button>

                <Heading
                    title={formatHistoryDayLabel(date)}
                    description="Detail lengkap instance task untuk satu hari, termasuk status, poin, dan catatan yang tersimpan."
                />

                <Card className="overflow-hidden border-0 bg-[linear-gradient(135deg,#f59e0b_0%,#fb7185_55%,#0ea5e9_100%)] py-0 text-white shadow-xl">
                    <CardContent className="grid gap-4 px-5 py-5 sm:grid-cols-3 sm:px-6">
                        <div>
                            <p className="text-xs tracking-[0.18em] text-white/65 uppercase">
                                Completion
                            </p>
                            <p className="mt-2 text-3xl font-semibold">
                                {summary.completion_rate}%
                            </p>
                            <Progress
                                value={summary.completion_rate}
                                className="mt-3 h-2 bg-white/15 [&_[data-slot=progress-indicator]]:bg-white"
                            />
                        </div>
                        <div>
                            <p className="text-xs tracking-[0.18em] text-white/65 uppercase">
                                Task selesai
                            </p>
                            <p className="mt-2 text-3xl font-semibold">
                                {summary.completed_tasks}/{summary.total_tasks}
                            </p>
                        </div>
                        <div>
                            <p className="text-xs tracking-[0.18em] text-white/65 uppercase">
                                Poin hari itu
                            </p>
                            <p className="mt-2 text-3xl font-semibold">
                                {summary.points_earned}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <section className="space-y-3">
                    {instances.map((instance) => (
                        <DetailInstanceRow
                            key={instance.id}
                            instance={instance}
                        />
                    ))}
                </section>
            </div>
        </>
    );
}

DailyQuestHistoryShow.layout = {
    breadcrumbs: [
        {
            title: 'History',
            href: '/history',
        },
    ],
};
