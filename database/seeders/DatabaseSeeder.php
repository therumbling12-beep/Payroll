<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AccidentClaim;
use App\Models\Attendance;
use App\Models\BudgetRequisition;
use App\Models\Claim;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\HmoEnrollment;
use App\Models\PerformanceBonus;
use App\Models\SalaryComputation;
use App\Models\TripIncome;
use App\Services\GroqAiComplianceService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with exactly 15 Filipino employees:
     * - 5 Regular Staff
     * - 5 Applicants / Probationary
     * - 5 Drivers (TNVS)
     */
    public function run(): void
    {
        // 0. Seed Company Dynamic Settings Mock Data
        $settings = [
            'sss_deduction_rate' => 0.05,
            'sss_maximum_cap' => 1750.00,
            'philhealth_deduction_rate' => 0.025,
            'philhealth_maximum_cap' => 2500.00,
            'pagibig_fixed_amount' => 200.00,
            'hmo_driver_deduction_rate' => 0.03,
            'bir_withholding_threshold' => 20833.33,
            'bir_withholding_rate' => 0.20,
            'counter_offer_exp_multiplier' => 2500.00,
            'counter_offer_cert_multiplier' => 3500.00,
            'financial_budget_ceiling' => 150000.00,
            'maternity_leave_days' => 105,
            'standard_working_days_divisor' => 26,
            'ai_wage_safety_floor' => 755.00,
            'tnvs_platform_commission_rate' => 0.20,
        ];

        foreach ($settings as $key => $value) {
            CompanySetting::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }

        // 1. Seed Departments & Salary Grades & Statutory Tables & Holiday Calendar
        $this->call(SalaryGradeSeeder::class);
        $this->call(GovernmentContributionSeeder::class);
        $this->call(HolidaySeeder::class);

        $fleetDept = Department::firstOrCreate(['name' => 'Fleet Operations (Drivers)']);
        $dispatchDept = Department::firstOrCreate(['name' => 'Dispatch & Routing']);
        $adminDept = Department::firstOrCreate(['name' => 'Administration & HR']);

        // 2. Exactly 15 Filipino Employees (5 Regular Staff, 5 Applicants/Probationary, 5 Drivers)
        $employeeData = [
            // --- 5 REGULAR STAFF ---
            [
                'employee_code' => 'EMP-1001',
                'first_name' => 'Maria',
                'last_name' => 'Santos',
                'email' => 'maria.santos@tripwise.com',
                'department_id' => $adminDept->id,
                'position' => 'HR Specialist',
                'hire_date' => '2023-01-15',
                'regularization_date' => '2023-07-15',
                'employment_status' => 'regular',
                'performance_rating' => 'Outstanding',
                'daily_rate' => 1230.77,
                'monthly_rate' => 32000.00,
                'current_step' => 2,
                'step_status' => 'normal',
                'payment_mode' => 'bank',
                'bank_name' => 'Security Bank Corporation',
                'bank_account_number' => '0012-3456-7891',
                'bank_account_no' => 'SBC-001234567891',
            ],
            [
                'employee_code' => 'EMP-1002',
                'first_name' => 'Jose',
                'last_name' => 'Reyes',
                'email' => 'jose.reyes@tripwise.com',
                'department_id' => $dispatchDept->id,
                'position' => 'Operations Dispatcher',
                'hire_date' => '2023-05-10',
                'regularization_date' => '2023-11-10',
                'employment_status' => 'regular',
                'performance_rating' => 'Very Satisfactory',
                'daily_rate' => 1076.92,
                'monthly_rate' => 28000.00,
                'current_step' => 1,
                'step_status' => 'normal',
                'payment_mode' => 'bank',
                'bank_name' => 'Security Bank Corporation',
                'bank_account_number' => '1234-5678-9012',
                'bank_account_no' => 'SBC-123456789012',
            ],
            [
                'employee_code' => 'EMP-1003',
                'first_name' => 'Ana',
                'last_name' => 'Dela Cruz',
                'email' => 'ana.delacruz@tripwise.com',
                'department_id' => $adminDept->id,
                'position' => 'Fleet Supervisor',
                'hire_date' => '2022-03-01',
                'regularization_date' => '2022-09-01',
                'employment_status' => 'regular',
                'performance_rating' => 'Outstanding',
                'daily_rate' => 1615.38,
                'monthly_rate' => 42000.00,
                'current_step' => 3,
                'step_status' => 'normal',
                'payment_mode' => 'bank',
                'bank_name' => 'Security Bank Corporation',
                'bank_account_number' => '2345-6789-0123',
                'bank_account_no' => 'SBC-234567890123',
            ],
            [
                'employee_code' => 'EMP-1004',
                'first_name' => 'Ramon',
                'last_name' => 'Villanueva',
                'email' => 'ramon.villanueva@tripwise.com',
                'department_id' => $adminDept->id,
                'position' => 'Payroll Officer',
                'hire_date' => '2023-08-15',
                'regularization_date' => '2024-02-15',
                'employment_status' => 'regular',
                'performance_rating' => 'Very Satisfactory',
                'daily_rate' => 1153.85,
                'monthly_rate' => 30000.00,
                'current_step' => 2,
                'step_status' => 'normal',
                'payment_mode' => 'bank',
                'bank_name' => 'Security Bank Corporation',
                'bank_account_number' => '3456-7890-1234',
                'bank_account_no' => 'SBC-345678901234',
            ],
            [
                'employee_code' => 'EMP-1005',
                'first_name' => 'Luisa',
                'last_name' => 'Bautista',
                'email' => 'luisa.bautista@tripwise.com',
                'department_id' => $dispatchDept->id,
                'position' => 'Admin Assistant',
                'hire_date' => '2024-01-10',
                'regularization_date' => '2024-07-10',
                'employment_status' => 'regular',
                'performance_rating' => 'Satisfactory',
                'daily_rate' => 846.15,
                'monthly_rate' => 22000.00,
                'current_step' => 1,
                'step_status' => 'normal',
                'payment_mode' => 'bank',
                'bank_name' => 'Security Bank Corporation',
                'bank_account_number' => '4567-8901-2345',
                'bank_account_no' => 'SBC-456789012345',
            ],

            // --- 5 APPLICANTS (PROBATIONARY) ---
            [
                'employee_code' => 'EMP-1006',
                'first_name' => 'Carlo',
                'last_name' => 'Mendoza',
                'email' => 'carlo.mendoza@tripwise.com',
                'department_id' => $dispatchDept->id,
                'position' => 'Operations Dispatcher',
                'hire_date' => '2026-03-01',
                'regularization_date' => null,
                'employment_status' => 'probationary',
                'performance_rating' => 'Very Satisfactory',
                'daily_rate' => 1000.00,
                'monthly_rate' => 26000.00,
                'current_step' => 1,
                'step_status' => 'normal',
                'payment_mode' => 'bank',
                'bank_name' => 'Security Bank Corporation',
                'bank_account_number' => '0012-3456-7896',
                'bank_account_no' => 'SBC-001234567896',
            ],
            [
                'employee_code' => 'EMP-1007',
                'first_name' => 'Kristine',
                'last_name' => 'Pascual',
                'email' => 'kristine.pascual@tripwise.com',
                'department_id' => $adminDept->id,
                'position' => 'HR Specialist',
                'hire_date' => '2026-04-15',
                'regularization_date' => null,
                'employment_status' => 'probationary',
                'performance_rating' => 'Outstanding',
                'daily_rate' => 1076.92,
                'monthly_rate' => 28000.00,
                'current_step' => 1,
                'step_status' => 'normal',
                'payment_mode' => 'bank',
                'bank_name' => 'Security Bank Corporation',
                'bank_account_number' => '1234-5678-9017',
                'bank_account_no' => 'SBC-123456789017',
            ],
            [
                'employee_code' => 'EMP-1008',
                'first_name' => 'Miguel',
                'last_name' => 'Alvarado',
                'email' => 'miguel.alvarado@tripwise.com',
                'department_id' => $fleetDept->id,
                'position' => 'Fleet Coordinator',
                'hire_date' => '2026-05-01',
                'regularization_date' => null,
                'employment_status' => 'probationary',
                'performance_rating' => 'Satisfactory',
                'daily_rate' => 923.08,
                'monthly_rate' => 24000.00,
                'current_step' => 1,
                'step_status' => 'normal',
                'payment_mode' => 'bank',
                'bank_name' => 'Security Bank Corporation',
                'bank_account_number' => '2345-6789-0128',
                'bank_account_no' => 'SBC-234567890128',
            ],
            [
                'employee_code' => 'EMP-1009',
                'first_name' => 'Rowena',
                'last_name' => 'Cabrera',
                'email' => 'rowena.cabrera@tripwise.com',
                'department_id' => $adminDept->id,
                'position' => 'Admin Assistant',
                'hire_date' => '2026-06-01',
                'regularization_date' => null,
                'employment_status' => 'probationary',
                'performance_rating' => 'Satisfactory',
                'daily_rate' => 769.23,
                'monthly_rate' => 20000.00,
                'current_step' => 1,
                'step_status' => 'normal',
                'payment_mode' => 'bank',
                'bank_name' => 'Security Bank Corporation',
                'bank_account_number' => '3456-7890-1239',
                'bank_account_no' => 'SBC-345678901239',
            ],
            [
                'employee_code' => 'EMP-1010',
                'first_name' => 'Dennis',
                'last_name' => 'Ferrer',
                'email' => 'dennis.ferrer@tripwise.com',
                'department_id' => $dispatchDept->id,
                'position' => 'Routing Analyst',
                'hire_date' => '2026-06-15',
                'regularization_date' => null,
                'employment_status' => 'probationary',
                'performance_rating' => 'Needs Improvement',
                'daily_rate' => 961.54,
                'monthly_rate' => 25000.00,
                'current_step' => 1,
                'step_status' => 'normal',
                'payment_mode' => 'bank',
                'bank_name' => 'Security Bank Corporation',
                'bank_account_number' => '4567-8901-2350',
                'bank_account_no' => 'SBC-456789012350',
            ],

            // --- 5 TNVS DRIVERS ---
            [
                'employee_code' => 'EMP-1011',
                'first_name' => 'Eduardo',
                'last_name' => 'Ramos',
                'email' => 'eduardo.ramos@tripwise.com',
                'department_id' => $fleetDept->id,
                'position' => 'TNVS Senior Driver',
                'hire_date' => '2022-04-10',
                'regularization_date' => '2022-10-10',
                'employment_status' => 'regular',
                'performance_rating' => 'Outstanding',
                'daily_rate' => 1346.15,
                'monthly_rate' => 35000.00,
                'current_step' => 2,
                'step_status' => 'normal',
                'payment_mode' => 'bank',
                'bank_name' => 'Security Bank Corporation',
                'bank_account_number' => '0012-3456-7811',
                'bank_account_no' => 'SBC-001234567811',
            ],
            [
                'employee_code' => 'EMP-1012',
                'first_name' => 'Benedicto',
                'last_name' => 'Soriano',
                'email' => 'benedicto.soriano@tripwise.com',
                'department_id' => $fleetDept->id,
                'position' => 'TNVS Senior Driver',
                'hire_date' => '2023-02-18',
                'regularization_date' => '2023-08-18',
                'employment_status' => 'regular',
                'performance_rating' => 'Very Satisfactory',
                'daily_rate' => 1230.77,
                'monthly_rate' => 32000.00,
                'current_step' => 2,
                'step_status' => 'normal',
                'payment_mode' => 'bank',
                'bank_name' => 'Security Bank Corporation',
                'bank_account_number' => '0917-123-4567',
                'bank_account_no' => 'SBC-09171234567',
            ],
            [
                'employee_code' => 'EMP-1013',
                'first_name' => 'Virgilio',
                'last_name' => 'Marquez',
                'email' => 'virgilio.marquez@tripwise.com',
                'department_id' => $fleetDept->id,
                'position' => 'TNVS Junior Driver',
                'hire_date' => '2024-03-01',
                'regularization_date' => '2024-09-01',
                'employment_status' => 'regular',
                'performance_rating' => 'Satisfactory',
                'daily_rate' => 961.54,
                'monthly_rate' => 25000.00,
                'current_step' => 1,
                'step_status' => 'normal',
                'payment_mode' => 'cash',
                'bank_name' => null,
                'bank_account_number' => null,
                'bank_account_no' => null,
            ],
            [
                'employee_code' => 'EMP-1014',
                'first_name' => 'Feliciano',
                'last_name' => 'Torres',
                'email' => 'feliciano.torres@tripwise.com',
                'department_id' => $fleetDept->id,
                'position' => 'TNVS Junior Driver',
                'hire_date' => '2026-05-15',
                'regularization_date' => null,
                'employment_status' => 'probationary',
                'performance_rating' => 'Satisfactory',
                'daily_rate' => 846.15,
                'monthly_rate' => 22000.00,
                'current_step' => 1,
                'step_status' => 'normal',
                'payment_mode' => 'cash',
                'bank_name' => null,
                'bank_account_number' => null,
                'bank_account_no' => null,
            ],
            [
                'employee_code' => 'EMP-1015',
                'first_name' => 'Natividad',
                'last_name' => 'Aguilar',
                'email' => 'natividad.aguilar@tripwise.com',
                'department_id' => $fleetDept->id,
                'position' => 'TNVS Junior Driver',
                'hire_date' => '2026-04-01',
                'regularization_date' => null,
                'employment_status' => 'probationary',
                'performance_rating' => 'Very Satisfactory',
                'daily_rate' => 923.08,
                'monthly_rate' => 24000.00,
                'current_step' => 1,
                'step_status' => 'normal',
                'payment_mode' => 'bank',
                'bank_name' => 'BPI',
                'bank_account_number' => '1234-5678-9015',
                'bank_account_no' => 'BPI-123456789015',
            ],
        ];

        $createdEmployees = collect();
        foreach ($employeeData as $data) {
            $emp = Employee::updateOrCreate(
                ['employee_code' => $data['employee_code']],
                $data
            );
            $createdEmployees->push($emp);
        }

        // 3. For each employee, seed attendance, trip income, performance bonus, and calculated salary
        $cutoff = '2026-07-01_15';
        foreach ($createdEmployees as $employee) {
            $daysWorked = rand(9, 11);

            Attendance::updateOrCreate(
                ['employee_id' => $employee->id, 'cutoff_period' => $cutoff],
                [
                    'days_worked' => $daysWorked,
                    'lates_count' => rand(0, 1),
                ]
            );

            $isDriver = str_contains($employee->position, 'Driver');
            $tripEarnings = $isDriver ? rand(25, 45) * 150.00 : 0.00;
            if ($isDriver) {
                TripIncome::updateOrCreate(
                    ['employee_id' => $employee->id, 'cutoff_period' => $cutoff],
                    [
                        'total_trips' => (int) ($tripEarnings / 150),
                        'total_trip_earnings' => $tripEarnings,
                    ]
                );
            }

            $bonusAmount = ($employee->performance_rating === 'Outstanding') ? 1500.00 : (($employee->performance_rating === 'Very Satisfactory') ? 1000.00 : 0.00);
            if ($bonusAmount > 0) {
                PerformanceBonus::updateOrCreate(
                    ['employee_id' => $employee->id, 'cutoff_period' => $cutoff],
                    [
                        'bonus_amount' => $bonusAmount,
                        'reason' => 'High performance rating (' . $employee->performance_rating . ')',
                    ]
                );
            }

            // Seed mock claims (Expense, Incentive, Maternity)
            Claim::updateOrCreate(
                ['employee_id' => $employee->id, 'cutoff_period' => $cutoff, 'type' => 'expense'],
                [
                    'amount' => rand(300, 1800),
                    'description' => 'Vehicle Fuel & Toll Reimbursement',
                    'receipt_number' => 'RCP-' . rand(10000, 99999),
                    'status' => 'approved',
                    'effective_date' => now(),
                ]
            );

            if ($isDriver) {
                Claim::updateOrCreate(
                    ['employee_id' => $employee->id, 'cutoff_period' => $cutoff, 'type' => 'incentive'],
                    [
                        'amount' => rand(1500, 3500),
                        'description' => 'Peak Hours High Efficiency Driver Incentive',
                        'receipt_number' => 'INC-' . rand(10000, 99999),
                        'status' => 'approved',
                        'effective_date' => now(),
                    ]
                );
            }

            // Seed HMO Enrollments
            $hmoPlan = $isDriver ? 'InLife Fleet Protect' : (str_contains($employee->position, 'Supervisor') ? 'Maxicard Gold' : 'Intellicare Silver');
            $mbl = $hmoPlan === 'Maxicard Gold' ? 250000.00 : ($hmoPlan === 'Intellicare Silver' ? 150000.00 : 100000.00);

            HmoEnrollment::updateOrCreate(
                ['employee_id' => $employee->id],
                [
                    'hmo_card_number' => rand(1000, 9999) . '-' . rand(1000, 9999) . '-' . rand(1000, 9999),
                    'provider_plan' => $hmoPlan,
                    'mbl_amount' => $mbl,
                    'status' => 'active',
                ]
            );

            // Seed Driver Accident Claims
            if ($isDriver && in_array($employee->employee_code, ['EMP-1011', 'EMP-1014'])) {
                AccidentClaim::updateOrCreate(
                    ['employee_id' => $employee->id],
                    [
                        'incident_number' => 'INCIDENT-' . rand(1000, 9999),
                        'description' => 'Minor vehicle collision assistance during delivery',
                        'bill_amount' => 12500.00,
                        'status' => 'paid',
                    ]
                );
            }

            $basePay = $isDriver ? 0.00 : ($employee->monthly_rate / 2);
            $grossPay = $basePay + $tripEarnings + $bonusAmount;

            $sss = $isDriver ? 0.00 : min(1350.00, round($grossPay * 0.045, 2));
            $philhealth = $isDriver ? 0.00 : min(1250.00, round($grossPay * 0.025, 2));
            $pagibig = $isDriver ? 0.00 : 100.00;
            $hmoDeduction = 0.00;
            $platformFee = $isDriver ? round($tripEarnings * 0.20, 2) : 0.00;

            $taxableIncome = $isDriver ? 0.00 : max(0.00, $grossPay - ($sss + $philhealth + $pagibig));
            $withholdingTax = $isDriver ? 0.00 : (($taxableIncome > 10416.67) ? round(($taxableIncome - 10416.67) * 0.15, 2) : 0.00);

            $totalDeductions = $sss + $philhealth + $pagibig + $hmoDeduction + $platformFee + $withholdingTax;
            $reimbursements = (float) Claim::where('employee_id', $employee->id)->where('cutoff_period', $cutoff)->where('type', 'expense')->sum('amount');
            $netPay = ($grossPay - $totalDeductions) + $reimbursements;

            $comp = SalaryComputation::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'cutoff_period' => $cutoff,
                ],
                [
                    'base_pay' => $basePay,
                    'trip_earnings' => $tripEarnings,
                    'performance_bonus' => $bonusAmount,
                    'reimbursements' => $reimbursements,
                    'gross_pay' => $grossPay,
                    'sss_deduction' => $sss,
                    'philhealth_deduction' => $philhealth,
                    'pagibig_deduction' => $pagibig,
                    'hmo_insurance_deduction' => $hmoDeduction,
                    'platform_fee_deduction' => $platformFee,
                    'withholding_tax' => $withholdingTax,
                    'total_deductions' => $totalDeductions,
                    'net_pay' => $netPay,
                    'status' => 'pending_approval',
                ]
            );

            app(GroqAiComplianceService::class)->analyzeCompliance($comp);
        }

        // 4. Seed Compensation Sub-modules Mock Scenarios (Counter Offers & Merit Promotions)
        $this->call(CounterOfferSeeder::class);
        $this->call(MeritPromotionSeeder::class);
        $this->call(EmployeeLoanSeeder::class);

        // 5. Seed Claims & Reimbursement Predefined Categories and Workflows
        $this->call(ClaimCategorySeeder::class);

        // 6. Seed Financial Budget Requisitions
        BudgetRequisition::updateOrCreate(
            ['requisition_code' => 'REQ-2026-081'],
            [
                'category' => 'Q3 HMO Provider Premiums',
                'amount' => 450000.00,
                'justification' => 'Annual corporate healthcare provider premium allocation for all staff.',
                'status' => 'approved',
            ]
        );

        BudgetRequisition::updateOrCreate(
            ['requisition_code' => 'REQ-2026-094'],
            [
                'category' => 'Driver Accident Emergency Pool Top-Up',
                'amount' => 100000.00,
                'justification' => 'Emergency fund top-up for active driver fleet coverage.',
                'status' => 'awaiting_approval',
            ]
        );
    }
}
