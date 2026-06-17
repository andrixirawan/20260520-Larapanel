import { Search } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { taskEmojiGroups } from '@/features/daily-quest/utils';

type EmojiPickerProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    value: string;
    onSelect: (value: string) => void;
};

export default function EmojiPicker({
    open,
    onOpenChange,
    value,
    onSelect,
}: EmojiPickerProps) {
    const [query, setQuery] = useState('');

    const emojis = useMemo(() => {
        const flattened = taskEmojiGroups.flat();

        if (query.trim() === '') {
            return flattened;
        }

        return flattened.filter((emoji) => emoji.includes(query.trim()));
    }, [query]);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-xl">
                <DialogHeader>
                    <DialogTitle>Pilih ikon task</DialogTitle>
                    <DialogDescription>
                        Simpan satu emoji sebagai identitas task agar lebih cepat
                        dikenali.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4">
                    <div className="relative">
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={query}
                            onChange={(event) => setQuery(event.target.value)}
                            placeholder="Cari emoji"
                            className="pl-9"
                        />
                    </div>

                    <div className="grid max-h-80 grid-cols-5 gap-2 overflow-y-auto pr-1 sm:grid-cols-10">
                        <Button
                            type="button"
                            variant={value === '' ? 'default' : 'outline'}
                            className="h-12 rounded-2xl"
                            onClick={() => {
                                onSelect('');
                                onOpenChange(false);
                            }}
                        >
                            None
                        </Button>

                        {emojis.map((emoji) => (
                            <Button
                                key={emoji}
                                type="button"
                                variant={value === emoji ? 'default' : 'outline'}
                                className="h-12 rounded-2xl text-2xl"
                                onClick={() => {
                                    onSelect(emoji);
                                    onOpenChange(false);
                                }}
                            >
                                {emoji}
                            </Button>
                        ))}
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
