import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { SharedData } from '@/types';
import type { Payslip } from '@/types/models';
import { Head, Link, usePage } from '@inertiajs/react';
import { Download, Printer } from 'lucide-react';

type Line = { label: string; basis: string; amount: number };

type Props = {
    payslip: Payslip;
    earnings: Line[];
    deductions: Line[];
};

const peso = (value: number | string): string => Number(value).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' });

// "2026-11-19" (or "2026-11-19 10:51:00") -> "Nov 19, 2026"
function formatDate(value: string): string {
    return new Date(`${value.slice(0, 10)}T00:00:00`).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
}

function formatDateTime(value: string): string {
    const date = new Date(value.replace(' ', 'T'));

    return `${date.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' })} at ${date.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
    })}`;
}

const ONES = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];
const TENS = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];

function wordsBelowThousand(n: number): string {
    const parts: string[] = [];
    const hundreds = Math.floor(n / 100);
    const rest = n % 100;

    if (hundreds) parts.push(`${ONES[hundreds]} hundred`);
    if (rest >= 20) parts.push(TENS[Math.floor(rest / 10)] + (rest % 10 ? `-${ONES[rest % 10]}` : ''));
    else if (rest) parts.push(ONES[rest]);

    return parts.join(' ');
}

// 25115.4 -> "Twenty-five thousand one hundred fifteen pesos and 40/100"
function amountInWords(value: number | string): string {
    const total = Math.round(Number(value) * 100);
    const pesos = Math.floor(total / 100);
    const centavos = String(total % 100).padStart(2, '0');

    const scales: [number, string][] = [
        [1_000_000_000, 'billion'],
        [1_000_000, 'million'],
        [1_000, 'thousand'],
    ];

    let remaining = pesos;
    const words: string[] = [];

    for (const [size, name] of scales) {
        if (remaining >= size) {
            words.push(`${wordsBelowThousand(Math.floor(remaining / size))} ${name}`);
            remaining %= size;
        }
    }

    if (remaining > 0) words.push(wordsBelowThousand(remaining));

    const text = words.length ? words.join(' ') : 'zero';

    return `${text.charAt(0).toUpperCase()}${text.slice(1)} ${pesos === 1 ? 'peso' : 'pesos'} and ${centavos}/100`;
}

function LineTable({ title, lines, totalLabel, total, empty }: { title: string; lines: Line[]; totalLabel: string; total: number; empty: string }) {
    return (
        <table className="w-full border-collapse text-sm">
            <thead>
                <tr className="bg-emerald-50 text-xs font-semibold tracking-wide text-emerald-900 uppercase">
                    <th className="px-3 py-2 text-left">{title}</th>
                    <th className="px-3 py-2 text-left font-medium text-emerald-800/70">Basis</th>
                    <th className="px-3 py-2 text-right font-medium text-emerald-800/70">Amount</th>
                </tr>
            </thead>
            <tbody>
                {lines.length === 0 ? (
                    <tr>
                        <td colSpan={3} className="px-3 py-3 text-center text-neutral-500">
                            {empty}
                        </td>
                    </tr>
                ) : (
                    lines.map((line, index) => (
                        <tr key={`${line.label}-${index}`} className="border-b border-neutral-200">
                            <td className="px-3 py-2.5">{line.label}</td>
                            <td className="px-3 py-2.5 text-neutral-500">{line.basis}</td>
                            <td className="px-3 py-2.5 text-right tabular-nums">{peso(line.amount)}</td>
                        </tr>
                    ))
                )}
                <tr>
                    <td className="px-3 py-2.5 font-semibold" colSpan={2}>
                        {totalLabel}
                    </td>
                    <td className="px-3 py-2.5 text-right font-semibold tabular-nums">{peso(total)}</td>
                </tr>
            </tbody>
        </table>
    );
}

function DetailRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-center justify-between gap-4 border-b border-emerald-800/10 py-2 text-sm last:border-b-0 dark:border-emerald-400/15">
            <span className="text-muted-foreground">{label}</span>
            <span className="text-right font-medium">{value}</span>
        </div>
    );
}

