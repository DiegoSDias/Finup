<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettlePayableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'payment_date' => ['required', 'date'],
            'payment_mode' => ['required', Rule::in(['pix', 'dinheiro', 'debito', 'credito'])],
            'bank_choice' => ['required', function (string $attribute, mixed $value, \Closure $fail): void {
                if (in_array($value, ['none', 'new'], true)) {
                    return;
                }
                if (! is_scalar($value) || ! ctype_digit((string) $value)
                    || ! \App\Models\ManualBank::query()->where('user_id', $this->user()->id)->whereKey($value)->exists()) {
                    $fail('Escolha um banco válido ou informe que o pagamento não passou por banco.');
                }
            }],
            'new_bank' => ['nullable', 'required_if:bank_choice,new', 'string', 'max:100'],
        ];
    }
}
