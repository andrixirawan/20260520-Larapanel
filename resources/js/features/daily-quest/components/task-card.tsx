import { router } from '@inertiajs/react';
import { MoreHorizontal, PauseCircle, PlayCircle, Trash2 } from 'lucide-react';
import { useRef } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import type { DailyQuestTask } from '@/features/daily-quest/types';
import { inferTaskStatus } from '@/features/daily-quest/utils';

type TaskCardProps = {
    task: DailyQuestTask;
    onOpenActions: (task: DailyQuestTask) => void;
};

export default function TaskCard({ task, onOpenActions }: TaskCardProps) {
    const timerRef = useRef<number | null>(null);
    const status = inferTaskStatus(task);

    const clearTimer = () => {
        if (timerRef.current !== null) {
            window.clearTimeout(timerRef.current);
            timerRef.current = null;
        }
    };

    return (
        <Card
            className="overflow-hidden rounded-[2rem] border-0 bg-[linear-gradient(145deg,rgba(255,255,255,0.92),rgba(248,250,252,0.98))] py-0 shadow-md ring-1 ring-slate-200/70 transition hover:-translate-y-0.5 hover:shadow-lg dark:bg-[linear-gradient(145deg,rgba(15,23,42,0.95),rgba(2,6,23,0.98))] dark:ring-slate-800"
            onContextMenu={(event) => {
                event.preventDefault();
                onOpenActions(task);
            }}
            onTouchStart={() => {
                clearTimer();
                timerRef.current = window.setTimeout(() => {
                    onOpenActions(task);
                    timerRef.current = null;
                }, 450);
            }}
            onTouchEnd={clearTimer}
            onTouchMove={clearTimer}
        >
            <CardContent className="p-0">
                <div
                    role="button"
                    tabIndex={0}
                    className="flex w-full cursor-pointer items-start gap-4 px-5 py-5 text-left"
                    onClick={() => router.visit(`/tasks/${task.public_id}/edit`)}
                    onKeyDown={(event) => {
                        if (event.key === 'Enter' || event.key === ' ') {
                            event.preventDefault();
                            router.visit(`/tasks/${task.public_id}/edit`);
                        }
                    }}
                >
                    <div
                        className="flex size-14 shrink-0 items-center justify-center rounded-[1.4rem] text-2xl text-white shadow-sm"
                        style={{
                            backgroundColor:
                                task.color ?? task.category?.color ?? '#0f172a',
                        }}
                    >
                        {task.icon ?? task.category?.icon ?? '📝'}
                    </div>

                    <div className="min-w-0 flex-1 space-y-3">
                        <div className="flex items-start justify-between gap-3">
                            <div className="min-w-0">
                                <p className="truncate text-lg font-semibold">
                                    {task.name}
                                </p>
                                <p className="mt-1 line-clamp-2 text-sm text-muted-foreground">
                                    {task.description || task.recurrence_summary}
                                </p>
                            </div>

                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                className="rounded-full"
                                onClick={(event) => {
                                    event.stopPropagation();
                                    onOpenActions(task);
                                }}
                            >
                                <MoreHorizontal className="size-4" />
                            </Button>
                        </div>

                        <div className="flex flex-wrap items-center gap-2">
                            <Badge variant="secondary" className="rounded-full">
                                {task.recurrence_summary}
                            </Badge>
                            <Badge variant="outline" className="rounded-full">
                                {task.points} pts
                            </Badge>
                            <Badge
                                className="rounded-full"
                                variant={status === 'active' ? 'default' : 'outline'}
                            >
                                {status === 'active' ? (
                                    <PlayCircle className="size-3.5" />
                                ) : status === 'paused' ? (
                                    <PauseCircle className="size-3.5" />
                                ) : (
                                    <Trash2 className="size-3.5" />
                                )}
                                {status === 'active'
                                    ? 'Aktif'
                                    : status === 'paused'
                                      ? 'Dijeda'
                                      : 'Arsip'}
                            </Badge>
                            {task.category ? (
                                <Badge variant="outline" className="rounded-full">
                                    {task.category.icon ? `${task.category.icon} ` : ''}
                                    {task.category.name}
                                </Badge>
                            ) : null}
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
