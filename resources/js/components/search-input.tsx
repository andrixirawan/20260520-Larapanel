import { Search, X } from 'lucide-react';
import type { ComponentProps } from 'react';
import { useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

type SearchInputProps = Omit<
    ComponentProps<typeof Input>,
    'type' | 'value' | 'defaultValue' | 'onChange'
> & {
    value?: string;
    defaultValue?: string;
    onValueChange?: (value: string) => void;
    inputClassName?: string;
};

export function SearchInput({
    value,
    defaultValue = '',
    onValueChange,
    className,
    inputClassName,
    name = 'search',
    ...props
}: SearchInputProps) {
    const [internalValue, setInternalValue] = useState(() => defaultValue);
    const inputRef = useRef<HTMLInputElement>(null);
    const currentValue = value ?? internalValue;

    const setSearchValue = (nextValue: string) => {
        if (value === undefined) {
            setInternalValue(nextValue);
        }

        onValueChange?.(nextValue);
    };

    return (
        <div className={cn('relative min-w-0', className)}>
            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input
                ref={inputRef}
                type="search"
                name={name}
                value={currentValue}
                onChange={(event) => setSearchValue(event.target.value)}
                className={cn('pr-9 pl-9', inputClassName)}
                {...props}
            />
            {currentValue ? (
                <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    className="absolute top-1/2 right-1 size-7 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                    onClick={() => {
                        setSearchValue('');
                        inputRef.current?.focus();
                    }}
                >
                    <X />
                    <span className="sr-only">Clear search</span>
                </Button>
            ) : null}
        </div>
    );
}
