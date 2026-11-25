import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

import { DataTable, type DataTableColumn } from '@/components/data-table';
import { DateFilter } from '@/components/list-filters';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { SharedData } from '@/types';
import type { AttendanceEmployeeSummary, Department, PaginatedData, Position } from '@/types/models';

// Types
type Filters = {
    department_id: string;
    position_id: string;
    date: string;
    status: 'active' | 'deleted';
};

type Stats = {
    records: number;
    employees: number;
    with_overtime: number;
    today: number;
};

type Props = {
    attendances: PaginatedData<AttendanceEmployeeSummary>;
    stats: Stats;
    departments: Pick<Department, 'id' | 'department_name'>[];
    positions: Pick<Position, 'id' | 'title'>[];
    filters: Filters;
    dateBounds: { min: string; max: string };
};

const emptyFilters: Filters = { department_id: '', position_id: '', date: '', status: 'active' };

function formatWorkDate(date: string): string {
    return new Date(`${date.slice(0, 10)}T00:00:00`).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function employeeName(summary: AttendanceEmployeeSummary): string {
    const employee = summary.employee;

    return employee ? [employee.first_name, employee.last_name, employee.suffix].filter(Boolean).join(' ') : `Employee #${summary.employee_id}`;
}

function hours(value: string | number | null): string {
    return value === null ? '—' : Number(value).toFixed(2);
}

// one status card above the table
function StatCard({ label, value, hint }: { label: string; value: number; hint: string }) {
    return (
        <div className="rounded-xl border border-emerald-800/10 bg-white p-4 shadow-sm dark:border-emerald-400/15 dark:bg-[#07130f]">
            <p className="text-muted-foreground text-sm">{label}</p>
            <p className="mt-1 text-2xl font-semibold tracking-tight">{value.toLocaleString()}</p>
            <p className="text-muted-foreground mt-1 text-xs">{hint}</p>
        </div>
    );
}

export default function AttendanceIndex({ attendances, stats, departments, positions, filters: initialFilters, dateBounds }: Props) {
    const [filters, setFilters] = useState(initialFilters);
    const { flash } = usePage<SharedData>().props;

    useEffect(() => {
        setFilters(initialFilters);
    }, [initialFilters]);

    const hasFilters = filters.department_id !== '' || filters.position_id !== '' || filters.date !== '' || filters.status !== 'active';

    function applyFilters(nextFilters: Filters) {
        setFilters(nextFilters);
        router.get(route('attendance.index'), nextFilters, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    }

    function updateFilter<K extends keyof Filters>(name: K, value: Filters[K]) {
        applyFilters({ ...filters, [name]: value });
    }

    // View opens all the records of that employee, keeping the status and date filters
    function recordsUrl(summary: AttendanceEmployeeSummary): string {
        return route('attendance.employee-records', {
            employee: summary.employee_id,
            ...(filters.status !== 'active' && { status: filters.status }),
            ...(filters.date !== '' && { date: filters.date }),
        });
    }

    return (
        <AppLayout>
            <Head title="Attendance" />
            <div className="to-background dark:to-background flex w-full min-w-0 flex-col gap-6 bg-gradient-to-b from-emerald-50/60 p-4 md:p-6 dark:from-emerald-950/20">
                <header className="flex flex-col justify-between gap-4 rounded-2xl border border-emerald-800/10 bg-white/80 p-5 shadow-sm sm:flex-row sm:items-center sm:p-6 dark:border-emerald-400/15 dark:bg-[#07130f]">
                    <div className="space-y-1">
                        <p className="text-xs font-semibold tracking-[0.16em] text-emerald-700 uppercase dark:text-emerald-400">
                            Time &amp; attendance
                        </p>
                        <h1 className="text-2xl font-semibold tracking-tight">Attendance</h1>
                        <p className="text-muted-foreground text-sm">Work hours and overtime of each employee. Use View to see all of their records.</p>
                    </div>
                </header>

                {flash.success && (
                    <div
                        role="status"
                        className="rounded-lg border border-emerald-700/20 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-400/20 dark:bg-emerald-950/30 dark:text-emerald-200"
                    >
                        {flash.success}
                    </div>
                )}

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard label="Records" value={stats.records} hint="Matching the filters" />
                    <StatCard label="Employees" value={stats.employees} hint="With attendance records" />
                    <StatCard label="With overtime" value={stats.with_overtime} hint="Records with overtime hours" />
                    <StatCard label="Recorded today" value={stats.today} hint="Records dated today" />
                </div>

                <DataTable
                    data={attendances.data}
                    getRowId={(summary) => summary.employee_id}
                    getSearchValue={(summary) => employeeName(summary)}
                    searchPlaceholder="Search employee..."
                    maxSearchLength={30}
                    emptyMessage={filters.status === 'deleted' ? 'No archived attendance records.' : 'No attendance records yet.'}
                    toolbar={
                        <div className="flex w-full flex-wrap gap-2 sm:w-auto">
                            <select
                                aria-label="Filter by department"
                                value={filters.department_id}
                                onChange={(event) => updateFilter('department_id', event.target.value)}
                                className="border-input bg-background h-10 min-w-36 rounded-md border px-3 text-sm"
                            >
                                <option value="">All departments</option>
                                {departments.map((department) => (
                                    <option key={department.id} value={department.id}>
                                        {department.department_name}
                                    </option>
                                ))}
                            </select>

                            <select
                                aria-label="Filter by position"
                                value={filters.position_id}
                                onChange={(event) => updateFilter('position_id', event.target.value)}
                                className="border-input bg-background h-10 min-w-36 rounded-md border px-3 text-sm"
                            >
                                <option value="">All positions</option>
                                {positions.map((position) => (
                                    <option key={position.id} value={position.id}>
                                        {position.title}
                                    </option>
                                ))}
                            </select>

                            <select
                                aria-label="Filter by record status"
                                value={filters.status}
                                onChange={(event) => updateFilter('status', event.target.value === 'deleted' ? 'deleted' : 'active')}
                                className="border-input bg-background h-10 min-w-36 rounded-md border px-3 text-sm"
                            >
                                <option value="active">Active</option>
                                <option value="deleted">Archived</option>
                            </select>

                            <DateFilter
                                label="Filter by date"
                                value={filters.date}
                                min={dateBounds.min}
                                max={dateBounds.max}
                                onChange={(date) => updateFilter('date', date)}
                            />

                            {hasFilters && (
                                <Button type="button" variant="outline" onClick={() => applyFilters(emptyFilters)} className="h-10">
                                    Clear filters
                                </Button>
                            )}
                        </div>
                    }
                    columns={
                        [
                            { header: 'Employee', cell: employeeName },
                            { header: 'Records', className: 'text-right', cell: (summary) => summary.records.toLocaleString() },
                            { header: 'Hours worked', className: 'text-right', cell: (summary) => hours(summary.total_hours) },
                            { header: 'Overtime', className: 'text-right', cell: (summary) => hours(summary.total_overtime) },
                            { header: 'Last attendance', cell: (summary) => formatWorkDate(summary.last_work_date) },
                            {
                                header: 'Actions',
                                className: 'text-right',
                                cell: (summary) => (
                                    <Button variant="ghost" size="sm" asChild>
                                        <Link href={recordsUrl(summary)}>View</Link>
                                    </Button>
                                ),
                            },
                        ] satisfies DataTableColumn<AttendanceEmployeeSummary>[]
                    }
                />

                <nav aria-label="Attendance pagination" className="flex flex-wrap justify-end gap-2">
                    {attendances.links.map((link, index) => {
                        const label = link.label.replace(/&laquo;|&raquo;/g, '').trim();

                        return link.url ? (
                            <Link
                                key={`${link.label}-${index}`}
                                href={link.url}
                                preserveScroll
                                className={`rounded-md border px-3 py-2 text-sm ${
                                    link.active ? 'bg-primary text-primary-foreground' : 'bg-background'
                                }`}
                                aria-current={link.active ? 'page' : undefined}
                            >
                                {label}
                            </Link>
                        ) : (
                            <span key={`${link.label}-${index}`} className="text-muted-foreground rounded-md border px-3 py-2 text-sm">
                                {label}
                            </span>
                        );
                    })}
                </nav>
            </div>
        </AppLayout>
    );
}