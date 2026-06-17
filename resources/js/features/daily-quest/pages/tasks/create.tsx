import { Head } from '@inertiajs/react';

type TaskCreateProps = {
    categories: Array<Record<string, unknown>>;
    recurrence_types: Array<Record<string, unknown>>;
};

export default function DailyQuestTasksCreate(props: TaskCreateProps) {
    return (
        <>
            <Head title="Create Task" />
            <div className="space-y-4 p-4">
                <h1 className="text-2xl font-semibold">Create Task</h1>
                <pre className="overflow-auto rounded-lg border p-4 text-sm">{JSON.stringify(props, null, 2)}</pre>
            </div>
        </>
    );
}
