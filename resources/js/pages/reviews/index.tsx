import { DataTable, type DataTableColumn } from '@/components/data-table';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import type { SharedData } from '@/types';
import type { PaginatedData } from '@/types/models';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Archive, Check, MoreHorizontal, RotateCcw, Star } from 'lucide-react';
import { useState } from 'react';

type ReviewStatus = 'pending' | 'approved' | 'archived';

type Row = {
    id: number;
    name: string;
    email: string;
    rating: number;
    comment: string;
    created_at: string;
};

type Props = {
    reviews: PaginatedData<Row>;
    filters: { status: ReviewStatus };
};

const STATUS_LABELS: Record<ReviewStatus, string> = {
    pending: 'Pending approval',
    approved: 'Approved',
    archived: 'Archived',
};

const formatDate = (value: string): string => new Date(value).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });

export default function ReviewIndex({ reviews, filters }: Props) {
    const { flash } = usePage<SharedData>().props;
    const [busyId, setBusyId] = useState<number | null>(null);

    function changeStatus(status: string) {
        const next = status === 'approved' || status === 'archived' ? status : 'pending';

        router.get(route('reviews.index'), next === 'pending' ? {} : { status: next }, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['reviews', 'filters'],
        });
    }

    function act(routeName: 'reviews.approve' | 'reviews.archive' | 'reviews.restore', row: Row) {
        if (busyId !== null) return;

        setBusyId(row.id);
        router.patch(route(routeName, row.id), {}, { preserveScroll: true, onFinish: () => setBusyId(null) });
    }

    return (
        <AppLayout>
            <Head title="Reviews" />
            <div className="flex w-full min-w-0 flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Reviews</h1>
                    <p className="text-muted-foreground text-sm">
                        Reviews from the public page. Only approved reviews show on the welcome page, and emails are never shown publicly.
                    </p>
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
                    data={reviews.data}
                    getRowId={(row) => row.id}
                    getSearchValue={(row) => `${row.name} ${row.comment}`}
                    searchPlaceholder="Search this page..."
                    maxSearchLength={30}
                    lettersOnly
                    emptyMessage={`No ${filters.status} reviews.`}
                    toolbar={
                        <select
                            aria-label="Filter reviews by status"
                            value={filters.status}
                            onChange={(event) => changeStatus(event.target.value)}
                            className="border-input bg-background h-10 min-w-44 rounded-md border px-3 text-sm"
                        >
                            {(Object.keys(STATUS_LABELS) as ReviewStatus[]).map((status) => (
                                <option key={status} value={status}>
                                    {STATUS_LABELS[status]}
                                </option>
                            ))}
                        </select>
                    }
                    columns={
                        [
                            {
                                header: 'Reviewer',
                                cell: (row) => (
                                    <div className="min-w-0">
                                        <p className="font-medium">{row.name}</p>
                                        <p className="text-muted-foreground text-xs break-all">{row.email}</p>
                                    </div>
                                ),
                            },
                            {
                                header: 'Rating',
                                cell: (row) => (
                                    <div className="flex items-center gap-0.5" aria-label={`${row.rating} out of 5 stars`}>
                                        {[1, 2, 3, 4, 5].map((star) => (
                                            <Star
                                                key={star}
                                                className={cn('size-4', star <= row.rating ? 'fill-amber-400 text-amber-400' : 'text-slate-300 dark:text-white/25')}
                                            />
                                        ))}
                                    </div>
                                ),
                            },
                            { header: 'Comment', cell: (row) => <p className="max-w-xs text-sm break-words whitespace-normal">{row.comment}</p> },
                            { header: 'Date', cell: (row) => formatDate(row.created_at) },
                            {
                                header: 'Actions',
                                className: 'text-right',
                                cell: (row) => (
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost" size="icon" disabled={busyId !== null} aria-label={`Actions for ${row.name}`}>
                                                <MoreHorizontal className="size-4" />
                                            </Button>
                                        </DropdownMenuTrigger>

                                        <DropdownMenuContent align="end">
                                            {filters.status === 'pending' && (
                                                <DropdownMenuItem onSelect={() => act('reviews.approve', row)}>
                                                    <Check aria-hidden="true" />
                                                    Approve
                                                </DropdownMenuItem>
                                            )}

                                            {filters.status === 'archived' ? (
                                                <DropdownMenuItem onSelect={() => act('reviews.restore', row)}>
                                                    <RotateCcw aria-hidden="true" />
                                                    Restore
                                                </DropdownMenuItem>
                                            ) : (
                                                <DropdownMenuItem onSelect={() => act('reviews.archive', row)}>
                                                    <Archive aria-hidden="true" />
                                                    Archive
                                                </DropdownMenuItem>
                                            )}
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                ),
                            },
                        ] satisfies DataTableColumn<Row>[]
                    }
                />

                <nav aria-label="Review pagination" className="flex flex-wrap justify-end gap-2">
                    {reviews.links.map((link, index) => {
                        const label = link.label.replace(/&laquo;|&raquo;/g, '').trim();

                        return link.url ? (
                            <Link
                                key={`${link.label}-${index}`}
                                href={link.url}
                                preserveScroll
                                className={`rounded-md border px-3 py-2 text-sm ${link.active ? 'bg-primary text-primary-foreground' : 'bg-background'}`}
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
            </div>
        </AppLayout>
    );
}