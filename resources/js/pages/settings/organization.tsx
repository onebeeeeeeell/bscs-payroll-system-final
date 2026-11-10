import { Check, Pencil, Plus, Trash2, X } from 'lucide-react';
import { useState, type FormEvent } from 'react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import type { ContributionRate, ContributionType, PayrollPeriod } from '@/types/models';
import { Head, router } from '@inertiajs/react';

type OptionRow = { id: number; label: string; count: number };

type Props = {
    departments: { id: number; department_name: string; employees_count: number }[];
    positions: { id: number; title: string; employees_count: number }[];
    statuses: { id: number; name: string; employees_count: number }[];
    payrollPeriods: PayrollPeriod[];
    contributionRates: (ContributionRate & { contribution_type?: ContributionType })[];
};

// Same CSRF header trick used in employee-form.tsx
function csrfHeaders(): HeadersInit {
    const xsrfCookie = document.cookie.split('; ').find((cookie) => cookie.startsWith('XSRF-TOKEN='));

    return {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(xsrfCookie ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfCookie.slice('XSRF-TOKEN='.length)) } : {}),
    };
}

function formatDate(value: string): string {
    // keep only YYYY-MM-DD so the date never shifts because of time zones
    return new Date(`${value.slice(0, 10)}T00:00:00`).toLocaleDateString('en-PH', { dateStyle: 'medium' });
}

function filterOptionName(value: string): string {
    return value.replace(/[^\p{L} ]/gu, '').replace(/ {2,}/g, ' ');
}

