import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { Attendance } from '@/types/models';
import { Head, Link } from '@inertiajs/react';

// deleted_at is filled when the record is soft deleted
type Props = {
    attendance: Attendance & { deleted_at: string | null };
};

function Detail({ label, value }: { label: string; value: string | null | undefined }) {
    return (
        <div className="space-y-1">
            <dt className="text-muted-foreground text-sm">{label}</dt>
            <dd className="font-medium break-words">{value || '—'}</dd>
        </div>
    );
}

function formatDate(date: string): string {
    return new Date(`${date.slice(0, 10)}T00:00:00`).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'long',
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

function formatDateTime(value: string): string {
    return new Date(value).toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

export default function AttendanceShow({ attendance }: Props) {
    const employee = attendance.employee;
    const employeeName = employee ? [employee.first_name, employee.last_name, employee.suffix].filter(Boolean).join(' ') : `Employee #${attendance.employee_id}`;
    const isDeleted = attendance.deleted_at !== null;

    return (
        <AppLayout>
            <Head title={`Attendance - ${employeeName}`} />
            <div className="to-background dark:to-background mx-auto flex w-full max-w-6xl min-w-0 flex-col gap-6 bg-gradient-to-b from-emerald-50/60 p-4 md:p-6 dark:from-emerald-950/20">
                <header className="flex flex-col justify-between gap-4 rounded-2xl border border-emerald-800/10 bg-white/80 p-5 shadow-sm lg:flex-row lg:items-center lg:p-6 dark:border-emerald-400/15 dark:bg-[#07130f]">
                    <div className="space-y-1">
                        <p className="text-xs font-semibold tracking-[0.16em] text-emerald-700 uppercase dark:text-emerald-400">Time &amp; attendance</p>
                        <h1 className="text-2xl font-semibold tracking-tight">{employeeName}</h1>
                        <p className="text-muted-foreground text-sm">Attendance details</p>
                    </div>
                    <div className="flex flex-col gap-2 lg:flex-row">
                        <Button
                            variant="outline"
                            className="w-full border-emerald-700/25 text-emerald-800 hover:bg-emerald-50 lg:w-auto dark:border-emerald-400/25 dark:text-emerald-300 dark:hover:bg-emerald-950/50"
                            asChild
                        >
                            <Link href={route('attendance.index')}>Back to Attendance</Link>
                        </Button>
                        {/* a deleted record cannot be edited, restore it from the list first */}
                        {!isDeleted && (
                            <Button
                                className="w-full bg-emerald-700 text-white hover:bg-emerald-800 lg:w-auto dark:bg-emerald-600 dark:hover:bg-emerald-500"
                                asChild
                            >
                                <Link href={route('attendance.edit', attendance.id)}>Edit Attendance</Link>
                            </Button>
                        )}
                    </div>
                </header>

                {isDeleted && (
                    <div
                        role="status"
                        className="rounded-lg border border-amber-600/30 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-400/30 dark:bg-amber-950/30 dark:text-amber-200"
                    >
                        This record was deleted on {formatDateTime(attendance.deleted_at as string)}. You can restore it from Deleted records in the Attendance list.
                    </div>
                )}

                <section className="rounded-2xl border border-emerald-800/10 bg-white p-5 shadow-sm sm:p-6 dark:border-emerald-400/15 dark:bg-[#07130f]">
                    <h2 className="mb-5 text-lg font-semibold">Employee</h2>
                    <dl className="grid gap-x-6 gap-y-5 lg:grid-cols-2">
                        <Detail label="Name" value={employeeName} />
                        <Detail label="Position" value={employee?.position?.title} />
                        <Detail label="Department" value={employee?.department?.department_name} />
                    </dl>
                </section>

                <section className="rounded-2xl border border-emerald-800/10 bg-white p-5 shadow-sm sm:p-6 dark:border-emerald-400/15 dark:bg-[#07130f]">
                    <h2 className="mb-5 text-lg font-semibold">Time record</h2>
                    <dl className="grid gap-x-6 gap-y-5 lg:grid-cols-2">
                        <Detail label="Work date" value={formatDate(attendance.work_date)} />
                        <Detail label="Time in" value={formatTime(attendance.time_in)} />
                        <Detail label="Time out" value={formatTime(attendance.time_out)} />
                        <Detail label="Hours worked" value={Number(attendance.hours_worked).toFixed(2)} />
                        <Detail label="Overtime hours" value={Number(attendance.overtime_hours).toFixed(2)} />
                        <Detail label="Recorded on" value={formatDateTime(attendance.created_at)} />
                    </dl>
                </section>
            </div>
        </AppLayout>
    );
}