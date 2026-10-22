import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import type { Employee, PaginatedData } from '@/types/models';
import { Head, Link, router } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';

type Props = {
    employees: PaginatedData<Employee>;
    filters: {
        search: string;
    };
};

export default function EmployeeIndex({ employees, filters }: Props) {
    const [search, setSearch] = useState(filters.search);

    function submitSearch(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        router.get(route('employees.index'), { search }, { preserveState: true, replace: true });
    }

    return (
        <AppLayout>
            <Head title="Employees" />
            <div className="flex w-full flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div className="space-y-1">
                        <h1 className="text-2xl font-semibold tracking-tight">Employees</h1>
                        <p className="text-muted-foreground text-sm">Manage employee records.</p>
                    </div>
                    <Button asChild>
                        <Link href={route('employees.create')}>Add Employee</Link>
                    </Button>
                </div>

                <form onSubmit={submitSearch} className="flex flex-col gap-2 sm:flex-row">
                    <Input
                        aria-label="Search employees"
                        placeholder="Search name or identification number"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                    />
                    <Button type="submit">Search</Button>
                    {filters.search && (
                        <Button type="button" variant="outline" asChild>
                            <Link href={route('employees.index')}>Clear</Link>
                        </Button>
                    )}
                </form>

                <div className="bg-card text-card-foreground overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left">
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
                                    <tr key={employee.id} className="border-b last:border-0">
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
                                                <Button variant="outline" size="sm" asChild>
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
