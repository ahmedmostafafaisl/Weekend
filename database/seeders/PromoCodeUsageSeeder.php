<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Models\User;
use Illuminate\Database\Seeder;

class PromoCodeUsageSeeder extends Seeder
{
    public function run(): void
    {
        $promoCodes = PromoCode::all();
        if ($promoCodes->isEmpty()) {
            return; // PromoCodeSeeder must run first
        }

        // Use real paid payments so original_amount reflects something real.
        $paidPayments = Payment::where('status', 'paid')->with('user')->inRandomOrder()->limit(40)->get();

        foreach ($paidPayments as $payment) {
            // Not every paid booking used a promo code — roughly half did.
            if (rand(0, 1) === 0) {
                continue;
            }

            $promo = $promoCodes->random();
            $user = $payment->user ?? User::inRandomOrder()->first();
            if (! $user) {
                continue;
            }

            $originalAmount = (float) $payment->amount;

            if ($promo->discount_type === 'percentage') {
                $discount = round($originalAmount * ((float) $promo->discount_value / 100), 2);
                if ($promo->max_discount) {
                    $discount = min($discount, (float) $promo->max_discount);
                }
            } else {
                // fixed
                $discount = min((float) $promo->discount_value, $originalAmount);
            }

            $finalAmount = max(0, round($originalAmount - $discount, 2));

            PromoCodeUsage::updateOrCreate(
                [
                    'promo_code_id' => $promo->id,
                    'user_id' => $user->id,
                    'payment_id' => $payment->id,
                ],
                [
                    'discount_amount' => $discount,
                    'original_amount' => $originalAmount,
                    'final_amount' => $finalAmount,
                ]
            );
        }
    }
}
