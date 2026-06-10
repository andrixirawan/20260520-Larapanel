import { Link, usePage } from '@inertiajs/react';
import {
    BookOpen,
    Boxes,
    Clock3,
    FileText,
    FolderGit2,
    LayoutGrid,
    ReceiptText,
    ShoppingCart,
    Users,
    WalletCards,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { Auth, NavItem } from '@/types';

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'Posts',
            href: '/posts',
            icon: FileText,
        },
        ...(auth.permissions['pos.sales.create']
            ? [
                  {
                      title: 'POS',
                      href: '/pos',
                      icon: ShoppingCart,
                  },
              ]
            : []),
        ...(auth.permissions['pos.products.view']
            ? [
                  {
                      title: 'POS Products',
                      href: '/pos/products',
                      icon: Boxes,
                  },
              ]
            : []),
        ...(auth.permissions['pos.sales.view']
            ? [
                  {
                      title: 'POS Sales',
                      href: '/pos/sales',
                      icon: ReceiptText,
                  },
              ]
            : []),
        ...(auth.permissions['pos.shifts.view']
            ? [
                  {
                      title: 'POS Shifts',
                      href: '/pos/shifts',
                      icon: Clock3,
                  },
              ]
            : []),
        ...(auth.permissions['pos.finance.view']
            ? [
                  {
                      title: 'POS Finance',
                      href: '/pos/finance',
                      icon: WalletCards,
                  },
              ]
            : []),
        ...(auth.permissions['users.manage']
            ? [
                  {
                      title: 'Users',
                      href: '/users',
                      icon: Users,
                  },
              ]
            : []),
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
