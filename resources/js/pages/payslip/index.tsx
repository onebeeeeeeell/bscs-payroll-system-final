import { DataTable, type DataTableColumn } from '@/components/data-table';
import AppLayout from '@/layouts/app-layout';
import type { PaginatedData, Payslip } from '@/types/models';
import { Head, Link, router } from '@inertiajs/react';

type Props = {
    payslips: PaginatedData<Payslip>;
    filters: {
        search: string;
    };
};

function formatCurrency(value: string | undefined): string {
    if (value === undefined) return '—';

    return Number(value).toLocaleString('en-PH', {
        style: 'currency',
        currency: 'PHP',
    });
}

export default function PayslipIndex({ payslips, filters }: Props) {
    // Server search: bumabalik sa page 1 at ang payslips lang ang nire-reload
    function search(term: string) {
        router.get(route('payslips.index'), term ? { search: term } : {}, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['payslips', 'filters'],
        });
    }

    return (
        <AppLayout>
            <Head title="Payslips" />
            <div className="flex w-full min-w-0 flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Payslips</h1>
                    <p className="text-muted-foreground text-sm">Generated payslips for processed payrolls.</p>
                </div>
                <DataTable
                    data={payslips.data}
                    onSearch={search}
                    initialSearch={filters.search}
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

                <nav aria-label="Payslip pagination" className="flex flex-wrap justify-end gap-2">
                    {payslips.links.map((link, index) => {
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