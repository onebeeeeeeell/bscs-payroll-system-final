export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}
export interface User {
    id: number;
    name: string;
    email: string;
}
export interface Position {
    id: number;
    title: string;
}
export interface Department {
    id: number;
    department_name: string;
}
export interface ContributionType {
    id: number;
    name: string;
}
export interface EmploymentStatus {
    id: number;
    name: string;
}
export interface PayrollPeriod {
    id: number;
    start_date: string;
    end_date: string;
    pay_date: string;
    status: 'Pending' | 'Processing' | 'Paid' | 'Failed';
    archived_at?: string | null;
}
export interface Employee {
    id: number;
    first_name: string;
    last_name: string;
    suffix: string | null;
    position_id: number;
    department_id: number;
    date_hired: string;
    daily_rate: string;
    sss_no: string | null;
    philhealth_no: string | null;
    pagibig_no: string | null;
    tin_no: string | null;
    status: string;
    archived_at: string | null;
    position?: Position;
    department?: Department;
    created_at: string;
}
export interface Attendance {
    id: number;
    employee_id: number;
    work_date: string;
    time_in: string;
    time_out: string | null;
    hours_worked: string;
    overtime_hours: string;
    employee?: Employee;
    created_at: string;
}
// one row of the attendance list: the totals of one employee (grouped query, no record id)
export interface AttendanceEmployeeSummary {
    employee_id: number;
    records: number;
    total_hours: string;
    total_overtime: string;
    last_work_date: string;
    employee?: Employee;
}
export interface Payroll {
    id: number;
    employee_id: number;
    payroll_period_id: number;
    user_id: number;
    gross_pay: string;
    total_deductions: string;
    net_pay: string;
    date_processed: string;
    archived_at?: string | null;
    employee?: Employee;
    payroll_period?: PayrollPeriod;
    user?: User;
    created_at: string;
}
export interface Payslip {
    id: number;
    payroll_id: number;
    date_generated: string;
    archived_at?: string | null;
    payroll?: Payroll;
    created_at: string;
}
// one row of the payslip list: the totals of one employee (grouped query, no payslip id)
export interface PayslipEmployeeSummary {
    employee_id: number;
    payslips_count: number;
    total_net_pay: string;
    last_generated: string;
    employee?: Employee;
}
export interface ContributionRate {
    id: number;
    contribution_type_id: number;
    calculation_type: 'fixed' | 'percent';
    rate: string;
    contribution_type?: ContributionType;
    created_at: string;
}
export interface Deduction {
    id: number;
    payroll_id: number;
    contribution_rate_id: number;
    amount: string;
    payroll?: Payroll;
    contribution_rate?: ContributionRate;
    created_at: string;
}

export type PayrollRowStatus = 'ready' | 'no_attendance' | 'open_time_out' | 'deductions_exceed_gross' | 'gross_pay_exceeds_limit';

export interface PayrollPreviewRow {
    employee_id: number;
    name: string;
    paid_days: number;
    overtime_hours: number;
    gross_pay: number;
    total_deductions: number;
    net_pay: number;
    status: PayrollRowStatus;
}

export interface PayrollPreviewSummary {
    employees: number;
    ready: number;
    no_attendance: number;
    blocked: number;
    gross_pay: number;
    total_deductions: number;
    net_pay: number;
}

export interface PayrollPreview {
    rows: PayrollPreviewRow[];
    summary: PayrollPreviewSummary;
}