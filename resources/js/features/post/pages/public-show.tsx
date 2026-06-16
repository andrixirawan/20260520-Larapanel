import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, ImageIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { Post } from '@/features/post/types';

type PageProps = {
    auth: {
        user: unknown | null;
    };
};

export default function PublicPostShow({ post }: { post: Post }) {
    const { auth } = usePage<PageProps>().props;

    return (
        <>
            <Head title={post.title} />

            <main className="min-h-screen bg-background text-foreground">
                <header className="border-b">
                    <div className="mx-auto flex w-full max-w-4xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
                        <Button asChild variant="ghost" size="sm">
                            <Link href="/blog">
                                <ArrowLeft />
                                Posts
                            </Link>
                        </Button>

                        {auth.user ? (
                            <Button asChild variant="outline" size="sm">
                                <Link href="/dashboard">Dashboard</Link>
                            </Button>
                        ) : (
                            <Button asChild variant="outline" size="sm">
                                <Link href="/login">Log in</Link>
                            </Button>
                        )}
                    </div>
                </header>

                <article className="mx-auto grid w-full max-w-4xl gap-6 px-4 py-8 sm:px-6">
                    <div className="grid gap-3">
                        <h1 className="text-3xl font-semibold tracking-normal sm:text-4xl">
                            {post.title}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {post.author}
                        </p>
                    </div>

                    {post.cover_url ? (
                        <img
                            src={post.cover_url}
                            alt={post.title}
                            className="max-h-[520px] w-full rounded-lg border object-cover"
                        />
                    ) : (
                        <div className="flex aspect-[16/8] w-full items-center justify-center rounded-lg border bg-muted text-muted-foreground">
                            <ImageIcon className="size-8" />
                        </div>
                    )}

                    <div className="text-base leading-8 whitespace-pre-line">
                        {post.body}
                    </div>
                </article>
            </main>
        </>
    );
}