export default function ShowPayslip({ payslip, earnings, deductions }: Props) {
    const { name, flash } = usePage<SharedData>().props;
    const company = name && name !== 'Laravel' ? name : 'BayanPay';

    const payroll = payslip.payroll;
    const employee = payroll?.employee;
    const period = payroll?.payroll_period;

    const employeeName = [employee?.first_name, employee?.last_name, employee?.suffix].filter(Boolean).join(' ') || '—';
    const employeeCode = employee ? `EMP-${String(employee.id).padStart(4, '0')}` : '—';
    const role = [employee?.position?.title, employee?.department?.department_name].filter(Boolean).join(' · ');
    const periodLabel = period ? `${formatDate(period.start_date)} – ${formatDate(period.end_date)}` : '—';
    const payDate = period ? formatDate(period.pay_date) : '—';
    const slipNumber = `PS-${payslip.date_generated.slice(0, 4)}-${String(payslip.id).padStart(4, '0')}`;

    const grossPay = Number(payroll?.gross_pay ?? 0);
    const totalDeductions = Number(payroll?.total_deductions ?? 0);
    const netPay = Number(payroll?.net_pay ?? 0);

    // Print and "Download PDF" both use the browser's print dialog (choose "Save as PDF").
    // The document title becomes the suggested file name.
    function printPayslip() {
        const previousTitle = document.title;
        document.title = `${slipNumber} ${employeeName}`;
        window.print();
        document.title = previousTitle;
    }

    return (
        <AppLayout>
            <Head title="Payslip Preview" />

            {/* Print only the payslip sheet, not the sidebar or buttons */}
            <style>{`
                @media print {
                    body * { visibility: hidden; }
                    #payslip-sheet, #payslip-sheet * { visibility: visible; }
                    #payslip-sheet { position: absolute; left: 0; top: 0; width: 100%; box-shadow: none; border: 0; }
                    @page { margin: 12mm; }
                }
            `}</style>

            <div className="flex w-full min-w-0 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Payslip preview</h1>
                        <p className="text-muted-foreground text-sm">
                            {employeeName}&apos;s statement for {periodLabel}.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href={route('payslips.index')}>Back to payslips</Link>
                        </Button>
                        <Button variant="outline" onClick={printPayslip}>
                            <Download className="size-4" aria-hidden="true" />
                            Download PDF
                        </Button>
                        <Button className="bg-emerald-600 text-white hover:bg-emerald-500" onClick={printPayslip}>
                            <Printer className="size-4" aria-hidden="true" />
                            Print payslip
                        </Button>
                    </div>
                </div>

                {flash.success && (
                    <div
                        role="status"
                        className="rounded-lg border border-emerald-700/20 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-400/20 dark:bg-emerald-950/30 dark:text-emerald-200"
                    >
                        {flash.success}
                    </div>
                )}

                <div className="grid min-w-0 items-start gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                    {/* The payslip sheet (always light, like paper) */}
                    <div id="payslip-sheet" className="min-w-0 overflow-x-auto rounded-lg border border-neutral-200 bg-white p-6 text-neutral-900 shadow-sm sm:p-8">
                        <div className="min-w-[560px] space-y-6">
                            <div className="flex items-start justify-between gap-4 border-b border-neutral-200 pb-5">
                                <div className="flex items-center gap-3">
                                    <AppLogoIcon className="size-11 rounded-md object-contain" />
                                    <p className="text-lg font-semibold">{company}</p>
                                </div>
                                <div className="text-right">
                                    <p className="text-2xl font-bold tracking-wide">PAYSLIP</p>
                                    <p className="text-xs text-neutral-500">{slipNumber}</p>
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-6">
                                <div>
                                    <p className="text-xs font-semibold tracking-wide text-neutral-500 uppercase">Employee</p>
                                    <p className="mt-1 text-lg font-semibold">{employeeName}</p>
                                    <p className="text-xs text-neutral-500">{[role, employeeCode].filter(Boolean).join(' · ')}</p>
                                </div>
                                <div>
                                    <p className="text-xs font-semibold tracking-wide text-neutral-500 uppercase">Pay period</p>
                                    <dl className="mt-1 space-y-0.5 text-sm">
                                        <div className="flex justify-between gap-3">
                                            <dt className="text-neutral-500">Period</dt>
                                            <dd className="font-medium">{periodLabel}</dd>
                                        </div>
                                        <div className="flex justify-between gap-3">
                                            <dt className="text-neutral-500">Pay date</dt>
                                            <dd className="font-medium">{payDate}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>

                            <LineTable title="Earnings" lines={earnings} totalLabel="Gross pay" total={grossPay} empty="No earnings recorded." />
                            <LineTable
                                title="Deductions"
                                lines={deductions}
                                totalLabel="Total deductions"
                                total={totalDeductions}
                                empty="No deductions recorded for this payroll."
                            />

                            <div className="flex items-center justify-between gap-4 rounded-lg bg-emerald-900 px-5 py-4 text-white">
                                <div>
                                    <p className="text-xs font-semibold tracking-wide text-emerald-200 uppercase">Net pay</p>
                                    <p className="text-xs text-emerald-100/80">{amountInWords(netPay)}</p>
                                </div>
                                <p className="text-2xl font-bold tabular-nums">{peso(netPay)}</p>
                            </div>

                            <div className="flex flex-wrap justify-between gap-2 text-xs text-neutral-500">
                                <p>This is a system-generated payslip.</p>
                                <p>Generated {formatDateTime(payslip.date_generated)}</p>
                            </div>
                        </div>
                    </div>

                    <aside className="space-y-4">
                        <div className="rounded-xl border border-emerald-800/10 bg-white p-4 shadow-sm dark:border-emerald-400/15 dark:bg-[#07130f]">
                            <h2 className="mb-1 text-sm font-semibold">Statement details</h2>
                            <DetailRow label="Employee ID" value={employeeCode} />
                            <DetailRow label="Period" value={periodLabel} />
                            <DetailRow label="Pay date" value={payDate} />
                            <DetailRow label="Net pay" value={peso(netPay)} />
                        </div>

                        <div className="rounded-xl border border-emerald-800/10 bg-white p-4 shadow-sm dark:border-emerald-400/15 dark:bg-[#07130f]">
                            <h2 className="mb-3 text-sm font-semibold">Audit trail</h2>
                            <ol className="space-y-3 text-sm">
                                <li className="flex gap-3">
                                    <span className="mt-1.5 size-2 shrink-0 rounded-full bg-emerald-500" aria-hidden="true" />
                                    <div>
                                        <p>Payslip generated</p>
                                        <p className="text-muted-foreground text-xs">{formatDateTime(payslip.date_generated)}</p>
                                    </div>
                                </li>
                                <li className="flex gap-3">
                                    <span className="bg-muted-foreground mt-1.5 size-2 shrink-0 rounded-full" aria-hidden="true" />
                                    <div>
                                        <p>Payroll processed{payroll?.user?.name ? ` by ${payroll.user.name}` : ''}</p>
                                        {payroll?.date_processed && <p className="text-muted-foreground text-xs">{formatDateTime(payroll.date_processed)}</p>}
                                    </div>
                                </li>
                            </ol>
                        </div>
                    </aside>
                </div>
            </div>
        </AppLayout>
    );
}