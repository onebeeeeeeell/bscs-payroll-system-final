import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Department, Employee, EmploymentStatus, Position } from '@/types/models';
import { Link, useForm } from '@inertiajs/react';
import { CalendarDays, Pencil, Plus, Trash2 } from 'lucide-react';
import { useRef, useState, type FormEvent } from 'react';

type Props = {
    employee?: Employee;
    positions: Pick<Position, 'id' | 'title'>[];
    departments: Pick<Department, 'id' | 'department_name'>[];
    statuses: EmploymentStatus[];
};

type CreatedOption = {
    id: number;
    label: string;
};

type ManagedOption = CreatedOption;

function filterOptionName(value: string) {
    return value.replace(/[^\p{L} ]/gu, '');
}

function filterDailyRate(value: string) {
    const sanitized = value.replace(/[^\d.]/g, '');
    const decimalIndex = sanitized.indexOf('.');
    const whole = decimalIndex === -1 ? sanitized : sanitized.slice(0, decimalIndex);
    const fraction = decimalIndex === -1 ? '' : sanitized.slice(decimalIndex + 1).replace(/\./g, '').slice(0, 2);

    return decimalIndex === -1 ? whole : `${whole}.${fraction}`;
}

type InlineOptionCreatorProps = {
    label: string;
    endpoint: string;
    fieldName: string;
    isOpen: boolean;
    onOpenChange: (isOpen: boolean) => void;
    onCreated: (option: CreatedOption) => void;
};

function InlineOptionCreator({ label, endpoint, fieldName, isOpen, onOpenChange, onCreated }: InlineOptionCreatorProps) {
    const [value, setValue] = useState('');
    const [error, setError] = useState('');
    const [saving, setSaving] = useState(false);

    async function createOption() {
        const name = value.trim();
        if (!name || saving) return;

        setSaving(true);
        setError('');

        const xsrfCookie = document.cookie.split('; ').find((cookie) => cookie.startsWith('XSRF-TOKEN='));
        let response: Response;

        try {
            response = await fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    ...(xsrfCookie ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfCookie.slice('XSRF-TOKEN='.length)) } : {}),
                },
                body: JSON.stringify({ [fieldName]: name }),
            });
        } catch {
            setError('Could not connect. Please try again.');
            setSaving(false);
            return;
        }

        let result: CreatedOption & { errors?: Record<string, string[]>; message?: string };
        try {
            result = await response.json();
        } catch {
            setError('Could not read the server response. Please try again.');
            setSaving(false);
            return;
        }

        setSaving(false);
        if (!response.ok) {
            setError(Object.values(result.errors ?? {})[0]?.[0] ?? result.message ?? 'Could not add this option.');
            return;
        }

        onCreated(result);
        setValue('');
        onOpenChange(false);
    }

    return (
        isOpen ? (
            <div className="space-y-2">
                <div className="flex gap-2">
                    <Input
                        value={value}
                        onChange={(event) => setValue(filterOptionName(event.target.value))}
                        onKeyDown={(event) => {
                            if (event.key === 'Enter') {
                                event.preventDefault();
                                void createOption();
                            }
                        }}
                        placeholder={`New ${label.toLowerCase()}`}
                        aria-label={`New ${label.toLowerCase()}`}
                        maxLength={label === 'Status' ? 50 : 255}
                        disabled={saving}
                        autoFocus
                    />
                    <Button type="button" size="sm" onClick={() => void createOption()} disabled={saving || !value.trim()}>
                        {saving ? 'Adding...' : 'Add'}
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        onClick={() => {
                            onOpenChange(false);
                            setError('');
                            setValue('');
                        }}
                        disabled={saving}
                    >
                        Cancel
                    </Button>
                </div>
                {error && <p className="text-destructive text-sm" role="alert">{error}</p>}
            </div>
        ) : null
    );
}

type InlineOptionManagerProps = {
    label: string;
    options: ManagedOption[];
    isOpen: boolean;
    onClose: () => void;
    createEndpoint: string;
    createFieldName: string;
    updateEndpoint: (id: number) => string;
    deleteEndpoint: (id: number) => string;
    onCreated: (option: ManagedOption) => void;
    onUpdated: (previous: ManagedOption, updated: ManagedOption) => void;
    onDeleted: (deleted: ManagedOption) => void;
};

