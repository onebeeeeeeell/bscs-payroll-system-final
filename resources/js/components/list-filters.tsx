import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import type { ReactNode } from 'react';

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

// One date picker (same as the Attendance page). Empty = no date filter.
export function DateFilter({ value, onChange, label }: { value: string; onChange: (value: string) => void; label: string }) {
    return (
        <Input type="date" aria-label={label} value={value} onChange={(event) => onChange(event.target.value)} className="h-10 w-44" />
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