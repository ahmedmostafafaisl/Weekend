<?php

namespace App\Services\PromoCode;

use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use Illuminate\Support\Facades\DB;

class PromoCodeService
{
    /**
     * Validate a promo code and return a discount preview.
     * Pure read — no side effects.
     *
     * @return array{
     *   valid: bool,
     *   message: string|null,
     *   promo_code: PromoCode|null,
     *   original_amount: float,
     *   discount_amount: float,
     *   final_amount: float,
     * }
     */
    public function validate(string $code, float $amount, ?int $userId = null): array
    {
        $promo = PromoCode::where('code', strtoupper(trim($code)))->first();

        if (! $promo) {
            return $this->invalid('Promo code not found.');
        }

        $error = $promo->validate($amount, $userId);

        if ($error) {
            return $this->invalid($error);
        }

        $discount = $promo->calculateDiscount($amount);
        $finalAmount = max(0, round($amount - $discount, 2));

        return [
            'valid' => true,
            'message' => null,
            'promo_code' => $promo,
            'original_amount' => $amount,
            'discount_amount' => $discount,
            'final_amount' => $finalAmount,
        ];
    }

    /**
     * Record a promo code usage after a payment row exists.
     * Call this inside the same DB transaction as the payment creation.
     */
    public function recordUsage(
        PromoCode $promo,
        int $paymentId,
        float $originalAmount,
        float $discountAmount,
        float $finalAmount,
        ?int $userId = null
    ): PromoCodeUsage {
        return PromoCodeUsage::create([
            'promo_code_id' => $promo->id,
            'user_id' => $userId,
            'payment_id' => $paymentId,
            'discount_amount' => $discountAmount,
            'original_amount' => $originalAmount,
            'final_amount' => $finalAmount,
        ]);
    }

    private function invalid(string $message): array
    {
        return [
            'valid' => false,
            'message' => $message,
            'promo_code' => null,
            'original_amount' => 0,
            'discount_amount' => 0,
            'final_amount' => 0,
        ];
    }
}
