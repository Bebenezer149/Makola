<?php

namespace App\Http\Controllers;

use App\Models\Ledger;
use App\Models\Payout;
use App\Services\PaymentService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PayoutController extends Controller
{
    //
    public function verifyName(Request $request)
    {
        $validated = $request->validate([
            'phone_number' => "string|required|max:10",
            'channel' => "string|required"
        ]);

        $verify = app(PaymentService::class);

        $response = $verify->verifyName($validated['phone_number'], $validated['channel']);
        return response()->json([
            'message' => "Success",
            "data" => $response
        ]);
    }
    public function initiateTransfer(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'receiver' => ['required', 'string', 'max:32'],
            'channel' => ['required', 'integer', 'in:1,2,3,4'],
            'idempotency_key' => ['required', 'string', 'max:100'],
        ]);

        $reference = 'BS-' . strtoupper(Str::random(12));
        $result = DB::transaction(function () use ($validated, $user, $reference) {
            $ledger = Ledger::where('vendor_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $ledger) {
                throw ValidationException::withMessages([
                    'amount' => 'No ledger is available for this account.',
                ]);
            }

            $existingPayout = Payout::where('vendor_id', $user->id)
                ->where('idempotency_key', $validated['idempotency_key'])
                ->first();

            if ($existingPayout) {
                return ['payout' => $existingPayout, 'created' => false];
            }

            $reserved = Ledger::whereKey($ledger->id)
                ->where('available_balance', '>=', $validated['amount'])
                ->decrement('available_balance', $validated['amount']);

            if (! $reserved) {
                throw ValidationException::withMessages([
                    'amount' => 'Insufficient available balance.',
                ]);
            }

            $payout = Payout::create([
                'vendor_id' => $user->id,
                'ledger_id' => $ledger->id,
                'payout_amount' => $validated['amount'],
                'receiver' => $validated['receiver'],
                'account_number' => $validated['receiver'],
                'channel' => $validated['channel'],
                'payout_status' => 'PENDING',
                'reference' => $reference,
                'idempotency_key' => $validated['idempotency_key'],
            ]);

            return ['payout' => $payout, 'created' => true];
        });

        $payout = $result['payout'];
        if (! $result['created']) {
            return response()->json([
                'message' => 'This request has already been submitted.',
                'payout_id' => $payout->id,
                'reference' => $payout->reference,
                'payout_status' => $payout->payout_status,
            ], $payout->payout_status === 'PENDING' ? 202 : 200);
        }

        try {
            $providerResponse = app(PaymentService::class)->initiateTransfer(
                $validated['amount'],
                $validated['channel'],
                $validated['receiver'],
                $payout->reference
            );
        } catch (ConnectionException $exception) {
            Log::warning('Moolre payout request outcome is unknown.', [
                'payout_id' => $payout->id,
                'reference' => $payout->reference,
                'exception' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Payout status is being verified. Check again using the reference.',
                'payout_id' => $payout->id,
                'reference' => $payout->reference,
                'payout_status' => 'PENDING',
            ], 202);
        }

        $providerData = $providerResponse->json();
        $status = $providerResponse->successful()
            ? data_get($providerData, 'data.txstatus')
            : null;

        $payoutStatus = $this->applyProviderStatus($payout->id, $status);

        if (! $providerResponse->successful() || ! in_array((string) $status, ['0', '1', '2'], true)) {
            return response()->json([
                'message' => 'Payout status is pending confirmation. Check again using the reference.',
                'payout_id' => $payout->id,
                'reference' => $payout->reference,
                'payout_status' => $payoutStatus,
            ], 202);
        }

        return response()->json([
            'message' => match ($payoutStatus) {
                'RELEASED' => 'Payout completed.',
                'REFUNDED' => 'Payout failed; reserved funds were returned.',
                default => 'Payout is being processed.',
            },
            'payout_id' => $payout->id,
            'reference' => $payout->reference,
            'payout_status' => $payoutStatus,
        ], $payoutStatus === 'REFUNDED' ? 422 : ($payoutStatus === 'RELEASED' ? 200 : 202));
    }

    public function checkTransferStatus(Request $request, Payout $payout)
    {
        if ((int) $payout->vendor_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Payout not found.'], 404);
        }

        if ($payout->payout_status !== 'PENDING') {
            return response()->json([
                'reference' => $payout->reference,
                'payout_status' => $payout->payout_status,
            ]);
        }

        if (! $payout->reference) {
            return response()->json([
                'message' => 'This payout has no provider reference and requires manual reconciliation.',
                'payout_id' => $payout->id,
                'payout_status' => 'PENDING',
            ], 409);
        }

        try {
            $providerResponse = app(PaymentService::class)->checkTransferStatus($payout->reference);
        } catch (ConnectionException $exception) {
            Log::warning('Moolre payout status check failed.', [
                'payout_id' => $payout->id,
                'reference' => $payout->reference,
                'exception' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Payout status is still unknown. Check again later.',
                'payout_id' => $payout->id,
                'reference' => $payout->reference,
                'payout_status' => 'PENDING',
            ], 202);
        }

        $providerData = $providerResponse->json();
        $status = $providerResponse->successful()
            ? data_get($providerData, 'data.txstatus')
            : null;
        $payoutStatus = $this->applyProviderStatus($payout->id, $status);

        return response()->json([
            'message' => 'Payout status checked.',
            'payout_id' => $payout->id,
            'reference' => $payout->reference,
            'payout_status' => $payoutStatus,
        ], $payoutStatus === 'PENDING' ? 202 : 200);
    }

    private function applyProviderStatus(int $payoutId, mixed $providerStatus): string
    {
        return DB::transaction(function () use ($payoutId, $providerStatus) {
            $payout = Payout::whereKey($payoutId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payout->payout_status !== 'PENDING') {
                return $payout->payout_status;
            }

            $status = is_int($providerStatus) || is_string($providerStatus)
                ? (string) $providerStatus
                : null;
            if ($status === '1') {
                $payout->update(['payout_status' => 'RELEASED']);
                return 'RELEASED';
            }

            if (in_array($status, ['0', '2'], true)) {
                $ledger = Ledger::whereKey($payout->ledger_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $ledger->increment('available_balance', $payout->payout_amount);
                $payout->update(['payout_status' => 'REFUNDED']);
                return 'REFUNDED';
            }

            return 'PENDING';
        });
    }
}
