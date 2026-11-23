<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\Group;
use App\Models\ManualBank;
use App\Models\PluggyAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinancialAnalysisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'year' => ['nullable', 'integer', 'between:1900,2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'source' => ['nullable', Rule::in(['manual', 'imported'])],
            'group_id' => ['nullable', function ($attribute, $value, $fail) {
                if ($value !== 'none' && (! is_scalar($value) || ! ctype_digit((string) $value)
                    || ! Group::where('user_id', $this->user()->id)->whereKey($value)->exists())) {
                    $fail('Selecione um grupo válido.');
                }
            }],
            'category_id' => ['nullable', function ($attribute, $value, $fail) {
                if ($value === 'none' && in_array($this->input('group_id'), [null, '', 'none'], true)) {
                    return;
                }

                $categories = Category::where('user_id', $this->user()->id);
                if ($this->filled('group_id')) {
                    $this->input('group_id') === 'none'
                        ? $categories->whereNull('group_id')
                        : $categories->where('group_id', is_scalar($this->input('group_id')) ? $this->input('group_id') : 0);
                }

                if (! is_scalar($value) || ! ctype_digit((string) $value) || ! $categories->whereKey($value)->exists()) {
                    $fail('Selecione uma categoria válida para o grupo.');
                }
            }],
            'bank' => ['nullable', function ($attribute, $value, $fail) {
                if (in_array($value, ['none', 'unknown'], true) && $this->input('source') !== 'imported') {
                    return;
                }
                if (! is_string($value) || ! preg_match('/^(manual|account):([1-9]\d*)$/D', $value, $matches)) {
                    $fail('Selecione um banco ou conta válido.');

                    return;
                }

                $manual = $matches[1] === 'manual';
                $model = $manual ? ManualBank::class : PluggyAccount::class;
                if (($manual && $this->input('source') === 'imported')
                    || (! $manual && $this->input('source') === 'manual')
                    || ! $model::where('user_id', $this->user()->id)->whereKey($matches[2])->exists()) {
                    $fail('Selecione um banco ou conta válido para a origem.');
                }
            }],
        ];
    }

    public function messages(): array
    {
        return [
            'year.integer' => 'Informe um ano válido.',
            'year.between' => 'Selecione um ano entre 1900 e 2100.',
            'month.integer' => 'Selecione um mês válido.',
            'month.between' => 'Selecione um mês entre janeiro e dezembro.',
            'source.in' => 'Selecione uma origem válida.',
        ];
    }
}
