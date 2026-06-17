import { useForm } from '@inertiajs/react';
import { Loader2, Sparkles } from 'lucide-react';
import { useEffect } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Drawer,
    DrawerContent,
    DrawerDescription,
    DrawerFooter,
    DrawerHeader,
    DrawerTitle,
} from '@/components/ui/drawer';
import { Input } from '@/components/ui/input';

type QuickAddTaskDrawerProps = {
    date: string;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

type QuickPreset = 'daily' | 'weekdays' | 'today';

const presetLabels: Record<
    QuickPreset,
    { label: string; description: string; points: number }
> = {
    daily: {
        label: 'Daily',
        description: 'Muncul setiap hari.',
        points: 10,
    },
    weekdays: {
        label: 'Weekdays',
        description: 'Senin sampai Jumat.',
        points: 15,
    },
    today: {
        label: 'Today only',
        description: 'Sekali untuk hari ini.',
        points: 5,
    },
};

export default function QuickAddTaskDrawer({
    date,
    open,
    onOpenChange,
}: QuickAddTaskDrawerProps) {
    const form = useForm({
        name: '',
        redirect_to: '/today',
        recurrence_preset: 'daily' as QuickPreset,
    });

    useEffect(() => {
        if (open) {
            form.clearErrors();
        }
    }, [form, open]);

    const selectedPreset = presetLabels[form.data.recurrence_preset];

    const submit = () => {
        const payload =
            form.data.recurrence_preset === 'weekdays'
                ? {
                      name: form.data.name,
                      points: selectedPreset.points,
                      redirect_to: form.data.redirect_to,
                      recurrence_type: 'specific_days',
                      recurrence_days: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
                  }
                : form.data.recurrence_preset === 'today'
                  ? {
                        name: form.data.name,
                        points: selectedPreset.points,
                        redirect_to: form.data.redirect_to,
                        recurrence_type: 'one_time',
                        recurrence_starts_at: date,
                    }
                  : {
                        name: form.data.name,
                        points: selectedPreset.points,
                        redirect_to: form.data.redirect_to,
                        recurrence_type: 'daily',
                    };

        form.transform(() => payload);
        form.post('/tasks', {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                form.setData('redirect_to', '/today');
                form.setData('recurrence_preset', 'daily');
                onOpenChange(false);
            },
        });
    };

    return (
        <Drawer open={open} onOpenChange={onOpenChange}>
            <DrawerContent className="mx-auto max-w-2xl">
                <DrawerHeader className="text-left">
                    <DrawerTitle className="text-xl">Tambah Task Cepat</DrawerTitle>
                    <DrawerDescription>
                        Isi nama task dan pilih preset pengulangan satu klik.
                    </DrawerDescription>
                </DrawerHeader>

                <div className="space-y-5 px-4 pb-2">
                    <div className="space-y-2">
                        <label
                            htmlFor="quick-task-name"
                            className="text-sm font-medium text-slate-800 dark:text-slate-200"
                        >
                            Nama task
                        </label>
                        <Input
                            id="quick-task-name"
                            placeholder="Contoh: Minum 2 liter air"
                            value={form.data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="space-y-3">
                        <div className="flex items-center justify-between gap-3">
                            <p className="text-sm font-medium text-slate-800 dark:text-slate-200">
                                Preset pengulangan
                            </p>
                            <Badge
                                variant="secondary"
                                className="rounded-full px-2.5 py-1"
                            >
                                <Sparkles className="size-3" />
                                {selectedPreset.points} pts
                            </Badge>
                        </div>

                        <div className="grid gap-2 sm:grid-cols-3">
                            {(Object.keys(presetLabels) as QuickPreset[]).map(
                                (preset) => {
                                    const option = presetLabels[preset];
                                    const active =
                                        form.data.recurrence_preset === preset;

                                    return (
                                        <button
                                            key={preset}
                                            type="button"
                                            className={`rounded-2xl border p-4 text-left transition ${
                                                active
                                                    ? 'border-emerald-500 bg-emerald-50 shadow-sm dark:bg-emerald-950/40'
                                                    : 'border-border bg-background hover:border-emerald-300 hover:bg-emerald-50/50 dark:hover:bg-emerald-950/20'
                                            }`}
                                            onClick={() =>
                                                form.setData(
                                                    'recurrence_preset',
                                                    preset,
                                                )
                                            }
                                        >
                                            <div className="space-y-1">
                                                <p className="text-sm font-semibold">
                                                    {option.label}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {option.description}
                                                </p>
                                            </div>
                                        </button>
                                    );
                                },
                            )}
                        </div>
                    </div>
                </div>

                <DrawerFooter className="border-t bg-background/80 backdrop-blur-sm">
                    <Button
                        type="button"
                        onClick={submit}
                        disabled={form.processing || form.data.name.trim() === ''}
                        className="w-full rounded-full"
                    >
                        {form.processing ? (
                            <Loader2 className="size-4 animate-spin" />
                        ) : null}
                        Simpan task
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        onClick={() => onOpenChange(false)}
                        className="w-full rounded-full"
                    >
                        Tutup
                    </Button>
                </DrawerFooter>
            </DrawerContent>
        </Drawer>
    );
}
