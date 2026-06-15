import { Form, Head, Link, usePage } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { Eye, ImageIcon, Pencil, Plus, Trash2 } from 'lucide-react';
import { useMemo } from 'react';
import { DataTable } from '@/components/data-table';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Post, PostFilters, PostListScope } from '@/features/post/types';
import type { Paginated } from '@/types/pagination';

export default function PostsIndex({
    posts,
    filters,
}: {
    posts: Paginated<Post>;
    filters: PostFilters;
    scope: PostListScope;
    sortOptions: Record<string, string>;
}) {
    const { auth } = usePage().props;
    const canCreatePost = auth.permissions['posts.create'];
    const tableRoute = '/posts';
    const pageTitle = 'My posts';
    const pageDescription = 'Posts created by your account.';
    const columns = useMemo<ColumnDef<Post>[]>(
        () => [
            {
                id: 'cover',
                header: 'Cover',
                enableSorting: false,
                meta: {
                    headerClassName: 'w-24',
                },
                cell: ({ row }) =>
                    row.original.cover_url ? (
                        <img
                            src={row.original.cover_url}
                            alt={row.original.title}
                            className="h-12 w-20 rounded-md border object-cover"
                            loading="lazy"
                        />
                    ) : (
                        <div className="flex h-12 w-20 items-center justify-center rounded-md border bg-muted text-muted-foreground">
                            <ImageIcon className="size-4" />
                        </div>
                    ),
            },
            {
                id: 'title',
                accessorKey: 'title',
                header: 'Post',
                cell: ({ row }) => (
                    <div className="max-w-[360px]">
                        <div className="truncate font-medium">
                            {row.original.title}
                        </div>
                        <div className="truncate text-sm text-muted-foreground">
                            {row.original.slug}
                        </div>
                    </div>
                ),
            },
            {
                id: 'author',
                accessorKey: 'author',
                header: 'Author',
            },
            {
                id: 'public_id',
                accessorKey: 'public_id',
                header: 'Public ID',
                meta: {
                    headerClassName: 'w-44',
                },
                cell: ({ row }) => (
                    <Badge variant="outline">
                        {row.original.public_id.slice(-8)}
                    </Badge>
                ),
            },
            {
                id: 'actions',
                header: 'Actions',
                enableSorting: false,
                meta: {
                    headerClassName: 'w-48 text-right',
                    cellClassName: 'text-right',
                },
                cell: ({ row }) => (
                    <div className="flex justify-end gap-2">
                        <Button asChild size="icon-sm" variant="ghost">
                            <Link href={`/posts/${row.original.public_id}`}>
                                <Eye />
                                <span className="sr-only">View</span>
                            </Link>
                        </Button>
                        {row.original.can_edit && (
                            <Button asChild size="icon-sm" variant="ghost">
                                <Link
                                    href={`/posts/${row.original.public_id}/edit`}
                                >
                                    <Pencil />
                                    <span className="sr-only">Edit</span>
                                </Link>
                            </Button>
                        )}
                        {row.original.can_delete && (
                            <Form
                                action={`/posts/${row.original.public_id}`}
                                method="post"
                                onSubmit={(event) => {
                                    if (!window.confirm('Delete this post?')) {
                                        event.preventDefault();
                                    }
                                }}
                            >
                                {({ processing }) => (
                                    <>
                                        <input
                                            type="hidden"
                                            name="_method"
                                            value="delete"
                                        />
                                        <Button
                                            type="submit"
                                            size="icon-sm"
                                            variant="ghost"
                                            disabled={processing}
                                        >
                                            <Trash2 />
                                            <span className="sr-only">
                                                Delete
                                            </span>
                                        </Button>
                                    </>
                                )}
                            </Form>
                        )}
                    </div>
                ),
            },
        ],
        [],
    );

    return (
        <>
            <Head title="Posts" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        title={pageTitle}
                        description={pageDescription}
                    />

                    {canCreatePost && (
                        <Button asChild className="w-fit">
                            <Link href="/posts/create">
                                <Plus />
                                New post
                            </Link>
                        </Button>
                    )}
                </div>

                <DataTable
                    columns={columns}
                    data={posts}
                    filters={filters}
                    route={tableRoute}
                    searchPlaceholder="Search posts"
                    emptyMessage="No posts yet."
                    totalLabel="posts"
                />
            </div>
        </>
    );
}

PostsIndex.layout = {
    breadcrumbs: [
        {
            title: 'My posts',
            href: '/posts',
        },
    ],
};
