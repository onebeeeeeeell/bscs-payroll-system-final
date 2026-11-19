import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ContributionType } from '@/types/models';
import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

type Props = {
    // Walang contributionType = create mode; mayroon = edit mode
    contributionType?: ContributionType;
};

export default function TypeForm({ contributionType }: Props) {
    const isEdit = contributionType !== undefined;

    const form = useForm({ name: contributionType?.name ?? '' });

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        if (isEdit) {
            form.patch(route('contribution_types.update', contributionType.id), { preserveScroll: true });
        } else {
            form.post(route('contribution_types.store'), { preserveScroll: true });
        }
    }

    return (
        <form
            onSubmit={submit}
            className="space-y-6 rounded-2xl border border-emerald-800/10 bg-white p-5 shadow-sm sm:p-6 dark:border-emerald-400/15 dark:bg-[#07130f]"
        >
            <div className="space-y-2">
                <Label htmlFor="name">Name</Label>
                <Input
                    id="name"
                    value={form.data.name}
                    onChange={(event) => form.setData('name', event.target.value)}
                    maxLength={30}
                    placeholder="e.g. SSS, PhilHealth, Pag-IBIG"
                    required
                    disabled={form.processing}
                    aria-invalid={Boolean(form.errors.name)}
                />
                <p className="text-muted-foreground text-xs">Letters and spaces only, up to 30 characters.</p>
                <InputError message={form.errors.name} />
            </div>

            <div className="flex flex-col-reverse justify-end gap-3 sm:flex-row">
                <Button
                    variant="outline"
                    className="border-emerald-700/25 text-emerald-800 hover:bg-emerald-50 dark:border-emerald-400/25 dark:text-emerald-300 dark:hover:bg-emerald-950/50"
                    type="button"
                    disabled={form.processing}
                    asChild
                >
                    <Link href={route('contribution_types.index')}>Cancel</Link>
                </Button>
                <Button
                    className="bg-emerald-700 text-white hover:bg-emerald-800 dark:bg-emerald-600 dark:hover:bg-emerald-500"
                    type="submit"
                    disabled={form.processing}
                >
                    {form.processing ? 'Saving...' : isEdit ? 'Update type' : 'Create type'}
                </Button>
            </div>
        </form>
    );
}