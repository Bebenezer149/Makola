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
        'service_fee',
        'transfer_amount',
        'receiver',
        'account_number',
        'channel',
        'payout_status',
        'reference',
        'idempotency_key'

    ];

    protected function casts(): array
    {
        return [
            'payout_amount' => 'decimal:2',
            'service_fee' => 'decimal:2',
            'transfer_amount' => 'decimal:2',
        ];
    }

    public function user(){
        return $this->belongsTo(User::class,'vendor_id');
    }
    public function ledger(){
        return $this->belongsTo(Ledger::class, 'ledger_id');
    }
}
