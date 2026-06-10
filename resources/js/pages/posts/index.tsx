import { Form, Head, Link, usePage } from '@inertiajs/react';
import { Eye, ImageIcon, Pencil, Plus, Search, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { Paginated, Post, PostFilters } from './types';

export default function PostsIndex({
    posts,
    filters,
    sortOptions,
}: {
    posts: Paginated<Post>;
    filters: PostFilters;
    sortOptions: Record<string, string>;
}) {
    const { auth } = usePage().props;
    const canCreatePost = auth.permissions['posts.create'];
    const canUpdatePost = auth.permissions['posts.update'];
    const canDeletePost = auth.permissions['posts.delete'];
    const hasFilters = Boolean(
        filters.search || filters.author || filters.sort !== 'latest',
    );

    return (
        <>
            <Head title="Posts" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        title="Posts"
                        description="Manage simple posts with optional cover images."
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

                <form
                    action="/posts"
                    method="get"
                    className="grid gap-3 rounded-lg border bg-card p-4 md:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_160px_120px_auto]"
                >
                    <div className="relative">
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            name="search"
                            defaultValue={filters.search}
                            placeholder="Search posts"
                            className="pl-9"
                        />
                    </div>

                    <Input
                        name="author"
                        defaultValue={filters.author}
                        placeholder="Filter author"
                    />

                    <select
                        name="sort"
                        defaultValue={filters.sort}
                        className="h-9 rounded-md border border-input bg-background px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    >
                        {Object.entries(sortOptions).map(([value, label]) => (
                            <option key={value} value={value}>
                                {label}
                            </option>
                        ))}
                    </select>

                    <select
                        name="per_page"
                        defaultValue={filters.per_page}
                        className="h-9 rounded-md border border-input bg-background px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    >
                        {[5, 10, 15, 25].map((value) => (
                            <option key={value} value={value}>
                                {value} / page
                            </option>
                        ))}
                    </select>

                    <div className="flex gap-2">
                        <Button type="submit" size="sm">
                            Apply
                        </Button>
                        {hasFilters && (
                            <Button asChild variant="ghost" size="sm">
                                <Link href="/posts">Reset</Link>
                            </Button>
                        )}
                    </div>
                </form>

                <div className="overflow-hidden rounded-lg border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-24">Cover</TableHead>
                                <TableHead>Post</TableHead>
                                <TableHead>Author</TableHead>
                                <TableHead className="w-28">ID</TableHead>
                                <TableHead className="w-48 text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {posts.data.length ? (
                                posts.data.map((post) => (
                                    <TableRow key={post.id}>
                                        <TableCell>
                                            {post.cover_url ? (
                                                <img
                                                    src={post.cover_url}
                                                    alt={post.title}
                                                    className="h-12 w-20 rounded-md border object-cover"
                                                    loading="lazy"
                                                />
                                            ) : (
                                                <div className="flex h-12 w-20 items-center justify-center rounded-md border bg-muted text-muted-foreground">
                                                    <ImageIcon className="size-4" />
                                                </div>
                                            )}
                                        </TableCell>
                                        <TableCell className="max-w-[360px]">
                                            <div className="truncate font-medium">
                                                {post.title}
                                            </div>
                                            <div className="truncate text-sm text-muted-foreground">
                                                {post.slug}
                                            </div>
                                        </TableCell>
                                        <TableCell>{post.author}</TableCell>
                                        <TableCell>
                                            <Badge variant="outline">
                                                #{post.id}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex justify-end gap-2">
                                                <Button
                                                    asChild
                                                    size="icon-sm"
                                                    variant="ghost"
                                                >
                                                    <Link
                                                        href={`/posts/${post.id}`}
                                                    >
                                                        <Eye />
                                                        <span className="sr-only">
                                                            View
                                                        </span>
                                                    </Link>
                                                </Button>
                                                {canUpdatePost && (
                                                    <Button
                                                        asChild
                                                        size="icon-sm"
                                                        variant="ghost"
                                                    >
                                                        <Link
                                                            href={`/posts/${post.id}/edit`}
                                                        >
                                                            <Pencil />
                                                            <span className="sr-only">
                                                                Edit
                                                            </span>
                                                        </Link>
                                                    </Button>
                                                )}
                                                {canDeletePost && (
                                                    <Form
                                                        action={`/posts/${post.id}`}
                                                        method="post"
                                                        onSubmit={(event) => {
                                                            if (
                                                                !window.confirm(
                                                                    'Delete this post?',
                                                                )
                                                            ) {
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
                                                                    disabled={
                                                                        processing
                                                                    }
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
                                        </TableCell>
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
                                        className="h-32 text-center text-muted-foreground"
                                    >
                                        No posts yet.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                <div className="flex flex-col gap-3 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                    <span>
                        Showing {posts.from ?? 0} to {posts.to ?? 0} of{' '}
                        {posts.total} posts
                    </span>
                    <div className="flex gap-2">
                        <Button
                            asChild={Boolean(posts.prev_page_url)}
                            variant="outline"
                            size="sm"
                            disabled={!posts.prev_page_url}
                        >
                            {posts.prev_page_url ? (
                                <Link href={posts.prev_page_url}>Previous</Link>
                            ) : (
                                <span>Previous</span>
                            )}
                        </Button>
                        <Button
                            asChild={Boolean(posts.next_page_url)}
                            variant="outline"
                            size="sm"
                            disabled={!posts.next_page_url}
                        >
                            {posts.next_page_url ? (
                                <Link href={posts.next_page_url}>Next</Link>
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

PostsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Posts',
            href: '/posts',
        },
    ],
};
