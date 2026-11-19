import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Department } from '@/types/models';
import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

type Props = {
    // Walang department = create mode; mayroon = edit mode
    department?: Department;
};

export default function DepartmentForm({ department }: Props) {
    const isEdit = department !== undefined;

    const form = useForm({ department_name: department?.department_name ?? '' });

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        if (isEdit) {
            form.patch(route('departments.update', department.id), { preserveScroll: true });
        } else {
            form.post(route('departments.store'), { preserveScroll: true });
        }
    }

    return (
        <form
            onSubmit={submit}
            className="space-y-6 rounded-2xl border border-emerald-800/10 bg-white p-5 shadow-sm sm:p-6 dark:border-emerald-400/15 dark:bg-[#07130f]"
        >
            <div className="space-y-2">
                <Label htmlFor="department_name">Department name</Label>
                <Input
                    id="department_name"
                    value={form.data.department_name}
                    onChange={(event) => form.setData('department_name', event.target.value)}
                    maxLength={30}
                    required
                    disabled={form.processing}
                    aria-invalid={Boolean(form.errors.department_name)}
                />
                <p className="text-muted-foreground text-xs">Letters and single spaces only, up to 30 characters. Must be unique.</p>
                <InputError message={form.errors.department_name} />
            </div>

            <div className="flex flex-col-reverse justify-end gap-3 sm:flex-row">
                <Button
                    variant="outline"
                    className="border-emerald-700/25 text-emerald-800 hover:bg-emerald-50 dark:border-emerald-400/25 dark:text-emerald-300 dark:hover:bg-emerald-950/50"
                    type="button"
                    disabled={form.processing}
                    asChild
                >
                    <Link href={route('departments.index')}>Cancel</Link>
                </Button>
                <Button
                    className="bg-emerald-700 text-white hover:bg-emerald-800 dark:bg-emerald-600 dark:hover:bg-emerald-500"
                    type="submit"
                    disabled={form.processing}
                >
                    {form.processing ? 'Saving...' : isEdit ? 'Update department' : 'Create department'}
                </Button>
            </div>
        </form>
    );
}