import { Link, usePage } from '@inertiajs/react';
import { AlertTriangle, Bell, CheckCircle2, Info } from 'lucide-react';
import { useEffect, useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/popover';

type NotificationItem = {
    id: string;
    type: 'info' | 'warning' | 'error';
    title: string;
    description: string;
    href: string;
};

const icons = {
    info: <Info className="size-4 text-sky-600" />,
    warning: <AlertTriangle className="size-4 text-amber-600" />,
    error: <AlertTriangle className="size-4 text-red-600" />,
};

export function NotificationsBell() {
    const { url } = usePage();
    const [open, setOpen] = useState(false);
    const [items, setItems] = useState<NotificationItem[]>([]);

    // Refresh the list every time the page changes, so the count stays current
    useEffect(() => {
        const controller = new AbortController();

        fetch(route('notifications.index'), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            signal: controller.signal,
        })
            .then((response) => (response.ok ? response.json() : { notifications: [] }))
            .then((data) => setItems(data.notifications ?? []))
            .catch(() => {
                // keep the old list if the request fails or is cancelled
            });

        return () => controller.abort();
    }, [url]);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button type="button" variant="outline" size="icon" className="relative h-9 w-9" aria-label={`Notifications (${items.length})`}>
                    <Bell className="size-4" />
                    {items.length > 0 && (
                        <Badge variant="destructive" className="absolute -top-1.5 -right-1.5 h-4 min-w-4 justify-center px-1 text-[10px] leading-none">
                            {items.length > 9 ? '9+' : items.length}
                        </Badge>
                    )}
                </Button>
            </PopoverTrigger>

            <PopoverContent align="end" className="w-80 p-0">
                <div className="border-b px-4 py-3 text-sm font-semibold">Notifications</div>

                {items.length === 0 ? (
                    <div className="text-muted-foreground flex flex-col items-center gap-2 px-4 py-8 text-sm">
                        <CheckCircle2 className="size-5 text-emerald-600" />
                        You're all caught up.
                    </div>
                ) : (
                    <ul className="max-h-80 divide-y overflow-y-auto">
                        {items.map((item) => (
                            <li key={item.id}>
                                <Link href={item.href} onClick={() => setOpen(false)} className="hover:bg-accent flex gap-3 px-4 py-3 text-sm">
                                    <span className="mt-0.5">{icons[item.type]}</span>
                                    <span className="space-y-0.5">
                                        <span className="block font-medium">{item.title}</span>
                                        <span className="text-muted-foreground block text-xs">{item.description}</span>
                                    </span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </PopoverContent>
        </Popover>
    );
}