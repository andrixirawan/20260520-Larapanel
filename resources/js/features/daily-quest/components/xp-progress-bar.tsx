import { Sparkles, Trophy } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';

type XPProgressBarProps = {
    level: number;
    currentXp: number;
    nextLevelXp: number;
    progress: number;
};

export default function XPProgressBar({
    level,
    currentXp,
    nextLevelXp,
    progress,
}: XPProgressBarProps) {
    return (
        <div className="rounded-[2rem] border bg-card/90 p-5 shadow-sm">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p className="text-sm font-medium text-muted-foreground">
                        XP progress
                    </p>
                    <div className="mt-2 flex items-center gap-2">
                        <Trophy className="size-5 text-amber-500" />
                        <p className="text-2xl font-semibold">Level {level}</p>
                    </div>
                </div>

                <Badge variant="secondary" className="rounded-full px-3 py-1">
                    <Sparkles className="size-3" />
                    {currentXp}/{nextLevelXp} XP
                </Badge>
            </div>

            <Progress value={progress} className="mt-4 h-3 rounded-full" />

            <p className="mt-3 text-sm text-muted-foreground">
                Konsistensi kecil tetap mendorong progres menuju level berikutnya.
            </p>
        </div>
    );
}
