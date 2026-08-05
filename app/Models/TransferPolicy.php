<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferPolicy extends Model
{
    protected $fillable = ['title', 'description', 'transfer_days', 'transfer_methods',
        'tax_rate', 'platform_fee_rate', 'is_active'];

    protected $casts = [
        'transfer_methods' => 'array',
        'tax_rate' => 'decimal:2',
        'platform_fee_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function transfers()
    {
        return $this->hasMany(ProviderTransfer::class);
    }
}
