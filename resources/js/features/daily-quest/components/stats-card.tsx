import type { LucideIcon } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';

type StatsCardProps = {
    title: string;
    value: string | number;
    description: string;
    icon: LucideIcon;
    tone?: 'default' | 'warm' | 'cool';
};

const toneClasses: Record<NonNullable<StatsCardProps['tone']>, string> = {
    default:
        'bg-[linear-gradient(145deg,rgba(255,255,255,0.95),rgba(248,250,252,0.98))] dark:bg-[linear-gradient(145deg,rgba(15,23,42,0.95),rgba(2,6,23,0.98))]',
    warm: 'bg-[linear-gradient(145deg,#fff7ed,#ffedd5)] dark:bg-[linear-gradient(145deg,rgba(67,20,7,0.95),rgba(41,12,5,0.98))]',
    cool: 'bg-[linear-gradient(145deg,#eff6ff,#e0f2fe)] dark:bg-[linear-gradient(145deg,rgba(8,47,73,0.95),rgba(10,37,64,0.98))]',
};

export default function StatsCard({
    title,
    value,
    description,
    icon: Icon,
    tone = 'default',
}: StatsCardProps) {
    return (
        <Card
            className={`overflow-hidden rounded-[2rem] border-0 py-0 shadow-md ring-1 ring-slate-200/70 dark:ring-slate-800 ${toneClasses[tone]}`}
        >
            <CardContent className="flex items-start justify-between gap-4 px-5 py-5">
                <div className="space-y-2">
                    <p className="text-sm font-medium text-muted-foreground">
                        {title}
                    </p>
                    <p className="text-3xl font-semibold">{value}</p>
                    <p className="text-sm text-muted-foreground">{description}</p>
                </div>

                <div className="rounded-[1.25rem] bg-slate-950 p-3 text-white shadow-sm dark:bg-white dark:text-slate-950">
                    <Icon className="size-5" />
                </div>
            </CardContent>
        </Card>
    );
}
