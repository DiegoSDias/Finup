<?php

namespace App\Http\Controllers\Pluggy;

use App\Http\Controllers\Controller;
use App\Models\PluggyTransactionRule;
use App\Services\Pluggy\PluggyTransactionRuleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\PluggyTransaction;

class PluggyTransactionRuleController extends Controller
{
    public function store(Request $request, PluggyTransactionRuleService $ruleService): RedirectResponse
    {
        $validated = $request->validate([
            'phrase' => ['required', 'string', 'min:3', 'max:150'],
            'pluggy_connection_id' => ['required', Rule::exists('pluggy_items', 'id')->where('user_id', $request->user()->id)->where('is_active', true)->whereNull('deleted_at')],
            'action' => ['required', 'in:' . implode(',', [
                PluggyTransactionRule::ACTION_EXCLUDE,
                PluggyTransactionRule::ACTION_INTERNAL_TRANSFER,
            ])],
        ]);

        DB::transaction(function () use ($request, $validated, $ruleService): void {
        PluggyTransactionRule::query()->firstOrCreate([
            'user_id' => $request->user()->id,
            'pluggy_connection_id' => $validated['pluggy_connection_id'],
            'phrase' => trim($validated['phrase']),
            'action' => $validated['action'],
            'ends_before' => null,
        ], ['is_active' => true]);

        $ruleService->reclassifyForUser($request->user()->id);
        });

        return back()->with('success', 'Regra salva e movimentações existentes atualizadas.');
    }

    public function destroy(Request $request, PluggyTransactionRule $rule, PluggyTransactionRuleService $ruleService): RedirectResponse
    {
        abort_unless($rule->user_id === $request->user()->id, 404);

        $validated = $request->validate(['ends_before' => ['required', 'date_format:Y-m-d']]);
        DB::transaction(function () use ($rule, $ruleService, $request, $validated): void {
            $rule->update(['ends_before' => $validated['ends_before']]);
            $ruleService->reclassifyForUser($request->user()->id);
        });

        return back()->with('success', 'Regra encerrada a partir da data escolhida e movimentações recalculadas.');
    }

    public function restore(Request $request, int $transaction, PluggyTransactionRuleService $ruleService): RedirectResponse
    {
        DB::transaction(function () use ($request, $transaction, $ruleService): void {
            $item = PluggyTransaction::query()->where('user_id', $request->user()->id)
                ->whereNull('removed_at')->whereNull('deleted_by_user_at')
                ->whereIn('exclusion_reason', ['user_exclusion_phrase', 'user_transfer_phrase'])
                ->lockForUpdate()->findOrFail($transaction);
            $item->update(['phrase_rules_restored' => true]);
            $ruleService->reclassifyForUser($request->user()->id);
        });
        return back()->with('success', 'Transação reavaliada. Transferências próprias e outras classificações automáticas continuam sendo respeitadas.');
    }

    public function update(Request $request, PluggyTransactionRule $rule, PluggyTransactionRuleService $ruleService): RedirectResponse
    {
        abort_unless($rule->user_id === $request->user()->id, 404);
        abort_if($rule->ends_before !== null, 422);

        $validated = $request->validate([
            'phrase' => ['required', 'string', 'min:3', 'max:150'],
            'action' => ['required', 'in:' . implode(',', [
                PluggyTransactionRule::ACTION_EXCLUDE,
                PluggyTransactionRule::ACTION_INTERNAL_TRANSFER,
            ])],
        ]);

        $phrase = trim($validated['phrase']);
        $duplicate = PluggyTransactionRule::query()
            ->where('user_id', $request->user()->id)
            ->where('phrase', $phrase)
            ->where('action', $validated['action'])
            ->where('id', '!=', $rule->id)
            ->exists();

        if ($duplicate) {
            return back()->withErrors(['phrase' => 'Já existe uma regra igual.'])->withInput();
        }

        $rule->update(['phrase' => $phrase, 'action' => $validated['action']]);
        $ruleService->reclassifyForUser($request->user()->id);

        return back()->with('success', 'Regra atualizada e movimentações recalculadas.');
    }
}
