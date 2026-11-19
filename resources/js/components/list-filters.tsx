import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
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

// One date picker. Empty = no date filter.
// presentOnly = only today's date (server date) can be picked; typed-in past or future dates are ignored.
export function DateFilter({
    value,
    onChange,
    label,
    presentOnly = false,
}: {
    value: string;
    onChange: (value: string) => void;
    label: string;
    presentOnly?: boolean;
}) {
    const { today } = usePage<SharedData>().props;

    return (
        <Input
            type="date"
            aria-label={label}
            value={value}
            min={presentOnly ? today : undefined}
            max={presentOnly ? today : undefined}
            onChange={(event) => {
                const next = event.target.value;

                if (presentOnly && next !== '' && next !== today) return;

                onChange(next);
            }}
            className="h-10 w-44"
        />
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