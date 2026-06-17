import { Head } from '@inertiajs/react';

type TodayPageProps = {
    date: string;
    instances: Array<Record<string, unknown>>;
    stats: Record<string, unknown>;
    streak: number;
};

export default function DailyQuestTodayIndex(props: TodayPageProps) {
    return (
        <>
            <Head title="Today" />
            <div className="space-y-4 p-4">
                <h1 className="text-2xl font-semibold">Today</h1>
                <pre className="overflow-auto rounded-lg border p-4 text-sm">{JSON.stringify(props, null, 2)}</pre>
            </div>
        </>
    );
}
