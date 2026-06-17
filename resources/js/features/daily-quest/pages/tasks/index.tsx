import { Head, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useMemo, useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import TaskActionsDrawer from '@/features/daily-quest/components/task-actions-drawer';
import TaskCard from '@/features/daily-quest/components/task-card';
import type {
    DailyQuestTask,
    TaskCategory,
    TaskStatusTab,
} from '@/features/daily-quest/types';
import {
    buildTaskRedirectPath,
    taskStatusMeta,
} from '@/features/daily-quest/utils';

type TasksIndexProps = {
    tasks: DailyQuestTask[];
    filters: {
        status?: TaskStatusTab;
        search?: string;
    };
    categories: TaskCategory[];
};

export default function DailyQuestTasksIndex({
    tasks,
    filters,
    categories,
}: TasksIndexProps) {
    const [selectedTask, setSelectedTask] = useState<DailyQuestTask | null>(null);
    const [drawerOpen, setDrawerOpen] = useState(false);
    const activeStatus = filters.status ?? 'active';
    const emptyStateCopy = taskStatusMeta[activeStatus];
    const listCountLabel = useMemo(() => `${tasks.length} task`, [tasks.length]);

    return (
        <>
            <Head title="Tasks" />

            <div className="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 px-4 py-5 sm:px-6">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <Heading
                        title="Task management"
                        description="Kelola semua task dari daftar aktif, dijeda, dan arsip tanpa meninggalkan pola mobile-first."
                    />

                    <Badge variant="outline" className="rounded-full px-3 py-1.5">
                        {categories.length} kategori
                    </Badge>
                </div>

                <section className="space-y-4 rounded-[2rem] border bg-card/90 p-4 shadow-sm">
                    <div className="flex flex-wrap gap-2">
                        {(Object.keys(taskStatusMeta) as TaskStatusTab[]).map((status) => {
                            const meta = taskStatusMeta[status];
                            const active = activeStatus === status;

                            return (
                                <Button
                                    key={status}
                                    type="button"
                                    variant={active ? 'default' : 'outline'}
                                    className="rounded-full"
                                    onClick={() =>
                                        router.get(
                                            '/tasks',
                                            { status },
                                            {
                                                preserveScroll: true,
                                                preserveState: true,
                                            },
                                        )
                                    }
                                >
                                    {meta.label}
                                    {active ? (
                                        <Badge
                                            variant="secondary"
                                            className="ml-1 rounded-full"
                                        >
                                            {tasks.length}
                                        </Badge>
                                    ) : null}
                                </Button>
                            );
                        })}
                    </div>

                    <p className="text-sm text-muted-foreground">
                        {emptyStateCopy.description} {listCountLabel} pada tab ini.
                    </p>
                </section>

                {tasks.length === 0 ? (
                    <section className="rounded-[2rem] border border-dashed bg-[radial-gradient(circle_at_top,#fef3c7,transparent_35%),linear-gradient(180deg,#fff,#f8fafc)] p-8 shadow-sm dark:bg-[radial-gradient(circle_at_top,#422006,transparent_25%),linear-gradient(180deg,#0f172a,#020617)]">
                        <h2 className="text-xl font-semibold">
                            Belum ada task {emptyStateCopy.label.toLowerCase()}
                        </h2>
                        <p className="mt-2 max-w-2xl text-sm text-muted-foreground">
                            {activeStatus === 'archived'
                                ? 'Task yang dihapus akan muncul di sini sebagai arsip.'
                                : 'Mulai dari satu task penting, lalu atur recurrence dan poinnya dari form lengkap.'}
                        </p>
                        <Button
                            type="button"
                            className="mt-5 rounded-full"
                            onClick={() => router.visit('/tasks/create')}
                        >
                            <Plus className="size-4" />
                            Buat task
                        </Button>
                    </section>
                ) : (
                    <div className="space-y-4">
                        {tasks.map((task) => (
                            <TaskCard
                                key={task.public_id}
                                task={task}
                                onOpenActions={(nextTask) => {
                                    setSelectedTask(nextTask);
                                    setDrawerOpen(true);
                                }}
                            />
                        ))}
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

            <TaskActionsDrawer
                open={drawerOpen}
                onOpenChange={setDrawerOpen}
                task={selectedTask}
                redirectTo={buildTaskRedirectPath(activeStatus, filters.search ?? '')}
            />
        </>
    );
}

DailyQuestTasksIndex.layout = {
    breadcrumbs: [
        {
            title: 'Tasks',
            href: '/tasks',
        },
    ],
};
