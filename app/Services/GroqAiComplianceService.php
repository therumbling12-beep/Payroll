<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AiComplianceLog;
use App\Models\SalaryComputation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqAiComplianceService
{
    /**
     * Analyze a salary computation record using the Groq API for pattern anomalies.
     */
    public function analyzeCompliance(SalaryComputation $computation): AiComplianceLog
    {
        $apiKey = config('services.groq.key', env('GROQ_API_KEY'));

        // Fallback to deterministic local heuristic evaluation if API key is missing
        if (! $apiKey) {
            return $this->fallbackHeuristicEvaluation($computation);
        }

        $prompt = "You are an AI Compliance & Audit Assistant for a Philippine TNVS Payroll System. Your role is to detect regulatory risk patterns, unusual anomalies, and DOLE advisory warnings.

Data to evaluate:
- Employee Name: {$computation->employee?->first_name} {$computation->employee?->last_name}
- Position: {$computation->employee?->position}
- Base Pay: ₱{$computation->base_pay}
- Trip Earnings: ₱{$computation->trip_earnings}
- Performance Bonus / Taxable Claims: ₱{$computation->performance_bonus}
- Reimbursements (Non-Taxable): ₱{$computation->reimbursements}
- Gross Pay: ₱{$computation->gross_pay}
- Deductions: SSS (₱{$computation->sss_deduction}), PhilHealth (₱{$computation->philhealth_deduction}), Pag-IBIG (₱{$computation->pagibig_deduction}), Driver HMO Insurance (₱{$computation->hmo_insurance_deduction}), BIR Withholding Tax (₱{$computation->withholding_tax})
- Total Deductions: ₱{$computation->total_deductions}
- Net Pay Payout: ₱{$computation->net_pay}

Pattern Rules to Audit:
1. Mandatory Statutory Deductions: Ensure SSS, PhilHealth, and Pag-IBIG contributions follow official 2026 Philippine tables.
2. DOLE Advisory Deduction Ceiling: Flag if Total Deductions exceed 50% of Gross Pay.
3. Minimum Wage Safety Floor (DOLE Wage Order NCR-27): Flag if regular staff net pay or daily rate falls below ₱755.00/day.
4. Tax & Reimbursement Integrity: Ensure non-taxable reimbursements are supported by legitimate business expense types.

Respond ONLY in valid JSON with this exact structure:
{
    \"compliance_score\": 95,
    \"status\": \"PASSED\",
    \"ai_summary\": \"Short 1-sentence audit summary.\",
    \"flagged_issues\": [\"Pattern issue 1 if any\", \"Pattern issue 2 if any\"],
    \"resolution_suggestions\": [\"Actionable step 1 on how to fix\", \"Actionable step 2\"]
}";

        try {
            $response = Http::withToken($apiKey)
                ->timeout(10)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama3-8b-8192',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You respond only in JSON.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.2,
                    'response_format' => ['type' => 'json_object'],
                ]);

            if ($response->successful()) {
                $data = json_decode($response->json('choices.0.message.content'), true);

                return AiComplianceLog::updateOrCreate(
                    ['salary_computation_id' => $computation->id],
                    [
                        'compliance_score' => $data['compliance_score'] ?? 95,
                        'status' => $data['status'] ?? 'PASSED',
                        'ai_summary' => $data['ai_summary'] ?? 'Compliant with DOLE regulations and internal risk thresholds.',
                        'flagged_issues' => $data['flagged_issues'] ?? [],
                        'resolution_suggestions' => $data['resolution_suggestions'] ?? [],
                    ]
                );
            }
        } catch (\Exception $e) {
            Log::error('Groq AI API Call Failed: ' . $e->getMessage());
        }

        return $this->fallbackHeuristicEvaluation($computation);
    }

    /**
     * Local deterministic heuristic evaluation fallback.
     */
    private function fallbackHeuristicEvaluation(SalaryComputation $computation): AiComplianceLog
    {
        $issues = [];
        $suggestions = [];
        $score = 100;
        $status = 'PASSED';

        $wageFloor = 755.00; // NCR-27 baseline

        if ((float) $computation->net_pay < $wageFloor && (float) $computation->gross_pay > 0) {
            $issues[] = 'Net pay payout (₱' . number_format((float)$computation->net_pay, 2) . ') falls below DOLE minimum daily wage safety floor threshold (₱' . number_format($wageFloor, 2) . ').';
            $suggestions[] = 'Use Manual Override to adjust base pay or review excessive deductions to ensure net pay meets DOLE daily minimum limits.';
            $score -= 30;
            $status = 'WARNING';
        }

        if ((float) $computation->gross_pay > 0 && (float) $computation->total_deductions > ((float) $computation->gross_pay * 0.5)) {
            $issues[] = 'Total deductions (₱' . number_format((float)$computation->total_deductions, 2) . ') exceed the DOLE advisory ceiling of 50% of gross pay (₱' . number_format((float)$computation->gross_pay, 2) . ').';
            $suggestions[] = 'Click Manual Override to reduce optional deductions (e.g. HMO, driver cash advances) so total deductions stay under 50%.';
            $score -= 20;
            $status = 'WARNING';
        }

        if ((float) $computation->gross_pay > 0 && ((float) $computation->sss_deduction == 0 && (float) $computation->philhealth_deduction == 0 && (float) $computation->pagibig_deduction == 0)) {
            $issues[] = 'Missing mandatory statutory government contributions (SSS, PhilHealth, or Pag-IBIG).';
            $suggestions[] = 'Verify employee government contribution settings or perform a manual override to recalculate statutory deductions.';
            $score -= 25;
            $status = 'WARNING';
        }

        if ($score < 70) {
            $status = 'FAILED';
        }

        return AiComplianceLog::updateOrCreate(
            ['salary_computation_id' => $computation->id],
            [
                'compliance_score' => max(0, $score),
                'status' => $status,
                'ai_summary' => empty($issues) ? 'Automated risk evaluation confirms full compliance.' : 'Flagged potential regulatory or deduction ceiling issues.',
                'flagged_issues' => $issues,
                'resolution_suggestions' => $suggestions,
            ]
        );
    }
}
