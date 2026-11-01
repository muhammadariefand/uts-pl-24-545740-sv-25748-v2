<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $transactions = $request->user()
            ->transactions()
            ->latest('transaction_date')
            ->latest()
            ->get();

        return response()->json([
            'data' => $transactions,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate($this->rules());
        } catch (ValidationException $exception) {
            Log::warning('transaction.validation_failed', [
                'actor' => 'user',
                'user_id' => $request->user()?->id,
                'action' => 'create',
                'reason' => 'request validation failed',
                'errors' => $exception->errors(),
            ]);

            throw $exception;
        }

        $transaction = $request->user()
            ->transactions()
            ->create($validated);

        Log::info('transaction.created', [
            'actor' => 'user',
            'user_id' => $request->user()->id,
            'transaction_id' => $transaction->id,
            'type' => $transaction->type,
            'amount' => $transaction->amount,
            'transaction_date' => $transaction->transaction_date,
        ]);

        return response()->json([
            'message' => 'Transaksi berhasil dibuat.',
            'data' => $transaction,
        ], 201);
    }

    public function show(Request $request, Transaction $transaction): JsonResponse
    {
        $this->ensureOwner($request, $transaction);

        return response()->json([
            'data' => $transaction,
        ]);
    }

    public function update(
        Request $request,
        Transaction $transaction
    ): JsonResponse {
        $this->ensureOwner($request, $transaction);

        try {
            $validated = $request->validate($this->rules());
        } catch (ValidationException $exception) {
            Log::warning('transaction.validation_failed', [
                'actor' => 'user',
                'user_id' => $request->user()?->id,
                'transaction_id' => $transaction->id,
                'action' => 'update',
                'reason' => 'request validation failed',
                'errors' => $exception->errors(),
            ]);

            throw $exception;
        }

        $transaction->update($validated);
        $transaction = $transaction->fresh();

        Log::info('transaction.updated', [
            'actor' => 'user',
            'user_id' => $request->user()->id,
            'transaction_id' => $transaction->id,
            'type' => $transaction->type,
            'amount' => $transaction->amount,
        ]);

        return response()->json([
            'message' => 'Transaksi berhasil diubah.',
            'data' => $transaction,
        ]);
    }

    public function destroy(
        Request $request,
        Transaction $transaction
    ): JsonResponse {
        $this->ensureOwner($request, $transaction);

        $transaction->delete();

        Log::info('transaction.deleted', [
            'actor' => 'user',
            'user_id' => $request->user()->id,
            'transaction_id' => $transaction->id,
        ]);

        return response()->json([
            'message' => 'Transaksi berhasil dihapus.',
        ]);
    }

    private function ensureOwner(
        Request $request,
        Transaction $transaction
    ): void {
        abort_unless(
            $transaction->user_id === $request->user()->id,
            404
        );
    }

    private function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['income', 'expense'])],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'integer', 'min:1'],
            'transaction_date' => ['required', 'date'],
        ];
    }
}