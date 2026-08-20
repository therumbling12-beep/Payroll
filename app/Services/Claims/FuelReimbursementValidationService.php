<?php

declare(strict_types=1);

namespace App\Services\Claims;

use App\Models\Claim;
use App\Models\ClaimCategory;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\PayrollAuditTrail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FuelReimbursementValidationService
{
    public const DEFAULT_FUEL_EFFICIENCY_KPL = 10.00;
    public const DEFAULT_PUMP_PRICE = 65.00;
    public const TOLERANCE_PERCENTAGE = 15.00;

    protected DuplicateClaimDetectionService $duplicateService;

    public function __construct(?DuplicateClaimDetectionService $duplicateService = null)
    {
        $this->duplicateService = $duplicateService ?? app(DuplicateClaimDetectionService::class);
    }

    /**
     * Get default fuel efficiency (km/L) from company settings or fallback
     */
    public function getDefaultEfficiency(): float
    {
        return (float) CompanySetting::getValue('fuel_default_efficiency_kpl', self::DEFAULT_FUEL_EFFICIENCY_KPL);
    }

    /**
     * Get default fuel pump price (PHP/L) from company settings or fallback
     */
    public function getDefaultPumpPrice(): float
    {
        return (float) CompanySetting::getValue('fuel_default_pump_price', self::DEFAULT_PUMP_PRICE);
    }

    /**
     * Get allowable fuel variance tolerance percentage from company settings or fallback
     */
    public function getTolerancePercentage(): float
    {
        return (float) CompanySetting::getValue('fuel_tolerance_percentage', self::TOLERANCE_PERCENTAGE);
    }

    /**
     * Compute expected fuel consumption, expected cost, and variance percentage against tolerance rule.
     *
     * @return array{
     *     distance_km: float,
     *     efficiency_kpl: float,
     *     pump_price: float,
     *     estimated_liters: float,
     *     expected_cost: float,
     *     actual_amount: float,
     *     variance_amount: float,
     *     variance_pct: float,
     *     is_within_tolerance: bool,
     *     auto_validated: bool,
     *     validation_status: string,
     *     status_badge: string,
     *     formula_explanation: string
     * }
     */
    public function validateFuelClaim(
        float $actualAmount,
        float $distanceKm,
        ?float $efficiencyKpl = null,
        ?float $pumpPrice = null
    ): array {
        $efficiency = ($efficiencyKpl && $efficiencyKpl > 0) ? $efficiencyKpl : $this->getDefaultEfficiency();
        $price = ($pumpPrice && $pumpPrice > 0) ? $pumpPrice : $this->getDefaultPumpPrice();
        $tolerance = $this->getTolerancePercentage();
        $distance = max(0.00, round($distanceKm, 2));
        $actual = max(0.00, round($actualAmount, 2));

        $estimatedLiters = $efficiency > 0 ? round($distance / $efficiency, 2) : 0.00;
        $expectedCost = round($estimatedLiters * $price, 2);

        $varianceAmount = round($actual - $expectedCost, 2);
        $variancePct = $expectedCost > 0 ? round(($varianceAmount / $expectedCost) * 100, 2) : 0.00;

        $isWithinTolerance = $variancePct <= $tolerance;
        $validationStatus = $isWithinTolerance ? 'auto_verified' : 'flagged_variance';
        $statusBadge = $isWithinTolerance
            ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
            : 'bg-amber-50 text-amber-800 border-amber-200';

        $explanation = $isWithinTolerance
            ? sprintf(
                'Auto-Verified: %.1f km at %.1f km/L with PHP %.2f/L yields PHP %s expected cost. Actual claim PHP %s is within %.1f%% tolerance (Variance: %+.1f%%).',
                $distance,
                $efficiency,
                $price,
                number_format($expectedCost, 2),
                number_format($actual, 2),
                $tolerance,
                $variancePct
            )
            : sprintf(
                'Flagged Variance: Expected cost is PHP %s for %.1f km. Actual receipt of PHP %s exceeds tolerance threshold (Variance: %+.1f%%, exceeding %.1f%% limit). Requires HR review.',
                number_format($expectedCost, 2),
                $distance,
                number_format($actual, 2),
                $variancePct,
                $tolerance
            );

        return [
            'distance_km' => $distance,
            'efficiency_kpl' => $efficiency,
            'pump_price' => $price,
            'estimated_liters' => $estimatedLiters,
            'expected_cost' => $expectedCost,
            'actual_amount' => $actual,
            'variance_amount' => $varianceAmount,
            'variance_pct' => $variancePct,
            'is_within_tolerance' => $isWithinTolerance,
            'auto_validated' => $isWithinTolerance,
            'validation_status' => $validationStatus,
            'status_badge' => $statusBadge,
            'formula_explanation' => $explanation,
        ];
    }

    /**
     * File and store a validated fuel expense claim
     *
     * @param array<string, mixed> $data
     */
    public function fileFuelClaim(array $data, ?UploadedFile $receiptFile = null): Claim
    {
        $employee = Employee::findOrFail($data['employee_id']);
        $actualAmount = (float) $data['amount'];
        $distanceKm = (float) ($data['distance_traveled_km'] ?? 0.0);
        $efficiency = ! empty($data['vehicle_fuel_efficiency_kpl']) ? (float) $data['vehicle_fuel_efficiency_kpl'] : self::DEFAULT_FUEL_EFFICIENCY_KPL;
        $pumpPrice = ! empty($data['fuel_pump_price']) ? (float) $data['fuel_pump_price'] : self::DEFAULT_PUMP_PRICE;

        $calc = $this->validateFuelClaim($actualAmount, $distanceKm, $efficiency, $pumpPrice);

        $attachmentPath = null;
        if ($receiptFile) {
            $attachmentPath = $receiptFile->store('receipts/claims', 'public');
        }

        $category = ClaimCategory::where('code', 'CAT-DRV-GAS')->first()
            ?: ClaimCategory::firstOrCreate(
                ['code' => 'CAT-DRV-GAS'],
                [
                    'name' => 'Driver Gas Expense',
                    'type' => 'reimbursement',
                    'tax_classification' => 'non_taxable',
                    'color_tag' => 'orange',
                    'max_amount' => 10000.00,
                    'is_active' => true,
                    'applicable_to' => 'driver',
                    'description' => 'Fuel and gasoline expenses incurred during scheduled trips.',
                ]
            );

        return DB::transaction(function () use ($data, $employee, $category, $calc, $actualAmount, $distanceKm, $efficiency, $pumpPrice, $attachmentPath) {
            $claim = Claim::create([
                'employee_id' => $employee->id,
                'category_id' => $category->id,
                'category' => $category->name,
                'type' => 'expense',
                'expense_subtype' => 'fuel',
                'receipt_number' => $data['receipt_number'] ?? ('GAS-' . strtoupper(uniqid())),
                'merchant_name' => $data['merchant_name'] ?? 'Petron Station',
                'merchant_tin' => $data['merchant_tin'] ?? null,
                'amount' => $actualAmount,
                'non_taxable_amount' => $actualAmount, // 100% Non-Taxable Business Reimbursement
                'taxable_amount' => 0.00,
                'tax_classification' => 'non_taxable',
                'distance_traveled_km' => $distanceKm,
                'vehicle_fuel_efficiency_kpl' => $efficiency,
                'fuel_liters' => ! empty($data['fuel_liters']) ? (float) $data['fuel_liters'] : $calc['estimated_liters'],
                'fuel_pump_price' => $pumpPrice,
                'expected_fuel_cost' => $calc['expected_cost'],
                'fuel_variance_pct' => $calc['variance_pct'],
                'auto_validated' => $calc['auto_validated'],
                'validation_status' => $calc['validation_status'],
                'odometer_start' => ! empty($data['odometer_start']) ? (float) $data['odometer_start'] : null,
                'odometer_end' => ! empty($data['odometer_end']) ? (float) $data['odometer_end'] : null,
                'expense_date' => $data['expense_date'] ?? now()->toDateString(),
                'cutoff_period' => $data['cutoff_period'] ?? '2026-08-13_19',
                'description' => $data['description'] ?? sprintf(
                    'Fuel reimbursement for %.1f km trip (%s). %s',
                    $distanceKm,
                    $data['merchant_name'] ?? 'Fuel Station',
                    $calc['formula_explanation']
                ),
                'attachment_path' => $attachmentPath,
                'approval_status' => 'pending_hr',
                'status' => 'pending',
                'hr_remarks' => $calc['is_within_tolerance']
                    ? 'Automated Trip Log Validation passed within 15% tolerance.'
                    : 'Flagged for variance review (>15% variance from dispatch distance).',
            ]);

            PayrollAuditTrail::create([
                'action' => 'FUEL_CLAIM_FILED',
                'model_type' => Claim::class,
                'model_id' => $claim->id,
                'user_name' => $employee->first_name . ' ' . $employee->last_name,
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'old_values' => [],
                'new_values' => [
                    'amount' => $actualAmount,
                    'distance_km' => $distanceKm,
                    'variance_pct' => $calc['variance_pct'],
                    'auto_validated' => $calc['auto_validated'],
                ],
            ]);

            $this->duplicateService->flagClaimIfDuplicate($claim);

            return $claim;
        });
    }
}
