import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Position } from '@/types/models';
import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

type Props = {
    // Walang position = create mode; mayroon = edit mode
    position?: Position;
};

export default function PositionForm({ position }: Props) {
    const isEdit = position !== undefined;

    const form = useForm({ title: position?.title ?? '' });

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        if (isEdit) {
            form.patch(route('positions.update', position.id), { preserveScroll: true });
        } else {
            form.post(route('positions.store'), { preserveScroll: true });
        }
    }

    return (
        <form
            onSubmit={submit}
            className="space-y-6 rounded-2xl border border-emerald-800/10 bg-white p-5 shadow-sm sm:p-6 dark:border-emerald-400/15 dark:bg-[#07130f]"
        >
            <div className="space-y-2">
                <Label htmlFor="title">Position name</Label>
                <Input
                    id="title"
                    value={form.data.title}
                    onChange={(event) => form.setData('title', event.target.value)}
                    maxLength={30}
                    required
                    disabled={form.processing}
                    aria-invalid={Boolean(form.errors.title)}
                />
                <p className="text-muted-foreground text-xs">Letters and single spaces only, up to 30 characters. Must be unique.</p>
                <InputError message={form.errors.title} />
            </div>

            <div className="flex flex-col-reverse justify-end gap-3 sm:flex-row">
                <Button
                    variant="outline"
                    className="border-emerald-700/25 text-emerald-800 hover:bg-emerald-50 dark:border-emerald-400/25 dark:text-emerald-300 dark:hover:bg-emerald-950/50"
                    type="button"
                    disabled={form.processing}
                    asChild
                >
                    <Link href={route('positions.index')}>Cancel</Link>
                </Button>
                <Button
                    className="bg-emerald-700 text-white hover:bg-emerald-800 dark:bg-emerald-600 dark:hover:bg-emerald-500"
                    type="submit"
                    disabled={form.processing}
                >
                    {form.processing ? 'Saving...' : isEdit ? 'Update position' : 'Create position'}
                </Button>
            </div>
        </form>
    );
}