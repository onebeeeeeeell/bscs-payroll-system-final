import { DataTable, type DataTableColumn } from '@/components/data-table';
import { ArchiveStatusFilter, DateFilter, cleanFilters } from '@/components/list-filters';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/app-layout';
import type { SharedData } from '@/types';
import type { Employee, PaginatedData, Payslip } from '@/types/models';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Archive, MoreHorizontal, RotateCcw } from 'lucide-react';
import { useState } from 'react';

type Filters = {
    status: 'active' | 'archived';
    date: string;
};

type Stats = {
    payslips: number;
    total_net_pay: number;
    last_generated: string | null;
};

type Props = {
    employee: Employee;
    payslips: PaginatedData<Payslip>;
    stats: Stats;
    filters: Filters;
    dateBounds: { min: string; max: string };
};

const peso = (value: number | string | undefined): string =>
    value === undefined ? '—' : Number(value).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' });

// "2026-11-19" (or "2026-11-19 10:51:00") -> "Nov 19, 2026"
function formatDate(value: string): string {
    return new Date(`${value.slice(0, 10)}T00:00:00`).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
}

function periodLabel(payslip: Payslip): string {
    const period = payslip.payroll?.payroll_period;

    return period ? `${formatDate(period.start_date)} – ${formatDate(period.end_date)}` : '—';
}

// one label + value in the employee info card
function Detail({ label, value }: { label: string; value: string | null | undefined }) {
    return (
        <div className="space-y-1">
            <dt className="text-muted-foreground text-sm">{label}</dt>
            <dd className="font-medium break-words">{value || '—'}</dd>
        </div>
    );
}

// one summary card above the table
function StatCard({ label, value, hint }: { label: string; value: string; hint: string }) {
    return (
        <div className="rounded-xl border border-emerald-800/10 bg-white p-4 shadow-sm dark:border-emerald-400/15 dark:bg-[#07130f]">
            <p className="text-muted-foreground text-sm">{label}</p>
            <p className="mt-1 text-2xl font-semibold tracking-tight">{value}</p>
            <p className="text-muted-foreground mt-1 text-xs">{hint}</p>
        </div>
    );
}

export default function PayslipEmployeeRecords({ employee, payslips, stats, filters, dateBounds }: Props) {
    const { flash } = usePage<SharedData>().props;
    const [archiveCandidate, setArchiveCandidate] = useState<Payslip | null>(null);
    const [isArchiving, setIsArchiving] = useState(false);
    const [restoringId, setRestoringId] = useState<number | null>(null);
    const isArchivedView = filters.status === 'archived';
    const employeeName = [employee.first_name, employee.last_name, employee.suffix].filter(Boolean).join(' ');

    // Every filter change goes back to the server (page 1); only the payslips are reloaded
    function applyFilters(next: Partial<Filters>) {
        router.get(route('payslips.employee-records', employee.id), cleanFilters({ ...filters, ...next }), {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['payslips', 'stats', 'filters'],
        });
    }

    function archivePayslip() {
        if (!archiveCandidate) return;

        setIsArchiving(true);
        router.delete(route('payslips.destroy', archiveCandidate.id), {
            preserveScroll: true,
            onSuccess: () => setArchiveCandidate(null),
            onFinish: () => setIsArchiving(false),
        });
    }

    function restorePayslip(payslip: Payslip) {
        setRestoringId(payslip.id);
        router.patch(route('payslips.restore', payslip.id), {}, { preserveScroll: true, onFinish: () => setRestoringId(null) });
    }

    return (
        <AppLayout>
            <Head title={`Payslips - ${employeeName}`} />
            <div className="flex w-full min-w-0 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">{employeeName}</h1>
                        <p className="text-muted-foreground text-sm">Payslips of this employee.</p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href={route('payslips.index')}>Back to Payslips</Link>
                    </Button>
                </div>

                {employee.archived_at && (
                    <div
                        role="status"
                        className="rounded-lg border border-amber-600/30 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-400/30 dark:bg-amber-950/30 dark:text-amber-200"
                    >
                        This employee is archived.
                    </div>
                )}

                {flash.success && (
                    <div
                        role="status"
                        className="rounded-lg border border-emerald-700/20 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-400/20 dark:bg-emerald-950/30 dark:text-emerald-200"
                    >
                        {flash.success}
                    </div>
                )}

                <section className="rounded-2xl border border-emerald-800/10 bg-white p-5 shadow-sm sm:p-6 dark:border-emerald-400/15 dark:bg-[#07130f]">
                    <h2 className="mb-5 text-lg font-semibold">Employee</h2>
                    <dl className="grid gap-x-6 gap-y-5 sm:grid-cols-2 lg:grid-cols-4">
                        <Detail label="Name" value={employeeName} />
                        <Detail label="Position" value={employee.position?.title} />
                        <Detail label="Department" value={employee.department?.department_name} />
                        <Detail label="Date hired" value={employee.date_hired ? formatDate(employee.date_hired) : null} />
                    </dl>
                </section>

                <div className="grid gap-4 sm:grid-cols-3">
                    <StatCard label="Payslips" value={stats.payslips.toLocaleString()} hint="Matching the filters" />
                    <StatCard label="Total net pay" value={peso(stats.total_net_pay)} hint="Total of the payslips" />
                    <StatCard label="Last generated" value={stats.last_generated ? formatDate(stats.last_generated) : '—'} hint="Most recent payslip" />
                </div>

                <DataTable
                    data={payslips.data}
                    getRowId={(payslip) => payslip.id}
                    getSearchValue={(payslip) => [periodLabel(payslip), formatDate(payslip.date_generated)].join(' ')}
                    searchPlaceholder="Search payslips..."
                    maxSearchLength={30}
                    emptyMessage={isArchivedView ? 'No archived payslips for this employee.' : 'No payslips yet.'}
                    toolbar={
                        <div className="flex w-full flex-wrap gap-2">
                            <DateFilter
                                label="Filter by date generated"
                                min={dateBounds.min}
                                max={dateBounds.max}
                                value={filters.date}
                                onChange={(value) => applyFilters({ date: value })}
                            />
                            <ArchiveStatusFilter
                                label="Filter by archive status"
                                value={filters.status}
                                onChange={(value) => applyFilters({ status: value })}
                            />
                        </div>
                    }
                    columns={
                        [
                            { header: 'Pay period', cell: periodLabel },
                            { header: 'Date generated', cell: (payslip) => formatDate(payslip.date_generated) },
                            { header: 'Gross pay', className: 'text-right', cell: (payslip) => peso(payslip.payroll?.gross_pay) },
                            { header: 'Deductions', className: 'text-right', cell: (payslip) => peso(payslip.payroll?.total_deductions) },
                            { header: 'Net pay', className: 'text-right', cell: (payslip) => peso(payslip.payroll?.net_pay) },
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

                <nav aria-label="Payslips pagination" className="flex flex-wrap justify-end gap-2">
                    {payslips.links.map((link, index) => {
                        const label = link.label.replace(/&laquo;|&raquo;/g, '').trim();

                        return link.url ? (
                            <Link
                                key={`${link.label}-${index}`}
                                href={link.url}
                                preserveScroll
                                className={`rounded-md border px-3 py-2 text-sm ${link.active ? 'bg-primary text-primary-foreground' : 'bg-background'}`}
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
                                ? `${employeeName}'s payslip generated on ${formatDate(archiveCandidate.date_generated)} will be moved to the archive. The payroll itself is not affected, and you can restore the payslip anytime.`
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