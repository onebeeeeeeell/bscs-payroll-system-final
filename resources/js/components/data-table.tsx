import { Search } from 'lucide-react';
import { useState, type ReactNode } from 'react';

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
};

export function DataTable<T>({ data, columns, getRowId, getSearchValue, searchPlaceholder, emptyMessage }: DataTableProps<T>) {
    const [search, setSearch] = useState('');
    const normalizedSearch = search.trim().toLocaleLowerCase();
    const filteredData = normalizedSearch ? data.filter((row) => getSearchValue(row).toLocaleLowerCase().includes(normalizedSearch)) : data;

    return (
        <div className="min-w-0 rounded-xl border border-emerald-800/10 bg-white p-4 shadow-sm dark:border-emerald-400/15 dark:bg-[#07130f]">
            <div className="relative w-full pb-4 sm:max-w-sm">
                <Search className="text-muted-foreground absolute top-3 left-3 size-4" aria-hidden="true" />
                <Input
                    type="search"
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder={searchPlaceholder}
                    aria-label={searchPlaceholder}
                    className="pl-9"
                />
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
