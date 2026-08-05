<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\Subscription;
use Illuminate\Database\Seeder;

class SubscriptionPaymentSeeder extends Seeder
{
    public function run(): void
    {
        $gateways = ['geidea', 'tabby', 'tamara', 'maysar'];

        Subscription::with('user')->get()->each(function (Subscription $sub, $i) use ($gateways) {
            if (! $sub->user) {
                return;
            }

            // Payment status should be consistent with the subscription's
            // own lifecycle state, not random — an active subscription
            // implies a paid payment; a pending one implies a pending or
            // recently-failed payment.
            $status = match ($sub->status) {
                'active' => 'paid',
                'pending' => rand(0, 1) ? 'pending' : 'failed',
                'inactive' => rand(0, 4) === 0 ? 'refunded' : 'paid', // was active, later refunded/cancelled
                default => 'paid',
            };

            $payment = Payment::updateOrCreate(
                ['subscription_id' => $sub->id],
                [
                    'user_id' => $sub->user_id,
                    'payment_type' => $gateways[$i % count($gateways)],
                    'amount' => $sub->amount,
                    'payment_id' => $status !== 'pending'
                        ? 'P-'.strtoupper(substr(md5($sub->id.$sub->user_id.'sub'), 0, 10))
                        : null,
                    'status' => $status,
                    'phone' => $sub->user->phone,
                ]
            );

            PaymentItem::updateOrCreate(
                ['payment_id' => $payment->id],
                [
                    'name' => 'اشتراك '.($sub->type === 'ad' ? 'إعلانات' : 'عقارات').' — '.($sub->count ? $sub->count.' إعلان' : ($sub->percentage ? $sub->percentage.'%' : 'باقة')),
                    'item_number' => 'SUB-'.$sub->id,
                    'price' => $sub->amount,
                    'quantity' => 1,
                    'total_amount' => $sub->amount,
                ]
            );
        });
    }
}
