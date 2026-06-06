<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClinicNotification;
use App\Models\FinancialTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FinancialTransactionController extends Controller
{
    public function index(Request $request): JsonResponse
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

        return response()->json($transactions);
    }

    public function store(Request $request): JsonResponse
    {
        $transaction = FinancialTransaction::create($this->validatedData($request));

        if ($transaction->status === 'paid') {
            ClinicNotification::create([
                'user_id' => $request->user()?->id,
                'title' => 'Pagamento confirmado',
                'body' => $transaction->description.' foi marcado como pago.',
                'type' => 'success',
                'data' => ['transaction_id' => $transaction->id],
            ]);
        }

        return response()->json($transaction->load('patient:id,nome,cpf,email', 'appointment:id,title,starts_at'), 201);
    }

    public function show(FinancialTransaction $financialTransaction): JsonResponse
    {
        return response()->json($financialTransaction->load('patient:id,nome,cpf,email', 'appointment:id,title,starts_at'));
    }

    public function update(Request $request, FinancialTransaction $financialTransaction): JsonResponse
    {
        $financialTransaction->update($this->validatedData($request, true));

        return response()->json($financialTransaction->refresh()->load('patient:id,nome,cpf,email', 'appointment:id,title,starts_at'));
    }

    public function destroy(FinancialTransaction $financialTransaction): JsonResponse
    {
        $financialTransaction->delete();

        return response()->json([
            'message' => 'Lançamento removido com sucesso.',
        ]);
    }

    private function validatedData(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'patient_id' => ['nullable', 'exists:patients,id'],
            'appointment_id' => ['nullable', 'exists:appointments,id'],
            'description' => [$required, 'string', 'max:255'],
            'type' => [$required, Rule::in(['income', 'expense'])],
            'category' => ['nullable', 'string', 'max:120'],
            'amount' => [$required, 'numeric', 'min:0', 'max:999999.99'],
            'due_date' => [$required, 'date'],
            'paid_at' => ['nullable', 'date'],
            'status' => [$required, Rule::in(['pending', 'paid', 'canceled'])],
            'payment_method' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
