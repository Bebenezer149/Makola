<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    //
    protected $fillable=[
        'vendor_id',
        'customer_name',
        'phone_number',
        'delivery_to',
        'additional_notes',
        'status',
        'total_amount',
        'payment_method'
    ];

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function Items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
    
}
