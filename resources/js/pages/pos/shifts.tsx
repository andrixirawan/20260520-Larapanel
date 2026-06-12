import { Head, Link, router, usePage } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { CheckCircle2, Clock3, Loader2, ShieldAlert, Wallet } from 'lucide-react';
import { useMemo, useState } from 'react';
import { toast } from 'sonner';
import { DataTable } from '@/components/data-table';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import type { Auth } from '@/types';
import type { TableFilters } from '@/types/pagination';
import type { Paginated } from './types';
import { firstErrorMessage } from './utils';
import { formatPosDateTime, posCurrency } from './utils';

type ShiftRow = {
    public_id: string;
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
    handover_to_cashier: string | null;
    handover_requested_by: string | null;
    handover_approved_by: string | null;
    handover_requested_at: string | null;
    handover_approved_at: string | null;
    handover_notes: string | null;
    requires_handover_approval: boolean;
};

export default function PosShifts({
    shifts,
    filters,
    handoverDifferenceThreshold,
}: {
    shifts: Paginated<ShiftRow>;
    filters: TableFilters;
    handoverDifferenceThreshold: number;
}) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const canManageShifts = auth.permissions['pos.shifts.manage'];
    const [approvalDialogOpen, setApprovalDialogOpen] = useState(false);
    const [approvalNotes, setApprovalNotes] = useState('');
    const [selectedShift, setSelectedShift] = useState<ShiftRow | null>(null);
    const [isApproving, setIsApproving] = useState(false);
    const openCount = shifts.data.filter((shift) => shift.status === 'open').length;
    const diffCount = shifts.data.filter(
        (shift) => shift.cash_difference !== null && shift.cash_difference !== 0,
    ).length;
    const pendingHandoverCount = shifts.data.filter(
        (shift) => shift.requires_handover_approval,
    ).length;
    const totalOpeningCash = shifts.data.reduce(
        (sum, shift) => sum + shift.opening_cash,
        0,
    );
    const approveHandover = () => {
        if (!selectedShift) {
            return;
        }

        router.patch(
            `/pos/shifts/${selectedShift.public_id}/handover-approval`,
            { notes: approvalNotes },
            {
                preserveScroll: true,
                onStart: () => setIsApproving(true),
                onError: (errors) => toast.error(firstErrorMessage(errors)),
                onSuccess: () => {
                    setApprovalDialogOpen(false);
                    setSelectedShift(null);
                    setApprovalNotes('');
                },
                onFinish: () => setIsApproving(false),
            },
        );
    };
    const columns = useMemo<ColumnDef<ShiftRow>[]>(
        () => [
            {
                id: 'id',
                accessorKey: 'id',
                header: 'Shift',
                cell: ({ row }) => (
                    <>
                        <div className="font-medium">
                            {row.original.public_id.slice(-8)}
                        </div>
                        <Badge
                            variant={
                                row.original.status === 'open'
                                    ? 'default'
                                    : row.original.status === 'handover_pending'
                                      ? 'destructive'
                                    : 'secondary'
                            }
                        >
                            {row.original.status}
                        </Badge>
                    </>
                ),
            },
            {
                id: 'cashier',
                header: 'Cashier',
                enableSorting: false,
                cell: ({ row }) => (
                    <>
                        <div>{row.original.cashier ?? '-'}</div>
                        <div className="text-xs text-muted-foreground">
                            Opened by {row.original.opened_by ?? '-'}
                        </div>
                        <div className="text-xs text-muted-foreground">
                            Closed by {row.original.closed_by ?? '-'}
                        </div>
                        {row.original.handover_to_cashier ? (
                            <div className="text-xs text-muted-foreground">
                                Handover to {row.original.handover_to_cashier}
                            </div>
                        ) : null}
                    </>
                ),
            },
            {
                id: 'opening_cash',
                accessorKey: 'opening_cash',
                header: 'Cash snapshot',
                cell: ({ row }) => (
                    <>
                        <div>
                            Opening:{' '}
                            {posCurrency.format(row.original.opening_cash)}
                        </div>
                        <div className="text-xs text-muted-foreground">
                            Expected:{' '}
                            {row.original.expected_cash === null
                                ? '-'
                                : posCurrency.format(row.original.expected_cash)}
                        </div>
                        <div className="text-xs text-muted-foreground">
                            Counted:{' '}
                            {row.original.counted_cash === null
                                ? '-'
                                : posCurrency.format(row.original.counted_cash)}
                        </div>
                        {row.original.requires_handover_approval ? (
                            <div className="text-xs text-amber-700">
                                Approval threshold {posCurrency.format(handoverDifferenceThreshold)}
                            </div>
                        ) : null}
                    </>
                ),
            },
            {
                id: 'cash_difference',
                accessorKey: 'cash_difference',
                header: 'Difference',
                cell: ({ row }) =>
                    row.original.cash_difference === null ? (
                        <span className="text-muted-foreground">-</span>
                    ) : (
                        <Badge
                            variant={
                                row.original.cash_difference === 0
                                    ? 'outline'
                                    : 'destructive'
                            }
                        >
                            {posCurrency.format(row.original.cash_difference)}
                        </Badge>
                    ),
            },
            {
                id: 'opened_at',
                accessorKey: 'opened_at',
                header: 'Timeline',
                cell: ({ row }) => (
                    <>
                        <div className="text-xs">
                            Opened: {formatPosDateTime(row.original.opened_at)}
                        </div>
                        <div className="text-xs text-muted-foreground">
                            Closed: {formatPosDateTime(row.original.closed_at)}
                        </div>
                        {row.original.handover_requested_at ? (
                            <div className="text-xs text-muted-foreground">
                                Handover: {formatPosDateTime(row.original.handover_requested_at)}
                            </div>
                        ) : null}
                    </>
                ),
            },
            {
                id: 'action',
                header: 'Action',
                enableSorting: false,
                meta: {
                    headerClassName: 'text-right',
                    cellClassName: 'text-right',
                },
                cell: ({ row }) => (
                    <>
                        {canManageShifts && row.original.requires_handover_approval ? (
                            <Button
                                size="sm"
                                onClick={() => {
                                    setSelectedShift(row.original);
                                    setApprovalDialogOpen(true);
                                }}
                            >
                                <CheckCircle2 />
                                Approve handover
                            </Button>
                        ) : (
                            <span className="text-xs text-muted-foreground">
                                {row.original.handover_approved_at
                                    ? `Approved ${formatPosDateTime(row.original.handover_approved_at)}`
                                    : '-'}
                            </span>
                        )}
                    </>
                ),
            },
        ],
        [canManageShifts, handoverDifferenceThreshold],
    );

    return (
        <>
            <Head title="POS Shifts" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <Heading
                        title="POS Shifts"
                        description="Cash opening, closing, and reconciliation snapshots per operator."
                    />
                    <Button asChild variant="outline" className="w-fit">
                        <Link href="/pos">
                            <Clock3 />
                            Open terminal
                        </Link>
                    </Button>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    {[
                        {
                            icon: Clock3,
                            label: 'Open shifts',
                            value: `${openCount}`,
                        },
                        {
                            icon: Wallet,
                            label: 'Visible opening cash',
                            value: posCurrency.format(totalOpeningCash),
                        },
                        {
                            icon: ShieldAlert,
                            label: 'Shifts with difference',
                            value: `${diffCount}`,
                        },
                        {
                            icon: CheckCircle2,
                            label: 'Pending handover approval',
                            value: `${pendingHandoverCount}`,
                        },
                    ].map((item) => (
                        <Card key={item.label}>
                            <CardContent className="flex items-center gap-4 p-5">
                                <div className="rounded-2xl bg-muted p-3">
                                    <item.icon className="size-5" />
                                </div>
                                <div>
                                    <div className="text-sm text-muted-foreground">
                                        {item.label}
                                    </div>
                                    <div className="text-lg font-semibold">
                                        {item.value}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Shift register</CardTitle>
                    </CardHeader>
                    <CardContent className="p-4">
                        <DataTable
                            columns={columns}
                            data={shifts}
                            filters={filters}
                            route="/pos/shifts"
                            searchPlaceholder="Search shifts"
                            emptyMessage="No shifts yet"
                            totalLabel="shifts"
                        />
                    </CardContent>
                </Card>
            </div>

            <Dialog open={approvalDialogOpen} onOpenChange={setApprovalDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Approve shift handover</DialogTitle>
                        <DialogDescription>
                            Admin approval ini akan menutup shift pending dan
                            membuka shift baru untuk cashier penerima.
                        </DialogDescription>
                    </DialogHeader>
                    {selectedShift ? (
                        <div className="space-y-4">
                            <div className="rounded-xl border bg-muted/40 p-4 text-sm">
                                <div className="font-medium">
                                    Shift {selectedShift.public_id.slice(-8)}
                                </div>
                                <div className="mt-2 text-muted-foreground">
                                    Cashier {selectedShift.cashier ?? '-'} ke{' '}
                                    {selectedShift.handover_to_cashier ?? '-'}
                                </div>
                                <div className="mt-3 flex items-center justify-between">
                                    <span className="text-muted-foreground">
                                        Difference
                                    </span>
                                    <span>
                                        {selectedShift.cash_difference === null
                                            ? '-'
                                            : posCurrency.format(selectedShift.cash_difference)}
                                    </span>
                                </div>
                            </div>
                            <Textarea
                                value={approvalNotes}
                                onChange={(event) =>
                                    setApprovalNotes(event.target.value)
                                }
                                placeholder="Approval note, optional"
                            />
                        </div>
                    ) : null}
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setApprovalDialogOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button onClick={approveHandover} disabled={isApproving}>
                            {isApproving ? (
                                <Loader2 className="animate-spin" />
                            ) : (
                                <CheckCircle2 />
                            )}
                            Approve
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
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
