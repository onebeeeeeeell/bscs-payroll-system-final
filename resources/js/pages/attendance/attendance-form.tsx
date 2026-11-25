import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/popover';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Attendance } from '@/types/models';
import { Link, useForm } from '@inertiajs/react';
import { Check, ChevronsUpDown } from 'lucide-react';
import { useState, type FormEvent } from 'react';

type EmployeeOption = {
    id: number;
    first_name: string;
    last_name: string;
    suffix: string | null;
};

type Props = {
    attendance?: AttendanceFormRecord;
    employees: EmployeeOption[];
};

export type AttendanceFormRecord = Pick<Attendance, 'id' | 'employee_id' | 'work_date' | 'time_in' | 'time_out' | 'hours_worked' | 'overtime_hours'>;

function EmployeeCombobox({
    value,
    employees,
    disabled,
    invalid,
    onValueChange,
}: {
    value: string;
    employees: EmployeeOption[];
    disabled: boolean;
    invalid: boolean;
    onValueChange: (value: string) => void;
}) {
    const [open, setOpen] = useState(false);
    const selectedEmployee = employees.find((employee) => String(employee.id) === value);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    id="employee_id"
                    type="button"
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    aria-required="true"
                    aria-invalid={invalid}
                    disabled={disabled}
                    className="border-input bg-background h-10 w-full justify-between px-3 py-2 text-sm font-normal"
                >
                    <span className={selectedEmployee ? '' : 'text-muted-foreground'}>
                        {selectedEmployee
                            ? [selectedEmployee.first_name, selectedEmployee.last_name, selectedEmployee.suffix].filter(Boolean).join(' ')
                            : 'Select an employee'}
                    </span>
                    <ChevronsUpDown className="text-muted-foreground ml-2 size-4 shrink-0" aria-hidden="true" />
                </Button>
            </PopoverTrigger>
            <PopoverContent align="start" className="w-[var(--radix-popover-trigger-width)] p-0">
                <Command>
                    <CommandInput placeholder="Search employees..." maxLength={30} />
                    <CommandList>
                        <CommandEmpty>No employees found.</CommandEmpty>
                        <CommandGroup>
                            {employees.map((employee) => {
                                const label = [employee.first_name, employee.last_name, employee.suffix].filter(Boolean).join(' ');

                                return (
                                    <CommandItem
                                        key={employee.id}
                                        value={`${label} ${employee.id}`}
                                        onSelect={() => {
                                            onValueChange(String(employee.id));
                                            setOpen(false);
                                        }}
                                    >
                                        <Check
                                            className={`size-4 ${value === String(employee.id) ? 'opacity-100' : 'opacity-0'}`}
                                            aria-hidden="true"
                                        />
                                        {label}
                                    </CommandItem>
                                );
                            })}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}

// Typed 12-hour time, e.g. "8:30 AM", "830am", "08:30 pm". The form keeps it as 24-hour "HH:mm".
function parseTime(text: string): string | null {
    const match = /^\s*(\d{1,2})(?::?(\d{2}))?\s*([AaPp])\.?[Mm]?\.?\s*$/.exec(text);
    if (!match) return null;

    const hour = Number(match[1]);
    const minute = Number(match[2] ?? '0');
    if (hour < 1 || hour > 12 || minute > 59) return null;

    const hour24 = (hour % 12) + (match[3].toLowerCase() === 'p' ? 12 : 0);

    return `${String(hour24).padStart(2, '0')}:${String(minute).padStart(2, '0')}`;
}

// "13:05" -> "01:05 PM" (what the box shows)
function formatTime(value: string): string {
    const match = /^([01]\d|2[0-3]):([0-5]\d)$/.exec(value);
    if (!match) return '';

    const hour24 = Number(match[1]);

    return `${String(hour24 % 12 || 12).padStart(2, '0')}:${match[2]} ${hour24 >= 12 ? 'PM' : 'AM'}`;
}

type TimeInputProps = {
    id: string;
    label: string;
    value: string;
    onChange: (value: string) => void;
    disabled: boolean;
    error?: string;
};

function TimeInput({ id, label, value, onChange, disabled, error }: TimeInputProps) {
    const [text, setText] = useState(() => formatTime(value));
    const [touched, setTouched] = useState(false);
    const invalidText = touched && text.trim() !== '' && parseTime(text) === null;

    return (
        <div className="space-y-2">
            <Label htmlFor={id}>{label}</Label>
            <Input
                id={id}
                type="text"
                inputMode="text"
                autoComplete="off"
                placeholder="hh:mm AM/PM (e.g. 8:30 AM)"
                maxLength={8}
                value={text}
                onChange={(event) => {
                    // only digits, colon, A/P/M and space can be typed
                    const next = event.target.value.replace(/[^0-9:apmAPM\s]/g, '');

                    setText(next);
                    onChange(parseTime(next) ?? '');
                }}
                onBlur={() => {
                    setTouched(true);

                    const parsed = parseTime(text);
                    if (parsed) setText(formatTime(parsed));
                }}
                required
                disabled={disabled}
                aria-invalid={Boolean(error) || invalidText}
                className="h-10"
            />
            {invalidText && <p className="text-destructive text-sm">Enter a valid time, like 8:30 AM.</p>}
            {error && <p className="text-destructive text-sm">{error}</p>}
        </div>
    );
}

function toMinutes(time: string): number | null {
    const match = /^([01]\d|2[0-3]):([0-5]\d)$/.exec(time);

    return match ? Number(match[1]) * 60 + Number(match[2]) : null;
}

