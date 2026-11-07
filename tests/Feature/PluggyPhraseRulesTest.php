<?php

namespace Tests\Feature;

use App\Models\PluggyAccount;
use App\Models\PluggyItem;
use App\Models\PluggyTransaction;
use App\Models\PluggyTransactionRule;
use App\Models\User;
use App\Services\Pluggy\PluggyTransactionImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PluggyPhraseRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_scoped_rule_restore_and_cutoff_survive_reimport(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $item = PluggyItem::factory()->create(['user_id' => $user->id, 'is_active' => true]);
        $second = PluggyItem::factory()->create(['user_id' => $user->id, 'is_active' => true]);
        $accounts = [];
        foreach ([$item, $second] as $connection) {
            $accounts[] = PluggyAccount::create(['user_id' => $user->id, 'pluggy_connection_id' => $connection->id, 'pluggy_account_id' => (string) Str::uuid(), 'name' => 'Conta', 'type' => 'BANK']);
        }
        $payloads = [];
        foreach (['2027-01-10', '2027-02-10', '2027-03-10'] as $date) {
            $payloads[] = ['id' => (string) Str::uuid(), 'description' => 'Assinatura mensal', 'date' => $date, 'amount' => -50, 'type' => 'DEBIT', 'status' => 'POSTED', 'currencyCode' => 'BRL'];
        }
        app(PluggyTransactionImporter::class)->import($item, $accounts[0], $payloads);
        $otherPayload = [...$payloads[0], 'id' => (string) Str::uuid()];
        app(PluggyTransactionImporter::class)->import($second, $accounts[1], [$otherPayload]);
        $this->actingAs($user)->post(route('pluggy.transaction-rules.store'), ['phrase' => 'assinatura', 'action' => 'exclude_from_totals', 'pluggy_connection_id' => $item->id])->assertSessionHasNoErrors();
        $rule = PluggyTransactionRule::where('user_id', $user->id)->sole();
        $january = PluggyTransaction::where('pluggy_transaction_id', $payloads[0]['id'])->sole();
        $february = PluggyTransaction::where('pluggy_transaction_id', $payloads[1]['id'])->sole();
        $march = PluggyTransaction::where('pluggy_transaction_id', $payloads[2]['id'])->sole();
        $this->assertFalse($january->fresh()->affects_totals);
        $this->assertTrue(PluggyTransaction::where('pluggy_transaction_id', $otherPayload['id'])->sole()->affects_totals);
        $this->actingAs($other)->post(route('pluggy.transactions.restore-phrase', $february))->assertNotFound();
        $this->actingAs($user)->post(route('pluggy.transactions.restore-phrase', $february))->assertSessionHasNoErrors();
        $this->assertTrue($february->fresh()->affects_totals);
        $this->delete(route('pluggy.transaction-rules.destroy', $rule), ['ends_before' => '2027-03-01'])->assertSessionHasNoErrors();
        $this->assertFalse($january->fresh()->affects_totals);
        $this->assertTrue($march->fresh()->affects_totals);
        app(PluggyTransactionImporter::class)->import($item, $accounts[0], $payloads);
        $this->assertFalse($january->fresh()->affects_totals);
        $this->assertTrue($february->fresh()->affects_totals);
        $this->assertTrue($march->fresh()->affects_totals);
    }
}
