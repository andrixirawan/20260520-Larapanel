import { Form, Head, Link } from '@inertiajs/react';
import { ImageIcon, Save, Upload } from 'lucide-react';
import { useEffect, useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

export default function PostsCreate() {
    const [coverPreview, setCoverPreview] = useState<string | null>(null);

    useEffect(() => {
        return () => {
            if (coverPreview) {
                URL.revokeObjectURL(coverPreview);
            }
        };
    }, [coverPreview]);

    return (
        <>
            <Head title="Create post" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
                <Heading
                    title="Create post"
                    description="Add a simple post with an optional cover image."
                />

                <Form
                    action="/posts"
                    method="post"
                    encType="multipart/form-data"
                    className="grid max-w-3xl gap-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="title">Title</Label>
                                <Input
                                    id="title"
                                    name="title"
                                    required
                                    placeholder="Post title"
                                />
                                <InputError message={errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="slug">Slug</Label>
                                <Input
                                    id="slug"
                                    name="slug"
                                    placeholder="leave empty to auto-generate"
                                />
                                <InputError message={errors.slug} />
                            </div>

                            <div className="grid gap-3">
                                <Label htmlFor="cover">Cover image</Label>
                                <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
                                    {coverPreview ? (
                                        <img
                                            src={coverPreview}
                                            alt="Cover preview"
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
                                            }}
                                        />
                                        <p className="text-sm text-muted-foreground">
                                            JPG, PNG, or WEBP. Max 2 MB.
                                        </p>
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
                                    className="min-h-64"
                                    placeholder="Write the post body"
                                />
                                <InputError message={errors.body} />
                            </div>

                            <div className="flex gap-3">
                                <Button disabled={processing}>
                                    <Save />
                                    Save
                                </Button>
                                <Button asChild variant="outline">
                                    <Link href="/posts/mine">Cancel</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

PostsCreate.layout = {
    breadcrumbs: [
        {
            title: 'Posts',
            href: '/posts/mine',
        },
        {
            title: 'Create',
            href: '/posts/create',
        },
    ],
};
