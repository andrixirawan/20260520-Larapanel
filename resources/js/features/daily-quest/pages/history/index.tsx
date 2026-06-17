import { Head } from '@inertiajs/react';

type HistoryIndexProps = {
    days: Array<Record<string, unknown>>;
    filters: Record<string, unknown>;
    tasks: Array<Record<string, unknown>>;
    categories: Array<Record<string, unknown>>;
};

export default function DailyQuestHistoryIndex(props: HistoryIndexProps) {
    return (
        <>
            <Head title="History" />
            <div className="space-y-4 p-4">
                <h1 className="text-2xl font-semibold">History</h1>
                <pre className="overflow-auto rounded-lg border p-4 text-sm">{JSON.stringify(props, null, 2)}</pre>
            </div>
        </>
    );
}
