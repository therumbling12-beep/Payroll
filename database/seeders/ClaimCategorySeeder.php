<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Claim;
use App\Models\ClaimCategory;
use App\Models\CompanySetting;
use App\Models\Employee;
use Illuminate\Database\Seeder;

class ClaimCategorySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Predefined Categories from v2.md §3.1
        $categories = [
            [
                'name' => 'Medical Assistance',
                'code' => 'CAT-MED',
                'type' => 'reimbursement',
                'tax_classification' => 'de_minimis',
                'color_tag' => 'emerald',
                'max_amount' => 15000.00,
                'de_minimis_annual_cap' => 10000.00,
                'is_active' => true,
                'requires_receipt' => true,
                'applicable_to' => 'all',
                'spending_limit_period' => 'per_year',
                'description' => 'Out-of-pocket medical, dental checkup, and prescription expenses (PHP 10k/yr de minimis tax-exempt cap).',
            ],
            [
                'name' => 'Transportation',
                'code' => 'CAT-TRANS',
                'type' => 'reimbursement',
                'tax_classification' => 'non_taxable',
                'color_tag' => 'sky',
                'max_amount' => 5000.00,
                'de_minimis_annual_cap' => null,
                'is_active' => true,
                'requires_receipt' => true,
                'applicable_to' => 'regular',
                'spending_limit_period' => 'per_month',
                'description' => 'Work-related official staff travel and dispatch transit fares.',
            ],
            [
                'name' => 'Meal / Representation',
                'code' => 'CAT-MEAL',
                'type' => 'reimbursement',
                'tax_classification' => 'non_taxable',
                'color_tag' => 'amber',
                'max_amount' => 3000.00,
                'de_minimis_annual_cap' => null,
                'is_active' => true,
                'requires_receipt' => true,
                'applicable_to' => 'regular',
                'spending_limit_period' => 'per_claim',
                'description' => 'Business client meals and official representation expenses.',
            ],
            [
                'name' => 'Training / Seminar',
                'code' => 'CAT-TRAIN',
                'type' => 'reimbursement',
                'tax_classification' => 'non_taxable',
                'color_tag' => 'violet',
                'max_amount' => 20000.00,
                'de_minimis_annual_cap' => null,
                'is_active' => true,
                'requires_receipt' => true,
                'applicable_to' => 'all',
                'spending_limit_period' => 'per_year',
                'description' => 'Job-related seminars, professional certifications, and workshops.',
            ],
            [
                'name' => 'Communication / Internet',
                'code' => 'CAT-COMM',
                'type' => 'reimbursement',
                'tax_classification' => 'non_taxable',
                'color_tag' => 'indigo',
                'max_amount' => 2500.00,
                'de_minimis_annual_cap' => null,
                'is_active' => true,
                'requires_receipt' => true,
                'applicable_to' => 'regular',
                'spending_limit_period' => 'per_month',
                'description' => 'Work-from-home internet allowances and official mobile load.',
            ],
            [
                'name' => 'Driver Gas Expense',
                'code' => 'CAT-DRV-GAS',
                'type' => 'reimbursement',
                'tax_classification' => 'non_taxable',
                'color_tag' => 'orange',
                'max_amount' => 10000.00,
                'de_minimis_annual_cap' => null,
                'is_active' => true,
                'requires_receipt' => true,
                'applicable_to' => 'driver',
                'spending_limit_period' => 'per_month',
                'description' => 'Fuel, gasoline, and diesel expenses incurred during scheduled trips.',
            ],
            [
                'name' => 'Driver Work-Related Expense',
                'code' => 'CAT-DRV-WORK',
                'type' => 'reimbursement',
                'tax_classification' => 'non_taxable',
                'color_tag' => 'blue',
                'max_amount' => 5000.00,
                'de_minimis_annual_cap' => null,
                'is_active' => true,
                'requires_receipt' => true,
                'applicable_to' => 'driver',
                'spending_limit_period' => 'per_month',
                'description' => 'Expressway RFID toll fees, terminal parking, and authorized vehicle upkeep.',
            ],
            [
                'name' => 'Driver Ride Milestone Incentive',
                'code' => 'CAT-DRV-INC',
                'type' => 'incentive',
                'tax_classification' => 'taxable',
                'color_tag' => 'purple',
                'max_amount' => 10000.00,
                'de_minimis_annual_cap' => null,
                'is_active' => true,
                'requires_receipt' => false,
                'applicable_to' => 'driver',
                'spending_limit_period' => 'per_month',
                'description' => '5-Tier milestone bonuses for completing target quota rides.',
            ],
            [
                'name' => 'Maternity Leave Benefit',
                'code' => 'CAT-MAT',
                'type' => 'maternity',
                'tax_classification' => 'non_taxable',
                'color_tag' => 'pink',
                'max_amount' => 150000.00,
                'de_minimis_annual_cap' => null,
                'is_active' => true,
                'requires_receipt' => true,
                'applicable_to' => 'regular',
                'spending_limit_period' => 'per_claim',
                'description' => '105-day statutory maternity benefit salary advance and company differential.',
            ],
        ];

        foreach ($categories as $cat) {
            ClaimCategory::updateOrCreate(['code' => $cat['code']], $cat);
        }

        // 2. Additional Company Settings for Claims & Incentives
        $settings = [
            'performance_rating_multiplier' => '1500.00',
            'driver_tier_10_amount' => '250.00',
            'driver_tier_20_amount' => '500.00',
            'driver_tier_30_amount' => '1000.00',
            'driver_tier_50_amount' => '2000.00',
        ];

        foreach ($settings as $k => $v) {
            CompanySetting::updateOrCreate(['key' => $k], ['value' => $v]);
        }

        // 3. Seed Realistic Multi-Step Claims for Demonstrating Workflows
        $cutoff = '2026-07-01_15';
        $employees = Employee::all();

        if ($employees->isEmpty()) {
            return;
        }

        $gasCat = ClaimCategory::where('code', 'CAT-DRV-GAS')->first();
        $workCat = ClaimCategory::where('code', 'CAT-DRV-WORK')->first();
        $rideIncCat = ClaimCategory::where('code', 'CAT-DRV-INC')->first();
        $perfCat = ClaimCategory::where('code', 'CAT-PERF')->first();
        $matCat = ClaimCategory::where('code', 'CAT-MAT')->first();
        $medCat = ClaimCategory::where('code', 'CAT-MED')->first();
        $transCat = ClaimCategory::where('code', 'CAT-TRANS')->first();

        // A. Driver Expense Claims
        $drivers = $employees->filter(fn($e) => str_contains(strtolower($e->position), 'driver'));
        $regularStaff = $employees->filter(fn($e) => !str_contains(strtolower($e->position), 'driver'));

        if ($drivers->isNotEmpty()) {
            $driver1 = $drivers->first();
            Claim::updateOrCreate(
                ['receipt_number' => 'RCP-202607-10491'],
                [
                    'employee_id' => $driver1->id,
                    'category_id' => $gasCat?->id,
                    'category' => 'Driver Gas Expense',
                    'type' => 'expense',
                    'amount' => 1850.00,
                    'cutoff_period' => $cutoff,
                    'description' => 'Fuel top-up at Shell EDSA Southbound (Fleet Trip #TR-8821)',
                    'status' => 'approved',
                    'approval_status' => 'approved',
                    'hr_approved_at' => now()->subDays(3),
                    'admin_approved_at' => now()->subDays(2),
                    'finance_approved_at' => now()->subDay(),
                    'payroll_queued_at' => now(),
                    'expense_date' => now()->subDays(4),
                    'attachment_path' => 'receipts/sample-fuel-receipt.jpg',
                    'effective_date' => now(),
                ]
            );

            if ($drivers->count() > 1) {
                $driver2 = $drivers->values()->get(1);
                Claim::updateOrCreate(
                    ['receipt_number' => 'RCP-202607-10492'],
                    [
                        'employee_id' => $driver2->id,
                        'category_id' => $workCat?->id,
                        'category' => 'Driver Work-Related Expense',
                        'type' => 'expense',
                        'amount' => 640.00,
                        'cutoff_period' => $cutoff,
                        'description' => 'SLEX / Skyway RFID toll reloads for airport charter trips',
                        'status' => 'pending',
                        'approval_status' => 'pending_admin',
                        'hr_approved_at' => now()->subHours(12),
                        'hr_remarks' => 'RFID transaction receipt verified with Team 9 trip logs.',
                        'expense_date' => now()->subDays(2),
                        'attachment_path' => 'receipts/sample-toll-receipt.pdf',
                        'effective_date' => now(),
                    ]
                );
            }

            if ($drivers->count() > 2) {
                $driver3 = $drivers->values()->get(2);
                Claim::updateOrCreate(
                    ['receipt_number' => 'RCP-202607-10493'],
                    [
                        'employee_id' => $driver3->id,
                        'category_id' => $gasCat?->id,
                        'category' => 'Driver Gas Expense',
                        'type' => 'expense',
                        'amount' => 1200.00,
                        'cutoff_period' => $cutoff,
                        'description' => 'Caltex gasoline reload during heavy evening traffic run',
                        'status' => 'pending',
                        'approval_status' => 'pending_hr',
                        'expense_date' => now()->subDays(1),
                        'attachment_path' => 'receipts/sample-gas-receipt.png',
                        'effective_date' => now(),
                    ]
                );
            }

            // B. Driver Ride Incentives (Tier-Based)
            foreach ($drivers->take(3) as $idx => $driver) {
                $trips = [35, 22, 54][$idx % 3];
                $incentiveAmount = ($trips >= 50) ? 2000.00 : (($trips >= 30) ? 1000.00 : (($trips >= 20) ? 500.00 : 250.00));
                $tierName = ($trips >= 50) ? 'Tier 4 (50+ Rides)' : (($trips >= 30) ? 'Tier 3 (30+ Rides)' : (($trips >= 20) ? 'Tier 2 (20+ Rides)' : 'Tier 1 (10+ Rides)'));

                Claim::updateOrCreate(
                    ['receipt_number' => 'INC-202607-' . (2001 + $idx)],
                    [
                        'employee_id' => $driver->id,
                        'category_id' => $rideIncCat?->id,
                        'category' => 'Driver Ride Incentive',
                        'type' => 'incentive',
                        'amount' => $incentiveAmount,
                        'cutoff_period' => $cutoff,
                        'description' => "Completed {$trips} verified passenger rides ({$tierName}) via Team 9 TNVS Ops.",
                        'status' => $idx === 0 ? 'approved' : 'pending',
                        'approval_status' => $idx === 0 ? 'payroll_queued' : ($idx === 1 ? 'pending_finance' : 'pending_hr'),
                        'hr_approved_at' => $idx <= 1 ? now()->subDays(2) : null,
                        'admin_approved_at' => $idx <= 1 ? now()->subDay() : null,
                        'finance_approved_at' => $idx === 0 ? now()->subHours(6) : null,
                        'payroll_queued_at' => $idx === 0 ? now() : null,
                        'expense_date' => now()->subDays(5),
                        'effective_date' => now(),
                    ]
                );
            }
        }

        // C. Performance Incentives (General Regular Staff)
        if ($regularStaff->isNotEmpty()) {
            $staff1 = $regularStaff->first();
            Claim::updateOrCreate(
                ['receipt_number' => 'PRF-202607-3001'],
                [
                    'employee_id' => $staff1->id,
                    'category_id' => $perfCat?->id,
                    'category' => 'Performance Incentive',
                    'type' => 'performance',
                    'amount' => 6000.00, // 4.0 rating * ₱1,500
                    'performance_rating' => 4.00,
                    'cutoff_period' => $cutoff,
                    'description' => 'Exceptional Q3 dispatch routing efficiency & zero backlog milestone.',
                    'status' => 'approved',
                    'approval_status' => 'payroll_queued',
                    'hr_approved_at' => now()->subDays(3),
                    'admin_approved_at' => now()->subDays(2),
                    'finance_approved_at' => now()->subDay(),
                    'payroll_queued_at' => now(),
                    'expense_date' => now()->subDays(6),
                    'effective_date' => now(),
                ]
            );

            if ($regularStaff->count() > 1) {
                $staff2 = $regularStaff->values()->get(1);
                Claim::updateOrCreate(
                    ['receipt_number' => 'PRF-202607-3002'],
                    [
                        'employee_id' => $staff2->id,
                        'category_id' => $perfCat?->id,
                        'category' => 'Performance Incentive',
                        'type' => 'performance',
                        'amount' => 7500.00, // 5.0 rating * ₱1,500
                        'performance_rating' => 5.00,
                        'cutoff_period' => $cutoff,
                        'description' => 'Top Customer Support Net Promoter Score (NPS 98%) during peak holiday load.',
                        'status' => 'pending',
                        'approval_status' => 'pending_finance',
                        'hr_approved_at' => now()->subDays(1),
                        'admin_approved_at' => now()->subHours(10),
                        'admin_remarks' => 'Exemplary output endorsed for priority disbursement.',
                        'expense_date' => now()->subDays(3),
                        'effective_date' => now(),
                    ]
                );
            }

            if ($regularStaff->count() > 2) {
                $staff3 = $regularStaff->values()->get(2);
                Claim::updateOrCreate(
                    ['receipt_number' => 'PRF-202607-3003'],
                    [
                        'employee_id' => $staff3->id,
                        'category_id' => $perfCat?->id,
                        'category' => 'Performance Incentive',
                        'type' => 'performance',
                        'amount' => 4500.00, // 3.0 rating * ₱1,500
                        'performance_rating' => 3.00,
                        'cutoff_period' => $cutoff,
                        'description' => 'Solid contribution to driver onboarding and document verification quota.',
                        'status' => 'pending',
                        'approval_status' => 'pending_hr',
                        'expense_date' => now()->subDays(2),
                        'effective_date' => now(),
                    ]
                );
            }

            // D. Maternity Leave Claim with SSS / Company Top-Up Split
            if ($regularStaff->count() > 3) {
                $maternityStaff = $regularStaff->values()->get(3);
                $dailyRate = $maternityStaff->daily_rate ?: ($maternityStaff->monthly_rate / 26);
                $totalMaternity = round($dailyRate * 105, 2);
                $sssShare = min($totalMaternity, 70000.00);
                $companyTopup = max(0.00, $totalMaternity - $sssShare);

                Claim::updateOrCreate(
                    ['receipt_number' => 'MAT-202607-4001'],
                    [
                        'employee_id' => $maternityStaff->id,
                        'category_id' => $matCat?->id,
                        'category' => 'Maternity Leave Incentive',
                        'type' => 'maternity',
                        'amount' => $totalMaternity,
                        'sss_maternity_share' => $sssShare,
                        'company_maternity_topup' => $companyTopup,
                        'cutoff_period' => $cutoff,
                        'description' => '105-Day Statutory RA 11210 Maternity Benefit with Company Salary Differential.',
                        'status' => 'pending',
                        'approval_status' => 'pending_admin',
                        'hr_approved_at' => now()->subDays(1),
                        'hr_remarks' => 'Medical certificate and SSS Mat-1 / Mat-2 notifications validated.',
                        'expense_date' => now()->subDays(10),
                        'attachment_path' => 'receipts/sample-maternity-medcert.pdf',
                        'effective_date' => now(),
                    ]
                );
            }
        }
    }
}
