import { Head, Link, usePage } from '@inertiajs/react';
import { ImageIcon } from 'lucide-react';
import { useState } from 'react';
import { SearchInput } from '@/components/search-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { Paginated, Post, PostFilters } from './posts/types';

type PageProps = {
    auth: {
        user: unknown | null;
    };
};

function excerpt(body: string) {
    return body.length > 180 ? `${body.slice(0, 180).trim()}...` : body;
}

export default function Welcome({
    posts,
    filters,
    sortOptions,
}: {
    posts: Paginated<Post>;
    filters: PostFilters;
    sortOptions: Record<string, string>;
}) {
    const { auth } = usePage<PageProps>().props;
    const [search, setSearch] = useState(filters.search);
    const hasFilters = Boolean(
        filters.search ||
            filters.author ||
            filters.sort !== 'created_at' ||
            filters.direction !== 'desc',
    );

    return (
        <>
            <Head title="Posts" />

            <main className="min-h-screen bg-background text-foreground">
                <header className="border-b">
                    <div className="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
                        <Link href="/" className="text-lg font-semibold">
                            Posts
                        </Link>

                        <nav className="flex items-center gap-2">
                            {auth.user ? (
                                <Button asChild variant="outline" size="sm">
                                    <Link href="/dashboard">Dashboard</Link>
                                </Button>
                            ) : (
                                <>
                                    <Button asChild variant="ghost" size="sm">
                                        <Link href="/login">Log in</Link>
                                    </Button>
                                    <Button asChild variant="outline" size="sm">
                                        <Link href="/register">Register</Link>
                                    </Button>
                                </>
                            )}
                        </nav>
                    </div>
                </header>

                <div className="mx-auto grid w-full max-w-6xl gap-6 px-4 py-8 sm:px-6">
                    <div className="grid gap-2">
                        <h1 className="text-2xl font-semibold tracking-normal sm:text-3xl">
                            Latest posts
                        </h1>
                        <p className="max-w-2xl text-sm text-muted-foreground">
                            Browse public posts by title, author, or content.
                        </p>
                    </div>

                    <form
                        action="/"
                        method="get"
                        className="grid gap-3 rounded-lg border bg-card p-4 md:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)_140px_120px_120px_auto]"
                    >
                        <SearchInput
                            value={search}
                            onValueChange={setSearch}
                            placeholder="Search posts"
                        />

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
                            {Object.entries(sortOptions).map(
                                ([value, label]) => (
                                    <option key={value} value={value}>
                                        {label}
                                    </option>
                                ),
                            )}
                        </select>

                        <select
                            name="direction"
                            defaultValue={filters.direction}
                            className="h-9 rounded-md border border-input bg-background px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option value="desc">Desc</option>
                            <option value="asc">Asc</option>
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
                            <Button
                                type="submit"
                                size="sm"
                                disabled={!search.trim()}
                            >
                                Search
                            </Button>
                            {hasFilters && (
                                <Button asChild variant="ghost" size="sm">
                                    <Link href="/">Reset</Link>
                                </Button>
                            )}
                        </div>
                    </form>

                    <section className="grid gap-4">
                        {posts.data.length ? (
                            posts.data.map((post) => (
                                <article
                                    key={post.public_id}
                                    className="grid gap-4 rounded-lg border bg-card p-4 sm:grid-cols-[180px_minmax(0,1fr)]"
                                >
                                    {post.cover_url ? (
                                        <img
                                            src={post.cover_url}
                                            alt={post.title}
                                            className="aspect-[4/3] w-full rounded-md border object-cover"
                                            loading="lazy"
                                        />
                                    ) : (
                                        <div className="flex aspect-[4/3] w-full items-center justify-center rounded-md border bg-muted text-muted-foreground">
                                            <ImageIcon className="size-6" />
                                        </div>
                                    )}

                                    <div className="grid content-start gap-3">
                                        <div className="grid gap-1">
                                            <Link
                                                href={`/p/${post.slug}`}
                                                className="text-xl font-semibold hover:underline"
                                            >
                                                {post.title}
                                            </Link>
                                            <p className="text-sm text-muted-foreground">
                                                {post.author}
                                            </p>
                                        </div>

                                        <p className="text-sm leading-6 text-muted-foreground">
                                            {excerpt(post.body)}
                                        </p>

                                        <Button
                                            asChild
                                            variant="outline"
                                            size="sm"
                                            className="w-fit"
                                        >
                                            <Link href={`/p/${post.slug}`}>
                                                Read
                                            </Link>
                                        </Button>
                                    </div>
                                </article>
                            ))
                        ) : (
                            <div className="rounded-lg border p-10 text-center text-sm text-muted-foreground">
                                No posts found.
                            </div>
                        )}
                    </section>

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
                                    <Link href={posts.prev_page_url}>
                                        Previous
                                    </Link>
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
            </main>
        </>
    );
}
