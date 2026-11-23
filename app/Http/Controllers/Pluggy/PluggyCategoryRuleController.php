<?php

namespace App\Http\Controllers\Pluggy;

use App\Http\Controllers\Controller;
use App\Models\PluggyCategoryRule;
use App\Models\Category;
use App\Services\Pluggy\PluggyCategoryRuleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PluggyCategoryRuleController extends Controller
{
    public function store(Request $request, PluggyCategoryRuleService $service)
    {
        $data = $request->validate([
            'phrase' => ['required', 'string', 'min:3', 'max:150', function ($attribute, $value, $fail) { if (mb_strlen(trim($value)) < 3) $fail('Informe ao menos três caracteres.'); }],
            'category_id' => ['required', function ($attribute, $value, $fail) use ($request) {
                if ($value === 'new') return;
                if (! is_scalar($value) || ! ctype_digit((string) $value) || ! Category::where('user_id', $request->user()->id)->whereKey($value)->exists()) $fail('Escolha uma categoria válida.');
            }],
            'new_category' => ['nullable', 'required_if:category_id,new', 'string', 'max:100', function ($attribute, $value, $fail) { if ($value !== null && trim($value) === '') $fail('Informe o nome da categoria.'); }],
            'new_category_type' => ['nullable', 'required_if:category_id,new', Rule::in(['income', 'expense'])],
            'starts_on' => ['nullable', 'date_format:Y-m-d'],
        ]);
        DB::transaction(function () use ($request, $data, $service): void {
            if ($data['category_id'] === 'new') {
                $data['category_id'] = Category::create(['user_id' => $request->user()->id, 'name' => trim($data['new_category']), 'type' => $data['new_category_type']])->id;
            }
            PluggyCategoryRule::create([...$data, 'phrase' => trim($data['phrase']), 'user_id' => $request->user()->id]);
            $service->reclassify($request->user()->id);
        });
        return back()->with('success', 'Regra de categoria criada e transações reavaliadas.');
    }

    public function update(Request $request, int $rule, PluggyCategoryRuleService $service)
    {
        $item = PluggyCategoryRule::query()->where('user_id', $request->user()->id)->findOrFail($rule);
        $data = $request->validate([
            'phrase' => ['required', 'string', 'min:3', 'max:150', function ($attribute, $value, $fail) { if (mb_strlen(trim($value)) < 3) $fail('Informe ao menos três caracteres.'); }],
            'category_id' => ['required', Rule::exists('categories', 'id')->where('user_id', $request->user()->id)],
            'starts_on' => ['nullable', 'date_format:Y-m-d'],
        ]);
        if ($item->ends_before && ! empty($data['starts_on']) && $data['starts_on'] >= $item->ends_before->toDateString()) {
            return back()->withErrors(['starts_on' => 'A data inicial deve ser anterior ao encerramento.']);
        }
        DB::transaction(function () use ($item, $data, $service, $request): void {
            $item->update([...$data, 'phrase' => trim($data['phrase'])]);
            $service->reclassify($request->user()->id);
        });
        return back()->with('success', 'Regra atualizada e transações reavaliadas.');
    }

    public function destroy(Request $request, int $rule, PluggyCategoryRuleService $service)
    {
        $item = PluggyCategoryRule::query()->where('user_id', $request->user()->id)->findOrFail($rule);
        $data = $request->validate(['mode' => ['required', Rule::in(['end', 'delete'])], 'ends_before' => ['nullable', 'required_if:mode,end', 'date_format:Y-m-d']]);
        if ($data['mode'] === 'end' && $item->starts_on && $data['ends_before'] <= $item->starts_on->toDateString()) {
            return back()->withErrors(['ends_before' => 'A data de encerramento deve ser posterior ao início.']);
        }
        DB::transaction(function () use ($item, $data, $service, $request): void {
            if ($data['mode'] === 'delete') $item->delete();
            else $item->update(['ends_before' => $data['ends_before']]);
            $service->reclassify($request->user()->id);
        });
        return back()->with('success', 'Regra de categoria atualizada e transações reavaliadas.');
    }
}
