import AppLayout from '@/layouts/app-layout';
import type { PayrollPeriod } from '@/types/models';
import { Head } from '@inertiajs/react';
import PeriodForm from './period-form';

type Props = {
    payrollPeriod: PayrollPeriod;
};

export default function EditPayrollPeriod({ payrollPeriod }: Props) {
    return (
        <AppLayout>
            <Head title="Edit Payroll Period" />
            <div className="to-background dark:to-background mx-auto w-full max-w-3xl min-w-0 space-y-6 bg-gradient-to-b from-emerald-50/60 p-4 md:p-6 dark:from-emerald-950/20">
                <div className="space-y-1 rounded-2xl border border-emerald-800/10 bg-white/80 p-5 shadow-sm sm:p-6 dark:border-emerald-400/15 dark:bg-[#07130f]">
                    <p className="text-xs font-semibold tracking-[0.16em] text-emerald-700 uppercase dark:text-emerald-400">Payroll</p>
                    <h1 className="text-2xl font-semibold tracking-tight">Edit payroll period</h1>
                    <p className="text-muted-foreground text-sm">Only Pending and Failed periods can be processed.</p>
                </div>

                <PeriodForm payrollPeriod={payrollPeriod} />
            </div>
        </AppLayout>
    );
}