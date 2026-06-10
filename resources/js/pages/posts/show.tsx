import { Head, Link, usePage } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Post } from './types';

export default function PostsShow({ post }: { post: Post }) {
    const { auth } = usePage().props;
    const canUpdatePost = auth.permissions['posts.update'];

    return (
        <>
            <Head title={post.title} />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <Heading title={post.title} description={post.slug} />

                    <div className="flex gap-2">
                        <Button asChild variant="outline">
                            <Link href="/posts">Back</Link>
                        </Button>
                        {canUpdatePost && (
                            <Button asChild>
                                <Link href={`/posts/${post.public_id}/edit`}>
                                    <Pencil />
                                    Edit
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                <div className="flex flex-wrap gap-2">
                    <Badge variant="outline">
                        {post.public_id.slice(-8)}
                    </Badge>
                    <Badge variant="secondary">{post.author}</Badge>
                </div>

                {post.cover_url && (
                    <img
                        src={post.cover_url}
                        alt={post.title}
                        className="max-h-[420px] w-full max-w-4xl rounded-lg border object-cover"
                    />
                )}

                <article className="max-w-4xl text-sm leading-7 whitespace-pre-line text-foreground">
                    {post.body}
                </article>
            </div>
        </>
    );
}

PostsShow.layout = {
    breadcrumbs: [
        {
            title: 'Posts',
            href: '/posts',
        },
        {
            title: 'Detail',
            href: '#',
        },
    ],
};
