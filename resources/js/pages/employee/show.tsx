import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { Employee } from '@/types/models';
import { Head, Link } from '@inertiajs/react';

type Props = {
    employee: Employee;
};

function Detail({ label, value }: { label: string; value: string | null | undefined }) {
    return (
        <div className="space-y-1">
            <dt className="text-muted-foreground text-sm">{label}</dt>
            <dd className="font-medium break-words">{value || '—'}</dd>
        </div>
    );
}

export default function EmployeeShow({ employee }: Props) {
    const employeeName = [employee.first_name, employee.last_name, employee.suffix].filter(Boolean).join(' ');

    return (
        <AppLayout>
            <Head title={employeeName} />
            <div className="to-background dark:to-background mx-auto flex w-full max-w-6xl min-w-0 flex-col gap-6 bg-gradient-to-b from-emerald-50/60 p-4 md:p-6 dark:from-emerald-950/20">
                <header className="flex flex-col justify-between gap-4 rounded-2xl border border-emerald-800/10 bg-white/80 p-5 shadow-sm lg:flex-row lg:items-center lg:p-6 dark:border-emerald-400/15 dark:bg-[#07130f]">
                    <div className="space-y-1">
                        <p className="text-xs font-semibold tracking-[0.16em] text-emerald-700 uppercase dark:text-emerald-400">Team management</p>
                        <h1 className="text-2xl font-semibold tracking-tight">{employeeName}</h1>
                        <p className="text-muted-foreground text-sm">Employee details</p>
                    </div>
                    <div className="flex flex-col gap-2 lg:flex-row">
                        <Button
                            variant="outline"
                            className="w-full border-emerald-700/25 text-emerald-800 hover:bg-emerald-50 lg:w-auto dark:border-emerald-400/25 dark:text-emerald-300 dark:hover:bg-emerald-950/50"
                            asChild
                        >
                            <Link href={route('employees.index')}>Back to Employees</Link>
                        </Button>
                        <Button
                            className="w-full bg-emerald-700 text-white hover:bg-emerald-800 lg:w-auto dark:bg-emerald-600 dark:hover:bg-emerald-500"
                            asChild
                        >
                            <Link href={route('employees.edit', employee.id)}>Edit Employee</Link>
                        </Button>
                    </div>
                </header>

                <section className="rounded-2xl border border-emerald-800/10 bg-white p-5 shadow-sm sm:p-6 dark:border-emerald-400/15 dark:bg-[#07130f]">
                    <h2 className="mb-5 text-lg font-semibold">Personal and employment information</h2>
                    <dl className="grid gap-x-6 gap-y-5 lg:grid-cols-2">
                        <Detail label="First name" value={employee.first_name} />
                        <Detail label="Last name" value={employee.last_name} />
                        <Detail label="Suffix" value={employee.suffix} />
                        <Detail label="Position" value={employee.position?.title} />
                        <Detail label="Department" value={employee.department?.department_name} />
                        <Detail label="Employment status" value={employee.status} />
                        <Detail label="Date hired" value={employee.date_hired} />
                        <Detail
                            label="Daily rate"
                            value={Number(employee.daily_rate).toLocaleString('en-PH', {
                                style: 'currency',
                                currency: 'PHP',
                            })}
                        />
                    </dl>
                </section>

                <section className="rounded-2xl border border-emerald-800/10 bg-white p-5 shadow-sm sm:p-6 dark:border-emerald-400/15 dark:bg-[#07130f]">
                    <h2 className="mb-5 text-lg font-semibold">Government identification</h2>
                    <dl className="grid gap-x-6 gap-y-5 lg:grid-cols-2">
                        <Detail label="SSS number" value={employee.sss_no} />
                        <Detail label="PhilHealth number" value={employee.philhealth_no} />
                        <Detail label="Pag-IBIG number" value={employee.pagibig_no} />
                        <Detail label="TIN" value={employee.tin_no} />
                    </dl>
                </section>
            </div>
        </AppLayout>
    );
}
