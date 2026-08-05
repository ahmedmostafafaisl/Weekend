<?php

namespace Database\Seeders;

use App\Models\ProviderTransfer;
use App\Models\TransferPolicy;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProviderTransferSeeder extends Seeder
{
    public function run(): void
    {
        $policy = TransferPolicy::where('is_active', true)->first();
        $providers = User::where('type', 'provider')->get();

        if ($providers->isEmpty() || ! $policy) {
            $this->command->warn('ProviderTransferSeeder: no providers or no active policy found — skipping.');

            return;
        }

        $taxRate = $policy->tax_rate / 100;
        $feeRate = $policy->platform_fee_rate / 100;

        $statuses = ['completed', 'completed', 'completed', 'pending', 'processing'];

        foreach ($providers as $i => $provider) {
            // 2–3 completed transfers per provider + 1 pending
            $amounts = [1800.00, 2400.00, 3200.00];

            foreach ($amounts as $j => $amount) {
                $taxAmt = round($amount * $taxRate, 2);
                $feeAmt = round($amount * $feeRate, 2);
                $net = $amount - $taxAmt - $feeAmt;

                $status = $statuses[($i + $j) % count($statuses)];

                ProviderTransfer::firstOrCreate(
                    [
                        'user_id' => $provider->id,
                        'amount' => $amount,
                        'status' => $status,
                    ],
                    [
                        'transfer_policy_id' => $policy->id,
                        'tax_amount' => $taxAmt,
                        'platform_fee' => $feeAmt,
                        'net_amount' => $net,
                        'method' => 'bank_transfer',
                        'reference' => 'TXN-'.strtoupper(substr(md5($provider->id.$amount), 0, 8)),
                        'notes' => 'تحويل للحجوزات المُعالجة في '.now()->subDays(($i + $j) * 7)->format('M Y'),
                        'scheduled_date' => now()->subDays(($i + $j) * 7 + 3),
                        'transferred_at' => $status === 'completed' ? now()->subDays(($i + $j) * 7) : null,
                        'created_by' => null,
                    ]
                );
            }
        }
    }
}
