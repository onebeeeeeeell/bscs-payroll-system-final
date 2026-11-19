import { DataTable, type DataTableColumn } from '@/components/data-table';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { Payroll } from '@/types/models';
import { Head, Link } from '@inertiajs/react';

type Props = {
    payrolls: Payroll[];
};

function formatCurrency(value: string): string {
    return Number(value).toLocaleString('en-PH', {
        style: 'currency',
        currency: 'PHP',
    });
}

export default function PayrollIndex({ payrolls }: Props) {
    return (
        <AppLayout>
            <Head title="Payrolls" />
            <div className="flex w-full min-w-0 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Payroll records</h1>
                        <p className="text-muted-foreground text-sm">Previously processed payroll runs.</p>
                    </div>
                    <Button
                        className="w-full bg-emerald-700 text-white hover:bg-emerald-800 sm:w-auto dark:bg-emerald-600 dark:hover:bg-emerald-500"
                        asChild
                    >
                        <Link href={route('payrolls.process.create')}>Process payroll</Link>
                    </Button>
                </div>
                <DataTable
                    data={payrolls}
                    getRowId={(payroll) => payroll.id}
                    getSearchValue={(payroll) =>
                        [
                            payroll.employee?.first_name,
                            payroll.employee?.last_name,
                            payroll.payroll_period?.start_date,
                            payroll.payroll_period?.end_date,
                            payroll.date_processed,
                        ]
                            .filter(Boolean)
                            .join(' ')
                    }
                    searchPlaceholder="Search payroll records..."
                    emptyMessage="No payroll records found."
                    columns={
                        [
                            {
                                header: 'Employee',
                                cell: (payroll) =>
                                    [payroll.employee?.first_name, payroll.employee?.last_name, payroll.employee?.suffix].filter(Boolean).join(' ') ||
                                    '—',
                            },
                            {
                                header: 'Pay period',
                                cell: (payroll) =>
                                    payroll.payroll_period ? `${payroll.payroll_period.start_date} – ${payroll.payroll_period.end_date}` : '—',
                            },
                            { header: 'Gross pay', cell: (payroll) => formatCurrency(payroll.gross_pay) },
                            { header: 'Deductions', cell: (payroll) => formatCurrency(payroll.total_deductions) },
                            { header: 'Net pay', cell: (payroll) => formatCurrency(payroll.net_pay) },
                            { header: 'Date processed', cell: (payroll) => payroll.date_processed },
                        ] satisfies DataTableColumn<Payroll>[]
                    }
                />
            </div>
        </AppLayout>
    );
}