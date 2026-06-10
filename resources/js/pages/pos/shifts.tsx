import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { Paginated } from './types';

type ShiftRow = {
    id: number;
    cashier: string | null;
    opened_by: string | null;
    closed_by: string | null;
    status: string;
    opening_cash: number;
    expected_cash: number | null;
    counted_cash: number | null;
    cash_difference: number | null;
    opened_at: string | null;
    closed_at: string | null;
};

const currency = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

export default function PosShifts({ shifts }: { shifts: Paginated<ShiftRow> }) {
    return (
        <>
            <Head title="POS Shifts" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        title="POS Shifts"
                        description="Cash opening, closing, and reconciliation per cashier shift."
                    />
                    <Button asChild variant="outline" className="w-fit">
                        <Link href="/pos">Open terminal</Link>
                    </Button>
                </div>

                <div className="overflow-hidden rounded-lg border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Shift</TableHead>
                                <TableHead>Cashier</TableHead>
                                <TableHead>Cash</TableHead>
                                <TableHead>Difference</TableHead>
                                <TableHead>Timeline</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {shifts.data.length ? (
                                shifts.data.map((shift) => (
                                    <TableRow key={shift.id}>
                                        <TableCell>
                                            <div className="font-medium">
                                                #{shift.id}
                                            </div>
                                            <Badge
                                                variant={
                                                    shift.status === 'open'
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {shift.status}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <div>{shift.cashier ?? '-'}</div>
                                            <div className="text-xs text-muted-foreground">
                                                Opened by{' '}
                                                {shift.opened_by ?? '-'}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div>
                                                Opening:{' '}
                                                {currency.format(
                                                    shift.opening_cash,
                                                )}
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                Expected:{' '}
                                                {shift.expected_cash === null
                                                    ? '-'
                                                    : currency.format(
                                                          shift.expected_cash,
                                                      )}
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                Counted:{' '}
                                                {shift.counted_cash === null
                                                    ? '-'
                                                    : currency.format(
                                                          shift.counted_cash,
                                                      )}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {shift.cash_difference === null ? (
                                                <span className="text-muted-foreground">
                                                    -
                                                </span>
                                            ) : (
                                                <Badge
                                                    variant={
                                                        shift.cash_difference ===
                                                        0
                                                            ? 'outline'
                                                            : 'destructive'
                                                    }
                                                >
                                                    {currency.format(
                                                        shift.cash_difference,
                                                    )}
                                                </Badge>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <div className="text-xs">
                                                Opened:{' '}
                                                {shift.opened_at
                                                    ? new Date(
                                                          shift.opened_at,
                                                      ).toLocaleString('id-ID')
                                                    : '-'}
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                Closed:{' '}
                                                {shift.closed_at
                                                    ? new Date(
                                                          shift.closed_at,
                                                      ).toLocaleString('id-ID')
                                                    : '-'}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
                                        className="h-32 text-center text-muted-foreground"
                                    >
                                        No shifts yet.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </>
    );
}

PosShifts.layout = {
    breadcrumbs: [
        {
            title: 'POS Shifts',
            href: '/pos/shifts',
        },
    ],
};
