import { DataTable, type DataTableColumn } from '@/components/data-table';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/app-layout';
import type { SharedData } from '@/types';
import type { PaginatedData, Payroll } from '@/types/models';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { MoreHorizontal, Trash2 } from 'lucide-react';
import { useState } from 'react';

type Props = {
    payrolls: PaginatedData<Payroll>;
    filters: {
        search: string;
    };
};

function formatCurrency(value: string): string {
    return Number(value).toLocaleString('en-PH', {
        style: 'currency',
        currency: 'PHP',
    });
}

function employeeName(payroll: Payroll): string {
    return [payroll.employee?.first_name, payroll.employee?.last_name, payroll.employee?.suffix].filter(Boolean).join(' ') || '—';
}

export default function PayrollIndex({ payrolls, filters }: Props) {
    const { flash } = usePage<SharedData>().props;
    const [deleteCandidate, setDeleteCandidate] = useState<Payroll | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);

    function deletePayroll() {
        if (!deleteCandidate) return;

        setIsDeleting(true);
        router.delete(route('payrolls.destroy', deleteCandidate.id), {
            preserveScroll: true,
            onSuccess: () => setDeleteCandidate(null),
            onFinish: () => setIsDeleting(false),
        });
    }

    // Server search: bumabalik sa page 1 at ang records lang ang nire-reload
    function search(term: string) {
        router.get(route('payrolls.index'), term ? { search: term } : {}, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['payrolls', 'filters'],
        });
    }

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
                {flash.success && (
                    <div
                        role="status"
                        className="rounded-lg border border-emerald-700/20 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-400/20 dark:bg-emerald-950/30 dark:text-emerald-200"
                    >
                        {flash.success}
                    </div>
                )}

                <DataTable
                    data={payrolls.data}
                    onSearch={search}
                    initialSearch={filters.search}
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
                                cell: employeeName,
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
                            {
                                header: 'Actions',
                                className: 'text-right',
                                cell: (payroll) => (
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost" size="icon" aria-label={`Actions for payroll ${payroll.id}`}>
                                                <MoreHorizontal className="size-4" />
                                            </Button>
                                        </DropdownMenuTrigger>

                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem asChild>
                                                <Link href={route('payrolls.show', payroll.id)}>View</Link>
                                            </DropdownMenuItem>

                                            {/* Bayad na ang period: walang edit/delete (hinaharang din ng server) */}
                                            {payroll.payroll_period?.status !== 'Paid' && (
                                                <>
                                                    <DropdownMenuItem asChild>
                                                        <Link href={route('payrolls.edit', payroll.id)}>Edit</Link>
                                                    </DropdownMenuItem>

                                                    <DropdownMenuItem
                                                        className="text-destructive focus:text-destructive"
                                                        onSelect={() => setDeleteCandidate(payroll)}
                                                    >
                                                        <Trash2 aria-hidden="true" />
                                                        Delete
                                                    </DropdownMenuItem>
                                                </>
                                            )}
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                ),
                            },
                        ] satisfies DataTableColumn<Payroll>[]
                    }
                />

                <nav aria-label="Payroll pagination" className="flex flex-wrap justify-end gap-2">
                    {payrolls.links.map((link, index) => {
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

                <Dialog
                    open={deleteCandidate !== null}
                    onOpenChange={(open) => {
                        if (!open && !isDeleting) setDeleteCandidate(null);
                    }}
                >
                    <DialogContent>
                        <DialogTitle>Delete payroll record?</DialogTitle>
                        <DialogDescription>
                            {deleteCandidate
                                ? `${employeeName(deleteCandidate)}'s payroll for this period will be permanently deleted, together with its deductions and payslip. This cannot be undone.`
                                : ''}
                        </DialogDescription>
                        <DialogFooter>
                            <Button variant="outline" disabled={isDeleting} onClick={() => setDeleteCandidate(null)}>
                                Cancel
                            </Button>
                            <Button variant="destructive" disabled={isDeleting} onClick={deletePayroll}>
                                {isDeleting ? 'Deleting...' : 'Delete payroll'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}