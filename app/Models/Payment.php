<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'subscription_id', 'amount', 'currency', 'payment_method', 'transaction_id', 'status'
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}