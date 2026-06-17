import { Head } from '@inertiajs/react';

type DashboardProps = {
    stats: Record<string, unknown>;
    date_range: Record<string, unknown>;
};

export default function DailyQuestDashboard(props: DashboardProps) {
    return (
        <>
            <Head title="Dashboard" />
            <div className="space-y-4 p-4">
                <h1 className="text-2xl font-semibold">Daily Quest Dashboard</h1>
                <pre className="overflow-auto rounded-lg border p-4 text-sm">{JSON.stringify(props, null, 2)}</pre>
            </div>
        </>
    );
}
