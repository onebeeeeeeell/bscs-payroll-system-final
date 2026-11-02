import AppLogoIcon from '@/components/app-logo-icon';
import AppearanceToggleDropdown from '@/components/appearance-dropdown';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';

interface AuthLayoutProps {
    children: React.ReactNode;
    title?: string;
    description?: string;
}

export default function AuthSplitLayout({ children, title, description }: AuthLayoutProps) {
    const { name } = usePage<SharedData>().props;

    return (
        <div className="bg-background relative grid min-h-svh grid-cols-1 lg:grid-cols-2">
            <aside className="border-border/70 bg-background relative hidden min-h-svh flex-col justify-between overflow-hidden border-r p-10 lg:flex xl:p-12 dark:border-emerald-500/15 dark:bg-[#03100c]">
                <div
                    aria-hidden="true"
                    className="absolute -top-32 -right-32 size-[34rem] rounded-full bg-emerald-700/[0.05] dark:bg-emerald-400/[0.07]"
                />
                <Link href={route('home')} className="relative z-10 flex w-fit items-center gap-3 font-semibold">
                    <AppLogoIcon className="size-10 rounded-xl object-contain" />
                    <span>{name}</span>
                </Link>

                <section className="relative z-10 max-w-xl space-y-5">
                    <div className="inline-flex items-center gap-2 rounded-full bg-emerald-700/10 px-3 py-1.5 text-xs font-semibold tracking-wide text-emerald-800 dark:bg-emerald-400/10 dark:text-emerald-300">
                        <span className="size-1.5 rounded-full bg-emerald-700 dark:bg-emerald-400" />
                        PAYROLL, ATTENDANCE &amp; COMPLIANCE
                    </div>

                    <h2 className="max-w-lg text-4xl leading-tight font-semibold tracking-tight xl:text-5xl">
                        The calm center for every <span className="text-emerald-700 dark:text-emerald-400">pay cycle.</span>
                    </h2>
                    <p className="text-muted-foreground max-w-lg text-base leading-relaxed">
                        Run accurate payroll, review attendance, and keep statutory records ready, all from one secure workspace.
                    </p>
                </section>

                <div className="text-muted-foreground relative z-10 flex items-center gap-2 text-sm">
                    <ShieldCheck className="size-4 text-emerald-700 dark:text-emerald-400" />
                    <span>Encrypted access · Audit logged</span>
                </div>
            </aside>

            <main className="bg-background relative flex min-h-svh w-full items-start justify-center overflow-x-clip px-4 pt-20 pb-8 sm:items-center sm:px-8 sm:py-16 dark:bg-[#03100c]">
                <div
                    aria-hidden="true"
                    className="absolute -top-40 -right-40 size-96 rounded-full bg-emerald-700/[0.05] lg:hidden dark:bg-emerald-400/[0.07]"
                />
                <AppearanceToggleDropdown className="absolute top-5 right-5 z-20" />
                <div className="relative z-10 my-auto flex w-full max-w-md flex-col gap-5 sm:gap-8">
                    <Link href={route('home')} className="flex items-center gap-3 font-semibold lg:hidden">
                        <AppLogoIcon className="size-10 rounded-xl object-contain" />
                        <span>{name}</span>
                    </Link>

                    <section className="border-border/80 bg-card w-full rounded-2xl border p-5 shadow-xl shadow-black/[0.06] sm:p-9 dark:border-emerald-500/20 dark:bg-[#041a13] dark:shadow-black/30">
                        <div className="mb-5 space-y-2 sm:mb-7">
                            <p className="text-xs font-bold tracking-wide text-emerald-700 uppercase dark:text-emerald-400">Secure workspace</p>
                            <h1 className="text-2xl font-semibold tracking-tight">{title}</h1>
                            <p className="text-muted-foreground text-sm leading-relaxed">{description}</p>
                        </div>
                        {children}
                    </section>
                </div>
            </main>
        </div>
    );
}
