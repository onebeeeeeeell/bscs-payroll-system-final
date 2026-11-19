import { DataTable, type DataTableColumn } from '@/components/data-table';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/app-layout';
import type { SharedData } from '@/types';
import type { ContributionRate, PaginatedData } from '@/types/models';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { MoreHorizontal, Trash2 } from 'lucide-react';
import { useState } from 'react';

// Ang service ay may withCount('deductions')
type RateRow = ContributionRate & { deductions_count: number };

type Props = {
    contributionRates: PaginatedData<RateRow>;
    currentRateIds: number[];
};

function formatCurrency(value: string): string {
    return Number(value).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' });
}

export default function ContributionRateIndex({ contributionRates, currentRateIds }: Props) {
    const { flash } = usePage<SharedData>().props;
    const { url } = usePage();
    const [deleteCandidate, setDeleteCandidate] = useState<RateRow | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);

    // Hindi ipinapasa ng controller ang filters, kaya binabasa ang ?search= sa URL
    const initialSearch = new URL(url, 'http://localhost').searchParams.get('search') ?? '';

    function deleteRate() {
        if (!deleteCandidate) return;

        setIsDeleting(true);
        router.delete(route('contribution_rates.destroy', deleteCandidate.id), {
            preserveScroll: true,
            onSuccess: () => setDeleteCandidate(null),
            onFinish: () => setIsDeleting(false),
        });
    }

    function search(term: string) {
        router.get(route('contribution_rates.index'), term ? { search: term } : {}, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['contributionRates', 'currentRateIds'],
        });
    }

    return (
        <AppLayout>
            <Head title="Contribution Rates" />
            <div className="flex w-full min-w-0 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Contribution rates</h1>
                        <p className="text-muted-foreground text-sm">The current rate of each type is deducted from every employee&apos;s pay.</p>
                    </div>
                    <Button
                        className="w-full bg-emerald-700 text-white hover:bg-emerald-800 sm:w-auto dark:bg-emerald-600 dark:hover:bg-emerald-500"
                        asChild
                    >
                        <Link href={route('contribution_rates.create')}>New rate</Link>
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
                    data={contributionRates.data}
                    onSearch={search}
                    initialSearch={initialSearch}
                    getRowId={(rate) => rate.id}
                    getSearchValue={(rate) => rate.contribution_type?.name ?? ''}
                    searchPlaceholder="Search by contribution type..."
                    emptyMessage="No contribution rates yet."
                    columns={
                        [
                            { header: 'Type', cell: (rate) => rate.contribution_type?.name ?? '—' },
                            { header: 'Amount', cell: (rate) => formatCurrency(rate.rate) },
                            {
                                header: 'Status',
                                cell: (rate) =>
                                    currentRateIds.includes(rate.id) ? (
                                        <span className="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                                            Current
                                        </span>
                                    ) : (
                                        <span className="text-muted-foreground text-xs">Superseded</span>
                                    ),
                            },
                            { header: 'Used in payroll', cell: (rate) => (rate.deductions_count > 0 ? `${rate.deductions_count}×` : 'Not yet') },
                            {
                                header: 'Actions',
                                className: 'text-right',
                                cell: (rate) => (
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost" size="icon" aria-label={`Actions for rate ${rate.id}`}>
                                                <MoreHorizontal className="size-4" />
                                            </Button>
                                        </DropdownMenuTrigger>

                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem asChild>
                                                <Link href={route('contribution_rates.edit', rate.id)}>{rate.deductions_count > 0 ? 'View' : 'Edit'}</Link>
                                            </DropdownMenuItem>

                                            {/* Cascade ang FK papunta sa deductions, kaya hindi pwedeng burahin ang nagamit na */}
                                            {rate.deductions_count === 0 && (
                                                <DropdownMenuItem
                                                    className="text-destructive focus:text-destructive"
                                                    onSelect={() => setDeleteCandidate(rate)}
                                                >
                                                    <Trash2 aria-hidden="true" />
                                                    Delete
                                                </DropdownMenuItem>
                                            )}
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                ),
                            },
                        ] satisfies DataTableColumn<RateRow>[]
                    }
                />

                <nav aria-label="Contribution rate pagination" className="flex flex-wrap justify-end gap-2">
                    {contributionRates.links.map((link, index) => {
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
                    open={deleteCandidate !== null}
                    onOpenChange={(open) => {
                        if (!open && !isDeleting) setDeleteCandidate(null);
                    }}
                >
                    <DialogContent>
                        <DialogTitle>Delete contribution rate?</DialogTitle>
                        <DialogDescription>
                            {deleteCandidate
                                ? `This ${formatCurrency(deleteCandidate.rate)} ${deleteCandidate.contribution_type?.name ?? ''} rate will be permanently deleted. If an older rate exists for this type, it becomes the current one.`
                                : ''}
                        </DialogDescription>
                        <DialogFooter>
                            <Button variant="outline" disabled={isDeleting} onClick={() => setDeleteCandidate(null)}>
                                Cancel
                            </Button>
                            <Button variant="destructive" disabled={isDeleting} onClick={deleteRate}>
                                {isDeleting ? 'Deleting...' : 'Delete rate'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}