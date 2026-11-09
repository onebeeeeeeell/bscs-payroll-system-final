import { Command as CommandPrimitive } from 'cmdk';
import { Search } from 'lucide-react';
import * as React from 'react';

import { Dialog, DialogContent, DialogDescription, DialogTitle } from '@/components/ui/dialog';
import { cn } from '@/lib/utils';

const Command = React.forwardRef<React.ElementRef<typeof CommandPrimitive>, React.ComponentPropsWithoutRef<typeof CommandPrimitive>>(
    ({ className, ...props }, ref) => (
        <CommandPrimitive
            ref={ref}
            className={cn('flex h-full w-full flex-col overflow-hidden rounded-md bg-popover text-popover-foreground', className)}
            {...props}
        />
    ),
);
Command.displayName = CommandPrimitive.displayName;

// Command inside a Dialog (this is the Ctrl+K popup)
type CommandDialogProps = React.ComponentProps<typeof Dialog> & {
    shouldFilter?: boolean;
};

const CommandDialog = ({ children, shouldFilter = true, ...props }: CommandDialogProps) => (
    <Dialog {...props}>
        <DialogContent className="top-[20%] translate-y-0 gap-0 overflow-hidden p-0 shadow-lg sm:max-w-xl">
            {/* hidden title and description so screen readers know what this dialog is */}
            <DialogTitle className="sr-only">Search</DialogTitle>
            <DialogDescription className="sr-only">Search pages and employees</DialogDescription>
            <Command shouldFilter={shouldFilter}>{children}</Command>
        </DialogContent>
    </Dialog>
);

const CommandInput = React.forwardRef<React.ElementRef<typeof CommandPrimitive.Input>, React.ComponentPropsWithoutRef<typeof CommandPrimitive.Input>>(
    ({ className, ...props }, ref) => (
        <div className="flex items-center border-b px-3" cmdk-input-wrapper="">
            <Search className="mr-2 h-4 w-4 shrink-0 opacity-50" />
            <CommandPrimitive.Input
                ref={ref}
                className={cn(
                    'flex h-11 w-full rounded-md bg-transparent py-3 text-sm outline-hidden placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50',
                    className,
                )}
                {...props}
            />
        </div>
    ),
);
CommandInput.displayName = CommandPrimitive.Input.displayName;

const CommandList = React.forwardRef<React.ElementRef<typeof CommandPrimitive.List>, React.ComponentPropsWithoutRef<typeof CommandPrimitive.List>>(
    ({ className, ...props }, ref) => (
        <CommandPrimitive.List ref={ref} className={cn('max-h-[320px] overflow-x-hidden overflow-y-auto', className)} {...props} />
    ),
);
CommandList.displayName = CommandPrimitive.List.displayName;

const CommandEmpty = React.forwardRef<React.ElementRef<typeof CommandPrimitive.Empty>, React.ComponentPropsWithoutRef<typeof CommandPrimitive.Empty>>(
    (props, ref) => <CommandPrimitive.Empty ref={ref} className="py-6 text-center text-sm" {...props} />,
);
CommandEmpty.displayName = CommandPrimitive.Empty.displayName;

const CommandGroup = React.forwardRef<React.ElementRef<typeof CommandPrimitive.Group>, React.ComponentPropsWithoutRef<typeof CommandPrimitive.Group>>(
    ({ className, ...props }, ref) => (
        <CommandPrimitive.Group
            ref={ref}
            className={cn(
                'overflow-hidden p-1 text-foreground [&_[cmdk-group-heading]]:px-2 [&_[cmdk-group-heading]]:py-1.5 [&_[cmdk-group-heading]]:text-xs [&_[cmdk-group-heading]]:font-medium [&_[cmdk-group-heading]]:text-muted-foreground',
                className,
            )}
            {...props}
        />
    ),
);
CommandGroup.displayName = CommandPrimitive.Group.displayName;

const CommandItem = React.forwardRef<React.ElementRef<typeof CommandPrimitive.Item>, React.ComponentPropsWithoutRef<typeof CommandPrimitive.Item>>(
    ({ className, ...props }, ref) => (
        <CommandPrimitive.Item
            ref={ref}
            className={cn(
                "relative flex cursor-default items-center gap-2 rounded-sm px-2 py-2 text-sm outline-hidden select-none data-[disabled=true]:pointer-events-none data-[disabled=true]:opacity-50 data-[selected=true]:bg-accent data-[selected=true]:text-accent-foreground [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0",
                className,
            )}
            {...props}
        />
    ),
);
CommandItem.displayName = CommandPrimitive.Item.displayName;

export { Command, CommandDialog, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList };