<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    //
    protected $fillable=[
        'vendor_id',
        'ledger_id',
        'payout_amount',
        'receiver',
        'account_number',
        'channel',
        'payout_status',
        'reference',
        'idempotency_key'

    ];

    public function user(){
        return $this->belongsTo(User::class,'vendor_id');
    }
    public function ledger(){
        return $this->belongsTo(Ledger::class, 'ledger_id');
    }
}
