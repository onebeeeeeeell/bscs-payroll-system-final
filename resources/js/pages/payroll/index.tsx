import { DataTable, type DataTableColumn } from '@/components/data-table';
import { ArchiveStatusFilter, DateFilter, FilterSelect, cleanFilters } from '@/components/list-filters';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/app-layout';
import type { SharedData } from '@/types';
import type { Department, PaginatedData, Payroll, Position } from '@/types/models';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Archive, MoreHorizontal, RotateCcw } from 'lucide-react';
import { useState } from 'react';

type Filters = {
    search: string;
    status: 'active' | 'archived';
    date: string;
    department_id: string;
    position_id: string;
};

type Props = {
    payrolls: PaginatedData<Payroll>;
    departments: Pick<Department, 'id' | 'department_name'>[];
    positions: Pick<Position, 'id' | 'title'>[];
    filters: Filters;
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

export default function PayrollIndex({ payrolls, departments, positions, filters }: Props) {
    const { flash } = usePage<SharedData>().props;
    const [archiveCandidate, setArchiveCandidate] = useState<Payroll | null>(null);
    const [isArchiving, setIsArchiving] = useState(false);
    const [restoringId, setRestoringId] = useState<number | null>(null);
    const isArchivedView = filters.status === 'archived';

    // Every filter change goes back to the server (page 1); only the records are reloaded
    function applyFilters(next: Partial<Filters>) {
        router.get(route('payrolls.index'), cleanFilters({ ...filters, ...next }), {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['payrolls', 'filters'],
        });
    }

    function archivePayroll() {
        if (!archiveCandidate) return;

        setIsArchiving(true);
        router.delete(route('payrolls.destroy', archiveCandidate.id), {
            data: cleanFilters({ ...filters }),
            preserveScroll: true,
            onSuccess: () => setArchiveCandidate(null),
            onFinish: () => setIsArchiving(false),
        });
    }

    function restorePayroll(payroll: Payroll) {
        setRestoringId(payroll.id);
        router.patch(route('payrolls.restore', payroll.id), cleanFilters({ ...filters }), {
            preserveScroll: true,
            onFinish: () => setRestoringId(null),
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
                    onSearch={(term) => applyFilters({ search: term })}
                    initialSearch={filters.search}
                    toolbar={
                        <div className="flex w-full flex-wrap gap-2">
                            <DateFilter label="Filter by date processed" presentOnly value={filters.date} onChange={(value) => applyFilters({ date: value })} />
                            <FilterSelect
                                label="Filter by department"
                                value={filters.department_id}
                                onChange={(value) => applyFilters({ department_id: value })}
                                className="min-w-36"
                            >
                                <option value="">All departments</option>
                                {departments.map((department) => (
                                    <option key={department.id} value={department.id}>
                                        {department.department_name}
                                    </option>
                                ))}
                            </FilterSelect>
                            <FilterSelect
                                label="Filter by position"
                                value={filters.position_id}
                                onChange={(value) => applyFilters({ position_id: value })}
                                className="min-w-36"
                            >
                                <option value="">All positions</option>
                                {positions.map((position) => (
                                    <option key={position.id} value={position.id}>
                                        {position.title}
                                    </option>
                                ))}
                            </FilterSelect>
                            <ArchiveStatusFilter
                                label="Filter by archive status"
                                value={filters.status}
                                onChange={(value) => applyFilters({ status: value })}
                            />
                        </div>
                    }
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
                    maxSearchLength={30}
                    lettersOnly
                    emptyMessage={isArchivedView ? 'No archived payroll records.' : 'No payroll records found.'}
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

                                            {isArchivedView ? (
                                                <DropdownMenuItem disabled={restoringId === payroll.id} onSelect={() => restorePayroll(payroll)}>
                                                    <RotateCcw aria-hidden="true" />
                                                    {restoringId === payroll.id ? 'Restoring...' : 'Restore'}
                                                </DropdownMenuItem>
                                            ) : (
                                                <>
                                                    <DropdownMenuItem asChild>
                                                        <Link href={route('payrolls.edit', payroll.id)}>Edit</Link>
                                                    </DropdownMenuItem>

                                                    <DropdownMenuItem
                                                        className="text-destructive focus:text-destructive"
                                                        onSelect={() => setArchiveCandidate(payroll)}
                                                    >
                                                        <Archive aria-hidden="true" />
                                                        Archive
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
                    open={archiveCandidate !== null}
                    onOpenChange={(open) => {
                        if (!open && !isArchiving) setArchiveCandidate(null);
                    }}
                >
                    <DialogContent>
                        <DialogTitle>Archive payroll record?</DialogTitle>
                        <DialogDescription>
                            {archiveCandidate
                                ? `${employeeName(archiveCandidate)}'s payroll for this period will be moved to the archive. Its deductions and payslip are kept, and you can restore it anytime.`
                                : ''}
                        </DialogDescription>
                        <DialogFooter>
                            <Button variant="outline" disabled={isArchiving} onClick={() => setArchiveCandidate(null)}>
                                Cancel
                            </Button>
                            <Button variant="destructive" disabled={isArchiving} onClick={archivePayroll}>
                                {isArchiving ? 'Archiving...' : 'Archive payroll'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}