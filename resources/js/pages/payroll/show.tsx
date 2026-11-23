import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import type { Deduction, Payroll } from '@/types/models';
import { Head, Link } from '@inertiajs/react';

type PayrollDetails = Payroll & {
    deductions?: Deduction[];
    payslip?: { id: number; payroll_id: number; date_generated: string } | null;
};

type Props = {
    payroll: PayrollDetails;
};

function formatCurrency(value: string | number): string {
    return Number(value).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' });
}

function SummaryItem({ label, value }: { label: string; value: string }) {
    return (
        <div className="space-y-1">
            <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">{label}</p>
            <p className="text-xl font-semibold tracking-tight">{value}</p>
        </div>
    );
}

export default function ShowPayroll({ payroll }: Props) {
    const employeeName = [payroll.employee?.first_name, payroll.employee?.last_name, payroll.employee?.suffix].filter(Boolean).join(' ') || '—';
    const period = payroll.payroll_period ? `${payroll.payroll_period.start_date} – ${payroll.payroll_period.end_date}` : '—';
    const deductions = payroll.deductions ?? [];

    return (
        <AppLayout>
            <Head title="Payroll Details" />
            <div className="to-background dark:to-background mx-auto w-full max-w-4xl min-w-0 space-y-6 bg-gradient-to-b from-emerald-50/60 p-4 md:p-6 dark:from-emerald-950/20">
                <div className="flex flex-col justify-between gap-4 rounded-2xl border border-emerald-800/10 bg-white/80 p-5 shadow-sm sm:flex-row sm:items-center sm:p-6 dark:border-emerald-400/15 dark:bg-[#07130f]">
                    <div className="space-y-1">
                        <p className="text-xs font-semibold tracking-[0.16em] text-emerald-700 uppercase dark:text-emerald-400">Payroll</p>
                        <h1 className="text-2xl font-semibold tracking-tight">{employeeName}</h1>
                        <p className="text-muted-foreground text-sm">Pay period: {period}</p>
                    </div>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            className="border-emerald-700/25 text-emerald-800 hover:bg-emerald-50 dark:border-emerald-400/25 dark:text-emerald-300 dark:hover:bg-emerald-950/50"
                            asChild
                        >
                            <Link href={route('payrolls.index')}>Back</Link>
                        </Button>
                        <Button className="bg-emerald-700 text-white hover:bg-emerald-800 dark:bg-emerald-600 dark:hover:bg-emerald-500" asChild>
                            <Link href={route('payrolls.edit', payroll.id)}>Edit</Link>
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-4 rounded-xl border border-emerald-800/10 bg-white p-4 shadow-sm sm:grid-cols-3 dark:border-emerald-400/15 dark:bg-[#07130f]">
                    <SummaryItem label="Gross pay" value={formatCurrency(payroll.gross_pay)} />
                    <SummaryItem label="Total deductions" value={formatCurrency(payroll.total_deductions)} />
                    <SummaryItem label="Net pay" value={formatCurrency(payroll.net_pay)} />
                </div>

                <div className="grid gap-4 rounded-xl border border-emerald-800/10 bg-white p-4 text-sm shadow-sm sm:grid-cols-2 dark:border-emerald-400/15 dark:bg-[#07130f]">
                    <div>
                        <p className="text-muted-foreground">Date processed</p>
                        <p className="mt-1 font-medium">{payroll.date_processed}</p>
                    </div>
                    <div>
                        <p className="text-muted-foreground">Period status</p>
                        <p className="mt-1 font-medium">{payroll.payroll_period?.status ?? '—'}</p>
                    </div>
                    <div>
                        <p className="text-muted-foreground">Payslip</p>
                        <p className="mt-1 font-medium">{payroll.payslip ? `Generated ${payroll.payslip.date_generated}` : 'Not generated yet'}</p>
                    </div>
                </div>

                <div className="space-y-3 rounded-xl border border-emerald-800/10 bg-white p-4 shadow-sm dark:border-emerald-400/15 dark:bg-[#07130f]">
                    <h2 className="text-lg font-semibold tracking-tight">Deductions breakdown</h2>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Contribution</TableHead>
                                <TableHead className="text-right">Amount</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {deductions.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={2} className="text-muted-foreground text-center">
                                        No deductions recorded for this payroll.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                deductions.map((deduction) => (
                                    <TableRow key={deduction.id}>
                                        <TableCell>{deduction.contribution_rate?.contribution_type?.name ?? `Rate #${deduction.contribution_rate_id}`}</TableCell>
                                        <TableCell className="text-right">{formatCurrency(deduction.amount)}</TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                        {deductions.length > 0 && (
                            <TableFooter>
                                <TableRow>
                                    <TableCell className="font-semibold">Total</TableCell>
                                    <TableCell className="text-right font-semibold">
                                        {formatCurrency(deductions.reduce((sum, deduction) => sum + Number(deduction.amount), 0))}
                                    </TableCell>
                                </TableRow>
                            </TableFooter>
                        )}
                    </Table>
                </div>
            </div>
        </AppLayout>
    );
}