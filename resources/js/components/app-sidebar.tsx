import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Calendar, FileText, HandCoins, LayoutGrid, Settings, Users } from 'lucide-react';
import AppLogo from './app-logo';

export function AppSidebar() {
    // Counts come from the server on every page (see HandleInertiaRequests)
    const { navCounts } = usePage<SharedData>().props;

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            url: '/dashboard',
            icon: LayoutGrid,
        },
        {
            title: 'Employees',
            url: '/employees',
            icon: Users,
        },
        {
            title: 'Attendance',
            url: '/attendance',
            icon: Calendar,
            badge: navCounts?.attendance, // employees with no attendance today
        },
        {
            title: 'Payrolls',
            url: '/payrolls',
            icon: HandCoins,
        },
        {
            title: 'Payslips',
            url: '/payslips',
            icon: FileText,
        },
    ];

    const adminNavItems: NavItem[] = [
        {
            title: 'Settings',
            url: '/settings',
            icon: Settings,
            children: [
                { title: 'Organization', url: '/settings/organization' },
                { title: 'Account', url: '/settings/profile', matches: ['/settings/password', '/settings/appearance'] },
            ],
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain label="Platform" items={mainNavItems} />
                <NavMain label="Administration" items={adminNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}