<?php

declare(strict_types=1);

namespace App\Services\Claims;

use App\Models\Claim;
use Carbon\Carbon;

class DuplicateClaimDetectionService
{
    /**
     * Check if a proposed claim collides with existing claims
     *
     * @return array{
     *     is_duplicate: bool,
     *     risk_level: string,
     *     risk_score: int,
     *     reason: string,
     *     matched_claims: array<int, array<string, mixed>>
     * }
     */
    public function checkDuplicate(
        int $employeeId,
        string $receiptNumber,
        float $amount,
        string $expenseDate,
        ?int $excludeClaimId = null
    ): array {
        $cleanReceipt = strtoupper(trim($receiptNumber));
        $date = Carbon::parse($expenseDate)->toDateString();

        $matchedClaims = [];
        $riskScore = 0;
        $reasons = [];

        // 1. Check Exact Receipt Collision across any non-rejected claims
        if (! empty($cleanReceipt)) {
            $receiptMatches = Claim::where('receipt_number', $cleanReceipt)
                ->where('status', '!=', 'rejected')
                ->when($excludeClaimId, fn ($q) => $q->where('id', '!=', $excludeClaimId))
                ->get();

            if ($receiptMatches->isNotEmpty()) {
                $riskScore = max($riskScore, 95);
                $reasons[] = "Exact Official Receipt (OR) collision with {$receiptMatches->count()} existing claim(s).";
                foreach ($receiptMatches as $m) {
                    $matchedClaims[$m->id] = [
                        'id' => $m->id,
                        'receipt_number' => $m->receipt_number,
                        'amount' => (float) $m->amount,
                        'expense_date' => $m->expense_date?->toDateString(),
                        'match_type' => 'EXACT_RECEIPT',
                    ];
                }
            }
        }

        // 2. Check Same Employee + Exact Amount + Exact Expense Date
        $amountDateMatches = Claim::where('employee_id', $employeeId)
            ->whereBetween('amount', [$amount - 0.01, $amount + 0.01])
            ->whereDate('expense_date', $date)
            ->where('status', '!=', 'rejected')
            ->when($excludeClaimId, fn ($q) => $q->where('id', '!=', $excludeClaimId))
            ->get();

        if ($amountDateMatches->isNotEmpty()) {
            $riskScore = max($riskScore, 90);
            $reasons[] = "Identical claim amount of PHP {$amount} on {$date} already filed by employee.";
            foreach ($amountDateMatches as $m) {
                $matchedClaims[$m->id] = [
                    'id' => $m->id,
                    'receipt_number' => $m->receipt_number,
                    'amount' => (float) $m->amount,
                    'expense_date' => $m->expense_date?->toDateString(),
                    'match_type' => 'SAME_EMPLOYEE_AMOUNT_DATE',
                ];
            }
        }

        // 3. Proximity Check (+/- 7 days, similar amount)
        $sevenDaysBefore = Carbon::parse($date)->subDays(7)->toDateString();
        $sevenDaysAfter = Carbon::parse($date)->addDays(7)->toDateString();

        $proximityMatches = Claim::where('employee_id', $employeeId)
            ->whereBetween('expense_date', [$sevenDaysBefore, $sevenDaysAfter])
            ->whereBetween('amount', [$amount * 0.95, $amount * 1.05])
            ->where('status', '!=', 'rejected')
            ->when($excludeClaimId, fn ($q) => $q->where('id', '!=', $excludeClaimId))
            ->get();

        if ($proximityMatches->isNotEmpty() && $riskScore < 60) {
            $riskScore = max($riskScore, 60);
            $reasons[] = 'Similar expense amount filed within 7 days by the same employee.';
            foreach ($proximityMatches as $m) {
                if (! isset($matchedClaims[$m->id])) {
                    $matchedClaims[$m->id] = [
                        'id' => $m->id,
                        'receipt_number' => $m->receipt_number,
                        'amount' => (float) $m->amount,
                        'expense_date' => $m->expense_date?->toDateString(),
                        'match_type' => 'PROXIMITY_OVERLAP',
                    ];
                }
            }
        }

        $riskLevel = match (true) {
            $riskScore >= 80 => 'HIGH_RISK',
            $riskScore >= 50 => 'MEDIUM_RISK',
            default => 'NONE',
        };

        return [
            'is_duplicate' => $riskScore >= 70,
            'risk_level' => $riskLevel,
            'risk_score' => $riskScore,
            'reason' => implode(' ', $reasons) ?: 'No duplicate collisions detected.',
            'matched_claims' => array_values($matchedClaims),
        ];
    }

    /**
     * Inspect and tag a Claim record with duplicate risk flags
     */
    public function flagClaimIfDuplicate(Claim $claim): Claim
    {
        $result = $this->checkDuplicate(
            $claim->employee_id,
            $claim->receipt_number ?? '',
            (float) $claim->amount,
            $claim->expense_date?->toDateString() ?? now()->toDateString(),
            $claim->id
        );

        $claim->update([
            'is_duplicate_flagged' => $result['is_duplicate'],
            'duplicate_risk_score' => $result['risk_score'],
            'duplicate_match_details' => $result['matched_claims'],
        ]);

        return $claim;
    }
}
