import { Head } from '@inertiajs/react';
import TaskForm from '@/features/daily-quest/components/task-form';
import type {
    DailyQuestTask,
    RecurrenceType,
    TaskCategory,
} from '@/features/daily-quest/types';

type TaskEditProps = {
    task: DailyQuestTask;
    categories: TaskCategory[];
    recurrence_types: Array<{ value: RecurrenceType; label: string }>;
};

export default function DailyQuestTasksEdit({
    task,
    categories,
    recurrence_types,
}: TaskEditProps) {
    return (
        <>
            <Head title="Edit Task" />
            <TaskForm
                mode="edit"
                task={task}
                categories={categories}
                recurrenceTypes={recurrence_types}
            />
        </>
    );
}

DailyQuestTasksEdit.layout = {
    breadcrumbs: [
        {
            title: 'Tasks',
            href: '/tasks',
        },
        {
            title: 'Edit',
            href: '/tasks/edit',
        },
    ],
};
