<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    //
    protected $fillable=[
        'vendor_id',
        'product_name',
        'description',
        'price',
        'quantity',
        'img',
        'status'
    ];
    public function user(){
       return $this->belongsTo(User::class,'vendor_id');
    }
    public function orderitem(){
       return $this->hasMany(OrderItem::class,'product_id');
        
    }
}
