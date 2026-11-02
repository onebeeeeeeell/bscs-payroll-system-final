import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
// DTO - Data Transfer Object
import type { Employee, PaginatedData } from '@/types/models';
import { Head, Link } from '@inertiajs/react';

// Types
type Props = {
    employees: PaginatedData<Employee>;
};

export default function EmployeeIndex({ employees }: Props) {
    return (
        <AppLayout>
            <Head title="Employees" />
            <div className="to-background dark:to-background flex w-full min-w-0 flex-col gap-6 bg-gradient-to-b from-emerald-50/60 p-4 md:p-6 dark:from-emerald-950/20">
                <div className="flex min-w-0 flex-col justify-between gap-4 rounded-2xl border border-emerald-800/10 bg-white/80 p-5 shadow-sm sm:flex-row sm:items-center sm:p-6 dark:border-emerald-400/15 dark:bg-[#07130f]">
                    <div className="space-y-1">
                        <p className="text-xs font-semibold tracking-[0.16em] text-emerald-700 uppercase dark:text-emerald-400">Team management</p>
                        <h1 className="text-2xl font-semibold tracking-tight">Employees</h1>
                        <p className="text-muted-foreground text-sm">Manage employee records</p>
                    </div>
                    <Button
                        className="w-full bg-emerald-700 text-white hover:bg-emerald-800 sm:w-auto dark:bg-emerald-600 dark:hover:bg-emerald-500"
                        asChild
                    >
                        <Link href={route('employees.create')}>Add Employee</Link>
                    </Button>
                </div>

                <div className="hidden overflow-x-auto rounded-xl border border-emerald-800/10 bg-white shadow-sm md:block dark:border-emerald-400/15 dark:bg-[#07130f]">
                    <table className="w-full min-w-[760px] text-sm">
                        <thead>
                            <tr className="border-b border-emerald-800/10 bg-emerald-50/70 text-left dark:border-emerald-400/15 dark:bg-emerald-950/30">
                                <th className="px-4 py-3 font-medium">Employee</th>
                                <th className="px-4 py-3 font-medium">Position</th>
                                <th className="px-4 py-3 font-medium">Department</th>
                                <th className="px-4 py-3 font-medium">Date Hired</th>
                                <th className="px-4 py-3 font-medium">Daily Rate</th>
                                <th className="px-4 py-3 font-medium">Status</th>
                                <th className="px-4 py-3 text-right font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {employees.data.length > 0 ? (
                                employees.data.map((employee) => (
                                    <tr
                                        key={employee.id}
                                        className="border-border/70 border-b transition-colors last:border-0 hover:bg-emerald-50/40 dark:hover:bg-emerald-950/20"
                                    >
                                        <td className="px-4 py-3">
                                            {[employee.first_name, employee.last_name, employee.suffix].filter(Boolean).join(' ')}
                                        </td>
                                        <td className="px-4 py-3">{employee.position?.title ?? '—'}</td>
                                        <td className="px-4 py-3">{employee.department?.department_name ?? '—'}</td>
                                        <td className="px-4 py-3">{employee.date_hired}</td>
                                        <td className="px-4 py-3">
                                            {Number(employee.daily_rate).toLocaleString(undefined, {
                                                minimumFractionDigits: 2,
                                                maximumFractionDigits: 2,
                                            })}
                                        </td>
                                        <td className="px-4 py-3">{employee.status}</td>
                                        <td className="px-4 py-3">
                                            <div className="flex justify-end gap-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    className="border-emerald-700/25 text-emerald-800 hover:bg-emerald-50 dark:border-emerald-400/25 dark:text-emerald-300 dark:hover:bg-emerald-950/50"
                                                    asChild
                                                >
                                                    <Link href={route('employees.edit', employee.id)}>Edit</Link>
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={7} className="text-muted-foreground px-4 py-10 text-center">
                                        No employees found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="grid min-w-0 gap-3 md:hidden">
                    {employees.data.length > 0 ? (
                        employees.data.map((employee) => (
                            <article
                                key={employee.id}
                                className="min-w-0 space-y-4 rounded-xl border border-emerald-800/10 bg-white p-4 shadow-sm dark:border-emerald-400/15 dark:bg-[#07130f]"
                            >
                                <div className="min-w-0">
                                    <h2 className="font-semibold break-words">
                                        {[employee.first_name, employee.last_name, employee.suffix].filter(Boolean).join(' ')}
                                    </h2>
                                    <p className="text-muted-foreground text-sm break-words">
                                        {employee.position?.title ?? 'Position not set'}
                                        {employee.department?.department_name ? ` · ${employee.department.department_name}` : ''}
                                    </p>
                                </div>
                                <dl className="grid grid-cols-2 gap-x-3 gap-y-2 text-sm">
                                    <div>
                                        <dt className="text-muted-foreground">Status</dt>
                                        <dd className="break-words">{employee.status}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground">Daily rate</dt>
                                        <dd>
                                            {Number(employee.daily_rate).toLocaleString(undefined, {
                                                minimumFractionDigits: 2,
                                                maximumFractionDigits: 2,
                                            })}
                                        </dd>
                                    </div>
                                    <div className="col-span-2">
                                        <dt className="text-muted-foreground">Date hired</dt>
                                        <dd>{employee.date_hired}</dd>
                                    </div>
                                </dl>
                                <Button
                                    className="w-full border-emerald-700/25 text-emerald-800 hover:bg-emerald-50 dark:border-emerald-400/25 dark:text-emerald-300 dark:hover:bg-emerald-950/50"
                                    variant="outline"
                                    asChild
                                >
                                    <Link href={route('employees.edit', employee.id)}>Edit Employee</Link>
                                </Button>
                            </article>
                        ))
                    ) : (
                        <p className="text-muted-foreground rounded-xl border px-4 py-10 text-center text-sm">No employees found.</p>
                    )}
                </div>

                <nav aria-label="Employee pagination" className="flex flex-wrap justify-end gap-2">
                    {employees.links.map((link, index) => {
                        const label = link.label.replace(/&laquo;|&raquo;/g, '').trim();

                        return link.url ? (
                            <Link
                                key={`${link.label}-${index}`}
                                href={link.url}
                                preserveScroll
                                className={`rounded-md border px-3 py-2 text-sm ${
                                    link.active ? 'bg-primary text-primary-foreground' : 'bg-background'
                                }`}
                                aria-current={link.active ? 'page' : undefined}
                            >
                                {label}
                            </Link>
                        ) : (
                            <span key={`${link.label}-${index}`} className="text-muted-foreground rounded-md border px-3 py-2 text-sm">
                                {label}
                            </span>
                        );
                    })}
                </nav>
            </div>
        </AppLayout>
    );
}
