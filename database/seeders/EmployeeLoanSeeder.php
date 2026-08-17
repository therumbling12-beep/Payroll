<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeLoan;
use Illuminate\Database\Seeder;

class EmployeeLoanSeeder extends Seeder
{
    /**
     * Seed realistic employee loans across SSS, Pag-IBIG, and Company Emergency categories.
     */
    public function run(): void
    {
        $employees = Employee::all()->keyBy('email');

        $loans = [
            [
                'email' => 'luisa.bautista@tripwise.com',
                'loan_type' => 'sss_salary_loan',
                'reference_no' => 'SSS-SL-2026-0012',
                'principal_amount' => 20000.00,
                'total_amount_due' => 22000.00,
                'term_months' => 24,
                'semi_monthly_amortization' => 458.33,
                'total_paid' => 3666.64,
                'remaining_balance' => 18333.36,
                'start_date' => '2026-01-01',
                'end_date' => '2027-12-31',
                'status' => 'active',
            ],
            [
                'email' => 'marco.santos@tripwise.com',
                'loan_type' => 'hdmf_multi_purpose_loan',
                'reference_no' => 'HDMF-MPL-2026-0045',
                'principal_amount' => 15000.00,
                'total_amount_due' => 16500.00,
                'term_months' => 12,
                'semi_monthly_amortization' => 687.50,
                'total_paid' => 5500.00,
                'remaining_balance' => 11000.00,
                'start_date' => '2026-02-01',
                'end_date' => '2027-01-31',
                'status' => 'active',
            ],
            [
                'email' => 'danilo.reyes@tripwise.com',
                'loan_type' => 'company_emergency_loan',
                'reference_no' => 'PR-ADV-2026-0089',
                'principal_amount' => 5000.00,
                'total_amount_due' => 5000.00,
                'term_months' => 6,
                'semi_monthly_amortization' => 416.67,
                'total_paid' => 1666.68,
                'remaining_balance' => 3333.32,
                'start_date' => '2026-03-01',
                'end_date' => '2026-08-31',
                'status' => 'active',
            ],
            [
                'email' => 'elena.bautista@tripwise.com',
                'loan_type' => 'hdmf_housing_loan',
                'reference_no' => 'HDMF-HL-2026-0312',
                'principal_amount' => 120000.00,
                'total_amount_due' => 144000.00,
                'term_months' => 36,
                'semi_monthly_amortization' => 2000.00,
                'total_paid' => 24000.00,
                'remaining_balance' => 120000.00,
                'start_date' => '2025-06-01',
                'end_date' => '2028-05-31',
                'status' => 'active',
            ],
        ];

        foreach ($loans as $loanData) {
            $email = $loanData['email'];
            unset($loanData['email']);

            $emp = $employees->get($email) ?? Employee::first();
            if ($emp) {
                $loanData['employee_id'] = $emp->id;
                EmployeeLoan::updateOrCreate(
                    ['reference_no' => $loanData['reference_no']],
                    $loanData
                );
            }
        }
    }
}
