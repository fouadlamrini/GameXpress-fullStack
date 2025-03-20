<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'payment_type',
        'status',
        'transaction_id',
        'amount',
        'payment_details'
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'payment_details' => 'json',
    ];
    
    /**
     * Get the order associated with this payment
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    
    /**
     * Check if the payment was successful
     */
    public function isSuccessful()
    {
        return $this->status === 'completed';
    }
}
