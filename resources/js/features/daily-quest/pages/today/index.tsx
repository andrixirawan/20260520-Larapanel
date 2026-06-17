import { Head, router } from '@inertiajs/react';
import {
    CheckCircle2,
    ChevronDown,
    ChevronUp,
    Plus,
    Sparkles,
    Target,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Progress } from '@/components/ui/progress';
import TaskInstanceOptionsDrawer from '@/features/daily-quest/components/task-instance-options-drawer';
import TaskItem from '@/features/daily-quest/components/task-item';
import type { TaskInstance, TodayStats } from '@/features/daily-quest/types';
import { dailyQuestId } from '@/features/daily-quest/utils';
import { post as completeInstance } from '@/routes/instances/complete';
import { post as uncompleteInstance } from '@/routes/instances/uncomplete';

type TodayPageProps = {
    date: string;
    instances: TaskInstance[];
    stats: TodayStats;
    streak: number;
};

function formatTodayLabel(date: string) {
    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
    }).format(new Date(`${date}T00:00:00`));
}

export default function DailyQuestTodayIndex({
    date,
    instances,
    stats,
    streak,
}: TodayPageProps) {
    const [optionsOpen, setOptionsOpen] = useState(false);
    const [completedOpen, setCompletedOpen] = useState(true);
    const [selectedInstance, setSelectedInstance] =
        useState<TaskInstance | null>(null);
    const [pendingIds, setPendingIds] = useState<string[]>([]);

    const currentStats = stats;

    const pendingInstances = useMemo(
        () => instances.filter((instance) => instance.completed_at === null),
        [instances],
    );
    const completedInstances = useMemo(
        () => instances.filter((instance) => instance.completed_at !== null),
        [instances],
    );

    const allCompleted =
        currentStats.total_tasks > 0 &&
        currentStats.completed_tasks === currentStats.total_tasks;

    const completionActionUrl = (instance: TaskInstance) => {
        const action =
            instance.completed_at === null
                ? completeInstance
                : uncompleteInstance;

        return action.url(dailyQuestId(instance));
    };

    const toggleInstance = (instance: TaskInstance, formData: FormData) => {
        const instanceId = dailyQuestId(instance);
        const submittedTaskId = formData.get('task_id');
        const taskId =
            (typeof submittedTaskId === 'string' ? submittedTaskId : '') ||
            instance.task_id ||
            dailyQuestId(instance.task);

        if (!instanceId || !taskId || pendingIds.includes(instanceId)) {
            return;
        }

        const willComplete = instance.completed_at === null;
        const action = willComplete ? completeInstance : uncompleteInstance;

        setPendingIds((current) => [...current, instanceId]);

        router.post(
            action.url(instanceId),
            { task_id: taskId },
            {
                preserveScroll: true,
                preserveState: false,
                onFinish: () => {
                    setPendingIds((current) =>
                        current.filter((id) => id !== instanceId),
                    );
                },
            },
        );
    };

    return (
        <>
            <Head title="Today" />

            <div className="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 px-4 py-5 sm:px-6">
                <Card className="overflow-hidden border-0 bg-[linear-gradient(135deg,#f59e0b_0%,#fb7185_50%,#38bdf8_100%)] py-0 text-white shadow-xl">
                    <CardHeader className="gap-4 px-5 py-5 sm:px-6">
                        <div className="flex flex-wrap items-start justify-between gap-4">
                            <div className="space-y-1">
                                <p className="text-sm/none font-medium tracking-[0.22em] text-white/70 uppercase">
                                    Today
                                </p>
                                <CardTitle className="text-3xl font-semibold capitalize">
                                    {formatTodayLabel(date)}
                                </CardTitle>
                                <CardDescription className="max-w-xl text-white/80">
                                    {currentStats.total_tasks > 0
                                        ? 'Selesaikan task penting dulu, sisanya akan terasa lebih ringan.'
                                        : 'Belum ada task untuk hari ini. Tambah satu task kecil untuk memulai momentum.'}
                                </CardDescription>
                            </div>

                            <div className="flex items-center gap-2 rounded-full bg-white/14 px-4 py-2 backdrop-blur-sm">
                                <Sparkles className="size-4" />
                                <span className="text-sm font-medium">
                                    {streak} hari streak
                                </span>
                            </div>
                        </div>

                        <div className="grid gap-3 sm:grid-cols-3">
                            <div className="rounded-2xl bg-white/12 p-4 backdrop-blur-sm">
                                <p className="text-xs tracking-[0.18em] text-white/70 uppercase">
                                    Progress
                                </p>
                                <p className="mt-2 text-2xl font-semibold">
                                    {currentStats.completed_tasks}/
                                    {currentStats.total_tasks}
                                </p>
                                <Progress
                                    value={currentStats.completion_rate}
                                    className="mt-3 h-2.5 bg-white/20 [&_[data-slot=progress-indicator]]:bg-white"
                                />
                            </div>

                            <div className="rounded-2xl bg-white/12 p-4 backdrop-blur-sm">
                                <p className="text-xs tracking-[0.18em] text-white/70 uppercase">
                                    Points hari ini
                                </p>
                                <p className="mt-2 text-2xl font-semibold">
                                    {currentStats.points_earned}
                                </p>
                                <p className="mt-2 text-sm text-white/80">
                                    Kumpulkan poin dari task selesai.
                                </p>
                            </div>

                            <div className="rounded-2xl bg-white/12 p-4 backdrop-blur-sm">
                                <p className="text-xs tracking-[0.18em] text-white/70 uppercase">
                                    Target tersisa
                                </p>
                                <p className="mt-2 text-2xl font-semibold">
                                    {pendingInstances.length}
                                </p>
                                <p className="mt-2 text-sm text-white/80">
                                    Fokus pada task yang masih pending.
                                </p>
                            </div>
                        </div>
                    </CardHeader>
                </Card>

                {allCompleted ? (
                    <Card className="border-emerald-200 bg-emerald-50 py-0 shadow-sm dark:border-emerald-900 dark:bg-emerald-950/40">
                        <CardContent className="flex flex-col gap-3 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <div className="flex items-start gap-3">
                                <div className="rounded-full bg-emerald-500 p-2 text-white shadow-lg">
                                    <CheckCircle2 className="size-5" />
                                </div>
                                <div>
                                    <p className="text-base font-semibold text-emerald-900 dark:text-emerald-100">
                                        Semua task hari ini selesai.
                                    </p>
                                    <p className="text-sm text-emerald-700 dark:text-emerald-300">
                                        Progress penuh, streak aman, dan poin
                                        hari ini sudah terkunci.
                                    </p>
                                </div>
                            </div>

                            <Badge className="rounded-full bg-emerald-600 px-3 py-1.5 text-white">
                                +{currentStats.points_earned} pts
                            </Badge>
                        </CardContent>
                    </Card>
                ) : null}

                {currentStats.total_tasks === 0 ? (
                    <Card className="border-dashed border-slate-300 bg-[radial-gradient(circle_at_top,#fef3c7,transparent_40%),linear-gradient(180deg,#fff,#f8fafc)] py-0 shadow-sm dark:border-slate-700 dark:bg-[radial-gradient(circle_at_top,#422006,transparent_30%),linear-gradient(180deg,#0f172a,#020617)]">
                        <CardContent className="flex flex-col items-start gap-5 px-5 py-8 sm:px-6">
                            <div className="rounded-2xl bg-slate-950 p-3 text-amber-300 dark:bg-white dark:text-slate-950">
                                <Target className="size-6" />
                            </div>
                            <div className="space-y-2">
                                <Heading
                                    title="Belum ada task untuk hari ini"
                                    description="Mulai dari satu task kecil melalui form task lengkap."
                                    variant="small"
                                />
                            </div>
                            <Button
                                type="button"
                                size="lg"
                                className="rounded-full"
                                onClick={() => router.visit('/tasks/create')}
                            >
                                <Plus className="size-4" />
                                Tambah task pertama
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="space-y-4">
                        <section className="space-y-3">
                            <div className="flex items-center justify-between gap-3">
                                <Heading
                                    title="Belum selesai"
                                    description="Task prioritas yang masih bisa kamu centang hari ini."
                                    variant="small"
                                />
                                <Badge
                                    variant="outline"
                                    className="rounded-full px-2.5 py-1"
                                >
                                    {pendingInstances.length} task
                                </Badge>
                            </div>

                            <div className="space-y-3">
                                {pendingInstances.map((instance) => (
                                    <TaskItem
                                        key={dailyQuestId(instance)}
                                        instance={instance}
                                        actionUrl={completionActionUrl(
                                            instance,
                                        )}
                                        busy={pendingIds.includes(
                                            dailyQuestId(instance),
                                        )}
                                        onToggle={toggleInstance}
                                        onOpenOptions={(nextInstance) => {
                                            setSelectedInstance(nextInstance);
                                            setOptionsOpen(true);
                                        }}
                                    />
                                ))}
                            </div>
                        </section>

                        <Collapsible
                            open={completedOpen}
                            onOpenChange={setCompletedOpen}
                            className="rounded-3xl border bg-background/90 p-4 shadow-sm backdrop-blur-sm"
                        >
                            <div className="flex items-center justify-between gap-3">
                                <div>
                                    <p className="text-sm font-semibold">
                                        Selesai
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        Task yang sudah kamu amankan hari ini.
                                    </p>
                                </div>

                                <CollapsibleTrigger asChild>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        className="rounded-full"
                                    >
                                        {completedInstances.length} task
                                        {completedOpen ? (
                                            <ChevronUp className="size-4" />
                                        ) : (
                                            <ChevronDown className="size-4" />
                                        )}
                                    </Button>
                                </CollapsibleTrigger>
                            </div>

                            <CollapsibleContent className="space-y-3 pt-4">
                                {completedInstances.map((instance) => (
                                    <TaskItem
                                        key={dailyQuestId(instance)}
                                        instance={instance}
                                        actionUrl={completionActionUrl(
                                            instance,
                                        )}
                                        busy={pendingIds.includes(
                                            dailyQuestId(instance),
                                        )}
                                        onToggle={toggleInstance}
                                        onOpenOptions={(nextInstance) => {
                                            setSelectedInstance(nextInstance);
                                            setOptionsOpen(true);
                                        }}
                                    />
                                ))}
                            </CollapsibleContent>
                        </Collapsible>
                    </div>
                )}
            </div>

            <div className="pointer-events-none fixed inset-x-0 bottom-6 z-30 flex justify-center px-4">
                <Button
                    type="button"
                    size="lg"
                    className="pointer-events-auto h-14 rounded-full px-6 shadow-xl"
                    onClick={() => router.visit('/tasks/create')}
                >
                    <Plus className="size-5" />
                    Tambah task
                </Button>
            </div>

            <TaskInstanceOptionsDrawer
                instance={selectedInstance}
                open={optionsOpen}
                onOpenChange={setOptionsOpen}
            />
        </>
    );
}

DailyQuestTodayIndex.layout = {
    breadcrumbs: [
        {
            title: 'Today',
            href: '/today',
        },
    ],
};
