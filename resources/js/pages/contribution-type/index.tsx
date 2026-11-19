import { DataTable, type DataTableColumn } from '@/components/data-table';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/app-layout';
import type { SharedData } from '@/types';
import type { ContributionType, PaginatedData } from '@/types/models';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { MoreHorizontal, Trash2 } from 'lucide-react';
import { useState } from 'react';

// Ang controller ay may withRates(): contributionRates (id, contribution_type_id, rate)
type TypeRow = ContributionType & {
    contribution_rates?: { id: number; contribution_type_id: number; rate: string }[];
};

type Props = {
    contributionTypes: PaginatedData<TypeRow>;
};

export default function ContributionTypeIndex({ contributionTypes }: Props) {
    const { flash } = usePage<SharedData>().props;
    const { url } = usePage();
    const [deleteCandidate, setDeleteCandidate] = useState<TypeRow | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);

    // Hindi ipinapasa ng controller ang filters, kaya binabasa ang ?search= sa URL
    const initialSearch = new URL(url, 'http://localhost').searchParams.get('search') ?? '';

    function deleteType() {
        if (!deleteCandidate) return;

        setIsDeleting(true);
        router.delete(route('contribution_types.destroy', deleteCandidate.id), {
            preserveScroll: true,
            onSuccess: () => setDeleteCandidate(null),
            onFinish: () => setIsDeleting(false),
        });
    }

    function search(term: string) {
        router.get(route('contribution_types.index'), term ? { search: term } : {}, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['contributionTypes'],
        });
    }

    return (
        <AppLayout>
            <Head title="Contribution Types" />
            <div className="flex w-full min-w-0 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Contribution types</h1>
                        <p className="text-muted-foreground text-sm">Kinds of contributions deducted from pay (each has its own rates).</p>
                    </div>
                    <Button
                        className="w-full bg-emerald-700 text-white hover:bg-emerald-800 sm:w-auto dark:bg-emerald-600 dark:hover:bg-emerald-500"
                        asChild
                    >
                        <Link href={route('contribution_types.create')}>New type</Link>
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
                    data={contributionTypes.data}
                    onSearch={search}
                    initialSearch={initialSearch}
                    getRowId={(type) => type.id}
                    getSearchValue={(type) => type.name}
                    searchPlaceholder="Search contribution types..."
                    emptyMessage="No contribution types yet."
                    columns={
                        [
                            { header: 'Name', cell: (type) => type.name },
                            { header: 'Rates', cell: (type) => type.contribution_rates?.length ?? 0 },
                            {
                                header: 'Actions',
                                className: 'text-right',
                                cell: (type) => (
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost" size="icon" aria-label={`Actions for ${type.name}`}>
                                                <MoreHorizontal className="size-4" />
                                            </Button>
                                        </DropdownMenuTrigger>

                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem asChild>
                                                <Link href={route('contribution_types.edit', type.id)}>Edit</Link>
                                            </DropdownMenuItem>

                                            {/* Cascade ang FK hanggang sa deductions, kaya type na walang rate lang ang pwedeng i-delete */}
                                            {(type.contribution_rates?.length ?? 0) === 0 && (
                                                <DropdownMenuItem
                                                    className="text-destructive focus:text-destructive"
                                                    onSelect={() => setDeleteCandidate(type)}
                                                >
                                                    <Trash2 aria-hidden="true" />
                                                    Delete
                                                </DropdownMenuItem>
                                            )}
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                ),
                            },
                        ] satisfies DataTableColumn<TypeRow>[]
                    }
                />

                <nav aria-label="Contribution type pagination" className="flex flex-wrap justify-end gap-2">
                    {contributionTypes.links.map((link, index) => {
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
                        <DialogTitle>Delete contribution type?</DialogTitle>
                        <DialogDescription>
                            {deleteCandidate ? `"${deleteCandidate.name}" will be permanently deleted. This cannot be undone.` : ''}
                        </DialogDescription>
                        <DialogFooter>
                            <Button variant="outline" disabled={isDeleting} onClick={() => setDeleteCandidate(null)}>
                                Cancel
                            </Button>
                            <Button variant="destructive" disabled={isDeleting} onClick={deleteType}>
                                {isDeleting ? 'Deleting...' : 'Delete type'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}