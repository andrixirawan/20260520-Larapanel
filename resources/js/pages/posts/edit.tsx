import { Form, Head, Link } from '@inertiajs/react';
import { ImageIcon, Save, Upload } from 'lucide-react';
import { useEffect, useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { Post } from './types';

export default function PostsEdit({ post }: { post: Post }) {
    const [coverPreview, setCoverPreview] = useState<string | null>(null);
    const [removeCover, setRemoveCover] = useState(false);
    const displayedCover = removeCover
        ? null
        : (coverPreview ?? post.cover_url);

    useEffect(() => {
        return () => {
            if (coverPreview) {
                URL.revokeObjectURL(coverPreview);
            }
        };
    }, [coverPreview]);

    return (
        <>
            <Head title={`Edit ${post.title}`} />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
                <Heading
                    title="Edit post"
                    description="Update the post content and cover image."
                />

                <Form
                    action={`/posts/${post.public_id}`}
                    method="post"
                    encType="multipart/form-data"
                    className="grid max-w-3xl gap-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <input type="hidden" name="_method" value="put" />

                            <div className="grid gap-2">
                                <Label htmlFor="title">Title</Label>
                                <Input
                                    id="title"
                                    name="title"
                                    required
                                    defaultValue={post.title}
                                />
                                <InputError message={errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="slug">Slug</Label>
                                <Input
                                    id="slug"
                                    name="slug"
                                    defaultValue={post.slug}
                                />
                                <InputError message={errors.slug} />
                            </div>

                            <div className="grid gap-3">
                                <Label htmlFor="cover">Cover image</Label>
                                <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
                                    {displayedCover ? (
                                        <img
                                            src={displayedCover}
                                            alt={post.title}
                                            className="h-32 w-52 rounded-md border object-cover"
                                        />
                                    ) : (
                                        <div className="flex h-32 w-52 items-center justify-center rounded-md border bg-muted text-muted-foreground">
                                            <ImageIcon className="size-6" />
                                        </div>
                                    )}

                                    <div className="grid gap-3">
                                        <Label
                                            htmlFor="cover"
                                            className="inline-flex h-9 w-fit cursor-pointer items-center justify-center gap-2 rounded-md border bg-background px-3 text-sm font-medium shadow-xs transition-colors hover:bg-accent hover:text-accent-foreground dark:border-input dark:bg-input/30 dark:hover:bg-input/50"
                                        >
                                            <Upload className="size-4" />
                                            Choose image
                                        </Label>
                                        <Input
                                            id="cover"
                                            name="cover"
                                            type="file"
                                            accept="image/jpeg,image/png,image/webp"
                                            className="sr-only"
                                            onChange={(event) => {
                                                const file =
                                                    event.target.files?.[0];

                                                if (coverPreview) {
                                                    URL.revokeObjectURL(
                                                        coverPreview,
                                                    );
                                                }

                                                setCoverPreview(
                                                    file
                                                        ? URL.createObjectURL(
                                                              file,
                                                          )
                                                        : null,
                                                );
                                                setRemoveCover(false);
                                            }}
                                        />
                                        <p className="text-sm text-muted-foreground">
                                            JPG, PNG, or WEBP. Max 2 MB.
                                        </p>
                                        {post.cover_url && (
                                            <label className="flex items-center gap-2 text-sm text-muted-foreground">
                                                <input
                                                    type="checkbox"
                                                    name="remove_cover"
                                                    value="1"
                                                    checked={removeCover}
                                                    disabled={Boolean(
                                                        coverPreview,
                                                    )}
                                                    onChange={(event) =>
                                                        setRemoveCover(
                                                            event.target
                                                                .checked,
                                                        )
                                                    }
                                                    className="size-4 rounded border-input"
                                                />
                                                Remove current cover
                                            </label>
                                        )}
                                    </div>
                                </div>
                                <InputError message={errors.cover} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="body">Body</Label>
                                <Textarea
                                    id="body"
                                    name="body"
                                    required
                                    defaultValue={post.body}
                                    className="min-h-64"
                                />
                                <InputError message={errors.body} />
                            </div>

                            <div className="flex gap-3">
                                <Button disabled={processing}>
                                    <Save />
                                    Save changes
                                </Button>
                                <Button asChild variant="outline">
                                    <Link href="/posts">Cancel</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

PostsEdit.layout = {
    breadcrumbs: [
        {
            title: 'Posts',
            href: '/posts',
        },
        {
            title: 'Edit',
            href: '#',
        },
    ],
};
