import AppLayout from '@/layouts/app-layout';
import type { Department } from '@/types/models';
import { Head } from '@inertiajs/react';
import DepartmentForm from './department-form';

type Props = {
    department: Department;
};

export default function EditDepartment({ department }: Props) {
    return (
        <AppLayout>
            <Head title="Edit Department" />
            <div className="to-background dark:to-background mx-auto w-full max-w-3xl min-w-0 space-y-6 bg-gradient-to-b from-emerald-50/60 p-4 md:p-6 dark:from-emerald-950/20">
                <div className="space-y-1 rounded-2xl border border-emerald-800/10 bg-white/80 p-5 shadow-sm sm:p-6 dark:border-emerald-400/15 dark:bg-[#07130f]">
                    <p className="text-xs font-semibold tracking-[0.16em] text-emerald-700 uppercase dark:text-emerald-400">Organization</p>
                    <h1 className="text-2xl font-semibold tracking-tight">Edit department</h1>
                </div>

                <DepartmentForm department={department} />
            </div>
        </AppLayout>
    );
}