<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\GovernmentContributionTable;
use App\Models\MinimumWageOrder;
use Illuminate\Database\Seeder;

class GovernmentContributionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ─────────────────────────────────────────────────────────────
        // 1. SSS Circular No. 2024-006 Brackets (2025–2026 Rate Schedule)
        // Rate: Employee 5%, Employer 10%, EC ₱10/₱30
        // ─────────────────────────────────────────────────────────────
        GovernmentContributionTable::where('table_type', 'SSS')->delete();

        $sssBrackets = [
            ['from' => 0.00, 'to' => 5249.99, 'msc' => 5000.00, 'ec' => 10.00],
            ['from' => 5250.00, 'to' => 5749.99, 'msc' => 5500.00, 'ec' => 10.00],
            ['from' => 5750.00, 'to' => 6249.99, 'msc' => 6000.00, 'ec' => 10.00],
            ['from' => 6250.00, 'to' => 6749.99, 'msc' => 6500.00, 'ec' => 10.00],
            ['from' => 6750.00, 'to' => 7249.99, 'msc' => 7000.00, 'ec' => 10.00],
            ['from' => 7250.00, 'to' => 7749.99, 'msc' => 7500.00, 'ec' => 10.00],
            ['from' => 7750.00, 'to' => 8249.99, 'msc' => 8000.00, 'ec' => 10.00],
            ['from' => 8250.00, 'to' => 8749.99, 'msc' => 8500.00, 'ec' => 10.00],
            ['from' => 8750.00, 'to' => 9249.99, 'msc' => 9000.00, 'ec' => 10.00],
            ['from' => 9250.00, 'to' => 9749.99, 'msc' => 9500.00, 'ec' => 10.00],
            ['from' => 9750.00, 'to' => 10249.99, 'msc' => 10000.00, 'ec' => 10.00],
            ['from' => 10250.00, 'to' => 10749.99, 'msc' => 10500.00, 'ec' => 10.00],
            ['from' => 10750.00, 'to' => 11249.99, 'msc' => 11000.00, 'ec' => 10.00],
            ['from' => 11250.00, 'to' => 11749.99, 'msc' => 11500.00, 'ec' => 10.00],
            ['from' => 11750.00, 'to' => 12249.99, 'msc' => 12000.00, 'ec' => 10.00],
            ['from' => 12250.00, 'to' => 12749.99, 'msc' => 12500.00, 'ec' => 10.00],
            ['from' => 12750.00, 'to' => 13249.99, 'msc' => 13000.00, 'ec' => 10.00],
            ['from' => 13250.00, 'to' => 13749.99, 'msc' => 13500.00, 'ec' => 10.00],
            ['from' => 13750.00, 'to' => 14249.99, 'msc' => 14000.00, 'ec' => 10.00],
            ['from' => 14250.00, 'to' => 14749.99, 'msc' => 14500.00, 'ec' => 10.00],
            ['from' => 14750.00, 'to' => 15249.99, 'msc' => 15000.00, 'ec' => 30.00],
            ['from' => 15250.00, 'to' => 15749.99, 'msc' => 15500.00, 'ec' => 30.00],
            ['from' => 15750.00, 'to' => 16249.99, 'msc' => 16000.00, 'ec' => 30.00],
            ['from' => 16250.00, 'to' => 16749.99, 'msc' => 16500.00, 'ec' => 30.00],
            ['from' => 16750.00, 'to' => 17249.99, 'msc' => 17000.00, 'ec' => 30.00],
            ['from' => 17250.00, 'to' => 17749.99, 'msc' => 17500.00, 'ec' => 30.00],
            ['from' => 17750.00, 'to' => 18249.99, 'msc' => 18000.00, 'ec' => 30.00],
            ['from' => 18250.00, 'to' => 18749.99, 'msc' => 18500.00, 'ec' => 30.00],
            ['from' => 18750.00, 'to' => 19249.99, 'msc' => 19000.00, 'ec' => 30.00],
            ['from' => 19250.00, 'to' => 19749.99, 'msc' => 19500.00, 'ec' => 30.00],
            ['from' => 19750.00, 'to' => 20249.99, 'msc' => 20000.00, 'ec' => 30.00],
            ['from' => 20250.00, 'to' => 20749.99, 'msc' => 20500.00, 'ec' => 30.00],
            ['from' => 20750.00, 'to' => 21249.99, 'msc' => 21000.00, 'ec' => 30.00],
            ['from' => 21250.00, 'to' => 21749.99, 'msc' => 21500.00, 'ec' => 30.00],
            ['from' => 21750.00, 'to' => 22249.99, 'msc' => 22000.00, 'ec' => 30.00],
            ['from' => 22250.00, 'to' => 22749.99, 'msc' => 22500.00, 'ec' => 30.00],
            ['from' => 22750.00, 'to' => 23249.99, 'msc' => 23000.00, 'ec' => 30.00],
            ['from' => 23250.00, 'to' => 23749.99, 'msc' => 23500.00, 'ec' => 30.00],
            ['from' => 23750.00, 'to' => 24249.99, 'msc' => 24000.00, 'ec' => 30.00],
            ['from' => 24250.00, 'to' => 24749.99, 'msc' => 24500.00, 'ec' => 30.00],
            ['from' => 24750.00, 'to' => 25249.99, 'msc' => 25000.00, 'ec' => 30.00],
            ['from' => 25250.00, 'to' => 25749.99, 'msc' => 25500.00, 'ec' => 30.00],
            ['from' => 25750.00, 'to' => 26249.99, 'msc' => 26000.00, 'ec' => 30.00],
            ['from' => 26250.00, 'to' => 26749.99, 'msc' => 26500.00, 'ec' => 30.00],
            ['from' => 26750.00, 'to' => 27249.99, 'msc' => 27000.00, 'ec' => 30.00],
            ['from' => 27250.00, 'to' => 27749.99, 'msc' => 27500.00, 'ec' => 30.00],
            ['from' => 27750.00, 'to' => 28249.99, 'msc' => 28000.00, 'ec' => 30.00],
            ['from' => 28250.00, 'to' => 28749.99, 'msc' => 28500.00, 'ec' => 30.00],
            ['from' => 28750.00, 'to' => 29249.99, 'msc' => 29000.00, 'ec' => 30.00],
            ['from' => 29250.00, 'to' => 29749.99, 'msc' => 29500.00, 'ec' => 30.00],
            ['from' => 29750.00, 'to' => 30249.99, 'msc' => 30000.00, 'ec' => 30.00],
            ['from' => 30250.00, 'to' => 30749.99, 'msc' => 30500.00, 'ec' => 30.00],
            ['from' => 30750.00, 'to' => 31249.99, 'msc' => 31000.00, 'ec' => 30.00],
            ['from' => 31250.00, 'to' => 31749.99, 'msc' => 31500.00, 'ec' => 30.00],
            ['from' => 31750.00, 'to' => 32249.99, 'msc' => 32000.00, 'ec' => 30.00],
            ['from' => 32250.00, 'to' => 32749.99, 'msc' => 32500.00, 'ec' => 30.00],
            ['from' => 32750.00, 'to' => 33249.99, 'msc' => 33000.00, 'ec' => 30.00],
            ['from' => 33250.00, 'to' => 33749.99, 'msc' => 33500.00, 'ec' => 30.00],
            ['from' => 33750.00, 'to' => 34249.99, 'msc' => 34000.00, 'ec' => 30.00],
            ['from' => 34250.00, 'to' => 34749.99, 'msc' => 34500.00, 'ec' => 30.00],
            ['from' => 34750.00, 'to' => null, 'msc' => 35000.00, 'ec' => 30.00],
        ];

        foreach ($sssBrackets as $b) {
            $msc = $b['msc'];
            GovernmentContributionTable::create([
                'table_type' => 'SSS',
                'effective_year' => 2026,
                'bracket_from' => $b['from'],
                'bracket_to' => $b['to'],
                'monthly_salary_credit' => $msc,
                'employee_rate' => 0.0500,
                'employer_rate' => 0.1000,
                'employee_fixed_amount' => round($msc * 0.05, 2),
                'employer_fixed_amount' => round($msc * 0.10, 2),
                'ec_contribution' => $b['ec'],
            ]);
        }

        // ─────────────────────────────────────────────────────────────
        // 2. PhilHealth (Advisory PA2025-0002 — 5% Rate, ₱10k Floor, ₱100k Ceiling)
        // ─────────────────────────────────────────────────────────────
        GovernmentContributionTable::where('table_type', 'PHILHEALTH')->delete();
        GovernmentContributionTable::create([
            'table_type' => 'PHILHEALTH',
            'effective_year' => 2026,
            'bracket_from' => 10000.00,
            'bracket_to' => 100000.00,
            'employee_rate' => 0.0250,
            'employer_rate' => 0.0250,
            'employee_fixed_amount' => 250.00, // Floor amount (500 total / 2)
            'employer_fixed_amount' => 250.00,
        ]);

        // ─────────────────────────────────────────────────────────────
        // 3. Pag-IBIG / HDMF (HDMF Circular No. 460 — Max ₱200/mo EE / ₱200 ER)
        // ─────────────────────────────────────────────────────────────
        GovernmentContributionTable::where('table_type', 'PAGIBIG')->delete();
        GovernmentContributionTable::create([
            'table_type' => 'PAGIBIG',
            'effective_year' => 2026,
            'bracket_from' => 0.00,
            'bracket_to' => 1500.00,
            'employee_rate' => 0.0100,
            'employer_rate' => 0.0200,
            'employee_fixed_amount' => null,
            'employer_fixed_amount' => null,
        ]);
        GovernmentContributionTable::create([
            'table_type' => 'PAGIBIG',
            'effective_year' => 2026,
            'bracket_from' => 1500.01,
            'bracket_to' => null,
            'employee_rate' => 0.0200,
            'employer_rate' => 0.0200,
            'employee_fixed_amount' => 200.00, // Max monthly cap
            'employer_fixed_amount' => 200.00,
        ]);

        // ─────────────────────────────────────────────────────────────
        // 4. BIR Withholding Tax Table (TRAIN Law, RA 10963 Annualized)
        // ─────────────────────────────────────────────────────────────
        GovernmentContributionTable::where('table_type', 'BIR_TRAIN')->delete();

        $birBrackets = [
            ['from' => 0.00, 'to' => 250000.00, 'base_tax' => 0.00, 'excess_rate' => 0.0000],
            ['from' => 250000.01, 'to' => 400000.00, 'base_tax' => 0.00, 'excess_rate' => 0.1500],
            ['from' => 400000.01, 'to' => 800000.00, 'base_tax' => 22500.00, 'excess_rate' => 0.2000],
            ['from' => 800000.01, 'to' => 2000000.00, 'base_tax' => 102500.00, 'excess_rate' => 0.2500],
            ['from' => 2000000.01, 'to' => 8000000.00, 'base_tax' => 402500.00, 'excess_rate' => 0.3000],
            ['from' => 8000000.01, 'to' => null, 'base_tax' => 2202500.00, 'excess_rate' => 0.3500],
        ];

        foreach ($birBrackets as $b) {
            GovernmentContributionTable::create([
                'table_type' => 'BIR_TRAIN',
                'effective_year' => 2026,
                'bracket_from' => $b['from'],
                'bracket_to' => $b['to'],
                'base_tax' => $b['base_tax'],
                'excess_rate' => $b['excess_rate'],
            ]);
        }

        // ─────────────────────────────────────────────────────────────
        // 5. Minimum Wage Orders Reference
        // ─────────────────────────────────────────────────────────────
        MinimumWageOrder::query()->delete();

        MinimumWageOrder::create([
            'region_code' => 'NCR',
            'region_name' => 'National Capital Region',
            'wage_order_number' => 'NCR-27',
            'daily_rate' => 755.00,
            'monthly_rate_equivalent' => 19680.42, // DOLE factor: 755 * 313 / 12
            'effective_date' => '2026-07-25',
            'is_active' => true,
        ]);

        MinimumWageOrder::create([
            'region_code' => 'REGION_4A',
            'region_name' => 'CALABARZON',
            'wage_order_number' => 'RBIVA-20',
            'daily_rate' => 600.00,
            'monthly_rate_equivalent' => 15650.00,
            'effective_date' => '2025-10-01',
            'is_active' => true,
        ]);

        MinimumWageOrder::create([
            'region_code' => 'REGION_3',
            'region_name' => 'Central Luzon',
            'wage_order_number' => 'RBIII-24',
            'daily_rate' => 560.00,
            'monthly_rate_equivalent' => 14606.67,
            'effective_date' => '2025-10-15',
            'is_active' => true,
        ]);
    }
}
