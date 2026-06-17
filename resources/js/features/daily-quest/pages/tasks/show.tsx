import { Head } from '@inertiajs/react';

type TaskShowProps = {
    task: Record<string, unknown>;
    recent_instances: Array<Record<string, unknown>>;
};

export default function DailyQuestTasksShow(props: TaskShowProps) {
    return (
        <>
            <Head title="Task Detail" />
            <div className="space-y-4 p-4">
                <h1 className="text-2xl font-semibold">Task Detail</h1>
                <pre className="overflow-auto rounded-lg border p-4 text-sm">{JSON.stringify(props, null, 2)}</pre>
            </div>
        </>
    );
}
