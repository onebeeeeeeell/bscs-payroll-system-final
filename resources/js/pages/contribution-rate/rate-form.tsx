import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { sanitizeAmount, trimTrailingDot } from '@/lib/utils';
import type { ContributionRate, ContributionType } from '@/types/models';
import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

type Props = {
    // create mode: contributionTypes. edit mode: contributionRate (+ locked kung nagamit na)
    contributionTypes?: ContributionType[];
    contributionRate?: ContributionRate;
    locked?: boolean;
};

export default function RateForm({ contributionTypes = [], contributionRate, locked = false }: Props) {
    const isEdit = contributionRate !== undefined;

    const form = useForm({
        contribution_type_id: contributionRate ? String(contributionRate.contribution_type_id) : '',
        calculation_type: contributionRate?.calculation_type ?? 'fixed',
        rate: contributionRate?.rate ?? '',
    });

    const isPercent = form.data.calculation_type === 'percent';

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        if (locked) return;

        if (isEdit) {
            // Calculation type at rate lang ang binabago sa edit; hindi na ipinapadala ang contribution type
            form.transform((data) => ({ calculation_type: data.calculation_type, rate: trimTrailingDot(data.rate) }));
            form.patch(route('contribution_rates.update', contributionRate.id), { preserveScroll: true });
        } else {
            form.transform((data) => ({ ...data, rate: trimTrailingDot(data.rate) }));
            form.post(route('contribution_rates.store'), { preserveScroll: true });
        }
    }

    const noTypes = !isEdit && contributionTypes.length === 0;

    return (
        <form
            onSubmit={submit}
            className="space-y-6 rounded-2xl border border-emerald-800/10 bg-white p-5 shadow-sm sm:p-6 dark:border-emerald-400/15 dark:bg-[#07130f]"
        >
            {locked && (
                <div role="alert" className="rounded-lg border border-amber-600/25 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                    This rate was already used in payroll, so it can&apos;t be changed. To use a different amount, add a new rate for the same type.
                </div>
            )}

            {noTypes && (
                <div role="alert" className="rounded-lg border border-amber-600/25 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                    There are no contribution types yet.{' '}
                    <Link href={route('contribution_types.create')} className="underline">
                        Create one first
                    </Link>
                    .
                </div>
            )}

            <div className="grid min-w-0 gap-5 sm:grid-cols-2">
                <div className="space-y-2">
                    <Label htmlFor="contribution_type_id">Contribution type</Label>
                    {isEdit ? (
                        <Input id="contribution_type_id" value={contributionRate.contribution_type?.name ?? '—'} readOnly tabIndex={-1} />
                    ) : (
                        <Select
                            value={form.data.contribution_type_id}
                            onValueChange={(value) => form.setData('contribution_type_id', value)}
                            disabled={form.processing || noTypes}
                        >
                            <SelectTrigger id="contribution_type_id" aria-invalid={Boolean(form.errors.contribution_type_id)}>
                                <SelectValue placeholder="Select a type" />
                            </SelectTrigger>
                            <SelectContent>
                                {contributionTypes.map((type) => (
                                    <SelectItem key={type.id} value={String(type.id)}>
                                        {type.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    )}
                    <InputError message={form.errors.contribution_type_id} />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="calculation_type">Calculation</Label>
                    <Select
                        value={form.data.calculation_type}
                        onValueChange={(value) => form.setData('calculation_type', value as 'fixed' | 'percent')}
                        disabled={form.processing || locked}
                    >
                        <SelectTrigger id="calculation_type" aria-invalid={Boolean(form.errors.calculation_type)}>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="fixed">Fixed amount (PHP)</SelectItem>
                            <SelectItem value="percent">Percentage of gross pay (%)</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={form.errors.calculation_type} />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="rate">{isPercent ? 'Percentage of gross pay (%)' : 'Amount per payroll (PHP)'}</Label>
                    <Input
                        id="rate"
                        type="text"
                        inputMode="decimal"
                        maxLength={11}
                        autoComplete="off"
                        value={form.data.rate}
                        onChange={(event) => form.setData('rate', sanitizeAmount(event.target.value))}
                        required
                        disabled={form.processing || locked}
                        aria-invalid={Boolean(form.errors.rate)}
                    />
                    <p className="text-muted-foreground text-xs">
                        Numbers only, up to 2 decimals.{' '}
                        {isPercent
                            ? "Deducted as this percentage of each employee's gross pay for the period (max 100). The newest rate of each type is the one used."
                            : "A flat amount deducted from every employee's pay (max 30,000). The newest rate of each type is the one used."}
                    </p>
                    <InputError message={form.errors.rate} />
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
                    <Link href={route('contribution_rates.index')}>Cancel</Link>
                </Button>
                <Button
                    className="bg-emerald-700 text-white hover:bg-emerald-800 dark:bg-emerald-600 dark:hover:bg-emerald-500"
                    type="submit"
                    disabled={form.processing || locked || noTypes}
                >
                    {form.processing ? 'Saving...' : isEdit ? 'Update rate' : 'Add rate'}
                </Button>
            </div>
        </form>
    );
}