import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import DatePicker from '@/features/daily-quest/components/date-picker';
import type { RecurrenceType } from '@/features/daily-quest/types';
import {
    buildTaskRecurrenceSummary,
    recurrenceDayOptions,
} from '@/features/daily-quest/utils';

type RecurrenceOption = {
    value: RecurrenceType;
    label: string;
};

type RecurrenceValues = {
    recurrence_type: RecurrenceType;
    recurrence_days: string[];
    recurrence_starts_at: string;
    recurrence_ends_at: string;
    x_days_span: string;
};

type RecurrenceFormProps = {
    options: RecurrenceOption[];
    values: RecurrenceValues;
    errors: Partial<Record<string, string>>;
    onChange: <K extends keyof RecurrenceValues>(
        key: K,
        value: RecurrenceValues[K],
    ) => void;
};

export default function RecurrenceForm({
    options,
    values,
    errors,
    onChange,
}: RecurrenceFormProps) {
    const summary = buildTaskRecurrenceSummary(values);

    return (
        <section className="space-y-4 rounded-[2rem] border bg-card/80 p-5 shadow-sm backdrop-blur-sm">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 className="text-base font-semibold">Jadwal pengulangan</h3>
                    <p className="text-sm text-muted-foreground">
                        Pilih pola kemunculan task, lalu lengkapi input tambahan bila perlu.
                    </p>
                </div>

                <Badge variant="secondary" className="rounded-full px-3 py-1">
                    {summary}
                </Badge>
            </div>

            <div className="grid gap-3 sm:grid-cols-2">
                {options.map((option) => {
                    const active = values.recurrence_type === option.value;

                    return (
                        <button
                            key={option.value}
                            type="button"
                            className={`rounded-3xl border p-4 text-left transition-all duration-200 ${
                                active
                                    ? 'border-amber-400 bg-amber-50 shadow-sm dark:bg-amber-950/30'
                                    : 'border-border bg-background hover:border-amber-300 hover:bg-amber-50/50 dark:hover:bg-amber-950/10'
                            }`}
                            onClick={() => onChange('recurrence_type', option.value)}
                        >
                            <p className="font-medium">{option.label}</p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {option.value === 'daily'
                                    ? 'Task muncul setiap hari.'
                                    : option.value === 'specific_days'
                                      ? 'Pilih kombinasi hari yang konsisten.'
                                      : option.value === 'one_time'
                                        ? 'Task hanya muncul sekali.'
                                        : option.value === 'x_days'
                                          ? 'Task aktif selama sejumlah hari.'
                                          : 'Task aktif dalam rentang tanggal tertentu.'}
                            </p>
                        </button>
                    );
                })}
            </div>

            <div className="overflow-hidden rounded-3xl border bg-background/90 p-4 transition-all duration-200">
                {values.recurrence_type === 'specific_days' ? (
                    <div className="space-y-3">
                        <Label>Pilih hari</Label>
                        <div className="grid grid-cols-7 gap-2">
                            {recurrenceDayOptions.map((day) => {
                                const active = values.recurrence_days.includes(day.value);

                                return (
                                    <Button
                                        key={day.value}
                                        type="button"
                                        variant={active ? 'default' : 'outline'}
                                        className="h-11 rounded-2xl"
                                        onClick={() =>
                                            onChange(
                                                'recurrence_days',
                                                active
                                                    ? values.recurrence_days.filter(
                                                          (value) => value !== day.value,
                                                      )
                                                    : [...values.recurrence_days, day.value],
                                            )
                                        }
                                    >
                                        {day.shortLabel}
                                    </Button>
                                );
                            })}
                        </div>
                        <InputError message={errors.recurrence_days} />
                    </div>
                ) : null}

                {values.recurrence_type === 'one_time' ? (
                    <div className="space-y-3">
                        <Label>Tanggal eksekusi</Label>
                        <DatePicker
                            value={values.recurrence_starts_at}
                            onChange={(value) => onChange('recurrence_starts_at', value)}
                            placeholder="Pilih tanggal task"
                        />
                        <InputError message={errors.recurrence_starts_at} />
                    </div>
                ) : null}

                {values.recurrence_type === 'x_days' ? (
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-3">
                            <Label>Tanggal mulai</Label>
                            <DatePicker
                                value={values.recurrence_starts_at}
                                onChange={(value) => onChange('recurrence_starts_at', value)}
                                placeholder="Mulai dari"
                            />
                            <InputError message={errors.recurrence_starts_at} />
                        </div>

                        <div className="space-y-3">
                            <Label htmlFor="x-days-span">Durasi hari</Label>
                            <Input
                                id="x-days-span"
                                type="number"
                                min={1}
                                max={365}
                                value={values.x_days_span}
                                onChange={(event) =>
                                    onChange('x_days_span', event.target.value)
                                }
                                placeholder="Contoh: 7"
                                className="rounded-2xl"
                            />
                            <p className="text-xs text-muted-foreground">
                                End date akan dihitung otomatis saat disimpan.
                            </p>
                            <InputError message={errors.recurrence_ends_at} />
                        </div>
                    </div>
                ) : null}

                {values.recurrence_type === 'date_range' ? (
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-3">
                            <Label>Tanggal mulai</Label>
                            <DatePicker
                                value={values.recurrence_starts_at}
                                onChange={(value) => onChange('recurrence_starts_at', value)}
                                placeholder="Pilih tanggal mulai"
                            />
                            <InputError message={errors.recurrence_starts_at} />
                        </div>

                        <div className="space-y-3">
                            <Label>Tanggal selesai</Label>
                            <DatePicker
                                value={values.recurrence_ends_at}
                                onChange={(value) => onChange('recurrence_ends_at', value)}
                                placeholder="Pilih tanggal selesai"
                            />
                            <InputError message={errors.recurrence_ends_at} />
                        </div>
                    </div>
                ) : null}
            </div>
        </section>
    );
}
