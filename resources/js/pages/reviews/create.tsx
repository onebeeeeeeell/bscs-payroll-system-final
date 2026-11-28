import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { type SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, Star } from 'lucide-react';
import { useState, type FormEvent } from 'react';

const NAME_MAX_LENGTH = 30;
const EMAIL_MAX_LENGTH = 30;
const COMMENT_MAX_LENGTH = 200;
const STARS = [1, 2, 3, 4, 5];

// Pareho ng ReviewRequest: letters at single spaces lang. Ang server pa rin ang huling bantay.
const cleanName = (value: string): string => value.replace(/[^\p{L} ]/gu, '').replace(/^ +/, '').replace(/ {2,}/g, ' ');

// Walang spaces at lowercase lang, kasi lowercase lang ang tinatanggap ng server.
const cleanEmail = (value: string): string => value.replace(/\s/g, '').toLowerCase();

export default function CreateReview() {
    const { flash } = usePage<SharedData>().props;
    const [hovered, setHovered] = useState(0);
    const form = useForm({
        name: '',
        email: '',
        rating: 0,
        comment: '',
        // Honeypot: nakatago sa tao, bot lang ang pupuno.
        website: '',
    });

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        if (form.processing) {
            return;
        }

        form.post(route('reviews.store'), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    }

    const shownRating = hovered || form.data.rating;

    return (
        <main className="flex min-h-dvh items-center justify-center bg-gradient-to-br from-emerald-50 via-white to-emerald-100 p-4 text-slate-950 dark:from-[#03140f] dark:via-[#02221a] dark:to-[#03100c] dark:text-white">
            <Head title="Write a Review" />
            <section className="w-full max-w-md rounded-2xl border border-emerald-800/15 bg-white p-6 shadow-xl sm:p-8 dark:border-[#1fe0a0]/30 dark:bg-[#07130f]">
                <Link href="/" className="text-muted-foreground hover:text-foreground inline-flex items-center gap-2 text-sm">
                    <ArrowLeft className="size-4" />
                    Back to home
                </Link>

                <h1 className="mt-4 text-2xl font-bold">Write a Review</h1>
                <p className="text-muted-foreground mt-2 text-sm leading-relaxed">
                    Tried our system? Tell us what you think. No account needed. Your review appears after it is approved.
                </p>

                {flash.success && (
                    <div className="mt-5 rounded-lg border border-emerald-700/20 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">
                        {flash.success}
                    </div>
                )}

                <form onSubmit={submit} className="mt-6 space-y-5">
                    <div className="space-y-2">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            type="text"
                            autoComplete="name"
                            maxLength={NAME_MAX_LENGTH}
                            value={form.data.name}
                            onChange={(event) => {
                                form.setData('name', cleanName(event.target.value));
                                form.clearErrors('name');
                            }}
                            required
                            disabled={form.processing}
                            aria-invalid={Boolean(form.errors.name)}
                        />
                        <p className="text-muted-foreground text-xs">Letters and single spaces only, up to 30 characters.</p>
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            autoComplete="email"
                            maxLength={EMAIL_MAX_LENGTH}
                            value={form.data.email}
                            onChange={(event) => {
                                form.setData('email', cleanEmail(event.target.value));
                                form.clearErrors('email');
                            }}
                            required
                            disabled={form.processing}
                            aria-invalid={Boolean(form.errors.email)}
                            placeholder="email@example.com"
                        />
                        <p className="text-muted-foreground text-xs">Kept private, never shown. One review per email.</p>
                        <InputError message={form.errors.email} />
                    </div>

                    <div className="space-y-2">
                        <Label>Rating</Label>
                        <div className="flex items-center gap-1" onMouseLeave={() => setHovered(0)} role="radiogroup" aria-label="Rating from 1 to 5 stars">
                            {STARS.map((star) => (
                                <button
                                    key={star}
                                    type="button"
                                    role="radio"
                                    aria-checked={form.data.rating === star}
                                    aria-label={`${star} star${star > 1 ? 's' : ''}`}
                                    disabled={form.processing}
                                    onMouseEnter={() => setHovered(star)}
                                    onClick={() => {
                                        form.setData('rating', star);
                                        form.clearErrors('rating');
                                    }}
                                    className="rounded-md p-1 transition-transform hover:scale-110 focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:outline-none"
                                >
                                    <Star
                                        className={cn(
                                            'size-8 transition-colors',
                                            star <= shownRating
                                                ? 'fill-amber-400 text-amber-400 drop-shadow-[0_0_6px_rgba(251,191,36,0.6)]'
                                                : 'text-slate-300 dark:text-white/25',
                                        )}
                                    />
                                </button>
                            ))}
                        </div>
                        <InputError message={form.errors.rating} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="comment">Comment</Label>
                        <textarea
                            id="comment"
                            rows={4}
                            maxLength={COMMENT_MAX_LENGTH}
                            value={form.data.comment}
                            onChange={(event) => {
                                form.setData('comment', event.target.value);
                                form.clearErrors('comment');
                            }}
                            required
                            disabled={form.processing}
                            aria-invalid={Boolean(form.errors.comment)}
                            className="border-input bg-background w-full resize-none rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:outline-none disabled:opacity-50"
                            placeholder="What did you like? What can we improve?"
                        />
                        <p className="text-muted-foreground text-right text-xs">
                            {form.data.comment.length}/{COMMENT_MAX_LENGTH}
                        </p>
                        <InputError message={form.errors.comment} />
                    </div>

                    {/* Honeypot: hindi nakikita at hindi na-ta-tab ng tunay na tao */}
                    <div aria-hidden className="absolute -left-[9999px] h-0 w-0 overflow-hidden">
                        <label htmlFor="website">Website</label>
                        <input
                            id="website"
                            type="text"
                            tabIndex={-1}
                            autoComplete="off"
                            value={form.data.website}
                            onChange={(event) => form.setData('website', event.target.value)}
                        />
                    </div>
                    <InputError message={form.errors.website} />

                    <Button
                        type="submit"
                        disabled={form.processing}
                        className="h-10 w-full bg-emerald-700 font-semibold text-white hover:bg-emerald-800 dark:bg-emerald-600 dark:hover:bg-emerald-500"
                    >
                        {form.processing ? 'Submitting...' : 'Submit Review'}
                    </Button>
                </form>
            </section>
        </main>
    );
}