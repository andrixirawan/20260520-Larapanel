import { router } from '@inertiajs/react';
import { Copy, PauseCircle, Pencil, PlayCircle, Trash2 } from 'lucide-react';
import { useState } from 'react';
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
import { Button } from '@/components/ui/button';
import {
    Drawer,
    DrawerContent,
    DrawerDescription,
    DrawerFooter,
    DrawerHeader,
    DrawerTitle,
} from '@/components/ui/drawer';
import type { DailyQuestTask } from '@/features/daily-quest/types';
import { dailyQuestId } from '@/features/daily-quest/utils';

type TaskActionsDrawerProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    task: DailyQuestTask | null;
    redirectTo: string;
};

export default function TaskActionsDrawer({
    open,
    onOpenChange,
    task,
    redirectTo,
}: TaskActionsDrawerProps) {
    const [confirmDeleteOpen, setConfirmDeleteOpen] = useState(false);

    if (!task) {
        return null;
    }

    const taskId = dailyQuestId(task);
    const closeDrawer = () => onOpenChange(false);

    return (
        <>
            <Drawer open={open} onOpenChange={onOpenChange}>
                <DrawerContent className="mx-auto max-w-2xl">
                    <DrawerHeader className="text-left">
                        <DrawerTitle>{task.name}</DrawerTitle>
                        <DrawerDescription>
                            Pilih aksi cepat tanpa meninggalkan daftar task.
                        </DrawerDescription>
                    </DrawerHeader>

                    <div className="grid gap-2 px-4 pb-2">
                        <Button
                            type="button"
                            variant="outline"
                            className="justify-start rounded-2xl"
                            onClick={() => {
                                closeDrawer();
                                router.visit(`/tasks/${taskId}/edit`);
                            }}
                        >
                            <Pencil className="size-4" />
                            Edit task
                        </Button>

                        {!task.deleted_at ? (
                            <Button
                                type="button"
                                variant="outline"
                                className="justify-start rounded-2xl"
                                onClick={() => {
                                    closeDrawer();
                                    router.post(
                                        `/tasks/${taskId}/pause`,
                                        { redirect_to: redirectTo },
                                        { preserveScroll: true },
                                    );
                                }}
                            >
                                {task.is_active ? (
                                    <PauseCircle className="size-4" />
                                ) : (
                                    <PlayCircle className="size-4" />
                                )}
                                {task.is_active ? 'Jeda task' : 'Aktifkan task'}
                            </Button>
                        ) : null}

                        <Button
                            type="button"
                            variant="outline"
                            className="justify-start rounded-2xl"
                            onClick={() => {
                                closeDrawer();
                                router.post(
                                    `/tasks/${taskId}/duplicate`,
                                    { redirect_to: redirectTo },
                                    { preserveScroll: true },
                                );
                            }}
                        >
                            <Copy className="size-4" />
                            Duplikat task
                        </Button>

                        {!task.deleted_at ? (
                            <Button
                                type="button"
                                variant="outline"
                                className="justify-start rounded-2xl text-red-600 hover:text-red-700 dark:text-red-400"
                                onClick={() => setConfirmDeleteOpen(true)}
                            >
                                <Trash2 className="size-4" />
                                Hapus task
                            </Button>
                        ) : null}
                    </div>

                    <DrawerFooter className="border-t bg-background/80 backdrop-blur-sm">
                        <Button
                            type="button"
                            variant="ghost"
                            className="rounded-full"
                            onClick={closeDrawer}
                        >
                            Tutup
                        </Button>
                    </DrawerFooter>
                </DrawerContent>
            </Drawer>

            <AlertDialog
                open={confirmDeleteOpen}
                onOpenChange={setConfirmDeleteOpen}
            >
                <AlertDialogContent size="sm">
                    <AlertDialogHeader>
                        <AlertDialogTitle>Hapus task ini?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Task akan dipindahkan ke arsip dan tidak lagi
                            digenerate untuk hari berikutnya.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Batal</AlertDialogCancel>
                        <AlertDialogAction
                            variant="destructive"
                            onClick={() => {
                                setConfirmDeleteOpen(false);
                                closeDrawer();
                                router.post(
                                    `/tasks/${taskId}/delete`,
                                    { redirect_to: redirectTo },
                                    {
                                        preserveScroll: true,
                                    },
                                );
                            }}
                        >
                            Hapus task
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}
