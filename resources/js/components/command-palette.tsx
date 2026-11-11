import { router } from '@inertiajs/react';
import { Calendar, FileText, HandCoins, LayoutGrid, Search, Settings, User, UserPlus, Users } from 'lucide-react';
import { useEffect, useState } from 'react';

import { CommandDialog, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/command';
import { Button } from '@/components/ui/button';

type EmployeeResult = {
    id: number;
    name: string;
    position: string | null;
    department: string | null;
};

// Pages you can jump to. Add new pages here and they show up in Ctrl+K.
const pages = [
    { title: 'Dashboard', href: '/dashboard', icon: LayoutGrid },
    { title: 'Employees', href: '/employees', icon: Users },
    { title: 'Add employee', href: '/employees/create', icon: UserPlus },
    { title: 'Attendance', href: '/attendance', icon: Calendar },
    { title: 'Payrolls', href: '/payrolls', icon: HandCoins },
    { title: 'Payslips', href: '/payslips', icon: FileText },
    { title: 'Organization settings', href: '/settings/organization', icon: Settings },
    { title: 'Account settings', href: '/settings/profile', icon: User },
];

export function CommandPalette() {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [employees, setEmployees] = useState<EmployeeResult[]>([]);

    // Ctrl+K (or Cmd+K on Mac) opens and closes the palette
    useEffect(() => {
        function onKeyDown(event: KeyboardEvent) {
            if (event.key.toLowerCase() === 'k' && (event.ctrlKey || event.metaKey)) {
                event.preventDefault();
                setOpen((current) => !current);
            }
        }

        document.addEventListener('keydown', onKeyDown);
        return () => document.removeEventListener('keydown', onKeyDown);
    }, []);

    // Look up employees on the server while typing (waits 250ms after the last keystroke)
    useEffect(() => {
        const term = query.trim();

        if (!open || term === '') {
            setEmployees([]);
            return;
        }

        const controller = new AbortController();
        const timer = setTimeout(() => {
            fetch(route('search', { q: term }), {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                signal: controller.signal,
            })
                .then((response) => (response.ok ? response.json() : { employees: [] }))
                .then((data) => setEmployees(data.employees ?? []))
                .catch(() => {
                    // ignore aborted or failed requests, the page list still works
                });
        }, 250);

        return () => {
            clearTimeout(timer);
            controller.abort();
        };
    }, [query, open]);

    function handleOpenChange(next: boolean) {
        setOpen(next);
        if (!next) setQuery('');
    }

    function go(href: string) {
        handleOpenChange(false);
        router.visit(href);
    }

    // We filter the pages ourselves because employee results already come filtered from the server
    const term = query.trim().toLowerCase();
    const matchingPages = pages.filter((page) => page.title.toLowerCase().includes(term));
    const hasResults = matchingPages.length > 0 || employees.length > 0;

    return (
        <>
            <Button
                type="button"
                variant="outline"
                onClick={() => setOpen(true)}
                aria-label="Search"
                className="text-muted-foreground h-9 w-9 gap-2 px-0 font-normal sm:w-56 sm:justify-start sm:px-3"
            >
                <Search className="size-4" />
                <span className="hidden sm:inline">Search...</span>
            </Button>

            <CommandDialog open={open} onOpenChange={handleOpenChange} shouldFilter={false}>
                <CommandInput
                    value={query}
                    onValueChange={(value) => setQuery(value.slice(0, 30))}
                    maxLength={30}
                    placeholder="Search pages or employees..."
                />
                <CommandList>
                    {!hasResults && <CommandEmpty>No results found.</CommandEmpty>}

                    {matchingPages.length > 0 && (
                        <CommandGroup heading="Pages">
                            {matchingPages.map((page) => (
                                <CommandItem key={page.href} value={page.href} onSelect={() => go(page.href)}>
                                    <page.icon />
                                    <span>{page.title}</span>
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    )}

                    {employees.length > 0 && (
                        <CommandGroup heading="Employees">
                            {employees.map((employee) => (
                                <CommandItem
                                    key={employee.id}
                                    value={`employee-${employee.id}`}
                                    onSelect={() => go(route('employees.show', employee.id))}
                                >
                                    <User />
                                    <span>{employee.name}</span>
                                    <span className="text-muted-foreground ml-auto text-xs">
                                        {[employee.position, employee.department].filter(Boolean).join(' · ')}
                                    </span>
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    )}
                </CommandList>
            </CommandDialog>
        </>
    );
}
