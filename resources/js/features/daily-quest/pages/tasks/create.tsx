import { Head } from '@inertiajs/react';
import TaskForm from '@/features/daily-quest/components/task-form';
import type {
    RecurrenceType,
    TaskCategory,
} from '@/features/daily-quest/types';

type TaskCreateProps = {
    categories: TaskCategory[];
    recurrence_types: Array<{ value: RecurrenceType; label: string }>;
};

export default function DailyQuestTasksCreate({
    categories,
    recurrence_types,
}: TaskCreateProps) {
    return (
        <>
            <Head title="Create Task" />
            <TaskForm
                mode="create"
                categories={categories}
                recurrenceTypes={recurrence_types}
            />
        </>
    );
}

DailyQuestTasksCreate.layout = {
    breadcrumbs: [
        {
            title: 'Tasks',
            href: '/tasks',
        },
        {
            title: 'Create',
            href: '/tasks/create',
        },
    ],
};
