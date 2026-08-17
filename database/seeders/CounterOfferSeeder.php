<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CompensationAdjustment;
use App\Models\Employee;
use Illuminate\Database\Seeder;

class CounterOfferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employees = Employee::with('department')->take(5)->get();

        // 1. Seed existing employee counter offers (retention cases)
        $employeeScenarios = [
            [
                'competitor' => 'Grab Philippines (Fleet Logistics)',
                'their_offer' => 42000.00,
                'counter_rate' => 45000.00,
                'bonus' => 5000.00,
                'urgency' => 2, // critical
                'status' => 'pending',
                'reason' => 'Counter-offer to retain key operations dispatcher receiving aggressive poaching offer.',
            ],
            [
                'competitor' => 'JoyRide TNVS Transport',
                'their_offer' => 32000.00,
                'counter_rate' => 34000.00,
                'bonus' => 2500.00,
                'urgency' => 4, // soon
                'status' => 'pending',
                'reason' => 'Senior driver retention match to maintain fleet peak-hour coverage reliability.',
            ],
            [
                'competitor' => 'Lalamove Enterprise Dispatch',
                'their_offer' => 48000.00,
                'counter_rate' => 50000.00,
                'bonus' => 7500.00,
                'urgency' => 1, // critical
                'status' => 'approved',
                'reason' => 'Fleet Supervisor retention package accepted. Matched competitor rate + retention bonus.',
            ],
            [
                'competitor' => 'Angkas Logistics Corp',
                'their_offer' => 36000.00,
                'counter_rate' => 38000.00,
                'bonus' => 3000.00,
                'urgency' => 6, // open
                'status' => 'pending',
                'reason' => 'HR Specialist competitive counter match against competitor expansion offer.',
            ],
            [
                'competitor' => 'FastCab Express',
                'their_offer' => 30000.00,
                'counter_rate' => 31500.00,
                'bonus' => 0.00,
                'urgency' => 7, // open
                'status' => 'pending',
                'reason' => 'Market rate alignment to retain dispatch coordinator.',
            ],
        ];

        foreach ($employees as $idx => $emp) {
            if (isset($employeeScenarios[$idx])) {
                $sc = $employeeScenarios[$idx];
                $isDriver = str_contains($emp->position, 'Driver');
                $oldRate = (float) ($isDriver ? ($emp->daily_rate * 26) : $emp->monthly_rate);

                CompensationAdjustment::create([
                    'employee_id' => $emp->id,
                    'subject_type' => 'employee',
                    'applicant_name' => null,
                    'applicant_position' => null,
                    'type' => 'counter_offer',
                    'old_rate' => $oldRate > 0 ? $oldRate : 30000.00,
                    'new_rate' => $sc['counter_rate'],
                    'bonus_amount' => $sc['bonus'],
                    'old_position' => $emp->position,
                    'new_position' => $emp->position,
                    'competitor_company' => $sc['competitor'],
                    'competitor_offer' => $sc['their_offer'],
                    'urgency_days' => $sc['urgency'],
                    'reason' => $sc['reason'],
                    'status' => $sc['status'],
                    'effective_date' => now(),
                ]);
            }
        }

        // 2. Seed external applicant counter offers (hiring packages)
        $applicantScenarios = [
            [
                'name' => 'Engr. Rafael Mendoza',
                'pos' => 'Fleet Supervisor',
                'competitor' => 'Transportify Logistics',
                'their_offer' => 52000.00,
                'counter_rate' => 55000.00,
                'bonus' => 8000.00,
                'urgency' => 3,
                'status' => 'pending',
                'reason' => 'Senior fleet engineering candidate with 8 yrs logistics experience. Countered competitor package.',
            ],
            [
                'name' => 'Clarissa Villanueva',
                'pos' => 'Operations Dispatcher',
                'competitor' => 'Ninja Van PH',
                'their_offer' => 35000.00,
                'counter_rate' => 37500.00,
                'bonus' => 3500.00,
                'urgency' => 2,
                'status' => 'pending',
                'reason' => 'Top candidate dispatch specialist. Matched external offer with signing incentive.',
            ],
            [
                'name' => 'Michael Angelo Reyes',
                'pos' => 'TNVS Senior Driver',
                'competitor' => 'Apex Fleet Services',
                'their_offer' => 28000.00,
                'counter_rate' => 30000.00,
                'bonus' => 2000.00,
                'urgency' => 5,
                'status' => 'approved',
                'reason' => 'Candidate accepted offer. Validated clean driving record and certifications.',
            ],
        ];

        foreach ($applicantScenarios as $app) {
            CompensationAdjustment::create([
                'employee_id' => null,
                'subject_type' => 'applicant',
                'applicant_name' => $app['name'],
                'applicant_position' => $app['pos'],
                'type' => 'counter_offer',
                'old_rate' => 0.00,
                'new_rate' => $app['counter_rate'],
                'bonus_amount' => $app['bonus'],
                'old_position' => $app['pos'],
                'new_position' => $app['pos'],
                'competitor_company' => $app['competitor'],
                'competitor_offer' => $app['their_offer'],
                'urgency_days' => $app['urgency'],
                'reason' => $app['reason'],
                'status' => $app['status'],
                'effective_date' => now(),
            ]);
        }
    }
}
