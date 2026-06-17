import { Head } from '@inertiajs/react';

type TaskEditProps = {
    task: Record<string, unknown>;
    categories: Array<Record<string, unknown>>;
    recurrence_types: Array<Record<string, unknown>>;
};

export default function DailyQuestTasksEdit(props: TaskEditProps) {
    return (
        <>
            <Head title="Edit Task" />
            <div className="space-y-4 p-4">
                <h1 className="text-2xl font-semibold">Edit Task</h1>
                <pre className="overflow-auto rounded-lg border p-4 text-sm">{JSON.stringify(props, null, 2)}</pre>
            </div>
        </>
    );
}
