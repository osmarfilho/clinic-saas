<?php

namespace App\Services;

use App\Models\FinancialTransaction;

class FinancialSummaryService
{
    public function summary(): array
    {
        $rows = FinancialTransaction::query()
            ->whereIn('status', ['paid', 'pending'])
            ->get(['type', 'status', 'amount']);

        $paidIncome = $this->sum($rows, 'income', 'paid');
        $paidExpenses = $this->sum($rows, 'expense', 'paid');
        $pendingIncome = $this->sum($rows, 'income', 'pending');
        $pendingExpenses = $this->sum($rows, 'expense', 'pending');

        return [
            'paid_income' => $paidIncome,
            'paid_expenses' => $paidExpenses,
            'pending_income' => $pendingIncome,
            'pending_expenses' => $pendingExpenses,
            'current_balance' => $paidIncome - $paidExpenses,
            'forecast_balance' => ($paidIncome + $pendingIncome) - ($paidExpenses + $pendingExpenses),
        ];
    }

    private function sum($rows, string $type, string $status): float
    {
        return (float) $rows
            ->where('type', $type)
            ->where('status', $status)
            ->sum(fn (FinancialTransaction $transaction) => (float) $transaction->amount);
    }
}
