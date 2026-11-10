import AppLayout from '@/layouts/app-layout';
// DTO - Data Transfer Object
import type { Department, Employee, EmploymentStatus, Position } from '@/types/models';
import { Head } from '@inertiajs/react';
import EmployeeForm from './employee-form';

// Types
type Props = {
    employee: Employee;
    positions: Pick<Position, 'id' | 'title'>[];
    departments: Pick<Department, 'id' | 'department_name'>[];
    statuses: EmploymentStatus[];
};

// update() - ui
export default function EditEmployee({ employee, positions, departments, statuses }: Props) {
    return (
        <AppLayout>
            <Head title={`Edit ${employee.first_name} ${employee.last_name}`} />
            <div className="to-background dark:to-background mx-auto w-full max-w-6xl min-w-0 space-y-6 bg-gradient-to-b from-emerald-50/60 p-4 md:p-6 dark:from-emerald-950/20">
                <div className="space-y-1 rounded-2xl border border-emerald-800/10 bg-white/80 p-5 shadow-sm sm:p-6 dark:border-emerald-400/15 dark:bg-[#07130f]">
                    <p className="text-xs font-semibold tracking-[0.16em] text-emerald-700 uppercase dark:text-emerald-400">Team management</p>
                    <h1 className="text-2xl font-semibold tracking-tight">Edit Employee</h1>
                    <p className="text-muted-foreground text-sm">
                        Update {employee.first_name} {employee.last_name}&apos;s details.
                    </p>
                </div>
                <EmployeeForm employee={employee} positions={positions} departments={departments} statuses={statuses} />
            </div>
        </AppLayout>
    );
}