export default function AttendanceForm({ attendance, employees }: Props) {
    const now = new Date();
    const minWorkDate = `${now.getFullYear()}-01-01`;
    // hindi pwede ang petsa sa hinaharap (local date ng browser)
    const maxWorkDate = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
    const form = useForm({
        employee_id: attendance ? String(attendance.employee_id) : '',
        work_date: attendance?.work_date ?? '',
        time_in: attendance?.time_in.slice(0, 5) ?? '',
        time_out: attendance?.time_out?.slice(0, 5) ?? '',
    });

    const timeInMinutes = toMinutes(form.data.time_in);
    const timeOutMinutes = toMinutes(form.data.time_out);
    const hasValidTimeRange = timeInMinutes !== null && timeOutMinutes !== null && timeOutMinutes > timeInMinutes;
    const workedMinutes = timeInMinutes !== null && timeOutMinutes !== null && timeOutMinutes > timeInMinutes ? timeOutMinutes - timeInMinutes : 0;
    const overtimeMinutes = Math.max(0, workedMinutes - 8 * 60);

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        if (attendance) {
            form.put(route('attendance.update', attendance.id));
            return;
        }

        form.post(route('attendance.store'));
    }

    function fieldError(name: keyof typeof form.data) {
        const message = form.errors[name];
        return message ? <p className="text-destructive text-sm">{message}</p> : null;
    }

    return employees.length === 0 ? (
        <section className="space-y-4 rounded-xl border border-emerald-800/10 bg-white p-5 shadow-sm dark:border-emerald-400/15 dark:bg-[#07130f]">
            <p className="text-sm">Add an employee before recording attendance.</p>
            <Button
                variant="outline"
                className="border-emerald-700/25 text-emerald-800 hover:bg-emerald-50 dark:border-emerald-400/25 dark:text-emerald-300 dark:hover:bg-emerald-950/50"
                asChild
            >
                <Link href={route('employees.create')}>Create Employee</Link>
            </Button>
        </section>
    ) : (
        <form
            onSubmit={submit}
            className="space-y-6 rounded-2xl border border-emerald-800/10 bg-white p-5 shadow-sm sm:p-6 dark:border-emerald-400/15 dark:bg-[#07130f]"
        >
            <div className="grid min-w-0 gap-5 lg:grid-cols-2">
                <div className="space-y-2 lg:col-span-2">
                    <Label htmlFor="employee_id">Employee</Label>
                    <EmployeeCombobox
                        value={form.data.employee_id}
                        employees={employees}
                        disabled={form.processing}
                        invalid={Boolean(form.errors.employee_id)}
                        onValueChange={(value) => form.setData('employee_id', value)}
                    />
                    {fieldError('employee_id')}
                </div>

                <div className="space-y-2 lg:col-span-2">
                    <Label htmlFor="work_date">Work date</Label>
                    <Input
                        id="work_date"
                        type="date"
                        min={minWorkDate}
                        max={maxWorkDate}
                        value={form.data.work_date}
                        onChange={(event) => form.setData('work_date', event.target.value)}
                        required
                        disabled={form.processing}
                        aria-invalid={Boolean(form.errors.work_date)}
                    />
                    {fieldError('work_date')}
                </div>

                <TimeInput
                    id="time_in"
                    label="Time in"
                    value={form.data.time_in}
                    onChange={(value) => form.setData('time_in', value)}
                    disabled={form.processing}
                    error={form.errors.time_in}
                />

                <TimeInput
                    id="time_out"
                    label="Time out"
                    value={form.data.time_out}
                    onChange={(value) => form.setData('time_out', value)}
                    disabled={form.processing}
                    error={form.errors.time_out}
                />

                <div className="grid gap-4 rounded-lg border border-emerald-800/10 bg-emerald-50/70 p-4 lg:col-span-2 lg:grid-cols-2 dark:border-emerald-400/15 dark:bg-emerald-950/30">
                    <div>
                        <p className="text-muted-foreground text-sm">Total hours worked</p>
                        <p className="mt-1 font-semibold">{hasValidTimeRange ? (workedMinutes / 60).toFixed(2) : '—'}</p>
                    </div>
                    <div>
                        <p className="text-muted-foreground text-sm">Overtime (above 8 hours)</p>
                        <p className="mt-1 font-semibold">{hasValidTimeRange ? (overtimeMinutes / 60).toFixed(2) : '—'}</p>
                    </div>
                    <p className="text-muted-foreground text-xs lg:col-span-2">
                        No break is deducted. Overtime is the portion of total hours worked beyond 8 hours.
                    </p>
                </div>
            </div>

            <div className="flex flex-col-reverse justify-end gap-3 lg:flex-row">
                <Button
                    variant="outline"
                    className="border-emerald-700/25 text-emerald-800 hover:bg-emerald-50 dark:border-emerald-400/25 dark:text-emerald-300 dark:hover:bg-emerald-950/50"
                    type="button"
                    disabled={form.processing}
                    asChild
                >
                    <Link href={route('attendance.index')}>Cancel</Link>
                </Button>
                <Button
                    className="bg-emerald-700 text-white hover:bg-emerald-800 dark:bg-emerald-600 dark:hover:bg-emerald-500"
                    type="submit"
                    disabled={form.processing}
                >
                    {form.processing ? 'Saving...' : attendance ? 'Update Attendance' : 'Save Attendance'}
                </Button>
            </div>
        </form>
    );
}