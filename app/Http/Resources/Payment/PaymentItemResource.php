<?php

namespace App\Http\Resources\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'item_number' => $this->item_number,
            'price' => (float) $this->price,
            'quantity' => $this->quantity,
            'total_amount' => (float) $this->total_amount,
        ];
    }
}
