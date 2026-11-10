import { DataTable, type DataTableColumn } from '@/components/data-table';
import AppLayout from '@/layouts/app-layout';
import type { Payslip } from '@/types/models';
import { Head } from '@inertiajs/react';

type Props = {
    payslips: Payslip[];
};

function formatCurrency(value: string | undefined): string {
    if (value === undefined) return '—';

    return Number(value).toLocaleString('en-PH', {
        style: 'currency',
        currency: 'PHP',
    });
}

export default function PayslipIndex({ payslips }: Props) {
    return (
        <AppLayout>
            <Head title="Payslips" />
            <div className="flex w-full min-w-0 flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Payslips</h1>
                    <p className="text-muted-foreground text-sm">Generated payslips for processed payrolls.</p>
                </div>
                <DataTable
                    data={payslips}
                    getRowId={(payslip) => payslip.id}
                    getSearchValue={(payslip) =>
                        [
                            payslip.payroll?.employee?.first_name,
                            payslip.payroll?.employee?.last_name,
                            payslip.date_generated,
                            payslip.payroll?.net_pay,
                        ]
                            .filter(Boolean)
                            .join(' ')
                    }
                    searchPlaceholder="Search payslips..."
                    emptyMessage="No payslips found."
                    columns={
                        [
                            {
                                header: 'Employee',
                                cell: (payslip) =>
                                    [payslip.payroll?.employee?.first_name, payslip.payroll?.employee?.last_name, payslip.payroll?.employee?.suffix]
                                        .filter(Boolean)
                                        .join(' ') || '—',
                            },
                            { header: 'Date generated', cell: (payslip) => payslip.date_generated },
                            { header: 'Net pay', cell: (payslip) => formatCurrency(payslip.payroll?.net_pay) },
                        ] satisfies DataTableColumn<Payslip>[]
                    }
                />
            </div>
        </AppLayout>
    );
}
