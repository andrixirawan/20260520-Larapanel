import { Head } from '@inertiajs/react';

type CategoriesPageProps = {
    categories: Array<Record<string, unknown>>;
};

export default function DailyQuestCategoriesIndex({ categories }: CategoriesPageProps) {
    return (
        <>
            <Head title="Categories" />
            <div className="space-y-4 p-4">
                <h1 className="text-2xl font-semibold">Task Categories</h1>
                <pre className="overflow-auto rounded-lg border p-4 text-sm">{JSON.stringify(categories, null, 2)}</pre>
            </div>
        </>
    );
}
