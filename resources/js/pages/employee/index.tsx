import { Archive, MoreHorizontal, RotateCcw } from 'lucide-react';

import { DataTable, type DataTableColumn } from '@/components/data-table';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/app-layout';
import type { SharedData } from '@/types';
import type { Department, Employee, PaginatedData, Position } from '@/types/models';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

// Types
type Props = {
    employees: PaginatedData<Employee>;
    departments: Pick<Department, 'id' | 'department_name'>[];
    positions: Pick<Position, 'id' | 'title'>[];
    hireYears: number[];
    filters: {
        department_id: string;
        position_id: string;
        hire_year: string;
        hire_month: string;
        status: 'active' | 'archived';
    };
};

const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

export default function EmployeeIndex({ employees, departments, positions, hireYears, filters: initialFilters }: Props) {
    const [filters, setFilters] = useState(initialFilters);
    const [archiveCandidate, setArchiveCandidate] = useState<Employee | null>(null);
    const [isArchiving, setIsArchiving] = useState(false);
    const [restoringEmployeeId, setRestoringEmployeeId] = useState<number | null>(null);
    const { flash } = usePage<SharedData>().props;

    useEffect(() => {
        setFilters(initialFilters);
    }, [initialFilters]);

    function updateFilter<K extends keyof typeof filters>(name: K, value: (typeof filters)[K]) {
        const nextFilters = { ...filters, [name]: value };
        setFilters(nextFilters);
        router.get(route('employees.index'), nextFilters, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    }

    function archiveEmployee() {
        if (!archiveCandidate) return;

        setIsArchiving(true);
        router.delete(route('employees.destroy', archiveCandidate.id), {
            data: filters,
            preserveScroll: true,
            onSuccess: () => setArchiveCandidate(null),
            onFinish: () => setIsArchiving(false),
        });
    }

    function restoreEmployee(employee: Employee) {
        setRestoringEmployeeId(employee.id);
        router.patch(route('employees.restore', employee.id), filters, {
            preserveScroll: true,
            onFinish: () => setRestoringEmployeeId(null),
        });
    }

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

                {flash.success && (
                    <div
                        role="status"
                        className="rounded-lg border border-emerald-700/20 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-400/20 dark:bg-emerald-950/30 dark:text-emerald-200"
                    >
                        {flash.success}
                    </div>
                )}

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
                    maxSearchLength={30}
                    emptyMessage="No employees found."
                    toolbar={
                        <div className="flex w-full flex-wrap gap-2 sm:w-auto">
                            <select
                                aria-label="Filter by department"
                                value={filters.department_id}
                                onChange={(event) => updateFilter('department_id', event.target.value)}
                                className="border-input bg-background h-10 min-w-36 rounded-md border px-3 text-sm"
                            >
                                <option value="">All departments</option>
                                {departments.map((department) => (
                                    <option key={department.id} value={department.id}>
                                        {department.department_name}
                                    </option>
                                ))}
                            </select>
                            <select
                                aria-label="Filter by position"
                                value={filters.position_id}
                                onChange={(event) => updateFilter('position_id', event.target.value)}
                                className="border-input bg-background h-10 min-w-36 rounded-md border px-3 text-sm"
                            >
                                <option value="">All positions</option>
                                {positions.map((position) => (
                                    <option key={position.id} value={position.id}>
                                        {position.title}
                                    </option>
                                ))}
                            </select>
                            <select
                                aria-label="Filter by date hired year"
                                value={filters.hire_year}
                                onChange={(event) => updateFilter('hire_year', event.target.value)}
                                className="border-input bg-background h-10 min-w-32 rounded-md border px-3 text-sm"
                            >
                                <option value="">All hire years</option>
                                {hireYears.map((year) => (
                                    <option key={year} value={year}>
                                        {year}
                                    </option>
                                ))}
                            </select>
                            <select
                                aria-label="Filter by date hired month"
                                value={filters.hire_month}
                                onChange={(event) => updateFilter('hire_month', event.target.value)}
                                className="border-input bg-background h-10 min-w-36 rounded-md border px-3 text-sm"
                            >
                                <option value="">All hire months</option>
                                {months.map((month, index) => (
                                    <option key={month} value={index + 1}>
                                        {month}
                                    </option>
                                ))}
                            </select>
                            <select
                                aria-label="Filter by employee archive status"
                                value={filters.status}
                                onChange={(event) => updateFilter('status', event.target.value === 'archived' ? 'archived' : 'active')}
                                className="border-input bg-background h-10 min-w-36 rounded-md border px-3 text-sm"
                            >
                                <option value="active">Active</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                    }
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
                                    Number(employee.daily_rate).toLocaleString('en-PH', {
                                        style: 'currency',
                                        currency: 'PHP',
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
                                            {filters.status === 'active' ? (
                                                <>
                                                    <DropdownMenuItem asChild>
                                                        <Link href={route('employees.edit', employee.id)}>Edit</Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        className="text-destructive focus:text-destructive"
                                                        onSelect={() => setArchiveCandidate(employee)}
                                                    >
                                                        <Archive aria-hidden="true" />
                                                        Archive
                                                    </DropdownMenuItem>
                                                </>
                                            ) : (
                                                <DropdownMenuItem
                                                    disabled={restoringEmployeeId === employee.id}
                                                    onSelect={() => restoreEmployee(employee)}
                                                >
                                                    <RotateCcw aria-hidden="true" />
                                                    {restoringEmployeeId === employee.id ? 'Restoring...' : 'Restore'}
                                                </DropdownMenuItem>
                                            )}
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

                <Dialog
                    open={archiveCandidate !== null}
                    onOpenChange={(open) => {
                        if (!open && !isArchiving) setArchiveCandidate(null);
                    }}
                >
                    <DialogContent>
                        <DialogTitle>Archive employee?</DialogTitle>
                        <DialogDescription>
                            {archiveCandidate
                                ? `${[archiveCandidate.first_name, archiveCandidate.last_name, archiveCandidate.suffix].filter(Boolean).join(' ')} will be removed from the active employee list. Existing payroll and attendance history will be kept.`
                                : ''}
                        </DialogDescription>
                        <DialogFooter>
                            <Button variant="outline" disabled={isArchiving} onClick={() => setArchiveCandidate(null)}>
                                Cancel
                            </Button>
                            <Button variant="destructive" disabled={isArchiving} onClick={archiveEmployee}>
                                {isArchiving ? 'Archiving...' : 'Archive employee'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
