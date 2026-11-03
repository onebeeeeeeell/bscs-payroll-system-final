import { MoreHorizontal } from 'lucide-react';

import { DataTable, type DataTableColumn } from '@/components/data-table';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/app-layout';
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

                <DataTable
                    data={employees.data}
                    getRowId={(employee) => employee.id}
                    getSearchValue={(employee) =>
                        [
                            employee.first_name,
                            employee.last_name,
                            employee.suffix,
                            employee.position?.title,
                            employee.department?.department_name,
                            employee.date_hired,
                            employee.daily_rate,
                            employee.status,
                        ]
                            .filter(Boolean)
                            .join(' ')
                    }
                    searchPlaceholder="Search employees..."
                    emptyMessage="No employees found."
                    columns={
                        [
                            {
                                header: 'Employee',
                                cell: (employee) => [employee.first_name, employee.last_name, employee.suffix].filter(Boolean).join(' '),
                            },
                            { header: 'Position', cell: (employee) => employee.position?.title ?? '—' },
                            { header: 'Department', cell: (employee) => employee.department?.department_name ?? '—' },
                            { header: 'Date Hired', cell: (employee) => employee.date_hired },
                            {
                                header: 'Daily Rate',
                                cell: (employee) =>
                                    Number(employee.daily_rate).toLocaleString(undefined, {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2,
                                    }),
                            },
                            { header: 'Status', cell: (employee) => employee.status },
                            {
                                header: 'Actions',
                                className: 'text-right',
                                cell: (employee) => (
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost" size="icon" aria-label={`Actions for employee ${employee.id}`}>
                                                <MoreHorizontal className="size-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem asChild>
                                                <Link href={route('employees.show', employee.id)}>View</Link>
                                            </DropdownMenuItem>
                                            <DropdownMenuItem asChild>
                                                <Link href={route('employees.edit', employee.id)}>Edit</Link>
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                ),
                            },
                        ] satisfies DataTableColumn<Employee>[]
                    }
                />

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
