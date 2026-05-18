<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price_chf',
        'price_eur',
        'duration_months',
        'is_active',
    ];

    protected $casts = [
        'price_chf' => 'decimal:2',
        'price_eur' => 'decimal:2',
        'duration_months' => 'integer',
        'is_active' => 'boolean',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}