import { DataTable, type DataTableColumn } from '@/components/data-table';
import { ArchiveStatusFilter, DateFilters, cleanFilters } from '@/components/list-filters';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/app-layout';
import type { SharedData } from '@/types';
import type { PaginatedData, PayrollPeriod } from '@/types/models';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Archive, MoreHorizontal, RotateCcw } from 'lucide-react';
import { useState } from 'react';

// Ang controller ay may withPayrollsCount()
type PeriodRow = PayrollPeriod & { payrolls_count: number };

type Filters = {
    search: string;
    status: 'active' | 'archived';
    year: string;
    month: string;
    day: string;
};

type Props = {
    payrollPeriods: PaginatedData<PeriodRow>;
    filters: Filters;
};

const PROCESSABLE = ['Pending', 'Failed'];

const STATUS_STYLES: Record<PayrollPeriod['status'], string> = {
    Pending: 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300',
    Processing: 'bg-sky-100 text-sky-800 dark:bg-sky-950/40 dark:text-sky-300',
    Paid: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300',
    Failed: 'bg-red-100 text-red-800 dark:bg-red-950/40 dark:text-red-300',
};

function periodLabel(period: PayrollPeriod): string {
    return `${period.start_date.slice(0, 10)} – ${period.end_date.slice(0, 10)}`;
}

export default function PayrollPeriodIndex({ payrollPeriods, filters }: Props) {
    const { flash } = usePage<SharedData>().props;
    const [archiveCandidate, setArchiveCandidate] = useState<PeriodRow | null>(null);
    const [isArchiving, setIsArchiving] = useState(false);
    const [restoringId, setRestoringId] = useState<number | null>(null);
    const isArchivedView = filters.status === 'archived';

    // Every filter change goes back to the server (page 1); only the periods are reloaded
    function applyFilters(next: Partial<Filters>) {
        router.get(route('payroll_periods.index'), cleanFilters({ ...filters, ...next }), {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['payrollPeriods', 'filters'],
        });
    }

    function archivePeriod() {
        if (!archiveCandidate) return;

        setIsArchiving(true);
        router.delete(route('payroll_periods.destroy', archiveCandidate.id), {
            data: cleanFilters({ ...filters }),
            preserveScroll: true,
            onSuccess: () => setArchiveCandidate(null),
            onFinish: () => setIsArchiving(false),
        });
    }

    function restorePeriod(period: PeriodRow) {
        setRestoringId(period.id);
        router.patch(route('payroll_periods.restore', period.id), cleanFilters({ ...filters }), {
            preserveScroll: true,
            onFinish: () => setRestoringId(null),
        });
    }

    return (
        <AppLayout>
            <Head title="Payroll Periods" />
            <div className="flex w-full min-w-0 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Payroll periods</h1>
                        <p className="text-muted-foreground text-sm">Create a period, then process it from Payrolls.</p>
                    </div>
                    <Button
                        className="w-full bg-emerald-700 text-white hover:bg-emerald-800 sm:w-auto dark:bg-emerald-600 dark:hover:bg-emerald-500"
                        asChild
                    >
                        <Link href={route('payroll_periods.create')}>New period</Link>
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
                    data={payrollPeriods.data}
                    onSearch={(term) => applyFilters({ search: term })}
                    initialSearch={filters.search}
                    toolbar={
                        <div className="flex w-full flex-wrap gap-2">
                            <DateFilters
                                labelPrefix="Filter by period date"
                                year={filters.year}
                                month={filters.month}
                                day={filters.day}
                                onChange={(name, value) => applyFilters({ [name]: value })}
                            />
                            <ArchiveStatusFilter
                                label="Filter by archive status"
                                value={filters.status}
                                onChange={(value) => applyFilters({ status: value })}
                            />
                        </div>
                    }
                    getRowId={(period) => period.id}
                    getSearchValue={(period) => period.status}
                    searchPlaceholder="Search by status..."
                    emptyMessage={isArchivedView ? 'No archived payroll periods.' : 'No payroll periods yet. Create one to get started.'}
                    columns={
                        [
                            { header: 'Period', cell: periodLabel },
                            { header: 'Pay date', cell: (period) => period.pay_date.slice(0, 10) },
                            {
                                header: 'Status',
                                cell: (period) => (
                                    <span className={`rounded-full px-2.5 py-1 text-xs font-medium ${STATUS_STYLES[period.status]}`}>
                                        {period.status}
                                    </span>
                                ),
                            },
                            { header: 'Payrolls', cell: (period) => period.payrolls_count },
                            {
                                header: 'Actions',
                                className: 'text-right',
                                cell: (period) => (
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost" size="icon" aria-label={`Actions for period ${periodLabel(period)}`}>
                                                <MoreHorizontal className="size-4" />
                                            </Button>
                                        </DropdownMenuTrigger>

                                        <DropdownMenuContent align="end">
                                            {isArchivedView ? (
                                                <DropdownMenuItem disabled={restoringId === period.id} onSelect={() => restorePeriod(period)}>
                                                    <RotateCcw aria-hidden="true" />
                                                    {restoringId === period.id ? 'Restoring...' : 'Restore'}
                                                </DropdownMenuItem>
                                            ) : (
                                                <>
                                                    {/* Same menu for every status. Only Pending/Failed periods can be processed; the others show why it is off. */}
                                                    {PROCESSABLE.includes(period.status) ? (
                                                        <DropdownMenuItem asChild>
                                                            <Link href={route('payrolls.process.create', { payroll_period_id: period.id })}>
                                                                Process payroll
                                                            </Link>
                                                        </DropdownMenuItem>
                                                    ) : (
                                                        <DropdownMenuItem disabled title="Only Pending or Failed periods can be processed">
                                                            Process payroll ({period.status})
                                                        </DropdownMenuItem>
                                                    )}

                                                    <DropdownMenuItem asChild>
                                                        <Link href={route('payroll_periods.edit', period.id)}>Edit</Link>
                                                    </DropdownMenuItem>

                                                    <DropdownMenuItem
                                                        className="text-destructive focus:text-destructive"
                                                        onSelect={() => setArchiveCandidate(period)}
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
                        ] satisfies DataTableColumn<PeriodRow>[]
                    }
                />

                <nav aria-label="Payroll period pagination" className="flex flex-wrap justify-end gap-2">
                    {payrollPeriods.links.map((link, index) => {
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
                        <DialogTitle>Archive payroll period?</DialogTitle>
                        <DialogDescription>
                            {archiveCandidate
                                ? `The period ${periodLabel(archiveCandidate)} will be moved to the archive. Its payroll records are kept, and you can restore it anytime.`
                                : ''}
                        </DialogDescription>
                        <DialogFooter>
                            <Button variant="outline" disabled={isArchiving} onClick={() => setArchiveCandidate(null)}>
                                Cancel
                            </Button>
                            <Button variant="destructive" disabled={isArchiving} onClick={archivePeriod}>
                                {isArchiving ? 'Archiving...' : 'Archive period'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}