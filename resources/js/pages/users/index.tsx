import { Head, useForm } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { useMemo } from 'react';
import { DataTable } from '@/components/data-table';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { Paginated, TableFilters } from '@/types/pagination';

type ManagedUser = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    roles: string[];
};

function UserUpdateForm({ user, roles }: { user: ManagedUser; roles: string[] }) {
    const form = useForm({
        name: user.name,
        email: user.email,
        role: user.roles[0] ?? 'subscriber',
    });

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                form.patch(`/users/${user.id}`, {
                    preserveScroll: true,
                });
            }}
        >
            <div className="grid gap-3 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_180px_auto]">
                <div className="space-y-1">
                    <Input
                        value={form.data.name}
                        onChange={(event) =>
                            form.setData('name', event.target.value)
                        }
                        placeholder="Full name"
                    />
                    <InputError message={form.errors.name} />
                </div>

                <div className="space-y-1">
                    <Input
                        type="email"
                        value={form.data.email}
                        onChange={(event) =>
                            form.setData('email', event.target.value)
                        }
                        placeholder="Email address"
                    />
                    <InputError message={form.errors.email} />
                </div>

                <div className="space-y-1">
                    <select
                        value={form.data.role}
                        onChange={(event) =>
                            form.setData('role', event.target.value)
                        }
                        className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    >
                        {roles.map((role) => (
                            <option key={role} value={role}>
                                {role}
                            </option>
                        ))}
                    </select>
                    <InputError message={form.errors.role} />
                </div>

                <div className="flex items-start justify-end">
                    <Button type="submit" disabled={form.processing}>
                        Save
                    </Button>
                </div>
            </div>
        </form>
    );
}

export default function UsersIndex({
    users,
    roles,
    filters,
}: {
    users: Paginated<ManagedUser>;
    roles: string[];
    filters: TableFilters;
}) {
    const columns = useMemo<ColumnDef<ManagedUser>[]>(
        () => [
            {
                id: 'name',
                accessorKey: 'name',
                header: 'User',
                meta: {
                    headerClassName: 'w-48',
                    cellClassName: 'align-top',
                },
                cell: ({ row }) => (
                    <>
                        <div className="font-medium">{row.original.name}</div>
                        <div className="text-sm text-muted-foreground">
                            #{row.original.id}
                        </div>
                    </>
                ),
            },
            {
                id: 'email',
                accessorKey: 'email',
                header: 'Email',
                meta: {
                    headerClassName: 'w-64',
                    cellClassName: 'align-top',
                },
                cell: ({ row }) => (
                    <div className="space-y-1">
                        <div>{row.original.email}</div>
                        <div className="text-xs text-muted-foreground">
                            {row.original.email_verified_at
                                ? 'Verified'
                                : 'Unverified'}
                        </div>
                    </div>
                ),
            },
            {
                id: 'roles',
                header: 'Current role',
                enableSorting: false,
                meta: {
                    headerClassName: 'w-48',
                    cellClassName: 'align-top',
                },
                cell: ({ row }) => (
                    <div className="flex flex-wrap gap-2">
                        {row.original.roles.map((role) => (
                            <Badge key={role} variant="outline">
                                {role}
                            </Badge>
                        ))}
                    </div>
                ),
            },
            {
                id: 'update',
                header: 'Update',
                enableSorting: false,
                cell: ({ row }) => (
                    <UserUpdateForm user={row.original} roles={roles} />
                ),
            },
        ],
        [roles],
    );

    return (
        <>
            <Head title="Users" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
                <Heading
                    title="Users"
                    description="Manage user profile data and role assignment for internal access."
                />

                <DataTable
                    columns={columns}
                    data={users}
                    filters={filters}
                    route="/users"
                    searchPlaceholder="Search by name or email"
                    emptyMessage="No users found."
                    totalLabel="users"
                />
            </div>
        </>
    );
}

UsersIndex.layout = {
    breadcrumbs: [
        {
            title: 'Users',
            href: '/users',
        },
    ],
};
