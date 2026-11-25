import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, Clock3 } from 'lucide-react';
import { useRef, type FormEvent } from 'react';

export default function TimeClock() {
    const { flash } = usePage<SharedData>().props;
    const submissionInProgress = useRef(false);
    const form = useForm({
        full_name: '',
        pin: '',
        action: 'time_in',
    });

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        if (submissionInProgress.current || form.processing) {
            return;
        }

        submissionInProgress.current = true;
        form.post('/time-clock', {
            onSuccess: () => form.reset('pin'),
            onFinish: () => {
                submissionInProgress.current = false;
            },
        });
    }

    function fieldError(field: keyof typeof form.data) {
        const message = form.errors[field];
        return message ? <p className="text-destructive text-sm">{message}</p> : null;
    }

    return (
        <main className="flex min-h-dvh items-center justify-center bg-gradient-to-br from-emerald-50 via-white to-emerald-100 p-4 text-slate-950 dark:from-[#03140f] dark:via-[#02221a] dark:to-[#03100c] dark:text-white">
            <Head title="Employee Clock-In" />
            <section className="w-full max-w-md rounded-2xl border border-emerald-800/15 bg-white p-6 shadow-xl sm:p-8 dark:border-[#1fe0a0]/30 dark:bg-[#07130f]">
                <Link href="/" className="text-muted-foreground hover:text-foreground inline-flex items-center gap-2 text-sm">
                    <ArrowLeft className="size-4" />
                    Back to home
                </Link>

                <h1 className="mt-4 text-2xl font-bold">Employee Clock-In</h1>
                <p className="text-muted-foreground mt-2 text-sm leading-relaxed">
                    Enter your full name and your six-digit PIN. Each employee can record one time-in and one time-out per workday.
                </p>

                {flash.success && (
                    <div className="mt-5 rounded-lg border border-emerald-700/20 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">
                        {flash.success}
                    </div>
                )}

                <form onSubmit={submit} className="mt-6 space-y-5">
                    <div className="space-y-2">
                        <Label htmlFor="full_name">Full name</Label>
                        <Input
                            id="full_name"
                            type="text"
                            autoComplete="name"
                            maxLength={30}
                            value={form.data.full_name}
                            onChange={(event) => {
                                form.setData('full_name', event.target.value.replace(/[^\p{L}\s]/gu, '').replace(/\s+/g, ' ').slice(0, 30));
                                form.clearErrors('full_name');
                            }}
                            required
                            disabled={form.processing}
                            aria-invalid={Boolean(form.errors.full_name)}
                        />
                        {fieldError('full_name')}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="pin">Six-digit PIN</Label>
                        <Input
                            id="pin"
                            type="password"
                            inputMode="numeric"
                            pattern="[0-9]{6}"
                            maxLength={6}
                            autoComplete="current-password"
                            value={form.data.pin}
                            onChange={(event) => {
                                form.setData('pin', event.target.value.replace(/\D/g, '').slice(0, 6));
                                form.clearErrors('pin');
                            }}
                            required
                            disabled={form.processing}
                            aria-invalid={Boolean(form.errors.pin)}
                        />
                        {fieldError('pin')}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="action">Attendance action</Label>
                        <select
                            id="action"
                            value={form.data.action}
                            onChange={(event) => {
                                form.setData('action', event.target.value);
                                form.clearErrors('action');
                            }}
                            disabled={form.processing}
                            className="border-input bg-background h-10 w-full rounded-md border px-3 text-sm"
                        >
                            <option value="time_in">Time in</option>
                            <option value="time_out">Time out</option>
                        </select>
                        {fieldError('action')}
                    </div>

                    <Button
                        type="submit"
                        disabled={form.processing || Boolean(form.errors.action)}
                        className="h-10 w-full bg-emerald-700 font-semibold text-white hover:bg-emerald-800 dark:bg-emerald-600 dark:hover:bg-emerald-500"
                    >
                        {form.processing ? 'Recording...' : 'Submit Attendance'}
                    </Button>
                </form>

                <p className="text-muted-foreground mt-5 text-xs leading-relaxed">
                    Your PIN is kept private. Contact an administrator if you need your time-clock PIN set or reset.
                </p>
            </section>
        </main>
    );
}