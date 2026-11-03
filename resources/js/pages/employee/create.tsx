import AppLayout from '@/layouts/app-layout';
// DTO - Data Transfer Object
import type { Department, Position } from '@/types/models';
import { Head } from '@inertiajs/react';
import EmployeeForm from './employee-form';

// Types 
type Props = {
    positions: Pick<Position, 'id' | 'title'>[];
    departments: Pick<Department, 'id' | 'department_name'>[];
};

// store() - ui
export default function CreateEmployee({ positions, departments }: Props) {
    return (
        <AppLayout>
            <Head title="Create Employee" />
            <div className="to-background dark:to-background mx-auto w-full max-w-4xl min-w-0 space-y-6 bg-gradient-to-b from-emerald-50/60 p-4 md:p-6 dark:from-emerald-950/20">
                <div className="space-y-1 rounded-2xl border border-emerald-800/10 bg-white/80 p-5 shadow-sm sm:p-6 dark:border-emerald-400/15 dark:bg-[#07130f]">
                    <p className="text-xs font-semibold tracking-[0.16em] text-emerald-700 uppercase dark:text-emerald-400">Team management</p>
                    <h1 className="text-2xl font-semibold tracking-tight">Create Employee</h1>
                    <p className="text-muted-foreground text-sm">Enter the employee's details below.</p>
                </div>
                <EmployeeForm positions={positions} departments={departments} />
            </div>
        </AppLayout>
    );
}
