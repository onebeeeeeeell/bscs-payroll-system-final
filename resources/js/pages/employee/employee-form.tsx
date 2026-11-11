import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/popover';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Department, Employee, EmploymentStatus, Position } from '@/types/models';
import { Link, useForm } from '@inertiajs/react';
import { CalendarDays, Check, ChevronsUpDown } from 'lucide-react';
import { useRef, useState, type FormEvent } from 'react';

type Props = {
    employee?: Employee;
    positions: Pick<Position, 'id' | 'title'>[];
    departments: Pick<Department, 'id' | 'department_name'>[];
    statuses: EmploymentStatus[];
};

type ComboboxOption = {
    value: string;
    label: string;
};

function EmployeeCombobox({
    id,
    value,
    options,
    placeholder,
    searchPlaceholder,
    disabled,
    invalid,
    onValueChange,
}: {
    id: string;
    value: string;
    options: ComboboxOption[];
    placeholder: string;
    searchPlaceholder: string;
    disabled: boolean;
    invalid: boolean;
    onValueChange: (value: string) => void;
}) {
    const [open, setOpen] = useState(false);
    const selectedOption = options.find((option) => option.value === value);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    id={id}
                    type="button"
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    aria-required="true"
                    aria-invalid={invalid}
                    disabled={disabled}
                    className="border-input bg-background h-10 w-full justify-between px-3 py-2 text-sm font-normal"
                >
                    <span className={selectedOption ? '' : 'text-muted-foreground'}>{selectedOption?.label ?? placeholder}</span>
                    <ChevronsUpDown className="text-muted-foreground ml-2 size-4 shrink-0" aria-hidden="true" />
                </Button>
            </PopoverTrigger>
            <PopoverContent align="start" className="w-[var(--radix-popover-trigger-width)] p-0">
                <Command>
                    <CommandInput placeholder={searchPlaceholder} maxLength={30} />
                    <CommandList>
                        <CommandEmpty>No options found.</CommandEmpty>
                        <CommandGroup>
                            {options.map((option) => (
                                <CommandItem
                                    key={option.value}
                                    value={`${option.label} ${option.value}`}
                                    onSelect={() => {
                                        onValueChange(option.value);
                                        setOpen(false);
                                    }}
                                >
                                    <Check className={`size-4 ${value === option.value ? 'opacity-100' : 'opacity-0'}`} aria-hidden="true" />
                                    {option.label}
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}

function filterDailyRate(value: string) {
    const sanitized = value.replace(/[^\d.]/g, '');
    const decimalIndex = sanitized.indexOf('.');
    const whole = decimalIndex === -1 ? sanitized : sanitized.slice(0, decimalIndex);
    const fraction =
        decimalIndex === -1
            ? ''
            : sanitized
                  .slice(decimalIndex + 1)
                  .replace(/\./g, '')
                  .slice(0, 2);

    return decimalIndex === -1 ? whole : `${whole}.${fraction}`;
}

function getTodayDate(): string {
    const today = new Date();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');

    return `${today.getFullYear()}-${month}-${day}`;
}

function getCurrentYearEnd(): string {
    return `${new Date().getFullYear()}-12-31`;
}

export default function EmployeeForm({ employee, positions, departments, statuses }: Props) {
    const editing = Boolean(employee);
    const today = getTodayDate();
    const currentYearEnd = getCurrentYearEnd();
    const dateHiredInput = useRef<HTMLInputElement>(null);
    const form = useForm({
        first_name: employee?.first_name ?? '',
        last_name: employee?.last_name ?? '',
        suffix: employee?.suffix ?? '',
        position_id: employee ? String(employee.position_id) : '',
        department_id: employee ? String(employee.department_id) : '',
        date_hired: employee?.date_hired ?? '',
        daily_rate: employee ? String(employee.daily_rate) : '',
        sss_no: employee?.sss_no ?? '',
        philhealth_no: employee?.philhealth_no ?? '',
        pagibig_no: employee?.pagibig_no ?? '',
        tin_no: employee?.tin_no ?? '',
        status: employee?.status ?? statuses[0]?.name ?? '',
    });

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        const requiredSelections = [
            [form.data.position_id, 'position_id', 'Please select a position.'],
            [form.data.department_id, 'department_id', 'Please select a department.'],
            [form.data.status, 'status', 'Please select an employment status.'],
        ] as const;
        const missingSelections = requiredSelections.filter(([value]) => value === '');

        if (missingSelections.length > 0) {
            missingSelections.forEach(([, field, message]) => form.setError(field, message));
            return;
        }

        if (employee) {
            form.put(route('employees.update', employee.id));
            return;
        }

        form.post(route('employees.store'));
    }

    function fieldError(name: keyof typeof form.data) {
        const message = form.errors[name];
        return message ? <p className="text-destructive text-sm">{message}</p> : null;
    }

    function filterName(value: string) {
        return value.replace(/[^\p{L}\s]/gu, '');
    }

    function filterIdentificationNumber(value: string) {
        return value.replace(/[^\d-]/g, '');
    }

    function openDatePicker() {
        const input = dateHiredInput.current;

        if (input?.showPicker) {
            input.showPicker();
        } else {
            input?.focus();
        }
    }

    return (
        <form
            onSubmit={submit}
            className="min-w-0 space-y-6 rounded-2xl border border-emerald-800/10 bg-white p-5 shadow-sm sm:p-6 dark:border-emerald-400/15 dark:bg-[#07130f]"
        >
            <div className="grid min-w-0 gap-5 lg:grid-cols-2">
                <div className="space-y-2">
                    <Label htmlFor="first_name">First name</Label>
                    <Input
                        id="first_name"
                        value={form.data.first_name}
                        onChange={(event) => form.setData('first_name', filterName(event.target.value))}
                        maxLength={30}
                        className="h-10"
                        title="Only letters and spaces are allowed."
                        required
                        disabled={form.processing}
                        aria-invalid={Boolean(form.errors.first_name)}
                    />
                    {fieldError('first_name')}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="last_name">Last name</Label>
                    <Input
                        id="last_name"
                        value={form.data.last_name}
                        onChange={(event) => form.setData('last_name', filterName(event.target.value))}
                        maxLength={20}
                        className="h-10"
                        title="Only letters and spaces are allowed."
                        required
                        disabled={form.processing}
                        aria-invalid={Boolean(form.errors.last_name)}
                    />
                    {fieldError('last_name')}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="suffix">Suffix</Label>
                    <Input
                        id="suffix"
                        value={form.data.suffix}
                        onChange={(event) => form.setData('suffix', filterName(event.target.value))}
                        maxLength={3}
                        className="h-10"
                        title="Only letters and spaces are allowed."
                        disabled={form.processing}
                        aria-invalid={Boolean(form.errors.suffix)}
                    />
                    {fieldError('suffix')}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="position_id">Position</Label>
                    <EmployeeCombobox
                        id="position_id"
                        value={form.data.position_id}
                        options={positions.map((position) => ({ value: String(position.id), label: position.title }))}
                        placeholder="Select a Position"
                        searchPlaceholder="Search positions..."
                        disabled={form.processing}
                        invalid={Boolean(form.errors.position_id)}
                        onValueChange={(value) => {
                            form.setData('position_id', value);
                            form.clearErrors('position_id');
                        }}
                    />
                    {fieldError('position_id')}
                </div>
                <div className="space-y-2">
                    <Label htmlFor="department_id">Department</Label>
                    <EmployeeCombobox
                        id="department_id"
                        value={form.data.department_id}
                        options={departments.map((department) => ({ value: String(department.id), label: department.department_name }))}
                        placeholder="Select a Department"
                        searchPlaceholder="Search departments..."
                        disabled={form.processing}
                        invalid={Boolean(form.errors.department_id)}
                        onValueChange={(value) => {
                            form.setData('department_id', value);
                            form.clearErrors('department_id');
                        }}
                    />
                    {fieldError('department_id')}
                </div>
                <div className="space-y-2">
                    <Label htmlFor="date_hired">Date hired</Label>
                    <div className="relative">
                        <Input
                            ref={dateHiredInput}
                            id="date_hired"
                            type="date"
                            min={today}
                            max={currentYearEnd}
                            value={form.data.date_hired}
                            onChange={(event) => form.setData('date_hired', event.target.value)}
                            className="h-10 pr-12 [&::-webkit-calendar-picker-indicator]:hidden"
                            required
                            disabled={form.processing}
                            aria-invalid={Boolean(form.errors.date_hired)}
                        />
                        <div className="absolute inset-y-0 right-0 flex items-center">
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                className="text-muted-foreground hover:text-foreground active:translate-y-0"
                                onClick={openDatePicker}
                                disabled={form.processing}
                                aria-label="Open date picker"
                                title="Open date picker"
                            >
                                <CalendarDays aria-hidden="true" />
                            </Button>
                        </div>
                    </div>
                    {fieldError('date_hired')}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="daily_rate">Daily rate</Label>
                    <div className="relative">
                        <span className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 -translate-y-1/2" aria-hidden="true">
                            ₱
                        </span>
                        <Input
                            id="daily_rate"
                            type="text"
                            inputMode="decimal"
                            min="0"
                            pattern="\d+(\.\d{1,2})?"
                            title="Enter numbers only, with up to two decimal places."
                            value={form.data.daily_rate}
                            onChange={(event) => form.setData('daily_rate', filterDailyRate(event.target.value))}
                            className="h-10 pl-8"
                            required
                            disabled={form.processing}
                            aria-invalid={Boolean(form.errors.daily_rate)}
                        />
                    </div>
                    {fieldError('daily_rate')}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="status">Employment status</Label>
                    <EmployeeCombobox
                        id="status"
                        value={form.data.status}
                        options={statuses.map((status) => ({ value: status.name, label: status.name }))}
                        placeholder="Select an Employment Status"
                        searchPlaceholder="Search statuses..."
                        disabled={form.processing}
                        invalid={Boolean(form.errors.status)}
                        onValueChange={(value) => {
                            form.setData('status', value);
                            form.clearErrors('status');
                        }}
                    />
                    {fieldError('status')}
                </div>

                {(
                    [
                        ['sss_no', 'SSS number'],
                        ['philhealth_no', 'PhilHealth number'],
                        ['pagibig_no', 'Pag-IBIG number'],
                        ['tin_no', 'TIN'],
                    ] as const
                ).map(([name, label]) => (
                    <div key={name} className="space-y-2">
                        <Label htmlFor={name}>{label}</Label>
                        <Input
                            id={name}
                            value={form.data[name]}
                            onChange={(event) => form.setData(name, filterIdentificationNumber(event.target.value))}
                            inputMode="numeric"
                            pattern="[0-9-]*"
                            title="Use numbers and hyphens only."
                            maxLength={20}
                            className="h-10"
                            disabled={form.processing}
                            aria-invalid={Boolean(form.errors[name])}
                        />
                        {fieldError(name)}
                    </div>
                ))}
            </div>

            <div className="flex flex-col-reverse gap-2 lg:flex-row lg:items-center">
                <Button
                    className="w-full bg-emerald-700 text-white hover:bg-emerald-800 lg:w-auto dark:bg-emerald-600 dark:hover:bg-emerald-500"
                    type="submit"
                    disabled={form.processing}
                >
                    {form.processing ? 'Saving...' : editing ? 'Update Employee' : 'Create Employee'}
                </Button>
                <Button
                    className="w-full border-emerald-700/25 text-emerald-800 hover:bg-emerald-50 lg:w-auto dark:border-emerald-400/25 dark:text-emerald-300 dark:hover:bg-emerald-950/50"
                    type="button"
                    variant="outline"
                    asChild
                >
                    <Link href={route('employees.index')}>Cancel</Link>
                </Button>
            </div>
        </form>
    );
}
