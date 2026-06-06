<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFinancialTransactionRequest;
use App\Http\Requests\UpdateFinancialTransactionRequest;
use App\Models\ClinicNotification;
use App\Models\FinancialTransaction;
use App\Services\FinancialSummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancialTransactionController extends Controller
{
    public function index(Request $request, FinancialSummaryService $summaryService): JsonResponse
    {
        $transactions = FinancialTransaction::query()
            ->with('patient:id,nome,cpf,email', 'appointment:id,title,starts_at')
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')->toString()))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($query) use ($search) {
                    $query
                        ->where('description', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhereHas('patient', fn ($query) => $query->where('nome', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('due_date')
            ->paginate($request->integer('per_page', 30));

        return response()->json([
            ...$transactions->toArray(),
            'summary' => $summaryService->summary(),
        ]);
    }

    public function store(StoreFinancialTransactionRequest $request): JsonResponse
    {
        $transaction = FinancialTransaction::create($request->validated());

        ClinicNotification::create([
            'user_id' => $request->user()?->id,
            'title' => 'Lançamento financeiro criado',
            'body' => 'Novo lançamento financeiro criado: '.$transaction->description.' - '.$this->money($transaction->amount).'.',
            'type' => 'info',
            'data' => ['transaction_id' => $transaction->id],
        ]);

        if ($transaction->status === 'paid') {
            ClinicNotification::create([
                'user_id' => $request->user()?->id,
                'title' => 'Pagamento confirmado',
                'body' => 'Pagamento confirmado: '.$transaction->description.' - '.$this->money($transaction->amount).'.',
                'type' => 'success',
                'data' => ['transaction_id' => $transaction->id],
            ]);
        }

        $this->notifyIfOverdue($request, $transaction);

        return response()->json($transaction->load('patient:id,nome,cpf,email', 'appointment:id,title,starts_at'), 201);
    }

    public function show(FinancialTransaction $financialTransaction): JsonResponse
    {
        return response()->json($financialTransaction->load('patient:id,nome,cpf,email', 'appointment:id,title,starts_at'));
    }

    public function update(UpdateFinancialTransactionRequest $request, FinancialTransaction $financialTransaction): JsonResponse
    {
        $previousStatus = $financialTransaction->status;
        $financialTransaction->fill($request->validated());
        $changedFields = array_keys($financialTransaction->getDirty());
        $financialTransaction->save();

        ClinicNotification::create([
            'user_id' => $request->user()?->id,
            'title' => 'Lançamento financeiro atualizado',
            'body' => $this->transactionUpdatedMessage($financialTransaction, $changedFields),
            'type' => 'info',
            'data' => ['transaction_id' => $financialTransaction->id],
        ]);

        if ($financialTransaction->status === 'paid' && $previousStatus !== 'paid') {
            ClinicNotification::create([
                'user_id' => $request->user()?->id,
                'title' => 'Pagamento confirmado',
                'body' => 'Pagamento confirmado: '.$financialTransaction->description.' - '.$this->money($financialTransaction->amount).'.',
                'type' => 'success',
                'data' => ['transaction_id' => $financialTransaction->id],
            ]);
        }

        $this->notifyIfOverdue($request, $financialTransaction);

        return response()->json($financialTransaction->refresh()->load('patient:id,nome,cpf,email', 'appointment:id,title,starts_at'));
    }

    public function destroy(Request $request, FinancialTransaction $financialTransaction): JsonResponse
    {
        ClinicNotification::create([
            'user_id' => $request->user()?->id,
            'title' => 'Lançamento financeiro removido',
            'body' => 'Lançamento financeiro removido: '.$financialTransaction->description.' - '.$this->money($financialTransaction->amount).'.',
            'type' => 'warning',
            'data' => ['transaction_id' => $financialTransaction->id],
        ]);

        $financialTransaction->delete();

        return response()->json([
            'message' => 'Lançamento removido com sucesso.',
        ]);
    }

    private function money(string|float|int $amount): string
    {
        return 'R$ '.number_format((float) $amount, 2, ',', '.');
    }

    private function transactionUpdatedMessage(FinancialTransaction $transaction, array $changedFields): string
    {
        if ($changedFields === []) {
            return 'Lançamento '.$transaction->description.' foi atualizado.';
        }

        $labels = [
            'patient_id' => 'paciente',
            'appointment_id' => 'agendamento',
            'description' => 'descrição',
            'type' => 'tipo',
            'category' => 'categoria',
            'amount' => 'valor',
            'due_date' => 'vencimento',
            'paid_at' => 'data de pagamento',
            'status' => 'status',
            'payment_method' => 'forma de pagamento',
            'notes' => 'observações',
        ];

        $changedLabels = collect($changedFields)
            ->map(fn (string $field) => $labels[$field] ?? $field)
            ->join(', ', ' e ');

        return 'Lançamento '.$transaction->description.' foi atualizado. Campos alterados: '.$changedLabels.'.';
    }

    private function notifyIfOverdue(Request $request, FinancialTransaction $transaction): void
    {
        if ($transaction->status !== 'pending' || ! $transaction->due_date?->lt(today())) {
            return;
        }

        ClinicNotification::create([
            'user_id' => $request->user()?->id,
            'title' => 'Lançamento vencido',
            'body' => 'Lançamento vencido: '.$transaction->description.' - '.$this->money($transaction->amount).'.',
            'type' => 'danger',
            'data' => ['transaction_id' => $transaction->id],
        ]);
    }
}
