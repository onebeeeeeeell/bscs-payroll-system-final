import AppLogoIcon from '@/components/app-logo-icon';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';

interface AuthLayoutProps {
    children: React.ReactNode;
    title?: string;
    description?: string;
}

export default function AuthSplitLayout({ children, title, description }: AuthLayoutProps) {
    const { name, quote } = usePage<SharedData>().props;

    return (
        <div className="relative grid min-h-svh grid-cols-1 lg:grid-cols-2">
            <div className="bg-muted relative hidden min-h-svh flex-col p-10 text-white lg:flex dark:border-r">
                <div className="absolute inset-0 bg-zinc-900" />
                <Link href={route('home')} className="relative z-20 flex items-center gap-3 text-lg font-medium">
                    <AppLogoIcon className="size-10 rounded-full object-contain" />
                    {name}
                </Link>
                {quote && (
                    <div className="relative z-20 mt-auto">
                        <blockquote className="space-y-2">
                            <p className="text-lg">&ldquo;{quote.message}&rdquo;</p>
                            <footer className="text-sm text-neutral-300">{quote.author}</footer>
                        </blockquote>
                    </div>
                )}
            </div>
            <div className="flex min-h-svh w-full items-center justify-center p-6 lg:p-8">
                <div className="mx-auto flex w-full max-w-sm flex-col justify-center space-y-6">
                    <Link href={route('home')} className="relative z-20 flex items-center justify-center gap-3 font-medium lg:hidden">
                        <AppLogoIcon className="size-10 rounded-full object-contain" />
                        <span>{name}</span>
                    </Link>
                    <div className="flex flex-col items-start gap-2 text-left sm:items-center sm:text-center">
                        <h1 className="text-xl font-medium">{title}</h1>
                        <p className="text-muted-foreground text-sm text-balance">{description}</p>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