function csrfHeaders(): HeadersInit {
    const xsrfCookie = document.cookie.split('; ').find((cookie) => cookie.startsWith('XSRF-TOKEN='));

    return {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        ...(xsrfCookie ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfCookie.slice('XSRF-TOKEN='.length)) } : {}),
    };
}

function InlineOptionManager({
    label,
    options,
    isOpen,
    onClose,
    createEndpoint,
    createFieldName,
    updateEndpoint,
    deleteEndpoint,
    onCreated,
    onUpdated,
    onDeleted,
}: InlineOptionManagerProps) {
    const pluralLabel = label === 'Status' ? 'statuses' : `${label.toLowerCase()}s`;
    const [editingId, setEditingId] = useState<number | null>(null);
    const [values, setValues] = useState<Record<number, string>>({});
    const [error, setError] = useState('');
    const [busyId, setBusyId] = useState<number | null>(null);
    const [adding, setAdding] = useState(false);
    const [deleteCandidate, setDeleteCandidate] = useState<ManagedOption | null>(null);

    async function updateOption(option: ManagedOption) {
        const name = (values[option.id] ?? option.label).trim();
        if (!name || busyId !== null) return;

        setBusyId(option.id);
        setError('');
        let response: Response;
        try {
            response = await fetch(updateEndpoint(option.id), {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: csrfHeaders(),
                body: JSON.stringify({ [label === 'Department' ? 'department_name' : label === 'Position' ? 'title' : 'name']: name }),
            });
        } catch {
            setError('Could not connect. Please try again.');
            setBusyId(null);
            return;
        }

        let result: { label?: string; errors?: Record<string, string[]>; message?: string };
        try {
            result = await response.json();
        } catch {
            setError('Could not read the server response. Please try again.');
            setBusyId(null);
            return;
        }

        setBusyId(null);
        if (!response.ok || !result.label) {
            setError(Object.values(result.errors ?? {})[0]?.[0] ?? result.message ?? `Could not update this ${label.toLowerCase()}.`);
            return;
        }

        onUpdated(option, { id: option.id, label: result.label });
        setEditingId(null);
    }

    async function deleteOption(option: ManagedOption) {
        if (busyId !== null) return;

        setBusyId(option.id);
        setError('');
        let response: Response;
        try {
            response = await fetch(deleteEndpoint(option.id), {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: csrfHeaders(),
            });
        } catch {
            setDeleteCandidate(null);
            setError('Could not connect. Please try again.');
            setBusyId(null);
            return;
        }

        let result: { errors?: Record<string, string[]>; message?: string };
        try {
            result = await response.json();
        } catch {
            setDeleteCandidate(null);
            setError('Could not read the server response. Please try again.');
            setBusyId(null);
            return;
        }

        setBusyId(null);
        if (!response.ok) {
            setDeleteCandidate(null);
            setError(Object.values(result.errors ?? {})[0]?.[0] ?? result.message ?? `Could not delete this ${label.toLowerCase()}.`);
            return;
        }

        onDeleted(option);
        setDeleteCandidate(null);
    }

    if (!isOpen) return null;

    return (
        <div className="w-full max-w-full space-y-3 rounded-xl border border-emerald-700/20 bg-emerald-50/60 p-3 shadow-sm sm:p-4 dark:border-emerald-400/20 dark:bg-emerald-950/20">
            <div className="flex items-start justify-between gap-2 border-b border-emerald-800/10 pb-3 dark:border-emerald-400/15">
                <div className="min-w-0">
                    <p className="text-sm font-semibold">Manage {pluralLabel}</p>
                    <p className="text-muted-foreground mt-1 text-xs">Changes apply to employee records using this option.</p>
                </div>
                <div className="flex gap-2">
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        className="border-emerald-700/20 bg-background dark:border-emerald-400/20"
                        onClick={() => {
                            setAdding((current) => !current);
                            setError('');
                        }}
                        disabled={busyId !== null}
                    >
                        <Plus aria-hidden="true" />
                        Add
                    </Button>
                    <Button type="button" size="sm" variant="ghost" onClick={onClose} disabled={busyId !== null}>
                        Done
                    </Button>
                </div>
            </div>
            <InlineOptionCreator
                label={label}
                endpoint={createEndpoint}
                fieldName={createFieldName}
                isOpen={adding}
                onOpenChange={setAdding}
                onCreated={onCreated}
            />
            {options.length === 0 ? (
                <p className="text-muted-foreground rounded-lg border border-dashed border-border bg-background/70 px-3 py-4 text-center text-sm">
                    No options yet. Add one above.
                </p>
            ) : null}
            <ul className="max-h-56 space-y-1 overflow-y-auto pr-1">
                {options.map((option) => (
                    <li
                        key={option.id}
                        className="flex flex-wrap items-center gap-2 rounded-lg border border-transparent bg-background/80 px-2 py-2 transition-colors hover:border-emerald-700/15 hover:bg-background dark:hover:border-emerald-400/20"
                    >
                        {editingId === option.id ? (
                            <>
                                <Input
                                    value={values[option.id] ?? option.label}
                                    onChange={(event) =>
                                        setValues((current) => ({ ...current, [option.id]: filterOptionName(event.target.value) }))
                                    }
                                    aria-label={`Edit ${label.toLowerCase()} ${option.label}`}
                                    maxLength={label === 'Status' ? 50 : 255}
                                    disabled={busyId === option.id}
                                />
                                <Button type="button" size="sm" onClick={() => void updateOption(option)} disabled={busyId !== null}>
                                    {busyId === option.id ? 'Saving...' : 'Save'}
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    onClick={() => {
                                        setEditingId(null);
                                        setError('');
                                    }}
                                    disabled={busyId !== null}
                                >
                                    Cancel
                                </Button>
                            </>
                        ) : (
                            <>
                                <span className="min-w-0 flex-1 truncate text-sm font-medium">{option.label}</span>
                                <Button
                                    type="button"
                                    size="icon"
                                    variant="outline"
                                    className="size-8"
                                    onClick={() => setEditingId(option.id)}
                                    disabled={busyId !== null}
                                    aria-label={`Edit ${label.toLowerCase()} ${option.label}`}
                                    title={`Edit ${label.toLowerCase()}`}
                                >
                                    <Pencil aria-hidden="true" />
                                </Button>
                                <Button
                                    type="button"
                                    size="icon"
                                    variant="destructive"
                                    className="size-8"
                                    onClick={() => setDeleteCandidate(option)}
                                    disabled={busyId !== null}
                                    aria-label={`Delete ${label.toLowerCase()} ${option.label}`}
                                    title={busyId === option.id ? 'Deleting...' : `Delete ${label.toLowerCase()}`}
                                >
                                    <Trash2 aria-hidden="true" />
                                </Button>
                            </>
                        )}
                    </li>
                ))}
            </ul>
            {error && <p className="text-destructive text-sm" role="alert">{error}</p>}
            <Dialog open={deleteCandidate !== null} onOpenChange={(open) => !open && busyId === null && setDeleteCandidate(null)}>
                <DialogContent className="border-emerald-700/20 dark:border-emerald-400/20">
                    <DialogTitle>Delete {label.toLowerCase()}?</DialogTitle>
                    <DialogDescription>
                        Delete <span className="font-medium text-foreground">{deleteCandidate?.label}</span>? This option can only be deleted if no employee records use it.
                    </DialogDescription>
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="outline" disabled={busyId !== null}>
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button
                            type="button"
                            variant="destructive"
                            onClick={() => deleteCandidate && void deleteOption(deleteCandidate)}
                            disabled={busyId !== null || deleteCandidate === null}
                        >
                            {busyId !== null ? 'Deleting...' : 'Delete'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}

function getTodayDate(): string {
    const today = new Date();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');

    return `${today.getFullYear()}-${month}-${day}`;
}

export default function EmployeeForm({ employee, positions: initialPositions, departments: initialDepartments, statuses: initialStatuses }: Props) {
    const editing = Boolean(employee);
    const today = getTodayDate();
    const dateHiredInput = useRef<HTMLInputElement>(null);
    const [positions, setPositions] = useState(initialPositions);
    const [departments, setDepartments] = useState(initialDepartments);
    const [statuses, setStatuses] = useState(initialStatuses);
    const [managingPosition, setManagingPosition] = useState(false);
    const [managingDepartment, setManagingDepartment] = useState(false);
    const [managingStatus, setManagingStatus] = useState(false);
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
        status: employee?.status ?? initialStatuses[0]?.name ?? '',
    });

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

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
            <div className="grid min-w-0 gap-5 sm:grid-cols-2">
                <div className="space-y-2">
                    <Label htmlFor="first_name">First name</Label>
                    <Input
                        id="first_name"
                        value={form.data.first_name}
                        onChange={(event) => form.setData('first_name', filterName(event.target.value))}
                        maxLength={255}
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
                        maxLength={255}
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
                        onChange={(event) => form.setData('suffix', event.target.value)}
                        maxLength={50}
                        disabled={form.processing}
                        aria-invalid={Boolean(form.errors.suffix)}
                    />
                    {fieldError('suffix')}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="position_id">Position</Label>
                    <div className="space-y-2">
                        <select
                            id="position_id"
                            value={form.data.position_id}
                            onChange={(event) => {
                                if (event.target.value === '__manage__') {
                                    setManagingPosition(true);
                                    return;
                                }
                                form.setData('position_id', event.target.value);
                            }}
                            required
                            disabled={form.processing}
                            aria-invalid={Boolean(form.errors.position_id)}
                            className="border-input bg-background flex h-10 w-full rounded-md border px-3 py-2 text-sm"
                        >
                            <option value="" disabled>
                                Select a Position
                            </option>
                            {positions.map((position) => (
                                <option key={position.id} value={position.id}>
                                    {position.title}
                                </option>
                            ))}
                            <option value="__manage__">Manage positions...</option>
                        </select>
                        <InlineOptionManager
                            label="Position"
                            options={positions.map((position) => ({ id: position.id, label: position.title }))}
                            isOpen={managingPosition}
                            onClose={() => setManagingPosition(false)}
                            createEndpoint={route('employee-options.positions.store')}
                            createFieldName="title"
                            updateEndpoint={(id) => route('employee-options.positions.update', id)}
                            deleteEndpoint={(id) => route('employee-options.positions.destroy', id)}
                            onCreated={(option) => {
                                setPositions((current) => [...current, { id: option.id, title: option.label }]);
                                form.setData('position_id', String(option.id));
                            }}
                            onUpdated={(previous, updated) =>
                                setPositions((current) => current.map((position) => (position.id === updated.id ? { ...position, title: updated.label } : position)))
                            }
                            onDeleted={(deleted) => {
                                setPositions((current) => current.filter((position) => position.id !== deleted.id));
                                if (form.data.position_id === String(deleted.id)) form.setData('position_id', '');
                            }}
                        />
                    </div>
                    {fieldError('position_id')}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="department_id">Department</Label>
                    <div className="space-y-2">
                        <select
                            id="department_id"
                            value={form.data.department_id}
                            onChange={(event) => {
                                if (event.target.value === '__manage__') {
                                    setManagingDepartment(true);
                                    return;
                                }
                                form.setData('department_id', event.target.value);
                            }}
                            required
                            disabled={form.processing}
                            aria-invalid={Boolean(form.errors.department_id)}
                            className="border-input bg-background flex h-10 w-full rounded-md border px-3 py-2 text-sm"
                        >
                            <option value="" disabled>
                                Select a Department
                            </option>
                            {departments.map((department) => (
                                <option key={department.id} value={department.id}>
                                    {department.department_name}
                                </option>
                            ))}
                            <option value="__manage__">Manage departments...</option>
                        </select>
                        <InlineOptionManager
                            label="Department"
                            options={departments.map((department) => ({ id: department.id, label: department.department_name }))}
                            isOpen={managingDepartment}
                            onClose={() => setManagingDepartment(false)}
                            createEndpoint={route('employee-options.departments.store')}
                            createFieldName="department_name"
                            updateEndpoint={(id) => route('employee-options.departments.update', id)}
                            deleteEndpoint={(id) => route('employee-options.departments.destroy', id)}
                            onCreated={(option) => {
                                setDepartments((current) => [...current, { id: option.id, department_name: option.label }]);
                                form.setData('department_id', String(option.id));
                            }}
                            onUpdated={(previous, updated) =>
                                setDepartments((current) =>
                                    current.map((department) =>
                                        department.id === updated.id ? { ...department, department_name: updated.label } : department,
                                    ),
                                )
                            }
                            onDeleted={(deleted) => {
                                setDepartments((current) => current.filter((department) => department.id !== deleted.id));
                                if (form.data.department_id === String(deleted.id)) form.setData('department_id', '');
                            }}
                        />
                    </div>
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
                            value={form.data.date_hired}
                            onChange={(event) => form.setData('date_hired', event.target.value)}
                            className="pr-12 [&::-webkit-calendar-picker-indicator]:hidden"
                            required
                            disabled={form.processing}
                            aria-invalid={Boolean(form.errors.date_hired)}
                        />
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="absolute top-0 right-0 text-muted-foreground hover:text-foreground"
                            onClick={openDatePicker}
                            disabled={form.processing}
                            aria-label="Open date picker"
                            title="Open date picker"
                        >
                            <CalendarDays aria-hidden="true" />
                        </Button>
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
                            className="pl-8"
                            required
                            disabled={form.processing}
                            aria-invalid={Boolean(form.errors.daily_rate)}
                        />
                    </div>
                    {fieldError('daily_rate')}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="status">Employment status</Label>
                    <div className="space-y-2">
                        <select
                            id="status"
                            value={form.data.status}
                            onChange={(event) => {
                                if (event.target.value === '__manage__') {
                                    setManagingStatus(true);
                                    return;
                                }
                                form.setData('status', event.target.value);
                            }}
                            required
                            disabled={form.processing}
                            aria-invalid={Boolean(form.errors.status)}
                            className="border-input bg-background flex h-10 w-full rounded-md border px-3 py-2 text-sm"
                        >
                            {statuses.map((status) => (
                                <option key={status.id} value={status.name}>
                                    {status.name}
                                </option>
                            ))}
                            <option value="__manage__">Manage statuses...</option>
                        </select>
                        <InlineOptionManager
                            label="Status"
                            options={statuses.map((status) => ({ id: status.id, label: status.name }))}
                            isOpen={managingStatus}
                            onClose={() => setManagingStatus(false)}
                            createEndpoint={route('employee-options.statuses.store')}
                            createFieldName="name"
                            updateEndpoint={(id) => route('employee-options.statuses.update', id)}
                            deleteEndpoint={(id) => route('employee-options.statuses.destroy', id)}
                            onCreated={(option) => {
                                setStatuses((current) => [...current, { id: option.id, name: option.label }]);
                                form.setData('status', option.label);
                            }}
                            onUpdated={(previous, updated) => {
                                setStatuses((current) => current.map((status) => (status.id === updated.id ? { ...status, name: updated.label } : status)));
                                if (form.data.status === previous.label) form.setData('status', updated.label);
                            }}
                            onDeleted={(deleted) => {
                                setStatuses((current) => current.filter((status) => status.id !== deleted.id));
                                if (form.data.status === deleted.label) form.setData('status', statuses.find((status) => status.name !== deleted.label)?.name ?? '');
                            }}
                        />
                    </div>
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
                            onChange={(event) => form.setData(name, event.target.value)}
                            maxLength={20}
                            disabled={form.processing}
                            aria-invalid={Boolean(form.errors[name])}
                        />
                        {fieldError(name)}
                    </div>
                ))}
            </div>

            <div className="flex flex-col-reverse gap-2 sm:flex-row sm:items-center">
                <Button
                    className="w-full bg-emerald-700 text-white hover:bg-emerald-800 sm:w-auto dark:bg-emerald-600 dark:hover:bg-emerald-500"
                    type="submit"
                    disabled={form.processing}
                >
                    {form.processing ? 'Saving...' : editing ? 'Update Employee' : 'Create Employee'}
                </Button>
                <Button
                    className="w-full border-emerald-700/25 text-emerald-800 hover:bg-emerald-50 sm:w-auto dark:border-emerald-400/25 dark:text-emerald-300 dark:hover:bg-emerald-950/50"
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
