<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccountPayableRequest;
use App\Http\Requests\SettlePayableRequest;
use App\Models\AccountPayable;
use App\Models\Category;
use App\Models\ManualBank;
use App\Models\PluggyTransaction;
use App\Services\PayableSettlementService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountPayableController extends Controller
{
    public function index(Request $request): View
    {
        return view('accounts-payable.index', ['payables' => AccountPayable::query()->forUser($request->user()->id)
            ->with(['category', 'settlement.manualExpense', 'settlement.pluggyTransaction'])->orderByRaw("case when status = 'pending' then 0 when status = 'paid' then 1 else 2 end")
            ->orderBy('due_date')->get()]);
    }

    public function create(Request $request): View
    {
        return view('accounts-payable.create', ['categories' => $this->categories($request->user()->id)]);
    }

    public function store(AccountPayableRequest $request): RedirectResponse
    {
        $payable = AccountPayable::create([...$request->validated(), 'user_id' => $request->user()->id, 'status' => AccountPayable::STATUS_PENDING]);

        return redirect()->route('accounts-payable.show', $payable)->with('success', 'Conta a pagar cadastrada. Ela só afetará o saldo quando for liquidada.');
    }

    public function show(Request $request, int $accountPayable): View
    {
        $payable = $this->payable($request, $accountPayable);
        $eligibleTransactions = collect();
        if ($payable->isPending()) {
            $eligibleTransactions = PluggyTransaction::query()->where('user_id', $request->user()->id)
                ->whereNull('removed_at')->whereNull('deleted_by_user_at')->contributesToTotals()
                ->whereIn('classification', [PluggyTransaction::CLASSIFICATION_EXPENSE, PluggyTransaction::CLASSIFICATION_FEE])
                ->whereDoesntHave('payableSettlement')->latest('transaction_date')->limit(100)->get();
        }

        return view('accounts-payable.show', compact('payable', 'eligibleTransactions') + ['banks' => ManualBank::query()->where('user_id', $request->user()->id)->orderBy('name')->get()]);
    }

    public function edit(Request $request, int $accountPayable): View
    {
        $payable = $this->payable($request, $accountPayable);
        abort_unless($payable->isPending(), 422, 'Apenas contas pendentes podem ser editadas.');

        return view('accounts-payable.edit', ['payable' => $payable, 'categories' => $this->categories($request->user()->id)]);
    }

    public function update(AccountPayableRequest $request, int $accountPayable): RedirectResponse
    {
        $payable = $this->payable($request, $accountPayable);
        if (! $payable->isPending()) {
            return back()->withErrors(['payable' => 'Apenas contas pendentes podem ser editadas.']);
        }
        $payable->update($request->validated());

        return redirect()->route('accounts-payable.show', $payable)->with('success', 'Conta a pagar atualizada.');
    }

    public function settleManually(SettlePayableRequest $request, int $accountPayable, PayableSettlementService $settlements): RedirectResponse
    {
        $payable = $this->payable($request, $accountPayable);
        $data = $request->validated();
        $settlements->settleManually($payable, $request->user()->id, $data);
        $date = Carbon::parse($data['payment_date']);

        return redirect()->route('month.show', [$date->year, $date->month])->with('success', 'Pagamento manual registrado uma única vez como despesa.');
    }

    public function settleWithPluggy(Request $request, int $accountPayable, PayableSettlementService $settlements): RedirectResponse
    {
        $data = $request->validate(['pluggy_transaction_id' => ['required', 'integer']]);
        $payable = $this->payable($request, $accountPayable);
        $settlements->settleWithPluggy($payable, $request->user()->id, $data['pluggy_transaction_id']);

        return redirect()->route('accounts-payable.show', $payable)->with('success', 'Conta vinculada à transação importada, sem criar nova despesa.');
    }

    public function reopen(Request $request, int $accountPayable, PayableSettlementService $settlements): RedirectResponse
    {
        $payable = $this->payable($request, $accountPayable);
        $settlements->reopen($payable, $request->user()->id);

        return redirect()->route('accounts-payable.show', $payable)->with('success', 'Liquidação desfeita e a conta voltou a ficar pendente.');
    }

    public function cancel(Request $request, int $accountPayable): RedirectResponse
    {
        $payable = $this->payable($request, $accountPayable);
        if (! $payable->isPending()) {
            return back()->withErrors(['payable' => 'Apenas contas pendentes podem ser canceladas.']);
        }
        $payable->update(['status' => AccountPayable::STATUS_CANCELLED]);

        return redirect()->route('accounts-payable.show', $payable)->with('success', 'Conta a pagar cancelada.');
    }

    public function destroy(Request $request, int $accountPayable): RedirectResponse
    {
        $payable = $this->payable($request, $accountPayable);
        if ($payable->status === AccountPayable::STATUS_PAID) {
            return back()->withErrors(['payable' => 'Desfaça a liquidação antes de excluir uma conta paga.']);
        }
        $payable->delete();

        return redirect()->route('accounts-payable.index')->with('success', 'Conta a pagar excluída.');
    }

    private function payable(Request $request, int $id): AccountPayable
    {
        return AccountPayable::query()->forUser($request->user()->id)->with(['category', 'settlement.manualExpense', 'settlement.pluggyTransaction'])->findOrFail($id);
    }

    private function categories(int $userId)
    {
        return Category::query()->where('user_id', $userId)->where('type', 'expense')->orderBy('name')->get();
    }
}
