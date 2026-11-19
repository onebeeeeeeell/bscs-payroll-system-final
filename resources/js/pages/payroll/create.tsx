import { DataTable, type DataTableColumn } from '@/components/data-table';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { PayrollPeriod, PayrollPreview, PayrollPreviewRow, PayrollRowStatus } from '@/types/models';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

type Props = {
    periods: PayrollPeriod[];
    preview: PayrollPreview | null;
    filters: {
        payroll_period_id: string;
    };
};

const statusLabels: Record<PayrollRowStatus, string> = {
    ready: 'Ready',
    no_attendance: 'No attendance',
    open_time_out: 'Open time-out',
    deductions_exceed_gross: 'Deductions exceed pay',
};

function formatCurrency(value: number): string {
    return value.toLocaleString('en-PH', { style: 'currency', currency: 'PHP' });
}

function formatNumber(value: number): string {
    return value.toLocaleString('en-PH', { maximumFractionDigits: 2 });
}

function StatusBadge({ status }: { status: PayrollRowStatus }) {
    if (status === 'ready') {
        return <Badge className="bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">{statusLabels[status]}</Badge>;
    }

    return <Badge variant={status === 'no_attendance' ? 'secondary' : 'destructive'}>{statusLabels[status]}</Badge>;
}

function SummaryItem({ label, value }: { label: string; value: string }) {
    return (
        <div className="space-y-1">
            <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">{label}</p>
            <p className="text-xl font-semibold tracking-tight">{value}</p>
        </div>
    );
}

export default function CreatePayroll({ periods, preview, filters }: Props) {
    const { errors } = usePage().props as { errors?: Record<string, string> };
    const form = useForm({ payroll_period_id: filters.payroll_period_id });
    const [confirmOpen, setConfirmOpen] = useState(false);
    const summary = preview?.summary;
    const canSave = Boolean(summary) && summary!.blocked === 0 && summary!.ready > 0 && !form.processing;
    const periodError = errors?.payroll_period_id;

    function selectPeriod(periodId: string) {
        form.setData('payroll_period_id', periodId);
        router.get(
            route('payrolls.process.create'),
            { payroll_period_id: periodId },
            { preserveScroll: true, preserveState: true, replace: true, only: ['preview', 'filters', 'errors'] },
        );
    }

    function save() {
        form.post(route('payrolls.process.store'), {
            preserveScroll: true,
            onFinish: () => setConfirmOpen(false),
        });
    }

    return (
        <AppLayout>
            <Head title="Process Payroll" />
            <div className="to-background dark:to-background mx-auto w-full max-w-6xl min-w-0 space-y-6 bg-gradient-to-b from-emerald-50/60 p-4 md:p-6 dark:from-emerald-950/20">
                <div className="space-y-1 rounded-2xl border border-emerald-800/10 bg-white/80 p-5 shadow-sm sm:p-6 dark:border-emerald-400/15 dark:bg-[#07130f]">
                    <p className="text-xs font-semibold tracking-[0.16em] text-emerald-700 uppercase dark:text-emerald-400">Payroll</p>
                    <h1 className="text-2xl font-semibold tracking-tight">Process Payroll</h1>
                    <p className="text-muted-foreground text-sm">
                        Pick a pay period to preview pay computed from attendance. Nothing is saved until you confirm.
                    </p>
                </div>

                <div className="space-y-4 rounded-xl border border-emerald-800/10 bg-white p-4 shadow-sm dark:border-emerald-400/15 dark:bg-[#07130f]">
                    <div className="max-w-sm space-y-2">
                        <Label htmlFor="payroll_period_id">Pay period</Label>
                        <Select value={form.data.payroll_period_id} onValueChange={selectPeriod}>
                            <SelectTrigger id="payroll_period_id" className="w-full" aria-invalid={Boolean(periodError)}>
                                <SelectValue placeholder={periods.length > 0 ? 'Select a pay period' : 'No pay periods to process'} />
                            </SelectTrigger>
                            <SelectContent>
                                {periods.map((period) => (
                                    <SelectItem key={period.id} value={String(period.id)}>
                                        {period.start_date} – {period.end_date} ({period.status})
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    {periodError && (
                        <Alert variant="destructive">
                            <AlertTitle>Cannot process payroll</AlertTitle>
                            <AlertDescription>{periodError}</AlertDescription>
                        </Alert>
                    )}

                    {summary && (
                        <>
                            <div className="grid grid-cols-2 gap-4 border-t border-emerald-800/10 pt-4 md:grid-cols-4 dark:border-emerald-400/15">
                                <SummaryItem label="Gross pay" value={formatCurrency(summary.gross_pay)} />
                                <SummaryItem label="Total deductions" value={formatCurrency(summary.total_deductions)} />
                                <SummaryItem label="Net pay" value={formatCurrency(summary.net_pay)} />
                                <SummaryItem label="Ready" value={`${summary.ready} of ${summary.employees} employees`} />
                            </div>

                            {summary.blocked > 0 && (
                                <Alert variant="destructive">
                                    <AlertTitle>
                                        {summary.blocked} {summary.blocked === 1 ? 'employee needs' : 'employees need'} attention
                                    </AlertTitle>
                                    <AlertDescription>
                                        Fix open time-outs in attendance, or check the contribution rates, then reload this page. Saving is blocked
                                        until none are flagged.
                                    </AlertDescription>
                                </Alert>
                            )}

                            {summary.no_attendance > 0 && (
                                <p className="text-muted-foreground text-sm">
                                    {summary.no_attendance} {summary.no_attendance === 1 ? 'employee has' : 'employees have'} no attendance in this
                                    period and will not be included.
                                </p>
                            )}
                        </>
                    )}
                </div>

                {preview && (
                    <DataTable
                        data={preview.rows}
                        getRowId={(row) => row.employee_id}
                        getSearchValue={(row) => row.name}
                        searchPlaceholder="Search employees..."
                        emptyMessage="No active employees found."
                        maxSearchLength={100}
                        columns={
                            [
                                { header: 'Employee', cell: (row) => row.name || '—' },
                                { header: 'Paid days', cell: (row) => formatNumber(row.paid_days) },
                                { header: 'Overtime hrs', cell: (row) => formatNumber(row.overtime_hours) },
                                { header: 'Gross pay', cell: (row) => formatCurrency(row.gross_pay) },
                                { header: 'Deductions', cell: (row) => formatCurrency(row.total_deductions) },
                                { header: 'Net pay', cell: (row) => formatCurrency(row.net_pay) },
                                { header: 'Status', cell: (row) => <StatusBadge status={row.status} /> },
                            ] satisfies DataTableColumn<PayrollPreviewRow>[]
                        }
                    />
                )}

                <div className="flex justify-end gap-2">
                    <Button asChild variant="outline">
                        <Link href={route('payrolls.index')}>Cancel</Link>
                    </Button>
                    <Button type="button" disabled={!canSave} onClick={() => setConfirmOpen(true)}>
                        Save payroll
                    </Button>
                </div>
            </div>

            <Dialog open={confirmOpen} onOpenChange={(open) => !form.processing && setConfirmOpen(open)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Save this payroll?</DialogTitle>
                        <DialogDescription>
                            This creates payroll records for {summary?.ready ?? 0} employees and marks the period as Processing. It cannot be
                            processed again.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button type="button" variant="outline" disabled={form.processing} onClick={() => setConfirmOpen(false)}>
                            Cancel
                        </Button>
                        <Button type="button" disabled={form.processing} onClick={save}>
                            {form.processing ? 'Saving...' : 'Confirm and save'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}