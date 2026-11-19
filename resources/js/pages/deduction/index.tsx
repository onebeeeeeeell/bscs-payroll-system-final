import { DataTable, type DataTableColumn } from '@/components/data-table';
import AppLayout from '@/layouts/app-layout';
import type { Deduction, PaginatedData } from '@/types/models';
import { Head, Link, router } from '@inertiajs/react';

type Props = {
    deductions: PaginatedData<Deduction>;
    filters: {
        search: string;
    };
};

function formatCurrency(value: string): string {
    return Number(value).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' });
}

function employeeName(deduction: Deduction): string {
    const employee = deduction.payroll?.employee;

    return [employee?.first_name, employee?.last_name, employee?.suffix].filter(Boolean).join(' ') || '—';
}

export default function DeductionIndex({ deductions, filters }: Props) {
    // Server search: bumabalik sa page 1 at ang deductions lang ang nire-reload
    function search(term: string) {
        router.get(route('deductions.index'), term ? { search: term } : {}, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['deductions', 'filters'],
        });
    }

    return (
        <AppLayout>
            <Head title="Deductions" />
            <div className="flex w-full min-w-0 flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Deductions</h1>
                    <p className="text-muted-foreground text-sm">
                        Contribution deductions applied to each payroll. These are created automatically when a payroll is processed, so they are view-only.
                    </p>
                </div>

                <DataTable
                    data={deductions.data}
                    onSearch={search}
                    initialSearch={filters.search}
                    getRowId={(deduction) => deduction.id}
                    getSearchValue={employeeName}
                    searchPlaceholder="Search by employee..."
                    maxSearchLength={30}
                    lettersOnly
                    emptyMessage="No deductions found."
                    columns={
                        [
                            { header: 'Employee', cell: employeeName },
                            {
                                header: 'Pay period',
                                cell: (deduction) =>
                                    deduction.payroll?.payroll_period
                                        ? `${deduction.payroll.payroll_period.start_date.slice(0, 10)} – ${deduction.payroll.payroll_period.end_date.slice(0, 10)}`
                                        : '—',
                            },
                            { header: 'Contribution', cell: (deduction) => deduction.contribution_rate?.contribution_type?.name ?? '—' },
                            { header: 'Amount', cell: (deduction) => formatCurrency(deduction.amount) },
                            {
                                header: 'Payroll',
                                className: 'text-right',
                                cell: (deduction) => (
                                    <Link href={route('payrolls.show', deduction.payroll_id)} className="text-emerald-700 underline dark:text-emerald-400">
                                        View
                                    </Link>
                                ),
                            },
                        ] satisfies DataTableColumn<Deduction>[]
                    }
                />

                <nav aria-label="Deduction pagination" className="flex flex-wrap justify-end gap-2">
                    {deductions.links.map((link, index) => {
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