// One reusable box for departments, positions and employment statuses.
// It uses the existing employee-options endpoints (add / rename / delete).
function OptionManager({
    title,
    description,
    field,
    rows,
    createEndpoint,
    updateEndpoint,
    deleteEndpoint,
}: {
    title: string;
    description: string;
    field: 'department_name' | 'title' | 'name'; // name of the field the endpoint validates
    rows: OptionRow[];
    createEndpoint: string;
    updateEndpoint: (id: number) => string;
    deleteEndpoint: (id: number) => string;
}) {
    const [newName, setNewName] = useState('');
    const [editingId, setEditingId] = useState<number | null>(null);
    const [editingName, setEditingName] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);

    // Sends the request, shows the server's error if there is one, and reloads the lists on success
    async function send(url: string, method: 'POST' | 'PATCH' | 'DELETE', name?: string): Promise<boolean> {
        setBusy(true);
        setError(null);

        try {
            const response = await fetch(url, {
                method,
                headers: csrfHeaders(),
                credentials: 'same-origin',
                body: name === undefined ? undefined : JSON.stringify({ [field]: name }),
            });

            if (!response.ok) {
                const data = await response.json().catch(() => ({}));
                setError(data.errors?.[field]?.[0] ?? data.message ?? 'Something went wrong. Try again.');
                return false;
            }

            router.reload({ only: ['departments', 'positions', 'statuses'] });
            return true;
        } catch {
            setError('Could not reach the server. Try again.');
            return false;
        } finally {
            setBusy(false);
        }
    }

    async function add(event: FormEvent) {
        event.preventDefault();
        if (!newName.trim()) return;
        if (await send(createEndpoint, 'POST', newName.trim())) setNewName('');
    }

    async function saveEdit(id: number) {
        if (!editingName.trim()) return;
        if (await send(updateEndpoint(id), 'PATCH', editingName.trim())) setEditingId(null);
    }

    async function remove(row: OptionRow) {
        if (window.confirm(`Delete "${row.label}"?`)) await send(deleteEndpoint(row.id), 'DELETE');
    }

    return (
        <section className="min-w-0 rounded-2xl border border-emerald-800/10 bg-white p-5 shadow-sm dark:border-emerald-400/15 dark:bg-[#07130f]">
            <h2 className="text-lg font-semibold">{title}</h2>
            <p className="text-muted-foreground mb-4 text-sm">{description}</p>

            <form onSubmit={add} className="mb-3 flex gap-2">
                <Input
                    value={newName}
                    onChange={(event) => setNewName(filterOptionName(event.target.value))}
                    maxLength={field === 'name' ? 50 : 255}
                    placeholder={`Add ${title.toLowerCase()}`}
                    aria-label={`Add ${title}`}
                />
                <Button type="submit" disabled={busy} className="bg-emerald-700 text-white hover:bg-emerald-800 dark:bg-emerald-600 dark:hover:bg-emerald-500">
                    <Plus className="size-4" />
                    Add
                </Button>
            </form>

            {error && (
                <p role="alert" className="mb-3 text-sm text-red-600 dark:text-red-400">
                    {error}
                </p>
            )}

            {rows.length === 0 ? (
                <p className="text-muted-foreground py-4 text-sm">Nothing here yet.</p>
            ) : (
                <ul className="divide-y">
                    {rows.map((row) => (
                        <li key={row.id} className="flex items-center gap-2 py-2">
                            {editingId === row.id ? (
                                <>
                                    <Input
                                        value={editingName}
                                        onChange={(event) => setEditingName(filterOptionName(event.target.value))}
                                        maxLength={field === 'name' ? 50 : 255}
                                        className="h-8"
                                        aria-label="Rename"
                                        autoFocus
                                    />
                                    <Button type="button" size="icon" variant="ghost" className="size-8" disabled={busy} onClick={() => saveEdit(row.id)} aria-label="Save">
                                        <Check className="size-4" />
                                    </Button>
                                    <Button type="button" size="icon" variant="ghost" className="size-8" onClick={() => setEditingId(null)} aria-label="Cancel">
                                        <X className="size-4" />
                                    </Button>
                                </>
                            ) : (
                                <>
                                    <span className="flex-1 truncate text-sm font-medium">{row.label}</span>
                                    <span className="text-muted-foreground text-xs">{row.count} employee{row.count === 1 ? '' : 's'}</span>
                                    <Button
                                        type="button"
                                        size="icon"
                                        variant="ghost"
                                        className="size-8"
                                        onClick={() => {
                                            setEditingId(row.id);
                                            setEditingName(row.label);
                                            setError(null);
                                        }}
                                        aria-label={`Rename ${row.label}`}
                                    >
                                        <Pencil className="size-4" />
                                    </Button>
                                    <Button type="button" size="icon" variant="ghost" className="size-8" disabled={busy} onClick={() => remove(row)} aria-label={`Delete ${row.label}`}>
                                        <Trash2 className="size-4" />
                                    </Button>
                                </>
                            )}
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}

export default function OrganizationSettings({ departments, positions, statuses, payrollPeriods, contributionRates }: Props) {
    return (
        <AppLayout>
            <Head title="Organization settings" />
            <div className="to-background dark:to-background flex w-full min-w-0 flex-col gap-6 bg-gradient-to-b from-emerald-50/60 p-4 md:p-6 dark:from-emerald-950/20">
                <div className="space-y-1 rounded-2xl border border-emerald-800/10 bg-white/80 p-5 shadow-sm sm:p-6 dark:border-emerald-400/15 dark:bg-[#07130f]">
                    <p className="text-xs font-semibold tracking-[0.16em] text-emerald-700 uppercase dark:text-emerald-400">Administration</p>
                    <h1 className="text-2xl font-semibold tracking-tight">Organization</h1>
                    <p className="text-muted-foreground text-sm">Departments, positions, employment statuses, pay periods, and contribution rates.</p>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <OptionManager
                        title="Departments"
                        description="Groups employees are assigned to."
                        field="department_name"
                        rows={departments.map((item) => ({ id: item.id, label: item.department_name, count: item.employees_count }))}
                        createEndpoint={route('employee-options.departments.store')}
                        updateEndpoint={(id) => route('employee-options.departments.update', id)}
                        deleteEndpoint={(id) => route('employee-options.departments.destroy', id)}
                    />
                    <OptionManager
                        title="Positions"
                        description="Job titles used on employee records."
                        field="title"
                        rows={positions.map((item) => ({ id: item.id, label: item.title, count: item.employees_count }))}
                        createEndpoint={route('employee-options.positions.store')}
                        updateEndpoint={(id) => route('employee-options.positions.update', id)}
                        deleteEndpoint={(id) => route('employee-options.positions.destroy', id)}
                    />
                    <OptionManager
                        title="Employment statuses"
                        description="Regular, Casual, Permanent, and so on."
                        field="name"
                        rows={statuses.map((item) => ({ id: item.id, label: item.name, count: item.employees_count }))}
                        createEndpoint={route('employee-options.statuses.store')}
                        updateEndpoint={(id) => route('employee-options.statuses.update', id)}
                        deleteEndpoint={(id) => route('employee-options.statuses.destroy', id)}
                    />

                    <section className="min-w-0 rounded-2xl border border-emerald-800/10 bg-white p-5 shadow-sm dark:border-emerald-400/15 dark:bg-[#07130f]">
                        <h2 className="text-lg font-semibold">Contribution rates</h2>
                        <p className="text-muted-foreground mb-4 text-sm">Rates used for deductions (view only for now).</p>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Type</TableHead>
                                    <TableHead className="text-right">Rate</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {contributionRates.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={2} className="text-muted-foreground text-center">
                                            No contribution rates yet.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    contributionRates.map((rate) => (
                                        <TableRow key={rate.id}>
                                            <TableCell>{rate.contribution_type?.name ?? '—'}</TableCell>
                                            <TableCell className="text-right">{Number(rate.rate).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </section>
                </div>

                <section className="min-w-0 rounded-2xl border border-emerald-800/10 bg-white p-5 shadow-sm dark:border-emerald-400/15 dark:bg-[#07130f]">
                    <h2 className="text-lg font-semibold">Pay periods</h2>
                    <p className="text-muted-foreground mb-4 text-sm">The 12 most recent pay periods (view only for now).</p>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Start</TableHead>
                                <TableHead>End</TableHead>
                                <TableHead>Pay date</TableHead>
                                <TableHead>Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {payrollPeriods.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={4} className="text-muted-foreground text-center">
                                        No pay periods yet.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                payrollPeriods.map((period) => (
                                    <TableRow key={period.id}>
                                        <TableCell>{formatDate(period.start_date)}</TableCell>
                                        <TableCell>{formatDate(period.end_date)}</TableCell>
                                        <TableCell>{formatDate(period.pay_date)}</TableCell>
                                        <TableCell>{period.status}</TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </section>
            </div>
        </AppLayout>
    );
}