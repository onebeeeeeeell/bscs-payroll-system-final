import { DataTable, type DataTableColumn } from '@/components/data-table';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/app-layout';
import type { SharedData } from '@/types';
import type { PaginatedData, Payslip } from '@/types/models';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { MoreHorizontal, Trash2 } from 'lucide-react';
import { useState } from 'react';

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

function employeeName(payslip: Payslip): string {
    return [payslip.payroll?.employee?.first_name, payslip.payroll?.employee?.last_name, payslip.payroll?.employee?.suffix].filter(Boolean).join(' ') || '—';
}

export default function PayslipIndex({ payslips, filters }: Props) {
    const { flash } = usePage<SharedData>().props;
    const [deleteCandidate, setDeleteCandidate] = useState<Payslip | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);

    function deletePayslip() {
        if (!deleteCandidate) return;

        setIsDeleting(true);
        router.delete(route('payslips.destroy', deleteCandidate.id), {
            preserveScroll: true,
            onSuccess: () => setDeleteCandidate(null),
            onFinish: () => setIsDeleting(false),
        });
    }

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
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Payslips</h1>
                        <p className="text-muted-foreground text-sm">Generated payslips for processed payrolls.</p>
                    </div>
                    <Button
                        className="w-full bg-emerald-700 text-white hover:bg-emerald-800 sm:w-auto dark:bg-emerald-600 dark:hover:bg-emerald-500"
                        asChild
                    >
                        <Link href={route('payslips.create')}>Generate payslip</Link>
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
                            { header: 'Employee', cell: employeeName },
                            { header: 'Date generated', cell: (payslip) => payslip.date_generated },
                            { header: 'Net pay', cell: (payslip) => formatCurrency(payslip.payroll?.net_pay) },
                            {
                                header: 'Actions',
                                className: 'text-right',
                                cell: (payslip) => (
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost" size="icon" aria-label={`Actions for payslip ${payslip.id}`}>
                                                <MoreHorizontal className="size-4" />
                                            </Button>
                                        </DropdownMenuTrigger>

                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem asChild>
                                                <Link href={route('payrolls.show', payslip.payroll_id)}>View payroll</Link>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem asChild>
                                                <Link href={route('payslips.edit', payslip.id)}>Edit date</Link>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem
                                                className="text-destructive focus:text-destructive"
                                                onSelect={() => setDeleteCandidate(payslip)}
                                            >
                                                <Trash2 aria-hidden="true" />
                                                Delete
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                ),
                            },
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

                <Dialog
                    open={deleteCandidate !== null}
                    onOpenChange={(open) => {
                        if (!open && !isDeleting) setDeleteCandidate(null);
                    }}
                >
                    <DialogContent>
                        <DialogTitle>Delete payslip?</DialogTitle>
                        <DialogDescription>
                            {deleteCandidate ? `${employeeName(deleteCandidate)}'s payslip will be deleted. The payroll itself is not affected, and you can generate the payslip again.` : ''}
                        </DialogDescription>
                        <DialogFooter>
                            <Button variant="outline" disabled={isDeleting} onClick={() => setDeleteCandidate(null)}>
                                Cancel
                            </Button>
                            <Button variant="destructive" disabled={isDeleting} onClick={deletePayslip}>
                                {isDeleting ? 'Deleting...' : 'Delete payslip'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}