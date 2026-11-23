<?php

namespace App\Console\Commands;

use App\Models\PluggyTransaction;
use App\Services\Pluggy\PluggyTransactionClassifier;
use Illuminate\Console\Command;

class PreviewPluggyPaymentMethods extends Command
{
    protected $signature = 'pluggy:preview-payment-methods {user : Local user ID}';
    protected $description = 'Preview automatic payment method changes without modifying transactions';

    public function handle(PluggyTransactionClassifier $classifier): int
    {
        $user = (string) $this->argument('user');
        if (! ctype_digit($user) || (int) $user < 1) {
            $this->error('Informe um identificador de usuário válido.');
            return self::INVALID;
        }

        $counts = ['reviewed' => 0, 'unchanged' => 0, 'manual' => 0, 'missing_payload' => 0];
        $changes = [];
        PluggyTransaction::query()->where('user_id', (int) $user)->whereNull('removed_at')
            ->whereNull('deleted_by_user_at')->with('account')->orderBy('id')
            ->chunkById(250, function ($transactions) use ($classifier, &$counts, &$changes): void {
                foreach ($transactions as $transaction) {
                    if ($transaction->user_modified_payment_method) {
                        $counts['manual']++;
                        continue;
                    }
                    if (! is_array($transaction->raw_payload) || $transaction->raw_payload === [] || ! $transaction->account) {
                        $counts['missing_payload']++;
                        continue;
                    }
                    $counts['reviewed']++;
                    $next = $classifier->classify($transaction->raw_payload, $transaction->account->type)['payment_method'];
                    $previous = in_array($transaction->payment_method, ['pix', 'card', 'other'], true) ? $transaction->payment_method : 'other';
                    if ($previous === $next) {
                        $counts['unchanged']++;
                    } else {
                        $key = "$previous -> $next";
                        $changes[$key] = ($changes[$key] ?? 0) + 1;
                    }
                }
            });

        $this->line("Avaliadas: {$counts['reviewed']}; sem mudança: {$counts['unchanged']}; escolhas manuais preservadas: {$counts['manual']}; sem dados para avaliar: {$counts['missing_payload']}.");
        foreach ($changes as $transition => $count) {
            $this->line("$transition: $count");
        }
        $this->info('Prévia somente de leitura; nenhuma transação foi alterada.');
        return self::SUCCESS;
    }
}
