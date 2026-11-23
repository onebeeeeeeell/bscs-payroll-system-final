<?php

namespace App\Http\Requests\PayrollPeriod;

use App\Models\PayrollPeriod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PayrollPeriodRequest extends FormRequest
{
    private const DATE_FIELDS = ['start_date', 'end_date', 'pay_date'];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $required = $this->isMethod('PATCH') ? 'sometimes' : 'required';

        return [
            'start_date' => [$required, 'date_format:Y-m-d', ...$this->notInThePastRules('start_date')],
            'end_date' => [$required, 'date_format:Y-m-d', ...$this->notInThePastRules('end_date')],
            'pay_date' => [$required, 'date_format:Y-m-d', ...$this->notInThePastRules('pay_date')],
            'status' => $this->statusRules($required),
        ];
    }

    /*
     * LABEL: Cross-field date checks
     * Purpose: Start <= end <= pay date, at walang overlap sa ibang active na period.
     * Dito na (hindi sa rules()) para tama rin ang PATCH na isa lang ang field:
     * ang kulang na petsa ay kinukuha sa naka-save na period.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(self::DATE_FIELDS)) {
                return;
            }

            $start = $this->effectiveDate('start_date');
            $end = $this->effectiveDate('end_date');
            $pay = $this->effectiveDate('pay_date');

            if ($start === null || $end === null || $pay === null) {
                return;
            }

            if ($end < $start) {
                $validator->errors()->add('end_date', 'The end date cannot be before the start date.');
            }

            if ($pay < $end) {
                $validator->errors()->add('pay_date', 'The pay date cannot be before the end date.');
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $overlap = PayrollPeriod::query()
                ->overlapping($start, $end, $this->period()?->getKey())
                ->first(['start_date', 'end_date']);

            if ($overlap !== null) {
                $validator->errors()->add(
                    'start_date',
                    'These dates overlap an existing payroll period ('.substr((string) $overlap->start_date, 0, 10).' to '.substr((string) $overlap->end_date, 0, 10).').',
                );
            }
        }];
    }

    private function period(): ?PayrollPeriod
    {
        $period = $this->route('payroll_period');

        return $period instanceof PayrollPeriod ? $period : null;
    }

    /*
     * Ang "hindi pwedeng nakaraan" na rule ay sa bagong petsa lang. Kapag hindi
     * binago ang petsa ng period na nagsimula na, pwede pa rin itong i-edit.
     *
     * @return list<string>
     */
    private function notInThePastRules(string $field): array
    {
        $period = $this->period();

        if ($period !== null && $this->input($field) === $this->storedDate($period, $field)) {
            return [];
        }

        return ['after_or_equal:today'];
    }

    /**
     * @return array<int, mixed>
     */
    private function statusRules(string $required): array
    {
        $period = $this->period();

        // Create: Pending lang. Hindi pwedeng mag-umpisa sa Paid o ibang status.
        if ($period === null) {
            return ['sometimes', Rule::in([PayrollPeriod::INITIAL_STATUS])];
        }

        return [
            $required,
            Rule::in(PayrollPeriod::STATUSES),
            function (string $attribute, mixed $value, \Closure $fail) use ($period): void {
                if (in_array($period->status, PayrollPeriod::FINAL_STATUSES, true) && $value !== $period->status) {
                    $fail("A {$period->status} period cannot be changed to another status.");
                }
            },
        ];
    }

    private function storedDate(PayrollPeriod $period, string $field): string
    {
        return substr((string) $period->getRawOriginal($field), 0, 10);
    }

    private function effectiveDate(string $field): ?string
    {
        $input = $this->input($field);

        if (is_string($input) && $input !== '') {
            return $input;
        }

        $period = $this->period();

        return $period === null ? null : $this->storedDate($period, $field);
    }
}