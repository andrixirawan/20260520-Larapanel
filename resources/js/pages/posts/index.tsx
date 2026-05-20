import { Form, Head, Link } from '@inertiajs/react';
import { Eye, ImageIcon, Pencil, Plus, Trash2 } from 'lucide-react';
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
import type { Paginated, Post } from './types';

export default function PostsIndex({ posts }: { posts: Paginated<Post> }) {
    return (
        <>
            <Head title="Posts" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        title="Posts"
                        description="Manage simple posts with optional cover images."
                    />

                    <Button asChild className="w-fit">
                        <Link href="/posts/create">
                            <Plus />
                            New post
                        </Link>
                    </Button>
                </div>

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
