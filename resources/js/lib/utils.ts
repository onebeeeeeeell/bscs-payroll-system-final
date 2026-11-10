import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

import { type BreadcrumbItem } from '@/types';

// Friendly names for URL pieces. Anything not listed is capitalised automatically.
const labels: Record<string, string> = {
    dashboard: 'Dashboard',
    employees: 'Employees',
    attendance: 'Attendance',
    payrolls: 'Payrolls',
    payslips: 'Payslips',
    settings: 'Settings',
    organization: 'Organization',
    profile: 'Profile',
    password: 'Password',
    appearance: 'Appearance',
    create: 'Add',
    edit: 'Edit',
};

// Builds breadcrumbs from the current URL so pages don't have to pass them in.
// /employees -> Employees
// /employees/5 -> Employees > View
// /employees/5/edit -> Employees > View > Edit
export function breadcrumbsFromUrl(url: string): BreadcrumbItem[] {
    const path = url.split('?')[0].split('#')[0];
    const segments = path.split('/').filter(Boolean);

    let href = '';

    return segments.map((segment) => {
        href += `/${segment}`;

        const isId = /^\d+$/.test(segment);
        const fallback = segment.replace(/[-_]/g, ' ').replace(/^\w/, (char) => char.toUpperCase());

        return {
            title: isId ? 'View' : (labels[segment.toLowerCase()] ?? fallback),
            href,
        };
    });
}