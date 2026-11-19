import { DataTable, type DataTableColumn } from '@/components/data-table';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/app-layout';
import type { SharedData } from '@/types';
import type { Position, PaginatedData } from '@/types/models';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { MoreHorizontal, Trash2 } from 'lucide-react';
import { useState } from 'react';

// Ang service ay may withEmployeesCount()
type Row = Position & { employees_count: number };

type Props = {
    positions: PaginatedData<Row>;
};

export default function PositionIndex({ positions }: Props) {
    const { flash } = usePage<SharedData>().props;
    const { url } = usePage();
    const [deleteCandidate, setDeleteCandidate] = useState<Row | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);

    // Hindi ipinapasa ng controller ang filters, kaya binabasa ang ?search= sa URL
    const initialSearch = new URL(url, 'http://localhost').searchParams.get('search') ?? '';

    function deleteRow() {
        if (!deleteCandidate) return;

        setIsDeleting(true);
        router.delete(route('positions.destroy', deleteCandidate.id), {
            preserveScroll: true,
            onSuccess: () => setDeleteCandidate(null),
            onFinish: () => setIsDeleting(false),
        });
    }

    function search(term: string) {
        router.get(route('positions.index'), term ? { search: term } : {}, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['positions'],
        });
    }

    return (
        <AppLayout>
            <Head title="Positions" />
            <div className="flex w-full min-w-0 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Positions</h1>
                        <p className="text-muted-foreground text-sm">Job titles employees hold. A position can only be deleted when no employee has it.</p>
                    </div>
                    <Button
                        className="w-full bg-emerald-700 text-white hover:bg-emerald-800 sm:w-auto dark:bg-emerald-600 dark:hover:bg-emerald-500"
                        asChild
                    >
                        <Link href={route('positions.create')}>New position</Link>
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
                    data={positions.data}
                    onSearch={search}
                    initialSearch={initialSearch}
                    getRowId={(row) => row.id}
                    getSearchValue={(row) => row.title}
                    searchPlaceholder="Search positions..."
                    maxSearchLength={30}
                    lettersOnly
                    emptyMessage="No positions yet."
                    columns={
                        [
                            { header: 'Position', cell: (row) => row.title },
                            { header: 'Employees', cell: (row) => row.employees_count },
                            {
                                header: 'Actions',
                                className: 'text-right',
                                cell: (row) => (
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost" size="icon" aria-label={`Actions for ${row.title}`}>
                                                <MoreHorizontal className="size-4" />
                                            </Button>
                                        </DropdownMenuTrigger>

                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem asChild>
                                                <Link href={route('positions.edit', row.id)}>Edit</Link>
                                            </DropdownMenuItem>

                                            {/* RESTRICT ang FK: may employees pa = hindi pwedeng i-delete */}
                                            {row.employees_count === 0 && (
                                                <DropdownMenuItem
                                                    className="text-destructive focus:text-destructive"
                                                    onSelect={() => setDeleteCandidate(row)}
                                                >
                                                    <Trash2 aria-hidden="true" />
                                                    Delete
                                                </DropdownMenuItem>
                                            )}
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                ),
                            },
                        ] satisfies DataTableColumn<Row>[]
                    }
                />

                <nav aria-label="Position pagination" className="flex flex-wrap justify-end gap-2">
                    {positions.links.map((link, index) => {
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
                        <DialogTitle>Delete position?</DialogTitle>
                        <DialogDescription>
                            {deleteCandidate ? `"${deleteCandidate.title}" will be permanently deleted. This cannot be undone.` : ''}
                        </DialogDescription>
                        <DialogFooter>
                            <Button variant="outline" disabled={isDeleting} onClick={() => setDeleteCandidate(null)}>
                                Cancel
                            </Button>
                            <Button variant="destructive" disabled={isDeleting} onClick={deleteRow}>
                                {isDeleting ? 'Deleting...' : 'Delete position'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}