import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Attendance } from '@/types/models';
import { Link, useForm } from '@inertiajs/react';
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

type TimeParts = {
    hour: string;
    minute: string;
    period: 'AM' | 'PM' | '';
};

type TwelveHourTimeInputProps = {
    id: string;
    label: string;
    value: string;
    onChange: (value: string) => void;
    disabled: boolean;
    error?: string;
};

function getTimeParts(value: string): TimeParts | null {
    const match = /^([01]\d|2[0-3]):([0-5]\d)$/.exec(value);
    if (!match) return null;

    const hour24 = Number(match[1]);

    return {
        hour: String(hour24 % 12 || 12).padStart(2, '0'),
        minute: match[2],
        period: hour24 >= 12 ? 'PM' : 'AM',
    };
}

function get24HourTime(parts: TimeParts & { period: 'AM' | 'PM' }): string {
    const hour12 = Number(parts.hour) % 12;
    const hour24 = parts.period === 'PM' ? hour12 + 12 : hour12;

    return `${String(hour24).padStart(2, '0')}:${parts.minute}`;
}

function TwelveHourTimeInput({ id, label, value, onChange, disabled, error }: TwelveHourTimeInputProps) {
    const [parts, setParts] = useState<TimeParts | null>(() => getTimeParts(value));
    const updatePart = (key: keyof TimeParts, nextValue: string) => {
        const hour = key === 'hour' ? nextValue : (parts?.hour ?? '');
        const minute = key === 'minute' ? nextValue : (parts?.minute ?? '');
        const period: TimeParts['period'] = key === 'period' ? (nextValue === 'PM' ? 'PM' : 'AM') : (parts?.period ?? '');
        const nextParts: TimeParts = {
            hour,
            minute,
            period,
        };

        setParts(nextParts);
        if (hour && minute && (period === 'AM' || period === 'PM')) {
            onChange(get24HourTime({ hour, minute, period }));
        } else {
            onChange('');
        }
    };

    return (
        <div className="space-y-2">
            <Label htmlFor={`${id}-hour`}>{label}</Label>
            <div className="flex items-center gap-2">
                <select
                    id={`${id}-hour`}
                    value={parts?.hour ?? ''}
                    onChange={(event) => updatePart('hour', event.target.value)}
                    required
                    disabled={disabled}
                    aria-label={`${label} hour`}
                    aria-invalid={Boolean(error)}
                    className="border-input bg-background flex h-10 min-w-0 flex-1 rounded-md border px-3 py-2 text-sm"
                >
                    <option value="" disabled>
                        Hour
                    </option>
                    {Array.from({ length: 12 }, (_, index) => String(index + 1).padStart(2, '0')).map((hour) => (
                        <option key={hour} value={hour}>
                            {hour}
                        </option>
                    ))}
                </select>
                <span aria-hidden="true">:</span>
                <select
                    value={parts?.minute ?? ''}
                    onChange={(event) => updatePart('minute', event.target.value)}
                    required
                    disabled={disabled}
                    aria-label={`${label} minute`}
                    aria-invalid={Boolean(error)}
                    className="border-input bg-background flex h-10 min-w-0 flex-1 rounded-md border px-3 py-2 text-sm"
                >
                    <option value="" disabled>
                        Minute
                    </option>
                    {Array.from({ length: 60 }, (_, index) => String(index).padStart(2, '0')).map((minute) => (
                        <option key={minute} value={minute}>
                            {minute}
                        </option>
                    ))}
                </select>
                <select
                    value={parts?.period ?? ''}
                    onChange={(event) => updatePart('period', event.target.value)}
                    required
                    disabled={disabled}
                    aria-label={`${label} AM or PM`}
                    aria-invalid={Boolean(error)}
                    className="border-input bg-background flex h-10 w-20 shrink-0 rounded-md border px-2 py-2 text-sm"
                >
                    <option value="" disabled>
                        AM/PM
                    </option>
                    <option value="AM">AM</option>
                    <option value="PM">PM</option>
                </select>
            </div>
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
    const maxWorkDate = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
    const form = useForm({
        employee_id: attendance ? String(attendance.employee_id) : '',
        work_date: attendance?.work_date ?? '',
        time_in: attendance?.time_in.slice(0, 5) ?? '',
        time_out: attendance?.time_out.slice(0, 5) ?? '',
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
            <div className="grid min-w-0 gap-5 sm:grid-cols-2">
                <div className="space-y-2 sm:col-span-2">
                    <Label htmlFor="employee_id">Employee</Label>
                    <select
                        id="employee_id"
                        value={form.data.employee_id}
                        onChange={(event) => form.setData('employee_id', event.target.value)}
                        required
                        disabled={form.processing}
                        aria-invalid={Boolean(form.errors.employee_id)}
                        className="border-input bg-background flex h-10 w-full rounded-md border px-3 py-2 text-sm"
                    >
                        <option value="" disabled>
                            Select an employee
                        </option>
                        {employees.map((employee) => (
                            <option key={employee.id} value={employee.id}>
                                {[employee.first_name, employee.last_name, employee.suffix].filter(Boolean).join(' ')}
                            </option>
                        ))}
                    </select>
                    {fieldError('employee_id')}
                </div>

                <div className="space-y-2 sm:col-span-2">
                    <Label htmlFor="work_date">Work date</Label>
                    <Input
                        id="work_date"
                        type="date"
                        min="1900-01-01"
                        max={maxWorkDate}
                        value={form.data.work_date}
                        onChange={(event) => form.setData('work_date', event.target.value)}
                        required
                        disabled={form.processing}
                        aria-invalid={Boolean(form.errors.work_date)}
                    />
                    {fieldError('work_date')}
                </div>

                <TwelveHourTimeInput
                    id="time_in"
                    label="Time in"
                    value={form.data.time_in}
                    onChange={(value) => form.setData('time_in', value)}
                    disabled={form.processing}
                    error={form.errors.time_in}
                />

                <TwelveHourTimeInput
                    id="time_out"
                    label="Time out"
                    value={form.data.time_out}
                    onChange={(value) => form.setData('time_out', value)}
                    disabled={form.processing}
                    error={form.errors.time_out}
                />

                <div className="grid gap-4 rounded-lg border border-emerald-800/10 bg-emerald-50/70 p-4 sm:col-span-2 sm:grid-cols-2 dark:border-emerald-400/15 dark:bg-emerald-950/30">
                    <div>
                        <p className="text-muted-foreground text-sm">Total hours worked</p>
                        <p className="mt-1 font-semibold">{hasValidTimeRange ? (workedMinutes / 60).toFixed(2) : '—'}</p>
                    </div>
                    <div>
                        <p className="text-muted-foreground text-sm">Overtime (above 8 hours)</p>
                        <p className="mt-1 font-semibold">{hasValidTimeRange ? (overtimeMinutes / 60).toFixed(2) : '—'}</p>
                    </div>
                    <p className="text-muted-foreground text-xs sm:col-span-2">
                        No break is deducted. Overtime is the portion of total hours worked beyond 8 hours.
                    </p>
                </div>
            </div>

            <div className="flex flex-col-reverse justify-end gap-3 sm:flex-row">
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
