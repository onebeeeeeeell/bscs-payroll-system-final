import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { sanitizeAmount, trimTrailingDot } from '@/lib/utils';
import type { SharedData } from '@/types';
import type { Payroll } from '@/types/models';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';

type Props = {
    payroll: Payroll;
};

function formatCurrency(value: number): string {
    return value.toLocaleString('en-PH', { style: 'currency', currency: 'PHP' });
}

function toAmount(value: string): number {
    const amount = Number(value);

    return Number.isFinite(amount) ? amount : 0;
}

export default function EditPayroll({ payroll }: Props) {
    // Date processed can only be today (server date), so the field always starts on today
    const { today } = usePage<SharedData>().props;

    const form = useForm({
        gross_pay: payroll.gross_pay,
        total_deductions: payroll.total_deductions,
        net_pay: payroll.net_pay,
        date_processed: today,
    });

    const employeeName = [payroll.employee?.first_name, payroll.employee?.last_name, payroll.employee?.suffix].filter(Boolean).join(' ') || '—';
    const period = payroll.payroll_period ? `${payroll.payroll_period.start_date} – ${payroll.payroll_period.end_date}` : '—';

    // Net pay ay laging gross - deductions; hindi ito tina-type ng user
    const net = Math.round((toAmount(form.data.gross_pay) - toAmount(form.data.total_deductions)) * 100) / 100;
    const netIsNegative = net < 0;

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        if (netIsNegative) {
            return;
        }

        form.transform((data) => ({
            ...data,
            gross_pay: trimTrailingDot(data.gross_pay),
            total_deductions: trimTrailingDot(data.total_deductions),
            net_pay: net.toFixed(2),
        }));
        form.patch(route('payrolls.update', payroll.id), { preserveScroll: true });
    }

    return (
        <AppLayout>
            <Head title="Edit Payroll" />
            <div className="to-background dark:to-background mx-auto w-full max-w-3xl min-w-0 space-y-6 bg-gradient-to-b from-emerald-50/60 p-4 md:p-6 dark:from-emerald-950/20">
                <div className="space-y-1 rounded-2xl border border-emerald-800/10 bg-white/80 p-5 shadow-sm sm:p-6 dark:border-emerald-400/15 dark:bg-[#07130f]">
                    <p className="text-xs font-semibold tracking-[0.16em] text-emerald-700 uppercase dark:text-emerald-400">Payroll</p>
                    <h1 className="text-2xl font-semibold tracking-tight">Edit Payroll</h1>
                    <p className="text-muted-foreground text-sm">Adjust the amounts for this payroll record. Numbers only, up to 2 decimals. Net pay is computed for you.</p>
                </div>

                <form
                    onSubmit={submit}
                    className="space-y-6 rounded-2xl border border-emerald-800/10 bg-white p-5 shadow-sm sm:p-6 dark:border-emerald-400/15 dark:bg-[#07130f]"
                >
                    <div className="grid min-w-0 gap-4 rounded-lg border border-emerald-800/10 bg-emerald-50/70 p-4 sm:grid-cols-2 dark:border-emerald-400/15 dark:bg-emerald-950/30">
                        <div>
                            <p className="text-muted-foreground text-sm">Employee</p>
                            <p className="mt-1 font-semibold">{employeeName}</p>
                        </div>
                        <div>
                            <p className="text-muted-foreground text-sm">Pay period</p>
                            <p className="mt-1 font-semibold">{period}</p>
                        </div>
                        {payroll.user && (
                            <div className="sm:col-span-2">
                                <p className="text-muted-foreground text-sm">Processed by</p>
                                <p className="mt-1 font-semibold">{payroll.user.name}</p>
                            </div>
                        )}
                    </div>

                    <div className="grid min-w-0 gap-5 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="gross_pay">Gross pay</Label>
                            <Input
                                id="gross_pay"
                                type="text"
                                inputMode="decimal"
                                maxLength={11}
                                autoComplete="off"
                                value={form.data.gross_pay}
                                onChange={(event) => form.setData('gross_pay', sanitizeAmount(event.target.value))}
                                required
                                disabled={form.processing}
                                aria-invalid={Boolean(form.errors.gross_pay)}
                            />
                            <InputError message={form.errors.gross_pay} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="total_deductions">Total deductions</Label>
                            <Input
                                id="total_deductions"
                                type="text"
                                inputMode="decimal"
                                maxLength={11}
                                autoComplete="off"
                                value={form.data.total_deductions}
                                onChange={(event) => form.setData('total_deductions', sanitizeAmount(event.target.value))}
                                required
                                disabled={form.processing}
                                aria-invalid={Boolean(form.errors.total_deductions)}
                            />
                            <InputError message={form.errors.total_deductions} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="net_pay">Net pay (computed)</Label>
                            <Input id="net_pay" value={formatCurrency(net)} readOnly tabIndex={-1} aria-invalid={netIsNegative} />
                            {netIsNegative ? (
                                <p className="text-destructive text-sm">Deductions cannot be more than gross pay.</p>
                            ) : (
                                <InputError message={form.errors.net_pay} />
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="date_processed">Date processed</Label>
                            <Input
                                id="date_processed"
                                type="date"
                                min={today}
                                max={today}
                                value={form.data.date_processed}
                                onChange={(event) => form.setData('date_processed', event.target.value === today ? today : form.data.date_processed)}
                                required
                                disabled={form.processing}
                                aria-invalid={Boolean(form.errors.date_processed)}
                            />
                            <p className="text-muted-foreground text-xs">Only today&apos;s date is allowed.</p>
                            <InputError message={form.errors.date_processed} />
                        </div>
                    </div>

                    <div className="flex flex-col-reverse justify-end gap-3 sm:flex-row">
                        <Button
                            variant="outline"
                            className="border-emerald-700/25 text-emerald-800 hover:bg-emerald-50 dark:border-emerald-400/25 dark:text-emerald-300 dark:hover:bg-emerald-950/50"
                            type="button"
                            disabled={form.processing}
                            asChild
                        >
                            <Link href={route('payrolls.index')}>Cancel</Link>
                        </Button>
                        <Button
                            className="bg-emerald-700 text-white hover:bg-emerald-800 dark:bg-emerald-600 dark:hover:bg-emerald-500"
                            type="submit"
                            disabled={form.processing || netIsNegative}
                        >
                            {form.processing ? 'Saving...' : 'Update Payroll'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}