import { cn } from '@/lib/utils';
import type { ReactNode } from 'react';

const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
const selectClass = 'border-input bg-background h-10 rounded-md border px-3 text-sm';

type FilterSelectProps = {
    label: string;
    value: string;
    onChange: (value: string) => void;
    children: ReactNode;
    className?: string;
};

export function FilterSelect({ label, value, onChange, children, className }: FilterSelectProps) {
    return (
        <select
            aria-label={label}
            value={value}
            onChange={(event) => onChange(event.target.value)}
            className={cn(selectClass, 'min-w-32', className)}
        >
            {children}
        </select>
    );
}

// Year / month / day dropdowns. Any of the three can be used alone or together.
export function DateFilters({
    year,
    month,
    day,
    onChange,
    labelPrefix,
}: {
    year: string;
    month: string;
    day: string;
    onChange: (name: 'year' | 'month' | 'day', value: string) => void;
    labelPrefix: string;
}) {
    const currentYear = new Date().getFullYear();
    const years = Array.from({ length: 11 }, (_, index) => currentYear + 1 - index);

    // Keep a year that is already selected (e.g. from the URL) even if it is outside the default range
    if (year && !years.includes(Number(year))) {
        years.push(Number(year));
        years.sort((a, b) => b - a);
    }

    return (
        <>
            <FilterSelect label={`${labelPrefix} year`} value={year} onChange={(value) => onChange('year', value)}>
                <option value="">All years</option>
                {years.map((value) => (
                    <option key={value} value={value}>
                        {value}
                    </option>
                ))}
            </FilterSelect>
            <FilterSelect label={`${labelPrefix} month`} value={month} onChange={(value) => onChange('month', value)}>
                <option value="">All months</option>
                {MONTHS.map((name, index) => (
                    <option key={name} value={index + 1}>
                        {name}
                    </option>
                ))}
            </FilterSelect>
            <FilterSelect label={`${labelPrefix} day`} value={day} onChange={(value) => onChange('day', value)} className="min-w-28">
                <option value="">All days</option>
                {Array.from({ length: 31 }, (_, index) => index + 1).map((value) => (
                    <option key={value} value={value}>
                        {value}
                    </option>
                ))}
            </FilterSelect>
        </>
    );
}

export function ArchiveStatusFilter({ value, onChange, label }: { value: string; onChange: (value: 'active' | 'archived') => void; label: string }) {
    return (
        <FilterSelect label={label} value={value} onChange={(next) => onChange(next === 'archived' ? 'archived' : 'active')}>
            <option value="active">Active</option>
            <option value="archived">Archived</option>
        </FilterSelect>
    );
}

// Drops empty filters so the URL stays clean (?status=active is the default, so it is dropped too)
export function cleanFilters(filters: Record<string, string>): Record<string, string> {
    return Object.fromEntries(Object.entries(filters).filter(([key, value]) => value !== '' && !(key === 'status' && value === 'active')));
}