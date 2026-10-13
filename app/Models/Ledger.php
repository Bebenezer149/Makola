<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ledger extends Model
{
    //
    protected $fillable = [
        'vendor_id',
        'available_balance',
        'payout_amount',
    ];

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }
    public function payouts()
    {
        return $this->hasMany(Payout::class, 'ledger_id');
    }
}
