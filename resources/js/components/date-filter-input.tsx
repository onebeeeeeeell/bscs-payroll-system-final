import { Input } from '@/components/ui/input';

type Props = {
    value: string;
    // allowed range (YYYY-MM-DD) from the server: current year + years that have records
    min: string;
    max: string;
    onApply: (value: string) => void;
    label?: string;
};

// Date filter that only applies a date inside [min, max] (or a cleared field).
// Typing a date outside the range is ignored, so the filter is never applied to it.
export default function DateFilterInput({ value, min, max, onApply, label = 'Filter by date' }: Props) {
    const isOutOfRange = (date: string) => date !== '' && (date < min || date > max);

    return (
        <Input
            type="date"
            aria-label={label}
            // uncontrolled on purpose: a controlled date input gets wiped while typing,
            // because a half-typed date reads as '' (or a wrong year)
            key={value}
            defaultValue={value}
            min={min}
            max={max}
            onChange={(event) => {
                // half-typed date: let the person keep typing
                if (event.target.validity.badInput) return;

                const next = event.target.value;

                if (isOutOfRange(next) || next === value) return;

                onApply(next);
            }}
            onBlur={(event) => {
                // leaving the field with a date outside the range: put back the applied one
                if (isOutOfRange(event.target.value)) event.target.value = value;
            }}
            className="h-10 w-44"
        />
    );
}