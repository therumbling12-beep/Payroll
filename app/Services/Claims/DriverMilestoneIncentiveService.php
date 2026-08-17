<?php

declare(strict_types=1);

namespace App\Services\Claims;

use App\Models\Claim;
use App\Models\ClaimCategory;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\PayrollAuditTrail;
use App\Models\TripIncome;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DriverMilestoneIncentiveService
{
    /**
     * Official 5-Tier Ride Milestone Structure (known.md Section 7.4)
     */
    public const MILESTONE_TIERS = [
        1 => ['tier' => 1, 'min_rides' => 20, 'amount' => 500.00, 'label' => 'Tier 1 (20 Rides)'],
        2 => ['tier' => 2, 'min_rides' => 40, 'amount' => 1000.00, 'label' => 'Tier 2 (40 Rides)'],
        3 => ['tier' => 3, 'min_rides' => 60, 'amount' => 1500.00, 'label' => 'Tier 3 (60 Rides)'],
        4 => ['tier' => 4, 'min_rides' => 80, 'amount' => 2500.00, 'label' => 'Tier 4 (80 Rides)'],
        5 => ['tier' => 5, 'min_rides' => 100, 'amount' => 4000.00, 'label' => 'Tier 5 (100+ Rides)'],
    ];

    public const CONSISTENCY_BONUS_AMOUNT = 500.00;
    public const ATTENDANCE_BONUS_AMOUNT = 500.00;

    /**
     * Retrieve all configured milestone tiers from CompanySetting or default
     *
     * @return array<int, array{tier: int, min_rides: int, amount: float, label: string}>
     */
    public function getTiers(): array
    {
        $setting = CompanySetting::getValue('driver_milestone_tiers');
        if ($setting) {
            $decoded = is_string($setting) ? json_decode($setting, true) : $setting;
            if (is_array($decoded) && ! empty($decoded)) {
                $tiers = [];
                foreach ($decoded as $key => $t) {
                    $tierNum = (int) ($t['tier'] ?? $key);
                    $minRides = (int) ($t['min_rides'] ?? 20);
                    $amount = (float) ($t['amount'] ?? 500.00);
                    $label = $t['label'] ?? "Tier {$tierNum} ({$minRides} Rides)";
                    $tiers[$tierNum] = [
                        'tier' => $tierNum,
                        'min_rides' => $minRides,
                        'amount' => $amount,
                        'label' => $label,
                    ];
                }
                return $tiers;
            }
        }

        return self::MILESTONE_TIERS;
    }

    /**
     * Retrieve monthly consistency bonus amount from CompanySetting or default
     */
    public function getConsistencyBonus(): float
    {
        return (float) CompanySetting::getValue('driver_consistency_bonus', self::CONSISTENCY_BONUS_AMOUNT);
    }

    /**
     * Retrieve perfect attendance bonus amount from CompanySetting or default
     */
    public function getAttendanceBonus(): float
    {
        return (float) CompanySetting::getValue('driver_attendance_bonus', self::ATTENDANCE_BONUS_AMOUNT);
    }

    /**
     * Compute the ride milestone incentive breakdown for a driver
     *
     * @return array{
     *     is_driver: bool,
     *     driver_id: int,
     *     driver_name: string,
     *     completed_rides: int,
     *     qualified_tier: ?int,
     *     tier_label: string,
     *     base_milestone_amount: float,
     *     consistency_bonus: float,
     *     attendance_bonus: float,
     *     total_incentive_amount: float,
     *     is_qualified: bool,
     *     passed_tiers: array<int, array{tier: int, min_rides: int, amount: float, label: string}>,
     *     next_tier: ?array{tier: int, min_rides: int, target_remaining: int, progress_pct: float},
     *     formula_explanation: string
     * }
     */
    public function computeDriverIncentive(
        Employee $driver,
        int $completedRides,
        bool $hasConsistency = false,
        bool $hasAttendance = false
    ): array {
        $isDriver = $driver->isDriver();

        if (! $isDriver) {
            return [
                'is_driver' => false,
                'driver_id' => $driver->id,
                'driver_name' => trim($driver->first_name . ' ' . $driver->last_name),
                'completed_rides' => $completedRides,
                'qualified_tier' => null,
                'tier_label' => 'Not Applicable (Non-Driver Staff)',
                'base_milestone_amount' => 0.00,
                'consistency_bonus' => 0.00,
                'attendance_bonus' => 0.00,
                'total_incentive_amount' => 0.00,
                'is_qualified' => false,
                'passed_tiers' => [],
                'next_tier' => null,
                'formula_explanation' => 'Ride milestone incentives apply exclusively to TNVS drivers and fleet personnel.',
            ];
        }

        $tiers = $this->getTiers();
        $passedTiers = [];
        $highestTier = null;

        foreach ($tiers as $tierNum => $tierDef) {
            if ($completedRides >= $tierDef['min_rides']) {
                $passedTiers[] = $tierDef;
                $highestTier = $tierDef;
            }
        }

        $isQualified = ! empty($highestTier);
        $baseMilestoneAmount = $isQualified ? $highestTier['amount'] : 0.00;

        // Consistency and Attendance Bonuses apply only if driver reached at least Tier 2
        $qualifiesForAddons = $isQualified && ($highestTier['tier'] >= 2);
        $consistencyBonus = ($hasConsistency && $qualifiesForAddons) ? $this->getConsistencyBonus() : 0.00;
        $attendanceBonus = ($hasAttendance && $qualifiesForAddons) ? $this->getAttendanceBonus() : 0.00;

        $totalIncentive = round($baseMilestoneAmount + $consistencyBonus + $attendanceBonus, 2);

        // Next Tier Target Determination
        $nextTier = null;
        foreach ($tiers as $tierDef) {
            if ($completedRides < $tierDef['min_rides']) {
                $targetRemaining = $tierDef['min_rides'] - $completedRides;
                $prevMin = $highestTier ? $highestTier['min_rides'] : 0;
                $span = $tierDef['min_rides'] - $prevMin;
                $doneInSpan = $completedRides - $prevMin;
                $progressPct = $span > 0 ? min(100.0, round(($doneInSpan / $span) * 100, 1)) : 0.0;

                $nextTier = [
                    'tier' => $tierDef['tier'],
                    'min_rides' => $tierDef['min_rides'],
                    'target_remaining' => $targetRemaining,
                    'progress_pct' => $progressPct,
                ];
                break;
            }
        }

        $firstTier = reset($tiers);
        $firstTierMin = $firstTier ? $firstTier['min_rides'] : 20;
        $firstTierLabel = $firstTier ? $firstTier['label'] : 'Tier 1';
        $maxTier = end($tiers);
        $maxTierNum = $maxTier ? $maxTier['tier'] : 5;
        $maxTierMin = $maxTier ? $maxTier['min_rides'] : 100;

        if (! $nextTier && $isQualified) {
            // Reached max tier
            $nextTier = [
                'tier' => $maxTierNum,
                'min_rides' => $maxTierMin,
                'target_remaining' => 0,
                'progress_pct' => 100.0,
            ];
        }

        $explanation = $isQualified
            ? sprintf(
                'Completed %d rides qualifying for %s (PHP %s base milestone incentive).',
                $completedRides,
                $highestTier['label'],
                number_format($baseMilestoneAmount, 2)
            )
            : sprintf(
                'Completed %d rides. Below initial qualification threshold (%s requires %d rides; %d rides remaining).',
                $completedRides,
                $firstTierLabel,
                $firstTierMin,
                max(0, $firstTierMin - $completedRides)
            );

        if ($consistencyBonus > 0) {
            $explanation .= ' Includes +PHP ' . number_format($consistencyBonus, 2) . ' Monthly Consistency Bonus.';
        }
        if ($attendanceBonus > 0) {
            $explanation .= ' Includes +PHP ' . number_format($attendanceBonus, 2) . ' Perfect Attendance Bonus.';
        }

        return [
            'is_driver' => true,
            'driver_id' => $driver->id,
            'driver_name' => trim($driver->first_name . ' ' . $driver->last_name),
            'completed_rides' => $completedRides,
            'qualified_tier' => $highestTier ? $highestTier['tier'] : null,
            'tier_label' => $highestTier ? $highestTier['label'] : 'Below Tier 1 Quota (<20 Rides)',
            'base_milestone_amount' => $baseMilestoneAmount,
            'consistency_bonus' => $consistencyBonus,
            'attendance_bonus' => $attendanceBonus,
            'total_incentive_amount' => $totalIncentive,
            'is_qualified' => $isQualified,
            'passed_tiers' => $passedTiers,
            'next_tier' => $nextTier,
            'formula_explanation' => $explanation,
        ];
    }

    /**
     * Qualify the entire active driver roster for a specific payroll cutoff period
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function qualifyDriverRoster(string $cutoffPeriod): Collection
    {
        $drivers = Employee::drivers()
            ->with(['tripIncomes' => fn ($q) => $q->where('cutoff_period', $cutoffPeriod)])
            ->orderBy('first_name')
            ->get();

        return $drivers->map(function (Employee $driver) use ($cutoffPeriod) {
            $tripIncome = $driver->tripIncomes->first();
            $completedTrips = $tripIncome ? (int) $tripIncome->total_trips : 0;

            // Existing claim check
            $existingClaim = Claim::where('employee_id', $driver->id)
                ->where('type', 'incentive')
                ->where('cutoff_period', $cutoffPeriod)
                ->first();

            $calc = $this->computeDriverIncentive($driver, $completedTrips);

            return array_merge($calc, [
                'employee_code' => $driver->employee_code,
                'position' => $driver->position,
                'cutoff_period' => $cutoffPeriod,
                'existing_claim_id' => $existingClaim?->id,
                'claim_status' => $existingClaim ? $existingClaim->approval_status : ($calc['is_qualified'] ? 'QUALIFIED' : 'BELOW_QUOTA'),
                'is_already_committed' => (bool) $existingClaim,
            ]);
        });
    }

    /**
     * Batch commit all qualified driver milestone incentives into active Claim records
     *
     * @param array<int, array<string, mixed>> $driverPlans
     */
    public function batchCommitDriverIncentives(array $driverPlans, string $cutoffPeriod): int
    {
        $category = ClaimCategory::where('code', 'CAT-DRV-INC')->first()
            ?: ClaimCategory::firstOrCreate(
                ['code' => 'CAT-DRV-INC'],
                [
                    'name' => 'Driver Ride Milestone Incentive',
                    'type' => 'incentive',
                    'tax_classification' => 'taxable',
                    'color_tag' => 'purple',
                    'max_amount' => 10000.00,
                    'is_active' => true,
                    'applicable_to' => 'driver',
                    'description' => '5-Tier milestone bonuses for completed trips.',
                ]
            );

        $committedCount = 0;

        DB::transaction(function () use ($driverPlans, $cutoffPeriod, $category, &$committedCount) {
            foreach ($driverPlans as $plan) {
                $driverId = $plan['driver_id'] ?? ($plan['id'] ?? null);
                if (! $driverId) {
                    continue;
                }

                $driver = Employee::find($driverId);
                if (! $driver) {
                    continue;
                }

                $rides = (int) ($plan['completed_rides'] ?? 0);
                $calc = $this->computeDriverIncentive($driver, $rides);

                if (! $calc['is_qualified'] || $calc['total_incentive_amount'] <= 0) {
                    continue;
                }

                $amount = $calc['total_incentive_amount'];
                $receiptNo = sprintf('INC-DRV-%s-%s-%04d', str_replace(['_', '-'], '', $cutoffPeriod), $driver->employee_code, $driver->id);

                Claim::updateOrCreate(
                    [
                        'employee_id' => $driver->id,
                        'cutoff_period' => $cutoffPeriod,
                        'type' => 'incentive',
                    ],
                    [
                        'category_id' => $category->id,
                        'receipt_number' => $receiptNo,
                        'amount' => $amount,
                        'non_taxable_amount' => 0.00,
                        'taxable_amount' => $amount,
                        'tax_classification' => 'taxable',
                        'description' => sprintf(
                            'Completed %d rides (%s) in cutoff %s. Total Milestone Bonus: PHP %s.',
                            $rides,
                            $calc['tier_label'],
                            $cutoffPeriod,
                            number_format($amount, 2)
                        ),
                        'approval_status' => 'approved',
                        'status' => 'approved',
                        'hr_approved_at' => now(),
                        'hr_remarks' => 'Automated Ride Milestone Incentive Qualification confirmed from Dispatch data.',
                        'expense_date' => now()->toDateString(),
                    ]
                );

                PayrollAuditTrail::create([
                    'action' => 'DRIVER_MILESTONE_INCENTIVE_COMMITTED',
                    'model_type' => Claim::class,
                    'model_id' => $driver->id,
                    'user_name' => 'Operations Fleet Supervisor',
                    'ip_address' => request()->ip() ?? '127.0.0.1',
                    'old_values' => [],
                    'new_values' => [
                        'cutoff_period' => $cutoffPeriod,
                        'completed_rides' => $rides,
                        'incentive_amount' => $amount,
                        'tier' => $calc['qualified_tier'],
                    ],
                ]);

                $committedCount++;
            }
        });

        return $committedCount;
    }
}
