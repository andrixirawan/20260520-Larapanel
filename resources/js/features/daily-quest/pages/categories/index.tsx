import { Head, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import ColorPicker from '@/features/daily-quest/components/color-picker';
import EmojiPicker from '@/features/daily-quest/components/emoji-picker';
import type { TaskCategory } from '@/features/daily-quest/types';

type CategoriesPageProps = {
    categories: TaskCategory[];
};

type CategoryFormData = {
    name: string;
    color: string;
    icon: string;
};

function toCategoryFormData(category?: TaskCategory | null): CategoryFormData {
    return {
        name: category?.name ?? '',
        color: category?.color ?? '',
        icon: category?.icon ?? '',
    };
}

export default function DailyQuestCategoriesIndex({
    categories,
}: CategoriesPageProps) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [emojiOpen, setEmojiOpen] = useState(false);
    const [editingCategory, setEditingCategory] = useState<TaskCategory | null>(
        null,
    );
    const [deleteTarget, setDeleteTarget] = useState<TaskCategory | null>(null);
    const form = useForm<CategoryFormData>(toCategoryFormData());
    const deleteForm = useForm({});

    const openCreate = () => {
        setEditingCategory(null);
        form.setData(toCategoryFormData());
        form.clearErrors();
        setDialogOpen(true);
    };

    const openEdit = (category: TaskCategory) => {
        setEditingCategory(category);
        form.setData(toCategoryFormData(category));
        form.clearErrors();
        setDialogOpen(true);
    };

    const submit = () => {
        if (editingCategory) {
            form.patch(`/categories/${editingCategory.id}`, {
                preserveScroll: true,
                onSuccess: () => setDialogOpen(false),
            });

            return;
        }

        form.post('/categories', {
            preserveScroll: true,
            onSuccess: () => setDialogOpen(false),
        });
    };

    return (
        <>
            <Head title="Categories" />

            <div className="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 px-4 py-5 sm:px-6">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <Heading
                        title="Task Categories"
                        description="Kelola kategori agar task lebih cepat dikenali lewat ikon, warna, dan grouping yang konsisten."
                    />

                    <Button
                        type="button"
                        className="rounded-full"
                        onClick={openCreate}
                    >
                        <Plus className="size-4" />
                        Tambah kategori
                    </Button>
                </div>

                {categories.length === 0 ? (
                    <Card className="rounded-[2rem] border border-dashed py-0 shadow-sm">
                        <CardContent className="px-5 py-8 sm:px-6">
                            <p className="text-lg font-semibold">
                                Belum ada kategori
                            </p>
                            <p className="mt-2 text-sm text-muted-foreground">
                                Mulai dari kategori sederhana seperti Health,
                                Focus, atau Learning.
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2">
                        {categories.map((category) => (
                            <Card
                                key={category.id}
                                className="rounded-[2rem] border bg-card/90 py-0 shadow-sm"
                            >
                                <CardContent className="space-y-4 px-5 py-5">
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="flex items-center gap-3">
                                            <div
                                                className="flex size-12 items-center justify-center rounded-2xl text-xl text-white"
                                                style={{
                                                    backgroundColor:
                                                        category.color ??
                                                        '#0f172a',
                                                }}
                                            >
                                                {category.icon ?? '🗂️'}
                                            </div>

                                            <div>
                                                <p className="text-lg font-semibold">
                                                    {category.name}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    {category.tasks_count ?? 0}{' '}
                                                    task
                                                </p>
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-2">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="icon"
                                                className="rounded-full"
                                                onClick={() =>
                                                    openEdit(category)
                                                }
                                            >
                                                <Pencil className="size-4" />
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="icon"
                                                className="rounded-full text-red-600 hover:text-red-700"
                                                onClick={() =>
                                                    setDeleteTarget(category)
                                                }
                                            >
                                                <Trash2 className="size-4" />
                                            </Button>
                                        </div>
                                    </div>

                                    <div className="flex flex-wrap gap-2">
                                        <Badge
                                            variant="outline"
                                            className="rounded-full"
                                        >
                                            {category.color || 'Tanpa warna'}
                                        </Badge>
                                        <Badge
                                            variant="outline"
                                            className="rounded-full"
                                        >
                                            {category.icon || 'Tanpa ikon'}
                                        </Badge>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>

            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent className="max-w-xl">
                    <DialogHeader>
                        <DialogTitle>
                            {editingCategory
                                ? 'Edit kategori'
                                : 'Tambah kategori'}
                        </DialogTitle>
                        <DialogDescription>
                            Tentukan nama, ikon, dan warna supaya task dalam
                            kategori ini konsisten secara visual.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="category-name">Nama kategori</Label>
                            <Input
                                id="category-name"
                                value={form.data.name}
                                onChange={(event) =>
                                    form.setData('name', event.target.value)
                                }
                                className="rounded-2xl"
                                placeholder="Contoh: Health"
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        <div className="space-y-2">
                            <Label>Ikon</Label>
                            <Button
                                type="button"
                                variant="outline"
                                className="w-full justify-between rounded-2xl"
                                onClick={() => setEmojiOpen(true)}
                            >
                                <span>
                                    {form.data.icon
                                        ? `${form.data.icon} Dipilih`
                                        : 'Pilih emoji'}
                                </span>
                            </Button>
                            <InputError message={form.errors.icon} />
                        </div>

                        <div className="space-y-2">
                            <Label>Warna</Label>
                            <ColorPicker
                                value={form.data.color}
                                onChange={(value) =>
                                    form.setData('color', value)
                                }
                            />
                            <InputError message={form.errors.color} />
                        </div>

                        <div className="flex gap-2">
                            <Button
                                type="button"
                                className="flex-1 rounded-full"
                                disabled={form.processing}
                                onClick={submit}
                            >
                                {editingCategory
                                    ? 'Simpan perubahan'
                                    : 'Buat kategori'}
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                className="rounded-full"
                                onClick={() => setDialogOpen(false)}
                            >
                                Batal
                            </Button>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>

            <EmojiPicker
                open={emojiOpen}
                onOpenChange={setEmojiOpen}
                value={form.data.icon}
                onSelect={(value) => form.setData('icon', value)}
            />

            <AlertDialog
                open={deleteTarget !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeleteTarget(null);
                    }
                }}
            >
                <AlertDialogContent size="sm">
                    <AlertDialogHeader>
                        <AlertDialogTitle>Hapus kategori ini?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Kategori akan dihapus permanen. Pastikan task yang
                            memakainya memang sudah siap dipindahkan.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Batal</AlertDialogCancel>
                        <AlertDialogAction
                            variant="destructive"
                            onClick={() => {
                                if (!deleteTarget) {
                                    return;
                                }

                                deleteForm.delete(
                                    `/categories/${deleteTarget.id}`,
                                    {
                                        preserveScroll: true,
                                        onSuccess: () => setDeleteTarget(null),
                                    },
                                );
                            }}
                        >
                            Hapus
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}

DailyQuestCategoriesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Categories',
            href: '/categories',
        },
    ],
};
