import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ChevronRight,
    Flame,
    LayoutGrid,
    Palette,
    Settings,
    Shield,
    Sparkles,
} from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Drawer,
    DrawerContent,
    DrawerDescription,
    DrawerFooter,
    DrawerHeader,
    DrawerTitle,
} from '@/components/ui/drawer';
import { Input } from '@/components/ui/input';
import type { ProfileStats } from '@/features/daily-quest/types';
import {
    calculateXp,
    profileHighlights,
} from '@/features/daily-quest/utils';
import { useInitials } from '@/hooks/use-initials';
import type { Auth } from '@/types';

type DailyQuestProfileProps = {
    auth: Auth;
    stats: ProfileStats;
    date_range: {
        today: string;
        week_start: string;
        week_end: string;
    };
};

export default function DailyQuestProfileIndex({
    stats,
}: {
    stats: ProfileStats;
    date_range: {
        today: string;
        week_start: string;
        week_end: string;
    };
}) {
    const { auth } = usePage<DailyQuestProfileProps>().props;
    const initials = useInitials();
    const [nameDrawerOpen, setNameDrawerOpen] = useState(false);
    const xp = calculateXp(stats.points.total);
    const form = useForm({
        name: auth.user.name,
    });

    const menuItems = [
        {
            title: 'Kategori task',
            description: 'Kelola grup task, warna, dan ikon kategori.',
            href: '/categories',
            icon: LayoutGrid,
        },
        {
            title: 'Tema & tampilan',
            description: 'Buka pengaturan appearance global aplikasi.',
            href: '/settings/appearance',
            icon: Palette,
        },
        {
            title: 'Account settings',
            description: 'Masuk ke page settings Laravel untuk email, avatar, dan data akun.',
            href: '/settings/profile',
            icon: Settings,
        },
        {
            title: 'Security',
            description: 'Kelola password, passkey, dan two-factor.',
            href: '/settings/security',
            icon: Shield,
        },
    ];

    const submitDisplayName = () => {
        form.patch('/daily-quest/profile/display-name', {
            preserveScroll: true,
            onSuccess: () => {
                setNameDrawerOpen(false);
            },
        });
    };

    return (
        <>
            <Head title="Daily Quest Profile" />

            <div className="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 px-4 py-5 sm:px-6">
                <Heading
                    title="Daily Quest Profile"
                    description="Ringkasan progres personal untuk kebiasaan, kategori, dan jalur pengaturan yang paling sering dipakai."
                />

                <Card className="overflow-hidden border-0 bg-[linear-gradient(135deg,#0f172a_0%,#1e293b_45%,#0ea5e9_100%)] py-0 text-white shadow-xl">
                    <CardContent className="space-y-5 px-5 py-5 sm:px-6">
                        <div className="flex flex-wrap items-center justify-between gap-4">
                            <div className="flex items-center gap-4">
                                <Avatar className="size-20 ring-4 ring-white/20">
                                    <AvatarImage
                                        src={String(auth.user.avatar ?? '')}
                                        alt={auth.user.name}
                                    />
                                    <AvatarFallback className="bg-white/15 text-xl font-semibold text-white">
                                        {initials(auth.user.name)}
                                    </AvatarFallback>
                                </Avatar>

                                <div className="space-y-1">
                                    <p className="text-3xl font-semibold">
                                        {auth.user.name}
                                    </p>
                                    <p className="text-sm text-white/75">
                                        {auth.user.email}
                                    </p>
                                    <Badge className="rounded-full bg-white/15 text-white hover:bg-white/15">
                                        <Sparkles className="size-3" />
                                        Level {xp.level}
                                    </Badge>
                                </div>
                            </div>

                            <Button
                                type="button"
                                variant="secondary"
                                className="rounded-full"
                                onClick={() => setNameDrawerOpen(true)}
                            >
                                Edit display name
                            </Button>
                        </div>

                        <div className="grid gap-3 sm:grid-cols-4">
                            {profileHighlights(stats).map((item) => (
                                <div
                                    key={item.label}
                                    className="rounded-2xl bg-white/10 p-4 backdrop-blur-sm"
                                >
                                    <p className="text-xs uppercase tracking-[0.18em] text-white/65">
                                        {item.label}
                                    </p>
                                    <p className="mt-2 text-2xl font-semibold">
                                        {item.value}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                <section className="grid gap-4 md:grid-cols-2">
                    <Card className="rounded-[2rem] border bg-card/90 py-0 shadow-sm">
                        <CardContent className="space-y-4 px-5 py-5">
                            <div className="flex items-center gap-2">
                                <Flame className="size-5 text-amber-500" />
                                <p className="text-lg font-semibold">Momentum</p>
                            </div>
                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="rounded-2xl border p-4">
                                    <p className="text-sm text-muted-foreground">
                                        Total poin
                                    </p>
                                    <p className="mt-2 text-3xl font-semibold">
                                        {stats.points.total}
                                    </p>
                                </div>
                                <div className="rounded-2xl border p-4">
                                    <p className="text-sm text-muted-foreground">
                                        Weekly completion
                                    </p>
                                    <p className="mt-2 text-3xl font-semibold">
                                        {stats.weekly_completion_rate}%
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="rounded-[2rem] border bg-card/90 py-0 shadow-sm">
                        <CardContent className="space-y-3 px-5 py-5">
                            <p className="text-lg font-semibold">Pengaturan</p>
                            {menuItems.map((item) => (
                                <Link
                                    key={item.title}
                                    href={item.href}
                                    className="flex items-center justify-between gap-3 rounded-2xl border p-4 transition hover:-translate-y-0.5 hover:shadow-sm"
                                >
                                    <div className="flex items-start gap-3">
                                        <div className="rounded-xl bg-slate-950 p-2 text-white dark:bg-white dark:text-slate-950">
                                            <item.icon className="size-4" />
                                        </div>
                                        <div>
                                            <p className="font-medium">{item.title}</p>
                                            <p className="text-sm text-muted-foreground">
                                                {item.description}
                                            </p>
                                        </div>
                                    </div>
                                    <ChevronRight className="size-4 text-muted-foreground" />
                                </Link>
                            ))}
                        </CardContent>
                    </Card>
                </section>
            </div>

            <Drawer open={nameDrawerOpen} onOpenChange={setNameDrawerOpen}>
                <DrawerContent className="mx-auto max-w-2xl">
                    <DrawerHeader className="text-left">
                        <DrawerTitle>Edit display name</DrawerTitle>
                        <DrawerDescription>
                            Ubah nama yang tampil di halaman Daily Quest tanpa membuka settings page penuh.
                        </DrawerDescription>
                    </DrawerHeader>

                    <div className="space-y-3 px-4 pb-2">
                        <Input
                            value={form.data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                            placeholder="Nama tampilan"
                            className="rounded-2xl"
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <DrawerFooter className="border-t bg-background/80 backdrop-blur-sm">
                        <Button
                            type="button"
                            className="rounded-full"
                            disabled={form.processing}
                            onClick={submitDisplayName}
                        >
                            Simpan nama
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            className="rounded-full"
                            onClick={() => setNameDrawerOpen(false)}
                        >
                            Tutup
                        </Button>
                    </DrawerFooter>
                </DrawerContent>
            </Drawer>
        </>
    );
}

DailyQuestProfileIndex.layout = {
    breadcrumbs: [
        {
            title: 'Daily Quest',
            href: '/today',
        },
        {
            title: 'Profile',
            href: '/daily-quest/profile',
        },
    ],
};
