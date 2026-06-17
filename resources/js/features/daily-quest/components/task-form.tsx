import { useForm } from '@inertiajs/react';
import { addDays, format, parseISO } from 'date-fns';
import { Loader2, Save, Sparkles } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import CategoryPicker from '@/features/daily-quest/components/category-picker';
import ColorPicker from '@/features/daily-quest/components/color-picker';
import EmojiPicker from '@/features/daily-quest/components/emoji-picker';
import RecurrenceForm from '@/features/daily-quest/components/recurrence-form';
import type {
    DailyQuestTask,
    RecurrenceType,
    TaskCategory,
} from '@/features/daily-quest/types';
import {
    buildTaskRecurrenceSummary,
    categoryLabel,
    todayDate,
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

type TaskFormProps = {
    mode: 'create' | 'edit';
    categories: TaskCategory[];
    recurrenceTypes: RecurrenceOption[];
    task?: DailyQuestTask;
};

type TaskFormData = {
    category_public_id: string;
    name: string;
    description: string;
    icon: string;
    color: string;
    points: number;
    recurrence_type: RecurrenceType;
    recurrence_days: string[];
    recurrence_starts_at: string;
    recurrence_ends_at: string;
    is_active: boolean;
    redirect_to: string;
    x_days_span: string;
};

function toInitialData(task?: DailyQuestTask): TaskFormData {
    const startsAt = task?.recurrence_starts_at ?? '';
    const endsAt = task?.recurrence_ends_at ?? '';

    return {
        category_public_id: task?.category?.public_id ?? '',
        name: task?.name ?? '',
        description: task?.description ?? '',
        icon: task?.icon ?? '',
        color: task?.color ?? task?.category?.color ?? '',
        points: task?.points ?? 10,
        recurrence_type: task?.recurrence_type ?? 'daily',
        recurrence_days: task?.recurrence_days ?? [],
        recurrence_starts_at: startsAt,
        recurrence_ends_at: endsAt,
        is_active: task?.is_active ?? true,
        redirect_to: '/tasks',
        x_days_span:
            task?.recurrence_type === 'x_days' && startsAt && endsAt
                ? String(
                      Math.max(
                          Math.round(
                              (parseISO(endsAt).getTime() - parseISO(startsAt).getTime()) /
                                  86_400_000,
                          ) + 1,
                          1,
                      ),
                  )
                : '',
    };
}

export default function TaskForm({
    mode,
    categories,
    recurrenceTypes,
    task,
}: TaskFormProps) {
    const [emojiOpen, setEmojiOpen] = useState(false);
    const form = useForm<TaskFormData>(toInitialData(task));
    const selectedCategory =
        categories.find((category) => category.public_id === form.data.category_public_id) ??
        null;
    const handleRecurrenceChange = <K extends keyof RecurrenceValues>(
        key: K,
        value: RecurrenceValues[K],
    ) => {
        form.setData(key as keyof TaskFormData, value as never);
    };

    const recurrenceSummary = buildTaskRecurrenceSummary({
        recurrence_type: form.data.recurrence_type,
        recurrence_days: form.data.recurrence_days,
        recurrence_starts_at: form.data.recurrence_starts_at,
        recurrence_ends_at: form.data.recurrence_ends_at,
        x_days_span: form.data.x_days_span,
    });

    const submit = () => {
        form.transform((data) => {
            const payload: Record<string, unknown> = {
                category_public_id: data.category_public_id || null,
                name: data.name.trim(),
                description: data.description.trim() || null,
                icon: data.icon || null,
                color: data.color || null,
                points: Number(data.points),
                recurrence_type: data.recurrence_type,
                recurrence_days:
                    data.recurrence_type === 'specific_days'
                        ? data.recurrence_days
                        : [],
                recurrence_starts_at: null,
                recurrence_ends_at: null,
                is_active: data.is_active,
                redirect_to: data.redirect_to,
            };

            if (data.recurrence_type === 'one_time') {
                payload.recurrence_starts_at = data.recurrence_starts_at || null;
            }

            if (data.recurrence_type === 'x_days') {
                const startDate = data.recurrence_starts_at || todayDate();
                const span = Math.max(Number(data.x_days_span) || 0, 1);

                payload.recurrence_starts_at = startDate;
                payload.recurrence_ends_at = format(
                    addDays(parseISO(startDate), span - 1),
                    'yyyy-MM-dd',
                );
            }

            if (data.recurrence_type === 'date_range') {
                payload.recurrence_starts_at = data.recurrence_starts_at || null;
                payload.recurrence_ends_at = data.recurrence_ends_at || null;
            }

            return payload;
        });

        if (mode === 'create') {
            form.post('/tasks', { preserveScroll: true });

            return;
        }

        form.patch(`/tasks/${task?.public_id}`, { preserveScroll: true });
    };

    return (
        <>
            <div className="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-6 px-4 py-5 sm:px-6">
                <Heading
                    title={mode === 'create' ? 'Buat task baru' : 'Edit task'}
                    description={
                        mode === 'create'
                            ? 'Simpan task dengan identitas visual, kategori, dan aturan recurrence yang jelas.'
                            : 'Perbarui detail task tanpa mengubah ritme yang sudah berjalan.'
                    }
                />

                <Card className="overflow-hidden rounded-[2rem] border-0 bg-[linear-gradient(135deg,#f59e0b_0%,#fb7185_55%,#0ea5e9_100%)] py-0 text-white shadow-xl">
                    <CardContent className="flex flex-col gap-4 px-5 py-5 sm:px-6">
                        <div className="flex items-center gap-4">
                            <button
                                type="button"
                                className="flex size-16 items-center justify-center rounded-[1.5rem] bg-white/15 text-3xl backdrop-blur-sm"
                                onClick={() => setEmojiOpen(true)}
                            >
                                {form.data.icon || '📝'}
                            </button>

                            <div className="space-y-1">
                                <p className="text-xs uppercase tracking-[0.22em] text-white/70">
                                    Preview
                                </p>
                                <p className="text-2xl font-semibold">
                                    {form.data.name.trim() || 'Nama task'}
                                </p>
                                <p className="text-sm text-white/80">
                                    {recurrenceSummary} • {form.data.points} pts
                                </p>
                            </div>
                        </div>

                        <div className="flex flex-wrap items-center gap-2 text-sm text-white/85">
                            <Badge className="rounded-full bg-white/15 text-white hover:bg-white/15">
                                {selectedCategory
                                    ? categoryLabel(selectedCategory)
                                    : 'Tanpa kategori'}
                            </Badge>
                            <Badge className="rounded-full bg-white/15 text-white hover:bg-white/15">
                                {form.data.color || 'Tanpa warna kustom'}
                            </Badge>
                        </div>
                    </CardContent>
                </Card>

                <section className="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
                    <div className="space-y-6">
                        <Card className="rounded-[2rem] border bg-card/90 py-0 shadow-sm">
                            <CardContent className="space-y-5 p-5">
                                <div className="space-y-2">
                                    <Label htmlFor="task-name">Nama task</Label>
                                    <Input
                                        id="task-name"
                                        value={form.data.name}
                                        onChange={(event) =>
                                            form.setData('name', event.target.value)
                                        }
                                        placeholder="Contoh: Baca 10 halaman"
                                        className="rounded-2xl"
                                    />
                                    <InputError message={form.errors.name} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="task-description">Deskripsi</Label>
                                    <Textarea
                                        id="task-description"
                                        value={form.data.description}
                                        onChange={(event) =>
                                            form.setData(
                                                'description',
                                                event.target.value,
                                            )
                                        }
                                        rows={4}
                                        placeholder="Tambahkan konteks singkat atau target spesifik."
                                        className="rounded-2xl"
                                    />
                                    <InputError message={form.errors.description} />
                                </div>
                            </CardContent>
                        </Card>

                        <RecurrenceForm
                            options={recurrenceTypes}
                            values={{
                                recurrence_type: form.data.recurrence_type,
                                recurrence_days: form.data.recurrence_days,
                                recurrence_starts_at: form.data.recurrence_starts_at,
                                recurrence_ends_at: form.data.recurrence_ends_at,
                                x_days_span: form.data.x_days_span,
                            }}
                            errors={form.errors}
                            onChange={handleRecurrenceChange}
                        />
                    </div>

                    <div className="space-y-6">
                        <Card className="rounded-[2rem] border bg-card/90 py-0 shadow-sm">
                            <CardContent className="space-y-5 p-5">
                                <div className="space-y-2">
                                    <Label>Ikon</Label>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="w-full justify-between rounded-2xl"
                                        onClick={() => setEmojiOpen(true)}
                                    >
                                        <span className="text-left">
                                            {form.data.icon
                                                ? `${form.data.icon} Dipilih`
                                                : 'Pilih emoji'}
                                        </span>
                                        <Sparkles className="size-4" />
                                    </Button>
                                    <InputError message={form.errors.icon} />
                                </div>

                                <div className="space-y-2">
                                    <Label>Kategori</Label>
                                    <CategoryPicker
                                        categories={categories}
                                        value={form.data.category_public_id}
                                        onChange={(value) =>
                                            form.setData('category_public_id', value)
                                        }
                                    />
                                    <InputError
                                        message={form.errors.category_public_id}
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label>Warna aksen</Label>
                                    <ColorPicker
                                        value={form.data.color}
                                        onChange={(value) =>
                                            form.setData('color', value)
                                        }
                                    />
                                    <InputError message={form.errors.color} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="task-points">Poin</Label>
                                    <Input
                                        id="task-points"
                                        type="number"
                                        min={0}
                                        max={100000}
                                        value={form.data.points}
                                        onChange={(event) =>
                                            form.setData(
                                                'points',
                                                Number(event.target.value || 0),
                                            )
                                        }
                                        className="rounded-2xl"
                                    />
                                    <InputError message={form.errors.points} />
                                </div>

                                <div className="flex items-center justify-between rounded-2xl border p-4">
                                    <div>
                                        <p className="font-medium">Task aktif</p>
                                        <p className="text-sm text-muted-foreground">
                                            Task aktif akan ikut dijadwalkan oleh generator.
                                        </p>
                                    </div>
                                    <Switch
                                        checked={form.data.is_active}
                                        onCheckedChange={(checked) =>
                                            form.setData('is_active', checked)
                                        }
                                    />
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </section>
            </div>

            <div className="sticky bottom-0 z-20 border-t bg-background/90 backdrop-blur-sm">
                <div className="mx-auto flex max-w-4xl items-center justify-between gap-3 px-4 py-4 sm:px-6">
                    <p className="text-sm text-muted-foreground">
                        {mode === 'create'
                            ? 'Task baru akan langsung muncul di daftar tasks.'
                            : 'Perubahan recurrence berlaku untuk instance berikutnya.'}
                    </p>

                    <Button
                        type="button"
                        size="lg"
                        className="rounded-full px-6"
                        onClick={submit}
                        disabled={form.processing}
                    >
                        {form.processing ? (
                            <Loader2 className="size-4 animate-spin" />
                        ) : (
                            <Save className="size-4" />
                        )}
                        {mode === 'create' ? 'Simpan task' : 'Perbarui task'}
                    </Button>
                </div>
            </div>

            <EmojiPicker
                open={emojiOpen}
                onOpenChange={setEmojiOpen}
                value={form.data.icon}
                onSelect={(value) => form.setData('icon', value)}
            />
        </>
    );
}
