<?php

namespace App\Http\Controllers\Pluggy;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\PluggyTransaction;
use App\Services\MonthlyBalanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InstallmentReviewController extends Controller
{
    public function addManual(Request $request, int $transaction)
    {
        $item = PluggyTransaction::where('user_id', $request->user()->id)->whereNull('removed_at')->whereNull('deleted_by_user_at')->findOrFail($transaction);
        abort_unless($item->is_installment && $item->total_installments && $item->purchase_date, 422);
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99', 'regex:/^\d+(\.\d{1,2})?$/'],
            'number' => ['required', 'integer', 'min:1', 'max:'.$item->total_installments],
            'category_id' => ['required', Rule::exists('categories', 'id')->where('user_id', $request->user()->id)->where('type', 'expense')],
        ]);

        DB::transaction(function () use ($request, $item, $data): void {
            $item = PluggyTransaction::where('user_id', $request->user()->id)->whereNull('removed_at')->whereNull('deleted_by_user_at')->lockForUpdate()->findOrFail($item->id);
            if (Expense::where('user_id', $request->user()->id)->where('pluggy_installment_source_id', $item->id)->where('current_installment', $data['number'])->exists()) {
                throw ValidationException::withMessages(['number' => 'Já existe uma parcela manual com este número vinculada a esta transação.']);
            }
            $date = Carbon::parse($data['date']);
            Expense::create([
                'user_id' => $request->user()->id, 'category_id' => $data['category_id'], 'description' => $item->description,
                'date' => $date, 'month' => $date->month, 'year' => $date->year, 'type' => 'expense', 'amount' => $data['amount'],
                'payment_mode' => 'cartao', 'bank_status' => null, 'is_installment' => true,
                'current_installment' => $data['number'], 'total_installments' => $item->total_installments,
                'pluggy_installment_source_id' => $item->id,
            ]);
            app(MonthlyBalanceService::class)->recalculateClosedFrom($request->user()->id, $date->year, $date->month);
        });

        return back()->with('success', 'Parcela manual registrada no mês informado.');
    }
}
