import { MoreHorizontal, RotateCcw, Trash2 } from 'lucide-react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

import { DataTable, type DataTableColumn } from '@/components/data-table';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import type { SharedData } from '@/types';
import type { Attendance, Department, PaginatedData, Position } from '@/types/models';

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
    attendances: PaginatedData<Attendance>;
    stats: Stats;
    departments: Pick<Department, 'id' | 'department_name'>[];
    positions: Pick<Position, 'id' | 'title'>[];
    filters: Filters;
};

const emptyFilters: Filters = { department_id: '', position_id: '', date: '', status: 'active' };

function formatWorkDate(date: string): string {
    return new Date(`${date}T00:00:00`).toLocaleDateString(undefined, {
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

function employeeName(attendance: Attendance): string {
    const employee = attendance.employee;

    return employee ? [employee.first_name, employee.last_name, employee.suffix].filter(Boolean).join(' ') : `Employee #${attendance.employee_id}`;
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

export default function AttendanceIndex({ attendances, stats, departments, positions, filters: initialFilters }: Props) {
    const [filters, setFilters] = useState(initialFilters);
    const [deleteCandidate, setDeleteCandidate] = useState<Attendance | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [restoringId, setRestoringId] = useState<number | null>(null);
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

    function deleteAttendance() {
        if (!deleteCandidate) return;

        setIsDeleting(true);
        router.delete(route('attendance.destroy', deleteCandidate.id), {
            data: filters,
            preserveScroll: true,
            onSuccess: () => setDeleteCandidate(null),
            onFinish: () => setIsDeleting(false),
        });
    }

    function restoreAttendance(attendance: Attendance) {
    setRestoringId(attendance.id);

    router.patch(route('attendance.restore', attendance.id), {}, {
        preserveScroll: true,
        preserveState: false,
        replace: true,
        onSuccess: () => {
            setRestoringId(null);
        },
        onError: () => {
            setRestoringId(null);
        },
        onFinish: () => {
            setRestoringId(null);
        },
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
                        <p className="text-muted-foreground text-sm">Review employee work hours and overtime records.</p>
                    </div>

                    {/* // <Button
                        className="w-full bg-emerald-700 text-white hover:bg-emerald-800 sm:w-auto dark:bg-emerald-600 dark:hover:bg-emerald-500"
                        asChild
                    >
                        <Link href={route('attendance.create')}>Record Attendance</Link>
                    </Button> */}
                    
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
                    getRowId={(attendance) => attendance.id}
                    getSearchValue={(attendance) =>
                        [
                            employeeName(attendance),
                            attendance.work_date,
                            attendance.time_in,
                            attendance.time_out,
                            attendance.hours_worked,
                            attendance.overtime_hours,
                        ]
                            .filter(Boolean)
                            .join(' ')
                    }
                    searchPlaceholder="Search attendance..."
                    maxSearchLength={30}
                    emptyMessage={filters.status === 'deleted' ? 'No deleted attendance records.' : 'No attendance records yet.'}
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

                            <Input
                                type="date"
                                aria-label="Filter by date"
                                value={filters.date}
                                onChange={(event) => updateFilter('date', event.target.value)}
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
                            { header: 'Employee', cell: employeeName },
                            { header: 'Work date', cell: (attendance) => formatWorkDate(attendance.work_date) },
                            { header: 'Time in', cell: (attendance) => formatTime(attendance.time_in) },
                            { header: 'Time out', cell: (attendance) => formatTime(attendance.time_out) },
                            {
                                header: 'Hours worked',
                                className: 'text-right',
                                cell: (attendance) => hours(attendance.hours_worked),
                            },
                            {
                                header: 'Overtime',
                                className: 'text-right',
                                cell: (attendance) => hours(attendance.overtime_hours),
                            },
                            {
                                header: 'Actions',
                                className: 'text-right',
                                cell: (attendance) => (
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost" size="icon" aria-label={`Actions for attendance ${attendance.id}`}>
                                                <MoreHorizontal className="size-4" />
                                            </Button>
                                        </DropdownMenuTrigger>

                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem asChild>
                                                <Link href={route('attendance.show', attendance.id)}>View</Link>
                                            </DropdownMenuItem>

                                            {filters.status === 'active' ? (
                                                <>
                                                    <DropdownMenuItem asChild>
                                                        <Link href={route('attendance.edit', attendance.id)}>Edit</Link>
                                                    </DropdownMenuItem>

                                                    <DropdownMenuItem
                                                        className="text-destructive focus:text-destructive"
                                                        onSelect={() => setDeleteCandidate(attendance)}
                                                    >
                                                        <Trash2 aria-hidden="true" />
                                                        Archive
                                                    </DropdownMenuItem>
                                                </>
                                            ) : (
                                                <DropdownMenuItem
                                                    disabled={restoringId === attendance.id}
                                                    onSelect={(event) => {
                                                        event.preventDefault();
                                                        restoreAttendance(attendance);
                                                    }}
                                                >
                                                    <RotateCcw aria-hidden="true" />
                                                    {restoringId === attendance.id ? 'Restoring...' : 'Restore'}
                                                </DropdownMenuItem>
                                            )}
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                ),
                            },
                        ] satisfies DataTableColumn<Attendance>[]
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

                <Dialog
                    open={deleteCandidate !== null}
                    onOpenChange={(open) => {
                        if (!open && !isDeleting) setDeleteCandidate(null);
                    }}
                >
                    <DialogContent>
                        <DialogTitle>Archive attendance record?</DialogTitle>
                        <DialogDescription>
                            {deleteCandidate
                                ? `${employeeName(deleteCandidate)}'s attendance on ${formatWorkDate(deleteCandidate.work_date)} will be removed from the active list. You can restore it later from Archived records.`
                                : ''}
                        </DialogDescription>
                        <DialogFooter>
                            <Button variant="outline" disabled={isDeleting} onClick={() => setDeleteCandidate(null)}>
                                Cancel
                            </Button>
                            <Button variant="destructive" disabled={isDeleting} onClick={deleteAttendance}>
                                {isDeleting ? 'Archiving...' : 'Archive record'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}