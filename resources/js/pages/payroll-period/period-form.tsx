import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { PayrollPeriod } from '@/types/models';
import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

type Props = {
    // Walang payrollPeriod = create mode; mayroon = edit mode
    payrollPeriod?: PayrollPeriod;
};

const STATUSES: PayrollPeriod['status'][] = ['Pending', 'Processing', 'Paid', 'Failed'];

// Galing DB ay "YYYY-MM-DD" (o ISO); ang <input type="date"> ay "YYYY-MM-DD" lang
const toDateInput = (value?: string): string => (value ? value.slice(0, 10) : '');

export default function PeriodForm({ payrollPeriod }: Props) {
    const isEdit = payrollPeriod !== undefined;
    const now = new Date();
    const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;

    const form = useForm({
        start_date: toDateInput(payrollPeriod?.start_date),
        end_date: toDateInput(payrollPeriod?.end_date),
        pay_date: toDateInput(payrollPeriod?.pay_date),
        // Bagong period ay laging Pending; status lang ang pwedeng baguhin sa edit
        status: payrollPeriod?.status ?? 'Pending',
    });

    const startBeforeToday = form.data.start_date !== '' && form.data.start_date < today;
    const endBeforeStart = form.data.start_date !== '' && form.data.end_date !== '' && form.data.end_date < form.data.start_date;
    const endBeforeToday = form.data.end_date !== '' && form.data.end_date < today;
    const payBeforeToday = form.data.pay_date !== '' && form.data.pay_date < today;
    const payBeforeEnd = form.data.pay_date !== '' && form.data.end_date !== '' && form.data.pay_date < form.data.end_date;
    const endMin = form.data.start_date > today ? form.data.start_date : today;
    const payMin = form.data.end_date > today ? form.data.end_date : today;
    const hasDateErrors = startBeforeToday || endBeforeStart || endBeforeToday || payBeforeToday || payBeforeEnd;

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        if (hasDateErrors) {
            return;
        }

        if (isEdit) {
            form.patch(route('payroll_periods.update', payrollPeriod.id), { preserveScroll: true });
        } else {
            form.post(route('payroll_periods.store'), { preserveScroll: true });
        }
    }

    return (
        <form
            onSubmit={submit}
            className="space-y-6 rounded-2xl border border-emerald-800/10 bg-white p-5 shadow-sm sm:p-6 dark:border-emerald-400/15 dark:bg-[#07130f]"
        >
            <div className="grid min-w-0 gap-5 sm:grid-cols-2">
                <div className="space-y-2">
                    <Label htmlFor="start_date">Start date</Label>
                    <Input
                        id="start_date"
                        type="date"
                        min={today}
                        value={form.data.start_date}
                        onChange={(event) => form.setData('start_date', event.target.value)}
                        required
                        disabled={form.processing}
                        aria-invalid={startBeforeToday || Boolean(form.errors.start_date)}
                    />
                    {startBeforeToday ? (
                        <p className="text-destructive text-sm">Start date cannot be in the past.</p>
                    ) : (
                        <InputError message={form.errors.start_date} />
                    )}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="end_date">End date</Label>
                    <Input
                        id="end_date"
                        type="date"
                        min={endMin}
                        value={form.data.end_date}
                        onChange={(event) => form.setData('end_date', event.target.value)}
                        required
                        disabled={form.processing}
                        aria-invalid={endBeforeStart || endBeforeToday || Boolean(form.errors.end_date)}
                    />
                    {endBeforeStart ? (
                        <p className="text-destructive text-sm">End date cannot be before the start date.</p>
                    ) : endBeforeToday ? (
                        <p className="text-destructive text-sm">End date cannot be in the past.</p>
                    ) : (
                        <InputError message={form.errors.end_date} />
                    )}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="pay_date">Pay date</Label>
                    <Input
                        id="pay_date"
                        type="date"
                        min={payMin}
                        value={form.data.pay_date}
                        onChange={(event) => form.setData('pay_date', event.target.value)}
                        required
                        disabled={form.processing}
                        aria-invalid={payBeforeToday || payBeforeEnd || Boolean(form.errors.pay_date)}
                    />
                    {payBeforeEnd ? (
                        <p className="text-destructive text-sm">Pay date cannot be before the end date.</p>
                    ) : payBeforeToday ? (
                        <p className="text-destructive text-sm">Pay date cannot be in the past.</p>
                    ) : (
                        <InputError message={form.errors.pay_date} />
                    )}
                </div>

                {isEdit && (
                    <div className="space-y-2">
                        <Label htmlFor="status">Status</Label>
                        <Select
                            value={form.data.status}
                            onValueChange={(value) => form.setData('status', value as PayrollPeriod['status'])}
                            disabled={form.processing}
                        >
                            <SelectTrigger id="status" aria-invalid={Boolean(form.errors.status)}>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {STATUSES.map((status) => (
                                    <SelectItem key={status} value={status}>
                                        {status}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.status} />
                    </div>
                )}
            </div>

            <div className="flex flex-col-reverse justify-end gap-3 sm:flex-row">
                <Button
                    variant="outline"
                    className="border-emerald-700/25 text-emerald-800 hover:bg-emerald-50 dark:border-emerald-400/25 dark:text-emerald-300 dark:hover:bg-emerald-950/50"
                    type="button"
                    disabled={form.processing}
                    asChild
                >
                    <Link href={route('payroll_periods.index')}>Cancel</Link>
                </Button>
                <Button
                    className="bg-emerald-700 text-white hover:bg-emerald-800 dark:bg-emerald-600 dark:hover:bg-emerald-500"
                    type="submit"
                    disabled={form.processing || hasDateErrors}
                >
                    {form.processing ? 'Saving...' : isEdit ? 'Update period' : 'Create period'}
                </Button>
            </div>
        </form>
    );
}