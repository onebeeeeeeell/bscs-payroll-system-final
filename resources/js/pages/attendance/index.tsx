import { Head, Link } from '@inertiajs/react';

import { DataTable, type DataTableColumn } from '@/components/data-table';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { Attendance, PaginatedData } from '@/types/models';

type Props = {
    attendances: PaginatedData<Attendance>;
};

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

export default function AttendanceIndex({ attendances }: Props) {
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
                    <Button
                        className="w-full bg-emerald-700 text-white hover:bg-emerald-800 sm:w-auto dark:bg-emerald-600 dark:hover:bg-emerald-500"
                        asChild
                    >
                        <Link href={route('attendance.create')}>Record Attendance</Link>
                    </Button>
                </header>

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
                    emptyMessage="No attendance records yet."
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
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="border-emerald-700/25 text-emerald-800 hover:bg-emerald-50 dark:border-emerald-400/25 dark:text-emerald-300 dark:hover:bg-emerald-950/50"
                                        asChild
                                    >
                                        <Link href={route('attendance.edit', attendance.id)}>Edit</Link>
                                    </Button>
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
            </div>
        </AppLayout>
    );
}
