import { Head } from '@inertiajs/react';

import AppLayout from '@/layouts/app-layout';
import AttendanceForm from './attendance-form';

type EmployeeOption = {
    id: number;
    first_name: string;
    last_name: string;
    suffix: string | null;
};

type Props = {
    employees: EmployeeOption[];
};

export default function CreateAttendance({ employees }: Props) {
    return (
        <AppLayout>
            <Head title="Record Attendance" />
            <div className="to-background dark:to-background mx-auto w-full max-w-3xl min-w-0 space-y-6 bg-gradient-to-b from-emerald-50/60 p-4 md:p-6 dark:from-emerald-950/20">
                <div className="space-y-1 rounded-2xl border border-emerald-800/10 bg-white/80 p-5 shadow-sm sm:p-6 dark:border-emerald-400/15 dark:bg-[#07130f]">
                    <p className="text-xs font-semibold tracking-[0.16em] text-emerald-700 uppercase dark:text-emerald-400">Time &amp; attendance</p>
                    <h1 className="text-2xl font-semibold tracking-tight">Record Attendance</h1>
                    <p className="text-muted-foreground text-sm">Enter an employee’s work date, times, and hours.</p>
                </div>
                <AttendanceForm employees={employees} />
            </div>
        </AppLayout>
    );
}
