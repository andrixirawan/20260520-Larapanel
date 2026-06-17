import { EllipsisVertical, Sparkles } from 'lucide-react';
import { useRef } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { cn } from '@/lib/utils';
import type { TaskInstance } from '@/features/daily-quest/types';

type TaskItemProps = {
    instance: TaskInstance;
    busy?: boolean;
    onToggle: (instance: TaskInstance) => void;
    onOpenOptions: (instance: TaskInstance) => void;
};

export default function TaskItem({
    instance,
    busy = false,
    onToggle,
    onOpenOptions,
}: TaskItemProps) {
    const longPressTimeoutRef = useRef<number | null>(null);
    const longPressTriggeredRef = useRef(false);
    const isCompleted = instance.completed_at !== null;

    const clearLongPress = () => {
        if (longPressTimeoutRef.current !== null) {
            window.clearTimeout(longPressTimeoutRef.current);
            longPressTimeoutRef.current = null;
        }
    };

    const handlePointerDown = () => {
        longPressTriggeredRef.current = false;
        clearLongPress();
        longPressTimeoutRef.current = window.setTimeout(() => {
            longPressTriggeredRef.current = true;
            onOpenOptions(instance);
        }, 450);
    };

    const handlePointerUp = () => {
        clearLongPress();
    };

    return (
        <Card
            className={cn(
                'gap-0 border-0 bg-white/80 py-0 shadow-sm ring-1 ring-black/5 backdrop-blur-sm transition-all dark:bg-neutral-950/80 dark:ring-white/10',
                isCompleted
                    ? 'opacity-75'
                    : 'hover:-translate-y-0.5 hover:shadow-md',
                busy && 'pointer-events-none opacity-70',
            )}
            onContextMenu={(event) => {
                event.preventDefault();
                onOpenOptions(instance);
            }}
            onPointerDown={handlePointerDown}
            onPointerUp={handlePointerUp}
            onPointerLeave={handlePointerUp}
            onPointerCancel={handlePointerUp}
        >
            <div className="flex items-center gap-3 p-4">
                <Checkbox
                    checked={isCompleted}
                    disabled={busy}
                    onCheckedChange={() => {
                        if (longPressTriggeredRef.current) {
                            longPressTriggeredRef.current = false;
                            return;
                        }

                        onToggle(instance);
                    }}
                    aria-label={`Toggle ${instance.task?.name ?? 'task'}`}
                    className={cn(
                        'mt-0.5 size-5 rounded-full border-2',
                        isCompleted &&
                            'border-emerald-500 bg-emerald-500 text-white',
                    )}
                />

                <div className="min-w-0 flex-1">
                    <div className="flex items-start gap-3">
                        <div
                            className="flex size-11 shrink-0 items-center justify-center rounded-2xl text-lg shadow-sm"
                            style={{
                                backgroundColor:
                                    instance.task?.color ?? '#f3f4f6',
                                color: instance.task?.color ? '#ffffff' : '#111827',
                            }}
                        >
                            {instance.task?.icon ?? '✨'}
                        </div>

                        <div className="min-w-0 flex-1 space-y-1">
                            <div className="flex items-start justify-between gap-3">
                                <div className="min-w-0">
                                    <p
                                        className={cn(
                                            'truncate text-sm font-semibold text-slate-950 dark:text-slate-50',
                                            isCompleted &&
                                                'text-slate-500 line-through dark:text-slate-400',
                                        )}
                                    >
                                        {instance.task?.name ?? 'Untitled task'}
                                    </p>
                                    {instance.task?.category ? (
                                        <p className="truncate text-xs text-muted-foreground">
                                            {instance.task.category.icon
                                                ? `${instance.task.category.icon} `
                                                : ''}
                                            {instance.task.category.name}
                                        </p>
                                    ) : (
                                        <p className="truncate text-xs text-muted-foreground">
                                            {instance.task?.recurrence_summary ??
                                                'Task'}
                                        </p>
                                    )}
                                </div>

                                <Badge
                                    variant={isCompleted ? 'secondary' : 'outline'}
                                    className="rounded-full px-2.5 py-1"
                                >
                                    <Sparkles className="size-3" />
                                    {instance.task?.points ?? 0}
                                </Badge>
                            </div>

                            {instance.task?.description ? (
                                <p className="line-clamp-2 text-xs text-muted-foreground">
                                    {instance.task.description}
                                </p>
                            ) : null}
                        </div>
                    </div>
                </div>

                <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    className="shrink-0 rounded-full"
                    onClick={() => onOpenOptions(instance)}
                >
                    <EllipsisVertical className="size-4" />
                </Button>
            </div>
        </Card>
    );
}
