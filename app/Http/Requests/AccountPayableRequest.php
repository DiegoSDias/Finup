<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccountPayableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99', 'regex:/^\d+(\.\d{1,2})?$/'],
            'due_date' => ['required', 'date'],
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where('user_id', $this->user()->id)->where('type', 'expense')],
        ];
    }
}
