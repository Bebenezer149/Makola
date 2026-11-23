<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

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
        'payment_method',
        'order_confirmation_token'

        

    ];

    protected function casts(): array
    {
        return ['total_amount' => 'decimal:2'];
    }

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function Items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function payments(){
        return $this->hasMany(Payment::class, "order_id");
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->whereHas('payments', fn (Builder $payments) => $payments->where('payment_status', 'PAID'));
    }

    // public function payouts(){
    //     return $this->hasMany(Payout::class,'order_id');
    // }
}
