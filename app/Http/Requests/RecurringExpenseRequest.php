<?php

namespace App\Http\Requests;

use App\Models\RecurringExpense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecurringExpenseRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return [
            'description' => [
                'nullable', 
                'string', 
                'max:255'
            ], 
            'amount' => [
                'required', 
                'numeric', 
                'min:0.01', 
                'max:99999999.99'
            ],
            'category_id' => [
                'required', 
                Rule::exists('categories', 'id')->where('user_id', $this->user()->id)->where('type', 'expense')
            ],
            'date' => [
                'required', 
                'date'
            ], 
            'end_date' => [
                'nullable', 
                'date', 
                'after_or_equal:date'
            ],
            'frequency' => [
                'required', 
                Rule::in(array_keys(RecurringExpense::FREQUENCIES))
            ],
            'payment_mode' => [
                'required', 
                Rule::in(['pix', 'dinheiro', 'debito', 'credito'])
            ],
            'bank_choice' => [
                'required', function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === 'none' || $value === 'new') {
                        return;
                    }

                    if (! is_scalar($value) || ! ctype_digit((string) $value)
                        || ! \App\Models\ManualBank::where('user_id', $this->user()->id)->whereKey($value)->exists()) {
                        $fail('Escolha um banco válido ou informe que não foi realizado por meio de um banco.');
                    }
                }
            ],
            'new_bank' => [
                'nullable', 
                'required_if:bank_choice,new', 
                'string', 
                'max:100'
            ],
        ];
    }
}
