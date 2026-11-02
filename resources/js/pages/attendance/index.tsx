import { Head, Link } from '@inertiajs/react';
import { CalendarDays, Clock3 } from 'lucide-react';

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

                <div className="hidden overflow-x-auto rounded-xl border border-emerald-800/10 bg-white shadow-sm md:block dark:border-emerald-400/15 dark:bg-[#07130f]">
                    <table className="w-full min-w-[760px] text-sm">
                        <thead>
                            <tr className="border-b border-emerald-800/10 bg-emerald-50/70 text-left dark:border-emerald-400/15 dark:bg-emerald-950/30">
                                <th className="px-4 py-3 font-medium">Employee</th>
                                <th className="px-4 py-3 font-medium">Work date</th>
                                <th className="px-4 py-3 font-medium">Time in</th>
                                <th className="px-4 py-3 font-medium">Time out</th>
                                <th className="px-4 py-3 text-right font-medium">Hours worked</th>
                                <th className="px-4 py-3 text-right font-medium">Overtime</th>
                                <th className="px-4 py-3 text-right font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {attendances.data.length > 0 ? (
                                attendances.data.map((attendance) => (
                                    <tr
                                        key={attendance.id}
                                        className="border-border/70 border-b transition-colors last:border-0 hover:bg-emerald-50/40 dark:hover:bg-emerald-950/20"
                                    >
                                        <td className="px-4 py-3 font-medium">{employeeName(attendance)}</td>
                                        <td className="px-4 py-3">{formatWorkDate(attendance.work_date)}</td>
                                        <td className="px-4 py-3">{formatTime(attendance.time_in)}</td>
                                        <td className="px-4 py-3">{formatTime(attendance.time_out)}</td>
                                        <td className="px-4 py-3 text-right">{hours(attendance.hours_worked)}</td>
                                        <td className="px-4 py-3 text-right">{hours(attendance.overtime_hours)}</td>
                                        <td className="px-4 py-3 text-right">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="border-emerald-700/25 text-emerald-800 hover:bg-emerald-50 dark:border-emerald-400/25 dark:text-emerald-300 dark:hover:bg-emerald-950/50"
                                                asChild
                                            >
                                                <Link href={route('attendance.edit', attendance.id)}>Edit</Link>
                                            </Button>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={7} className="text-muted-foreground px-4 py-10 text-center">
                                        No attendance records yet.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="grid min-w-0 gap-3 md:hidden">
                    {attendances.data.length > 0 ? (
                        attendances.data.map((attendance) => (
                            <article
                                key={attendance.id}
                                className="space-y-4 rounded-xl border border-emerald-800/10 bg-white p-4 shadow-sm dark:border-emerald-400/15 dark:bg-[#07130f]"
                            >
                                <div className="flex min-w-0 items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <h2 className="font-semibold break-words">{employeeName(attendance)}</h2>
                                        <p className="text-muted-foreground mt-1 flex items-center gap-2 text-sm">
                                            <CalendarDays className="size-4 shrink-0" />
                                            {formatWorkDate(attendance.work_date)}
                                        </p>
                                    </div>
                                    <Clock3 className="text-muted-foreground mt-1 size-4 shrink-0" />
                                </div>

                                <dl className="grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <dt className="text-muted-foreground">Time in</dt>
                                        <dd>{formatTime(attendance.time_in)}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground">Time out</dt>
                                        <dd>{formatTime(attendance.time_out)}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground">Hours worked</dt>
                                        <dd>{hours(attendance.hours_worked)}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground">Overtime</dt>
                                        <dd>{hours(attendance.overtime_hours)}</dd>
                                    </div>
                                </dl>
                                <Button
                                    variant="outline"
                                    className="w-full border-emerald-700/25 text-emerald-800 hover:bg-emerald-50 dark:border-emerald-400/25 dark:text-emerald-300 dark:hover:bg-emerald-950/50"
                                    asChild
                                >
                                    <Link href={route('attendance.edit', attendance.id)}>Edit Attendance</Link>
                                </Button>
                            </article>
                        ))
                    ) : (
                        <p className="text-muted-foreground rounded-xl border px-4 py-10 text-center text-sm">No attendance records yet.</p>
                    )}
                </div>

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
