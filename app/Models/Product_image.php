<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product_image extends Model
{
    //

    protected $fillable = [
        'product_id',
        'secondary_url'
    ];

    public function product() {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
