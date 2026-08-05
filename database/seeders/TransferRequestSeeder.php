<?php

namespace Database\Seeders;

use App\Models\ProviderTransfer;
use App\Models\TransferRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class TransferRequestSeeder extends Seeder
{
    public function run(): void
    {
        $providers = User::where('type', 'provider')->get();

        if ($providers->isEmpty()) {
            $this->command->warn('TransferRequestSeeder: no providers found — skipping.');

            return;
        }

        $methods = ['bank_transfer', 'digital_wallet', 'bank_transfer', 'cash', 'bank_transfer'];

        foreach ($providers as $i => $provider) {
            // 1 approved, 1 pending per provider
            TransferRequest::firstOrCreate(
                ['user_id' => $provider->id, 'requested_amount' => 1500.00, 'status' => 'approved'],
                [
                    'preferred_method' => 'bank_transfer',
                    'notes' => 'IBAN: SA12 3456 7890 1234 5678 '.str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                    'admin_response' => 'Approved and transfer initiated.',
                    'transfer_id' => ProviderTransfer::where('user_id', $provider->id)->value('id'),
                ]
            );

            TransferRequest::firstOrCreate(
                ['user_id' => $provider->id, 'requested_amount' => 900.00, 'status' => 'pending'],
                [
                    'preferred_method' => $methods[$i % count($methods)],
                    'notes' => 'Please process before end of month.',
                    'admin_response' => null,
                    'transfer_id' => null,
                ]
            );
        }
    }
}
