import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Calendar, CalendarRange, FileText, HandCoins, LayoutGrid, Settings, SlidersHorizontal, Users } from 'lucide-react';
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
            title: 'Pay periods',
            url: '/payroll_periods',
            icon: CalendarRange,
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
        {
            title: 'Payroll setup',
            url: '/contribution_rates',
            icon: SlidersHorizontal,
            children: [
                { title: 'Contribution types', url: '/contribution_types' },
                { title: 'Contribution rates', url: '/contribution_rates' },
                { title: 'Departments', url: '/departments' },
                { title: 'Positions', url: '/positions' },
                { title: 'Deductions', url: '/deductions' },
            ],
        },
    ];

    const adminNavItems: NavItem[] = [
        {
            title: 'Settings',
            url: '/settings',
            icon: Settings,
            children: [
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