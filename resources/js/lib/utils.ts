import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

import { type BreadcrumbItem } from '@/types';

// Amount inputs: only digits and one decimal point, up to 2 decimals (e.g. "12a3..45.678" -> "123.45").
// Meant for onChange, so it also cleans pasted text.
export function sanitizeAmount(value: string, maxDecimals = 2): string {
    const cleaned = value.replace(/[^\d.]/g, '');
    const dot = cleaned.indexOf('.');

    if (dot === -1) return cleaned;

    // ".5" -> "0.5" so the server's number format accepts it
    return `${cleaned.slice(0, dot) || '0'}.${cleaned.slice(dot + 1).replace(/\./g, '').slice(0, maxDecimals)}`;
}

// "5." -> "5" (a trailing decimal point is not a valid amount when sending to the server)
export const trimTrailingDot = (value: string): string => value.replace(/\.$/, '');

// Friendly names for URL pieces. Anything not listed is capitalised automatically.
const labels: Record<string, string> = {
    dashboard: 'Dashboard',
    employees: 'Employees',
    attendance: 'Attendance',
    payrolls: 'Payrolls',
    payslips: 'Payslips',
    settings: 'Settings',
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