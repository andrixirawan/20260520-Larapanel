import { Link } from '@inertiajs/react';
import { FileText, Forward, StickyNote } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import {
    Drawer,
    DrawerContent,
    DrawerDescription,
    DrawerFooter,
    DrawerHeader,
    DrawerTitle,
} from '@/components/ui/drawer';
import { Textarea } from '@/components/ui/textarea';
import type { TaskInstance } from '@/features/daily-quest/types';

type TaskInstanceOptionsDrawerProps = {
    instance: TaskInstance | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export default function TaskInstanceOptionsDrawer({
    instance,
    open,
    onOpenChange,
}: TaskInstanceOptionsDrawerProps) {
    const [draftNotes, setDraftNotes] = useState(instance?.notes ?? '');

    useEffect(() => {
        setDraftNotes(instance?.notes ?? '');
    }, [instance]);

    if (! instance) {
        return null;
    }

    return (
        <Drawer open={open} onOpenChange={onOpenChange}>
            <DrawerContent className="mx-auto max-w-2xl">
                <DrawerHeader className="text-left">
                    <DrawerTitle>{instance.task?.name ?? 'Task options'}</DrawerTitle>
                    <DrawerDescription>
                        Aksi cepat untuk task hari ini. Tekan lama kartu task untuk
                        membuka drawer ini kapan saja.
                    </DrawerDescription>
                </DrawerHeader>

                <div className="space-y-5 px-4 pb-2">
                    <div className="space-y-2">
                        <div className="flex items-center gap-2 text-sm font-medium">
                            <StickyNote className="size-4" />
                            Catatan cepat
                        </div>
                        <Textarea
                            value={draftNotes}
                            onChange={(event) => setDraftNotes(event.target.value)}
                            placeholder="Catatan singkat untuk task ini"
                            rows={4}
                        />
                        <p className="text-xs text-muted-foreground">
                            Endpoint simpan catatan belum ada, jadi drawer ini
                            menyiapkan UX dan input untuk phase berikutnya.
                        </p>
                    </div>

                    <div className="grid gap-2 sm:grid-cols-2">
                        <Button
                            type="button"
                            variant="outline"
                            className="justify-start rounded-2xl"
                            onClick={() => {
                                toast.info('Skip flow akan disambungkan setelah endpoint backend tersedia.');
                            }}
                        >
                            <Forward className="size-4" />
                            Skip untuk hari ini
                        </Button>

                        <Button
                            type="button"
                            variant="outline"
                            className="justify-start rounded-2xl"
                            onClick={() => {
                                toast.info(
                                    draftNotes.trim() === ''
                                        ? 'Tulis catatan dulu sebelum menyimpannya.'
                                        : 'Simpan catatan akan dihubungkan pada phase backend berikutnya.',
                                );
                            }}
                        >
                            <StickyNote className="size-4" />
                            Simpan draft catatan
                        </Button>
                    </div>
                </div>

                <DrawerFooter className="border-t bg-background/80 backdrop-blur-sm">
                    <Button asChild className="w-full rounded-full">
                        <Link href={`/tasks/${instance.task?.public_id}`}>
                            <FileText className="size-4" />
                            Lihat detail task
                        </Link>
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        className="w-full rounded-full"
                        onClick={() => onOpenChange(false)}
                    >
                        Selesai
                    </Button>
                </DrawerFooter>
            </DrawerContent>
        </Drawer>
    );
}
