<?php

namespace App\Http\Controllers;

use App\Models\Ledger;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LedgerController extends Controller
{
    public function activateLedger(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        [$ledger, $created, $activated] = DB::transaction(function () use ($user) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            $ledger = Ledger::firstOrCreate(
                ['vendor_id' => $user->id],
                [
                    'available_balance' => 0,
                    'payout_amount' => 0,
                    'status' => 'live',
                ]
            );

            $created = $ledger->wasRecentlyCreated;
            $activated = $created || $ledger->status === 'Inactive';

            if ($activated && ! $created) {
                $ledger->status = 'live';
                $ledger->save();
            }

            return [$ledger, $created, $activated];
        });

        return response()->json([
            'message' => $activated ? 'Wallet is now live.' : 'Wallet is already live.',
            'data' => $ledger,
        ], $created ? 201 : 200);
    }
    public function fetchLedger(Request $request){
        $user=$request->user();
        $ledger=Ledger::where('vendor_id',$user->id)->first();
        return response($ledger);
    }
}
