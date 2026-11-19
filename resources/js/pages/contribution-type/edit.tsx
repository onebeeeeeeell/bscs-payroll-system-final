import AppLayout from '@/layouts/app-layout';
import type { ContributionType } from '@/types/models';
import { Head } from '@inertiajs/react';
import TypeForm from './type-form';

type Props = {
    contributionType: ContributionType;
};

export default function EditContributionType({ contributionType }: Props) {
    return (
        <AppLayout>
            <Head title="Edit Contribution Type" />
            <div className="to-background dark:to-background mx-auto w-full max-w-3xl min-w-0 space-y-6 bg-gradient-to-b from-emerald-50/60 p-4 md:p-6 dark:from-emerald-950/20">
                <div className="space-y-1 rounded-2xl border border-emerald-800/10 bg-white/80 p-5 shadow-sm sm:p-6 dark:border-emerald-400/15 dark:bg-[#07130f]">
                    <p className="text-xs font-semibold tracking-[0.16em] text-emerald-700 uppercase dark:text-emerald-400">Contributions</p>
                    <h1 className="text-2xl font-semibold tracking-tight">Edit contribution type</h1>
                </div>

                <TypeForm contributionType={contributionType} />
            </div>
        </AppLayout>
    );
}