import { format, parseISO } from 'date-fns';
import { CalendarIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

type DatePickerProps = {
    value: string;
    onChange: (value: string) => void;
    placeholder: string;
    disabled?: (date: Date) => boolean;
};

export default function DatePicker({
    value,
    onChange,
    placeholder,
    disabled,
}: DatePickerProps) {
    return (
        <Popover>
            <PopoverTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    className={cn(
                        'w-full justify-between rounded-2xl',
                        ! value && 'text-muted-foreground',
                    )}
                >
                    {value ? format(parseISO(value), 'dd MMM yyyy') : placeholder}
                    <CalendarIcon className="size-4" />
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-auto p-0" align="start">
                <Calendar
                    mode="single"
                    selected={value ? parseISO(value) : undefined}
                    onSelect={(date) =>
                        onChange(date ? format(date, 'yyyy-MM-dd') : '')
                    }
                    disabled={disabled}
                />
            </PopoverContent>
        </Popover>
    );
}
