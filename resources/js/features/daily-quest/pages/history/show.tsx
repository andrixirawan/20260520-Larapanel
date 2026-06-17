import { Head } from '@inertiajs/react';

type HistoryShowProps = {
    date: string;
    summary: Record<string, unknown>;
    instances: Array<Record<string, unknown>>;
};

export default function DailyQuestHistoryShow(props: HistoryShowProps) {
    return (
        <>
            <Head title="History Detail" />
            <div className="space-y-4 p-4">
                <h1 className="text-2xl font-semibold">History Detail</h1>
                <pre className="overflow-auto rounded-lg border p-4 text-sm">{JSON.stringify(props, null, 2)}</pre>
            </div>
        </>
    );
}
