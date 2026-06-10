import { Head, Link, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

type ManagedUser = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    roles: string[];
};

type PaginatedUsers = {
    data: ManagedUser[];
    current_page: number;
    from: number | null;
    next_page_url: string | null;
    prev_page_url: string | null;
    to: number | null;
    total: number;
};

function UserRow({ user, roles }: { user: ManagedUser; roles: string[] }) {
    const form = useForm({
        name: user.name,
        email: user.email,
        role: user.roles[0] ?? 'subscriber',
    });

    return (
        <TableRow>
            <TableCell className="align-top">
                <div className="font-medium">{user.name}</div>
                <div className="text-sm text-muted-foreground">#{user.id}</div>
            </TableCell>
            <TableCell className="align-top">
                <div className="space-y-1">
                    <div>{user.email}</div>
                    <div className="text-xs text-muted-foreground">
                        {user.email_verified_at ? 'Verified' : 'Unverified'}
                    </div>
                </div>
            </TableCell>
            <TableCell className="align-top">
                <div className="flex flex-wrap gap-2">
                    {user.roles.map((role) => (
                        <Badge key={role} variant="outline">
                            {role}
                        </Badge>
                    ))}
                </div>
            </TableCell>
            <TableCell>
                <form
                    className="grid gap-3 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_180px_auto]"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.patch(`/users/${user.id}`, {
                            preserveScroll: true,
                        });
                    }}
                >
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
                </form>
            </TableCell>
        </TableRow>
    );
}

export default function UsersIndex({
    users,
    roles,
    filters,
}: {
    users: PaginatedUsers;
    roles: string[];
    filters: { search: string };
}) {
    return (
        <>
            <Head title="Users" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        title="Users"
                        description="Manage user profile data and role assignment for internal access."
                    />
                </div>

                <form
                    action="/users"
                    method="get"
                    className="grid gap-3 rounded-lg border bg-card p-4 md:grid-cols-[minmax(0,1fr)_auto]"
                >
                    <Input
                        name="search"
                        defaultValue={filters.search}
                        placeholder="Search by name or email"
                    />

                    <div className="flex gap-2">
                        <Button type="submit" size="sm">
                            Search
                        </Button>
                        {filters.search && (
                            <Button asChild variant="ghost" size="sm">
                                <Link href="/users">Reset</Link>
                            </Button>
                        )}
                    </div>
                </form>

                <div className="overflow-hidden rounded-lg border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-48">User</TableHead>
                                <TableHead className="w-64">Email</TableHead>
                                <TableHead className="w-48">Current role</TableHead>
                                <TableHead>Update</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {users.data.length ? (
                                users.data.map((user) => (
                                    <UserRow key={user.id} user={user} roles={roles} />
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={4}
                                        className="h-32 text-center text-muted-foreground"
                                    >
                                        No users found.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                <div className="flex flex-col gap-3 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                    <span>
                        Showing {users.from ?? 0} to {users.to ?? 0} of {users.total} users
                    </span>
                    <div className="flex gap-2">
                        <Button
                            asChild={Boolean(users.prev_page_url)}
                            variant="outline"
                            size="sm"
                            disabled={!users.prev_page_url}
                        >
                            {users.prev_page_url ? (
                                <Link href={users.prev_page_url}>Previous</Link>
                            ) : (
                                <span>Previous</span>
                            )}
                        </Button>
                        <Button
                            asChild={Boolean(users.next_page_url)}
                            variant="outline"
                            size="sm"
                            disabled={!users.next_page_url}
                        >
                            {users.next_page_url ? (
                                <Link href={users.next_page_url}>Next</Link>
                            ) : (
                                <span>Next</span>
                            )}
                        </Button>
                    </div>
                </div>
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
