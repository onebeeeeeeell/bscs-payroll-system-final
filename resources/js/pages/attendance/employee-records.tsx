import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

import { DataTable, type DataTableColumn } from '@/components/data-table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import type { SharedData } from '@/types';
import type { Attendance, Employee, PaginatedData } from '@/types/models';

// Types
type Filters = {
    date: string;
    status: 'active' | 'deleted';
};

type Stats = {
    records: number;
    total_hours: number;
    total_overtime: number;
    open_records: number;
};

type Props = {
    employee: Employee;
    attendances: PaginatedData<Attendance>;
    stats: Stats;
    filters: Filters;
};

const emptyFilters: Filters = { date: '', status: 'active' };

function formatWorkDate(date: string): string {
    return new Date(`${date.slice(0, 10)}T00:00:00`).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function formatTime(time: string | null): string {
    const match = time?.match(/^([01]\d|2[0-3]):([0-5]\d)/);
    if (!match) return '—';

    const hour24 = Number(match[1]);
    const hour12 = hour24 % 12 || 12;
    const period = hour24 >= 12 ? 'PM' : 'AM';

    return `${hour12}:${match[2]} ${period}`;
}

function hours(value: string | number | null): string {
    return value === null ? '—' : Number(value).toFixed(2);
}

// one label + value in the employee info card
function Detail({ label, value }: { label: string; value: string | null | undefined }) {
    return (
        <div className="space-y-1">
            <dt className="text-muted-foreground text-sm">{label}</dt>
            <dd className="font-medium break-words">{value || '—'}</dd>
        </div>
    );
}

// one summary card above the table
function StatCard({ label, value, hint }: { label: string; value: string; hint: string }) {
    return (
        <div className="rounded-xl border border-emerald-800/10 bg-white p-4 shadow-sm dark:border-emerald-400/15 dark:bg-[#07130f]">
            <p className="text-muted-foreground text-sm">{label}</p>
            <p className="mt-1 text-2xl font-semibold tracking-tight">{value}</p>
            <p className="text-muted-foreground mt-1 text-xs">{hint}</p>
        </div>
    );
}

export default function AttendanceEmployeeRecords({ employee, attendances, stats, filters: initialFilters }: Props) {
    const [filters, setFilters] = useState(initialFilters);
    // only today's date (server date) can be used in the date filter
    const { today } = usePage<SharedData>().props;
    const employeeName = [employee.first_name, employee.last_name, employee.suffix].filter(Boolean).join(' ');

    useEffect(() => {
        setFilters(initialFilters);
    }, [initialFilters]);

    const hasFilters = filters.date !== '' || filters.status !== 'active';

    function applyFilters(nextFilters: Filters) {
        setFilters(nextFilters);
        router.get(route('attendance.employee-records', employee.id), nextFilters, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    }

    function updateFilter<K extends keyof Filters>(name: K, value: Filters[K]) {
        applyFilters({ ...filters, [name]: value });
    }

    return (
        <AppLayout>
            <Head title={`Attendance records - ${employeeName}`} />
            <div className="to-background dark:to-background flex w-full min-w-0 flex-col gap-6 bg-gradient-to-b from-emerald-50/60 p-4 md:p-6 dark:from-emerald-950/20">
                <header className="flex flex-col justify-between gap-4 rounded-2xl border border-emerald-800/10 bg-white/80 p-5 shadow-sm sm:flex-row sm:items-center sm:p-6 dark:border-emerald-400/15 dark:bg-[#07130f]">
                    <div className="space-y-1">
                        <p className="text-xs font-semibold tracking-[0.16em] text-emerald-700 uppercase dark:text-emerald-400">Time &amp; attendance</p>
                        <h1 className="text-2xl font-semibold tracking-tight">{employeeName}</h1>
                        <p className="text-muted-foreground text-sm">Attendance records of this employee.</p>
                    </div>

                    <Button
                        variant="outline"
                        className="w-full border-emerald-700/25 text-emerald-800 hover:bg-emerald-50 sm:w-auto dark:border-emerald-400/25 dark:text-emerald-300 dark:hover:bg-emerald-950/50"
                        asChild
                    >
                        <Link href={route('attendance.index')}>Back to Attendance</Link>
                    </Button>
                </header>

                {employee.archived_at && (
                    <div
                        role="status"
                        className="rounded-lg border border-amber-600/30 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-400/30 dark:bg-amber-950/30 dark:text-amber-200"
                    >
                        This employee is archived.
                    </div>
                )}

                <section className="rounded-2xl border border-emerald-800/10 bg-white p-5 shadow-sm sm:p-6 dark:border-emerald-400/15 dark:bg-[#07130f]">
                    <h2 className="mb-5 text-lg font-semibold">Employee</h2>
                    <dl className="grid gap-x-6 gap-y-5 sm:grid-cols-2 lg:grid-cols-4">
                        <Detail label="Name" value={employeeName} />
                        <Detail label="Position" value={employee.position?.title} />
                        <Detail label="Department" value={employee.department?.department_name} />
                        <Detail label="Date hired" value={employee.date_hired ? formatWorkDate(employee.date_hired) : null} />
                    </dl>
                </section>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard label="Records" value={stats.records.toLocaleString()} hint="Matching the filters" />
                    <StatCard label="Hours worked" value={hours(stats.total_hours)} hint="Total of the records" />
                    <StatCard label="Overtime" value={hours(stats.total_overtime)} hint="Total overtime hours" />
                    <StatCard label="No time out" value={stats.open_records.toLocaleString()} hint="Records without time out" />
                </div>

                <DataTable
                    data={attendances.data}
                    getRowId={(attendance) => attendance.id}
                    getSearchValue={(attendance) =>
                        [formatWorkDate(attendance.work_date), formatTime(attendance.time_in), formatTime(attendance.time_out)].join(' ')
                    }
                    searchPlaceholder="Search records..."
                    maxSearchLength={30}
                    lettersOnly
                    emptyMessage={filters.status === 'deleted' ? 'No archived records for this employee.' : 'No attendance records yet.'}
                    toolbar={
                        <div className="flex w-full flex-wrap gap-2 sm:w-auto">
                            <select
                                aria-label="Filter by record status"
                                value={filters.status}
                                onChange={(event) => updateFilter('status', event.target.value === 'deleted' ? 'deleted' : 'active')}
                                className="border-input bg-background h-10 min-w-36 rounded-md border px-3 text-sm"
                            >
                                <option value="active">Active</option>
                                <option value="deleted">Archived</option>
                            </select>

                            <Input
                                type="date"
                                aria-label="Filter by date"
                                value={filters.date}
                                min={today}
                                max={today}
                                onChange={(event) => {
                                    const next = event.target.value;

                                    // typed-in past or future dates are ignored
                                    if (next !== '' && next !== today) return;

                                    updateFilter('date', next);
                                }}
                                className="h-10 w-44"
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
                            { header: 'Work date', cell: (attendance) => formatWorkDate(attendance.work_date) },
                            { header: 'Time in', cell: (attendance) => formatTime(attendance.time_in) },
                            { header: 'Time out', cell: (attendance) => formatTime(attendance.time_out) },
                            { header: 'Hours worked', className: 'text-right', cell: (attendance) => hours(attendance.hours_worked) },
                            { header: 'Overtime', className: 'text-right', cell: (attendance) => hours(attendance.overtime_hours) },
                            {
                                header: 'Actions',
                                className: 'text-right',
                                // archived records can't be edited, restore them from the Attendance list
                                cell: (attendance) =>
                                    filters.status === 'active' ? (
                                        <Button variant="ghost" size="sm" asChild>
                                            <Link href={route('attendance.edit', attendance.id)}>Edit</Link>
                                        </Button>
                                    ) : (
                                        '—'
                                    ),
                            },
                        ] satisfies DataTableColumn<Attendance>[]
                    }
                />

                <nav aria-label="Attendance records pagination" className="flex flex-wrap justify-end gap-2">
                    {attendances.links.map((link, index) => {
                        const label = link.label.replace(/&laquo;|&raquo;/g, '').trim();

                        return link.url ? (
                            <Link
                                key={`${link.label}-${index}`}
                                href={link.url}
                                preserveScroll
                                className={`rounded-md border px-3 py-2 text-sm ${link.active ? 'bg-primary text-primary-foreground' : 'bg-background'}`}
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