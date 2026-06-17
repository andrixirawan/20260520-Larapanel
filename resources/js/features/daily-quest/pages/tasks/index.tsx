import { Head } from '@inertiajs/react';

type TasksIndexProps = {
    tasks: Array<Record<string, unknown>>;
    filters: Record<string, unknown>;
    categories: Array<Record<string, unknown>>;
};

export default function DailyQuestTasksIndex(props: TasksIndexProps) {
    return (
        <>
            <Head title="Tasks" />
            <div className="space-y-4 p-4">
                <h1 className="text-2xl font-semibold">Tasks</h1>
                <pre className="overflow-auto rounded-lg border p-4 text-sm">{JSON.stringify(props, null, 2)}</pre>
            </div>
        </>
    );
}
