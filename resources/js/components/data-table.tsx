import { Search } from 'lucide-react';
import { useEffect, useRef, useState, type ReactNode } from 'react';

import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { cn } from '@/lib/utils';

export type DataTableColumn<T> = {
    header: ReactNode;
    cell: (row: T) => ReactNode;
    className?: string;
};

type DataTableProps<T> = {
    data: T[];
    columns: DataTableColumn<T>[];
    getRowId: (row: T) => string | number;
    getSearchValue: (row: T) => string;
    searchPlaceholder: string;
    emptyMessage: string;
    maxSearchLength?: number;
    // Search box accepts letters and spaces only (no numbers or special characters)
    lettersOnly?: boolean;
    toolbar?: ReactNode;
    /*
     * Server search mode (optional). Kapag may onSearch, hindi na sa browser
     * nagfi-filter ang table: tinatawag ang onSearch(term) pagkatapos tumigil
     * mag-type (debounced), at ang server ang magpapadala ng na-filter na data.
     * initialSearch = ang kasalukuyang ?search= para hindi blangko ang input
     * pagka-reload. Kapag wala ang onSearch, browser search pa rin gaya ng dati.
     */
    onSearch?: (term: string) => void;
    initialSearch?: string;
};

const SEARCH_DEBOUNCE_MS = 300;

export function DataTable<T>({
    data,
    columns,
    getRowId,
    getSearchValue,
    searchPlaceholder,
    emptyMessage,
    maxSearchLength,
    lettersOnly = false,
    toolbar,
    onSearch,
    initialSearch = '',
}: DataTableProps<T>) {
    const [search, setSearch] = useState(initialSearch);
    const normalizedSearch = search.trim().toLocaleLowerCase();
    const isServerSearch = onSearch !== undefined;
    const filteredData = !isServerSearch && normalizedSearch ? data.filter((row) => getSearchValue(row).toLocaleLowerCase().includes(normalizedSearch)) : data;

    // Debounced na tawag sa server; hindi tinatawag sa unang render o kung
    // pareho lang ang term sa kasalukuyang ?search=.
    const lastSent = useRef(initialSearch.trim());
    const onSearchRef = useRef(onSearch);
    onSearchRef.current = onSearch;

    useEffect(() => {
        if (!isServerSearch) return;

        const term = search.trim();
        if (term === lastSent.current) return;

        const timer = setTimeout(() => {
            lastSent.current = term;
            onSearchRef.current?.(term);
        }, SEARCH_DEBOUNCE_MS);

        return () => clearTimeout(timer);
    }, [search, isServerSearch]);

    return (
        <div className="min-w-0 rounded-xl border border-emerald-800/10 bg-white p-4 shadow-sm dark:border-emerald-400/15 dark:bg-[#07130f]">
            <div className="flex flex-wrap items-center gap-3 pb-4">
                <div className="relative w-full sm:max-w-sm">
                    <span className="pointer-events-none absolute inset-y-0 left-3 flex items-center" aria-hidden="true">
                        <Search className="text-muted-foreground size-4" />
                    </span>
                    <Input
                        type="search"
                        value={search}
                        onChange={(event) => {
                            const value = lettersOnly ? event.target.value.replace(/[^\p{L} ]/gu, '') : event.target.value;

                            setSearch(value.slice(0, maxSearchLength));
                        }}
                        maxLength={maxSearchLength}
                        placeholder={searchPlaceholder}
                        aria-label={searchPlaceholder}
                        className="pl-9"
                    />
                </div>
                {toolbar}
            </div>
            <Table className="min-w-[760px]">
                <TableHeader>
                    <TableRow className="border-emerald-800/10 bg-emerald-50/70 hover:bg-emerald-50/70 dark:border-emerald-400/15 dark:bg-emerald-950/30 dark:hover:bg-emerald-950/30">
                        {columns.map((column, index) => (
                            <TableHead key={index} className={cn('text-foreground', column.className)}>
                                {column.header}
                            </TableHead>
                        ))}
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {filteredData.length > 0 ? (
                        filteredData.map((row) => (
                            <TableRow
                                key={getRowId(row)}
                                className="border-border/70 transition-colors hover:bg-emerald-50/40 dark:hover:bg-emerald-950/20"
                            >
                                {columns.map((column, index) => (
                                    <TableCell key={index} className={cn('px-4 py-3', column.className)}>
                                        {column.cell(row)}
                                    </TableCell>
                                ))}
                            </TableRow>
                        ))
                    ) : (
                        <TableRow>
                            <TableCell colSpan={columns.length} className="text-muted-foreground h-24 text-center">
                                {normalizedSearch ? 'No matching records found.' : emptyMessage}
                            </TableCell>
                        </TableRow>
                    )}
                </TableBody>
            </Table>
        </div>
    );
}