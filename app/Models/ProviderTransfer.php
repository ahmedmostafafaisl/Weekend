<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProviderTransfer extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'transfer_policy_id', 'amount', 'tax_amount', 'platform_fee',
        'net_amount', 'method', 'status', 'reference', 'notes', 'scheduled_date', 'transferred_at', 'created_by'];

    protected $casts = ['scheduled_date' => 'date', 'transferred_at' => 'datetime'];

    public function provider()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function policy()
    {
        return $this->belongsTo(TransferPolicy::class, 'transfer_policy_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transferRequest()
    {
        return $this->hasOne(TransferRequest::class, 'transfer_id');
    }
}
