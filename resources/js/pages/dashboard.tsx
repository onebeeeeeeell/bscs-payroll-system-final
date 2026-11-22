import { Head, Link } from '@inertiajs/react';

import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import type { Payroll, PayrollPeriod } from '@/types/models';

// Types (same shape as DashboardService::data())
type PeriodStatus = {
    status: PayrollPeriod['status'];
    total: number;
};

type DepartmentCount = {
    id: number;
    department_name: string;
    employees_count: number;
};

type Props = {
    stats: {
        active_employees: number;
        pending_periods: number;
        payrolls: number;
        awaiting_payslip: number;
        gross_pay: number;
        total_deductions: number;
        net_pay: number;
    };
    recent_payrolls: Payroll[];
    period_statuses: PeriodStatus[];
    departments: DepartmentCount[];
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

// badge style per pay period status
const statusVariant: Record<PayrollPeriod['status'], 'default' | 'secondary' | 'outline' | 'destructive'> = {
    Pending: 'outline',
    Processing: 'secondary',
    Paid: 'default',
    Failed: 'destructive',
};

function peso(value: string | number): string {
    return Number(value).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' });
}

function formatDate(date: string): string {
    return new Date(`${date.slice(0, 10)}T00:00:00`).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function employeeName(payroll: Payroll): string {
    const employee = payroll.employee;

    return employee ? [employee.first_name, employee.last_name, employee.suffix].filter(Boolean).join(' ') : `Employee #${payroll.employee_id}`;
}

function periodRange(payroll: Payroll): string {
    const period = payroll.payroll_period;

    return period ? `${formatDate(period.start_date)} - ${formatDate(period.end_date)}` : '—';
}

// one card at the top
function StatCard({ label, value, hint }: { label: string; value: string; hint: string }) {
    return (
        <Card className="border-emerald-800/10 dark:border-emerald-400/15">
            <CardHeader>
                <CardDescription>{label}</CardDescription>
                <CardTitle className="text-3xl font-semibold tracking-tight">{value}</CardTitle>
            </CardHeader>
            <CardContent>
                <p className="text-muted-foreground text-xs">{hint}</p>
            </CardContent>
        </Card>
    );
}

export default function Dashboard({ stats, recent_payrolls, period_statuses, departments }: Props) {
    // the biggest department is 100% wide, the others are relative to it
    const maxEmployees = Math.max(1, ...departments.map((department) => department.employees_count));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="to-background dark:to-background flex w-full min-w-0 flex-col gap-6 bg-gradient-to-b from-emerald-50/60 p-4 md:p-6 dark:from-emerald-950/20">
                <header className="space-y-1">
                    <p className="text-xs font-semibold tracking-[0.16em] text-emerald-700 uppercase dark:text-emerald-400">Overview</p>
                    <h1 className="text-2xl font-semibold tracking-tight">Dashboard</h1>
                    <p className="text-muted-foreground text-sm">A quick look at pay periods, payrolls and payslips.</p>
                </header>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard label="Pending pay periods" value={stats.pending_periods.toLocaleString()} hint="Waiting to be processed" />
                    <StatCard label="Payrolls processed" value={stats.payrolls.toLocaleString()} hint="Active payroll records" />
                    <StatCard label="Awaiting payslip" value={stats.awaiting_payslip.toLocaleString()} hint="Payrolls without a payslip yet" />
                    <StatCard
                        label="Total net pay"
                        value={peso(stats.net_pay)}
                        hint={`${peso(stats.gross_pay)} gross less ${peso(stats.total_deductions)} deductions`}
                    />
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="border-emerald-800/10 lg:col-span-2 dark:border-emerald-400/15">
                        <CardHeader>
                            <CardTitle>Recent payrolls</CardTitle>
                            <CardDescription>
                                Latest processed payrolls.{' '}
                                <Link href={route('payrolls.index')} className="text-emerald-700 underline-offset-4 hover:underline dark:text-emerald-400">
                                    View all payrolls
                                </Link>
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Employee</TableHead>
                                        <TableHead>Period</TableHead>
                                        <TableHead className="text-right">Gross</TableHead>
                                        <TableHead className="text-right">Deductions</TableHead>
                                        <TableHead className="text-right">Net pay</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {recent_payrolls.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={5} className="text-muted-foreground text-center">
                                                No payrolls processed yet.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        recent_payrolls.map((payroll) => (
                                            <TableRow key={payroll.id}>
                                                <TableCell className="font-medium">{employeeName(payroll)}</TableCell>
                                                <TableCell>{periodRange(payroll)}</TableCell>
                                                <TableCell className="text-right">{peso(payroll.gross_pay)}</TableCell>
                                                <TableCell className="text-right">{peso(payroll.total_deductions)}</TableCell>
                                                <TableCell className="text-right font-medium">{peso(payroll.net_pay)}</TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    <Card className="border-emerald-800/10 dark:border-emerald-400/15">
                        <CardHeader>
                            <CardTitle>Pay periods</CardTitle>
                            <CardDescription>
                                Active periods by status.{' '}
                                <Link
                                    href={route('payroll_periods.index')}
                                    className="text-emerald-700 underline-offset-4 hover:underline dark:text-emerald-400"
                                >
                                    View all
                                </Link>
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {period_statuses.map((period) => (
                                <div key={period.status} className="flex items-center justify-between">
                                    <Badge variant={statusVariant[period.status]}>{period.status}</Badge>
                                    <span className="font-medium">{period.total.toLocaleString()}</span>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                </div>

                <Card className="border-emerald-800/10 dark:border-emerald-400/15">
                    <CardHeader>
                        <CardTitle>Employees per department</CardTitle>
                        <CardDescription>
                            {stats.active_employees.toLocaleString()} active {stats.active_employees === 1 ? 'employee' : 'employees'} in total. Archived
                            employees are not counted.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {departments.length === 0 ? (
                            <p className="text-muted-foreground text-sm">No departments yet.</p>
                        ) : (
                            departments.map((department) => (
                                <div key={department.id} className="space-y-1">
                                    <div className="flex items-center justify-between text-sm">
                                        <span>{department.department_name}</span>
                                        <span className="font-medium">{department.employees_count.toLocaleString()}</span>
                                    </div>
                                    <div className="h-2 w-full overflow-hidden rounded-full bg-emerald-100 dark:bg-emerald-950">
                                        <div
                                            className="h-full rounded-full bg-emerald-600 dark:bg-emerald-500"
                                            style={{ width: `${(department.employees_count / maxEmployees) * 100}%` }}
                                        />
                                    </div>
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}