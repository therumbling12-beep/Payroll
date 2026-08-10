<?php

declare(strict_types=1);

namespace App\Services;

class FinancialService
{
    /**
     * Check if a proposed salary / bonus amount fits within Financial budget limits.
     */
    public function checkBudgetAvailability(float $amount, string $departmentName = 'General'): array
    {
        // Mock financial budget verification logic (Team 5 Integration)
        // Rejects amounts over 150,000 or randomly fails 10% of high requests
        $maxLimit = (float) \App\Models\CompanySetting::getValue('financial_budget_ceiling', 150000.00);
        $isAvailable = $amount <= $maxLimit;

        return [
            'approved' => $isAvailable,
            'amount_requested' => $amount,
            'department' => $departmentName,
            'reason' => $isAvailable 
                ? 'Budget approved by Team 5 Financial Management' 
                : 'Budget check rejected: Proposed amount exceeds allocated financial ceiling (₱' . number_format($maxLimit, 2) . ')',
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
