import { DataTable, type DataTableColumn } from '@/components/data-table';
import { ArchiveStatusFilter, DateFilter, FilterSelect, cleanFilters } from '@/components/list-filters';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/app-layout';
import type { SharedData } from '@/types';
import type { Department, PaginatedData, PayslipEmployeeSummary, Position } from '@/types/models';
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
    payslips: PaginatedData<PayslipEmployeeSummary>;
    departments: Pick<Department, 'id' | 'department_name'>[];
    positions: Pick<Position, 'id' | 'title'>[];
    filters: Filters;
    dateBounds: { min: string; max: string };
};

function formatCurrency(value: string | undefined): string {
    if (value === undefined) return '—';

    return Number(value).toLocaleString('en-PH', {
        style: 'currency',
        currency: 'PHP',
    });
}

function formatDate(value: string): string {
    return new Date(`${value.slice(0, 10)}T00:00:00`).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
}

function employeeName(summary: PayslipEmployeeSummary): string {
    const employee = summary.employee;

    return employee ? [employee.first_name, employee.last_name, employee.suffix].filter(Boolean).join(' ') : `Employee #${summary.employee_id}`;
}

export default function PayslipIndex({ payslips, departments, positions, filters, dateBounds }: Props) {
    const { flash } = usePage<SharedData>().props;
    const [archiveCandidate, setArchiveCandidate] = useState<PayslipEmployeeSummary | null>(null);
    const [isArchiving, setIsArchiving] = useState(false);
    const [restoringId, setRestoringId] = useState<number | null>(null);
    const [actionError, setActionError] = useState('');
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

    // Archive / Restore apply to the payslips of that employee that match the date filter
    function archivePayslips() {
        if (!archiveCandidate) return;

        setIsArchiving(true);
        router.delete(route('payslips.employee-records.archive', archiveCandidate.employee_id), {
            data: filters.date !== '' ? { date: filters.date } : {},
            preserveScroll: true,
            onSuccess: () => setArchiveCandidate(null),
            onFinish: () => setIsArchiving(false),
        });
    }

    function restorePayslips(summary: PayslipEmployeeSummary) {
        setRestoringId(summary.employee_id);
        setActionError('');

        router.patch(route('payslips.employee-records.restore', summary.employee_id), filters.date !== '' ? { date: filters.date } : {}, {
            preserveScroll: true,
            onError: (errors) => setActionError(Object.values(errors)[0] ?? 'Could not restore the payslips.'),
            onFinish: () => setRestoringId(null),
        });
    }

    // View opens all the payslips of that employee, keeping the status and date filters
    function payslipsUrl(summary: PayslipEmployeeSummary): string {
        return route('payslips.employee-records', {
            employee: summary.employee_id,
            ...(filters.status !== 'active' && { status: filters.status }),
            ...(filters.date !== '' && { date: filters.date }),
        });
    }

    return (
        <AppLayout>
            <Head title="Payslips" />
            <div className="flex w-full min-w-0 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Payslips</h1>
                        <p className="text-muted-foreground text-sm">Payslips of each employee. Use View to see all of their payslips.</p>
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

                {actionError && (
                    <div
                        role="alert"
                        className="rounded-lg border border-red-600/30 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-400/30 dark:bg-red-950/30 dark:text-red-200"
                    >
                        {actionError}
                    </div>
                )}

                <DataTable
                    data={payslips.data}
                    onSearch={(term) => applyFilters({ search: term })}
                    initialSearch={filters.search}
                    toolbar={
                        <div className="flex w-full flex-wrap gap-2">
                            <DateFilter
                                label="Filter by date generated"
                                min={dateBounds.min}
                                max={dateBounds.max}
                                value={filters.date}
                                onChange={(value) => applyFilters({ date: value })}
                            />
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
                    getRowId={(summary) => summary.employee_id}
                    getSearchValue={(summary) => employeeName(summary)}
                    searchPlaceholder="Search employee..."
                    maxSearchLength={30}
                    lettersOnly
                    emptyMessage={isArchivedView ? 'No archived payslips.' : 'No payslips found.'}
                    columns={
                        [
                            { header: 'Employee', cell: employeeName },
                            { header: 'Payslips', className: 'text-right', cell: (summary) => summary.payslips_count.toLocaleString() },
                            { header: 'Total net pay', className: 'text-right', cell: (summary) => formatCurrency(summary.total_net_pay) },
                            { header: 'Last generated', cell: (summary) => formatDate(summary.last_generated) },
                            {
                                header: 'Actions',
                                className: 'text-right',
                                cell: (summary) => (
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost" size="icon" aria-label={`Actions for ${employeeName(summary)}`}>
                                                <MoreHorizontal className="size-4" />
                                            </Button>
                                        </DropdownMenuTrigger>

                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem asChild>
                                                <Link href={payslipsUrl(summary)}>View</Link>
                                            </DropdownMenuItem>

                                            {isArchivedView ? (
                                                <DropdownMenuItem disabled={restoringId === summary.employee_id} onSelect={() => restorePayslips(summary)}>
                                                    <RotateCcw aria-hidden="true" />
                                                    {restoringId === summary.employee_id ? 'Restoring...' : 'Restore'}
                                                </DropdownMenuItem>
                                            ) : (
                                                <DropdownMenuItem
                                                    className="text-destructive focus:text-destructive"
                                                    onSelect={() => setArchiveCandidate(summary)}
                                                >
                                                    <Archive aria-hidden="true" />
                                                    Archive
                                                </DropdownMenuItem>
                                            )}
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                ),
                            },
                        ] satisfies DataTableColumn<PayslipEmployeeSummary>[]
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
                        <DialogTitle>Archive payslips?</DialogTitle>
                        <DialogDescription>
                            {archiveCandidate
                                ? `${archiveCandidate.payslips_count.toLocaleString()} ${archiveCandidate.payslips_count === 1 ? 'payslip' : 'payslips'} of ${employeeName(archiveCandidate)}${filters.date ? ` generated on ${formatDate(filters.date)}` : ''} will be moved to the archive. The payrolls are not affected, and you can restore the payslips anytime.`
                                : ''}
                        </DialogDescription>
                        <DialogFooter>
                            <Button variant="outline" disabled={isArchiving} onClick={() => setArchiveCandidate(null)}>
                                Cancel
                            </Button>
                            <Button variant="destructive" disabled={isArchiving} onClick={archivePayslips}>
                                {isArchiving ? 'Archiving...' : 'Archive payslips'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}