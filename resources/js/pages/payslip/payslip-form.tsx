import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { Payroll, Payslip } from '@/types/models';
import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

type Props = {
    // create mode: payrolls (wala pang payslip). edit mode: payslip
    payrolls?: Payroll[];
    payslip?: Payslip;
};

function formatCurrency(value: string): string {
    return Number(value).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' });
}

function employeeName(payroll?: Payroll): string {
    return [payroll?.employee?.first_name, payroll?.employee?.last_name, payroll?.employee?.suffix].filter(Boolean).join(' ') || '—';
}

function periodLabel(payroll?: Payroll): string {
    return payroll?.payroll_period ? `${payroll.payroll_period.start_date.slice(0, 10)} – ${payroll.payroll_period.end_date.slice(0, 10)}` : '—';
}

// Ang timestamp column ay "YYYY-MM-DD HH:MM:SS" (o ISO); ang datetime-local ay "YYYY-MM-DDTHH:MM"
const toDateTimeLocal = (value: string): string => value.replace(' ', 'T').slice(0, 16);

function nowLocal(): string {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());

    return now.toISOString().slice(0, 16);
}

export default function PayslipForm({ payrolls = [], payslip }: Props) {
    const isEdit = payslip !== undefined;

    const form = useForm({
        payroll_id: payslip ? String(payslip.payroll_id) : '',
        date_generated: payslip ? toDateTimeLocal(payslip.date_generated) : nowLocal(),
    });

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        if (isEdit) {
            // Petsa lang ang binabago sa edit
            form.transform((data) => ({ date_generated: data.date_generated }));
            form.patch(route('payslips.update', payslip.id), { preserveScroll: true });
        } else {
            form.post(route('payslips.store'), { preserveScroll: true });
        }
    }

    const noPayrolls = !isEdit && payrolls.length === 0;

    return (
        <form
            onSubmit={submit}
            className="space-y-6 rounded-2xl border border-emerald-800/10 bg-white p-5 shadow-sm sm:p-6 dark:border-emerald-400/15 dark:bg-[#07130f]"
        >
            {noPayrolls && (
                <div role="alert" className="rounded-lg border border-amber-600/25 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                    Every payroll already has a payslip, or no payroll has been processed yet.
                </div>
            )}

            <div className="grid min-w-0 gap-5 sm:grid-cols-2">
                <div className="space-y-2 sm:col-span-2">
                    <Label htmlFor="payroll_id">Payroll</Label>
                    {isEdit ? (
                        <Input
                            id="payroll_id"
                            value={`${employeeName(payslip.payroll)} · ${periodLabel(payslip.payroll)}`}
                            readOnly
                            tabIndex={-1}
                        />
                    ) : (
                        <Select value={form.data.payroll_id} onValueChange={(value) => form.setData('payroll_id', value)} disabled={form.processing || noPayrolls}>
                            <SelectTrigger id="payroll_id" aria-invalid={Boolean(form.errors.payroll_id)}>
                                <SelectValue placeholder="Select a payroll" />
                            </SelectTrigger>
                            <SelectContent>
                                {payrolls.map((payroll) => (
                                    <SelectItem key={payroll.id} value={String(payroll.id)}>
                                        {employeeName(payroll)} · {periodLabel(payroll)} · {formatCurrency(payroll.net_pay)}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    )}
                    <InputError message={form.errors.payroll_id} />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="date_generated">Date generated</Label>
                    <Input
                        id="date_generated"
                        type="datetime-local"
                        value={form.data.date_generated}
                        onChange={(event) => form.setData('date_generated', event.target.value)}
                        required
                        disabled={form.processing}
                        aria-invalid={Boolean(form.errors.date_generated)}
                    />
                    <InputError message={form.errors.date_generated} />
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
                    <Link href={route('payslips.index')}>Cancel</Link>
                </Button>
                <Button
                    className="bg-emerald-700 text-white hover:bg-emerald-800 dark:bg-emerald-600 dark:hover:bg-emerald-500"
                    type="submit"
                    disabled={form.processing || noPayrolls}
                >
                    {form.processing ? 'Saving...' : isEdit ? 'Update payslip' : 'Generate payslip'}
                </Button>
            </div>
        </form>
    );
}