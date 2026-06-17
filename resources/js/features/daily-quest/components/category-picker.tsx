import { Check, ChevronsUpDown, FolderSearch } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Drawer,
    DrawerContent,
    DrawerDescription,
    DrawerHeader,
    DrawerTitle,
} from '@/components/ui/drawer';
import type { TaskCategory } from '@/features/daily-quest/types';
import { categoryLabel } from '@/features/daily-quest/utils';
import { cn } from '@/lib/utils';

type CategoryPickerProps = {
    categories: TaskCategory[];
    value: string;
    onChange: (value: string) => void;
};

export default function CategoryPicker({
    categories,
    value,
    onChange,
}: CategoryPickerProps) {
    const [open, setOpen] = useState(false);
    const selectedCategory =
        categories.find((category) => category.public_id === value) ?? null;

    const sortedCategories = useMemo(
        () => [...categories].sort((left, right) => left.name.localeCompare(right.name)),
        [categories],
    );

    return (
        <>
            <Button
                type="button"
                variant="outline"
                className="w-full justify-between rounded-2xl"
                onClick={() => setOpen(true)}
            >
                <span className="truncate">
                    {selectedCategory ? categoryLabel(selectedCategory) : 'Pilih kategori'}
                </span>
                <ChevronsUpDown className="size-4 text-muted-foreground" />
            </Button>

            <Drawer open={open} onOpenChange={setOpen}>
                <DrawerContent className="mx-auto max-w-2xl">
                    <DrawerHeader className="text-left">
                        <DrawerTitle>Pilih kategori</DrawerTitle>
                        <DrawerDescription>
                            Cari kategori lalu pilih satu yang paling relevan untuk task ini.
                        </DrawerDescription>
                    </DrawerHeader>

                    <div className="px-4 pb-4">
                        <Command className="rounded-2xl border">
                            <CommandInput placeholder="Cari kategori..." />
                            <CommandList className="max-h-72">
                                <CommandEmpty>
                                    <div className="flex flex-col items-center gap-2 py-6 text-center text-sm text-muted-foreground">
                                        <FolderSearch className="size-5" />
                                        Tidak ada kategori yang cocok.
                                    </div>
                                </CommandEmpty>

                                <CommandItem
                                    value="Tanpa kategori"
                                    onSelect={() => {
                                        onChange('');
                                        setOpen(false);
                                    }}
                                >
                                    <Check
                                        className={cn(
                                            'size-4',
                                            value === '' ? 'opacity-100' : 'opacity-0',
                                        )}
                                    />
                                    Tanpa kategori
                                </CommandItem>

                                {sortedCategories.map((category) => (
                                    <CommandItem
                                        key={category.public_id}
                                        value={`${category.name} ${category.icon ?? ''}`}
                                        onSelect={() => {
                                            onChange(category.public_id);
                                            setOpen(false);
                                        }}
                                    >
                                        <Check
                                            className={cn(
                                                'size-4',
                                                value === category.public_id
                                                    ? 'opacity-100'
                                                    : 'opacity-0',
                                            )}
                                        />
                                        <span className="truncate">
                                            {categoryLabel(category)}
                                        </span>
                                    </CommandItem>
                                ))}
                            </CommandList>
                        </Command>
                    </div>
                </DrawerContent>
            </Drawer>
        </>
    );
}
