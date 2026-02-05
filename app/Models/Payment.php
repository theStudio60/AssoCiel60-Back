<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'organization_id', 
        'subscription_id',
        'payment_method',
        'transaction_id',
        'amount',
        'currency',
        'status',
        'paid_at',        
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation avec l'organisation
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Relation avec l'abonnement
     */
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}