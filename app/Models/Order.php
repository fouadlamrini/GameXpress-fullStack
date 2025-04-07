<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Order extends Model
{
    use SoftDeletes,Notifiable;
    
    protected $fillable = [
        'user_id', 
        'total_price', 
        'status'
    ];
    
    protected $casts = [
        'total_price' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
    

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
