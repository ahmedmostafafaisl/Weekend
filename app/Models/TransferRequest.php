<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferRequest extends Model
{
    protected $fillable = ['user_id', 'requested_amount', 'preferred_method', 'notes',
        'status', 'admin_response', 'transfer_id'];

    public function provider()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function transfer()
    {
        return $this->belongsTo(ProviderTransfer::class, 'transfer_id');
    }
}
