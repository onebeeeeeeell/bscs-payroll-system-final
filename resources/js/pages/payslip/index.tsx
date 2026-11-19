import { DataTable, type DataTableColumn } from '@/components/data-table';
import { ArchiveStatusFilter, DateFilter, FilterSelect, cleanFilters } from '@/components/list-filters';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/app-layout';
import type { SharedData } from '@/types';
import type { Department, PaginatedData, Payslip, Position } from '@/types/models';
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
    payslips: PaginatedData<Payslip>;
    departments: Pick<Department, 'id' | 'department_name'>[];
    positions: Pick<Position, 'id' | 'title'>[];
    filters: Filters;
};

function formatCurrency(value: string | undefined): string {
    if (value === undefined) return '—';

    return Number(value).toLocaleString('en-PH', {
        style: 'currency',
        currency: 'PHP',
    });
}

function employeeName(payslip: Payslip): string {
    return (
        [payslip.payroll?.employee?.first_name, payslip.payroll?.employee?.last_name, payslip.payroll?.employee?.suffix].filter(Boolean).join(' ') ||
        '—'
    );
}

export default function PayslipIndex({ payslips, departments, positions, filters }: Props) {
    const { flash } = usePage<SharedData>().props;
    const [archiveCandidate, setArchiveCandidate] = useState<Payslip | null>(null);
    const [isArchiving, setIsArchiving] = useState(false);
    const [restoringId, setRestoringId] = useState<number | null>(null);
    const isArchivedView = filters.status === 'archived';

    // Every filter change goes back to the server (page 1); only the payslips are reloaded
    function applyFilters(next: Partial<Filters>) {
        router.get(route('payslips.index'), cleanFilters({ ...filters, ...next }), {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['payslips', 'filters'],
        });
    }

    function archivePayslip() {
        if (!archiveCandidate) return;

        setIsArchiving(true);
        router.delete(route('payslips.destroy', archiveCandidate.id), {
            data: cleanFilters({ ...filters }),
            preserveScroll: true,
            onSuccess: () => setArchiveCandidate(null),
            onFinish: () => setIsArchiving(false),
        });
    }

    function restorePayslip(payslip: Payslip) {
        setRestoringId(payslip.id);
        router.patch(route('payslips.restore', payslip.id), cleanFilters({ ...filters }), {
            preserveScroll: true,
            onFinish: () => setRestoringId(null),
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
                    onSearch={(term) => applyFilters({ search: term })}
                    initialSearch={filters.search}
                    toolbar={
                        <div className="flex w-full flex-wrap gap-2">
                            <DateFilter label="Filter by date generated" presentOnly value={filters.date} onChange={(value) => applyFilters({ date: value })} />
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
                    maxSearchLength={30}
                    lettersOnly
                    emptyMessage={isArchivedView ? 'No archived payslips.' : 'No payslips found.'}
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
                                                <Link href={route('payslips.show', payslip.id)}>View payslip</Link>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem asChild>
                                                <Link href={route('payrolls.show', payslip.payroll_id)}>View payroll</Link>
                                            </DropdownMenuItem>

                                            {isArchivedView ? (
                                                <DropdownMenuItem disabled={restoringId === payslip.id} onSelect={() => restorePayslip(payslip)}>
                                                    <RotateCcw aria-hidden="true" />
                                                    {restoringId === payslip.id ? 'Restoring...' : 'Restore'}
                                                </DropdownMenuItem>
                                            ) : (
                                                <DropdownMenuItem
                                                    className="text-destructive focus:text-destructive"
                                                    onSelect={() => setArchiveCandidate(payslip)}
                                                >
                                                    <Archive aria-hidden="true" />
                                                    Archive
                                                </DropdownMenuItem>
                                            )}
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
                    open={archiveCandidate !== null}
                    onOpenChange={(open) => {
                        if (!open && !isArchiving) setArchiveCandidate(null);
                    }}
                >
                    <DialogContent>
                        <DialogTitle>Archive payslip?</DialogTitle>
                        <DialogDescription>
                            {archiveCandidate
                                ? `${employeeName(archiveCandidate)}'s payslip will be moved to the archive. The payroll itself is not affected, and you can restore the payslip anytime.`
                                : ''}
                        </DialogDescription>
                        <DialogFooter>
                            <Button variant="outline" disabled={isArchiving} onClick={() => setArchiveCandidate(null)}>
                                Cancel
                            </Button>
                            <Button variant="destructive" disabled={isArchiving} onClick={archivePayslip}>
                                {isArchiving ? 'Archiving...' : 'Archive payslip'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}