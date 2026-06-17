import { Check } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverDescription,
    PopoverHeader,
    PopoverTitle,
    PopoverTrigger,
} from '@/components/ui/popover';
import { taskColors } from '@/features/daily-quest/utils';
import { cn } from '@/lib/utils';

type ColorPickerProps = {
    value: string;
    onChange: (value: string) => void;
};

export default function ColorPicker({ value, onChange }: ColorPickerProps) {
    return (
        <Popover>
            <PopoverTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    className="w-full justify-between rounded-2xl"
                >
                    <span className="flex items-center gap-3">
                        <span
                            className="size-4 rounded-full border"
                            style={{ backgroundColor: value || '#cbd5e1' }}
                        />
                        {value || 'Pilih warna aksen'}
                    </span>
                    <span className="text-xs text-muted-foreground">Swatches</span>
                </Button>
            </PopoverTrigger>
            <PopoverContent align="start" className="w-80">
                <PopoverHeader>
                    <PopoverTitle>Warna task</PopoverTitle>
                    <PopoverDescription>
                        Pakai aksen yang sama dengan kategori atau bedakan task penting.
                    </PopoverDescription>
                </PopoverHeader>

                <div className="mt-4 grid grid-cols-5 gap-2">
                    {taskColors.map((color) => (
                        <button
                            key={color}
                            type="button"
                            className={cn(
                                'flex h-11 items-center justify-center rounded-2xl border transition hover:scale-[1.02]',
                                value === color && 'ring-2 ring-slate-950 ring-offset-2 dark:ring-white',
                            )}
                            style={{ backgroundColor: color }}
                            onClick={() => onChange(color)}
                        >
                            {value === color ? (
                                <Check className="size-4 text-white" />
                            ) : null}
                        </button>
                    ))}
                </div>

                <Button
                    type="button"
                    variant="ghost"
                    className="mt-3 w-full rounded-2xl"
                    onClick={() => onChange('')}
                >
                    Reset warna
                </Button>
            </PopoverContent>
        </Popover>
    );
}
