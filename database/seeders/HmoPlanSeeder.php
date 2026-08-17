<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AccreditedFacility;
use App\Models\CompanySetting;
use App\Models\HmoGradeLimit;
use Illuminate\Database\Seeder;

class HmoPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed HMO Grade Limits (known.md §8.5)
        $gradeLimits = [
            [
                'grade_min' => 1,
                'grade_max' => 2,
                'title' => 'PG-1 to PG-2 (Drivers / Fleet Entry)',
                'mbl_amount' => 100000.00,
                'room_and_board' => 'semi_private',
                'max_dependents' => 0,
                'dependent_premium_coshare_pct' => 100.00,
                'is_active' => true,
            ],
            [
                'grade_min' => 3,
                'grade_max' => 4,
                'title' => 'PG-3 to PG-4 (Specialists / Dispatchers)',
                'mbl_amount' => 150000.00,
                'room_and_board' => 'semi_private',
                'max_dependents' => 1,
                'dependent_premium_coshare_pct' => 50.00,
                'is_active' => true,
            ],
            [
                'grade_min' => 5,
                'grade_max' => 5,
                'title' => 'PG-5 (Supervisors / Team Leads)',
                'mbl_amount' => 200000.00,
                'room_and_board' => 'private',
                'max_dependents' => 2,
                'dependent_premium_coshare_pct' => 25.00,
                'is_active' => true,
            ],
            [
                'grade_min' => 6,
                'grade_max' => 6,
                'title' => 'PG-6 (Department Managers)',
                'mbl_amount' => 300000.00,
                'room_and_board' => 'private',
                'max_dependents' => 3,
                'dependent_premium_coshare_pct' => 0.00,
                'is_active' => true,
            ],
            [
                'grade_min' => 7,
                'grade_max' => 10,
                'title' => 'PG-7 and above (Executives / Directors)',
                'mbl_amount' => 500000.00,
                'room_and_board' => 'suite',
                'max_dependents' => 4,
                'dependent_premium_coshare_pct' => 0.00,
                'is_active' => true,
            ],
        ];

        foreach ($gradeLimits as $gl) {
            HmoGradeLimit::updateOrCreate(
                ['grade_min' => $gl['grade_min'], 'grade_max' => $gl['grade_max']],
                $gl
            );
        }

        // 2. Seed Accredited Facilities (known.md §8.11 Item 7)
        $facilities = [
            [
                'name' => 'St. Luke\'s Medical Center - Global City',
                'facility_type' => 'Hospital',
                'region' => 'NCR',
                'address' => '32nd St. cor. 5th Ave., Bonifacio Global City, Taguig City',
                'contact_number' => '(02) 8789-7700',
                'is_emergency_ready' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Makati Medical Center',
                'facility_type' => 'Hospital',
                'region' => 'NCR',
                'address' => '2 Amorsolo St., Legaspi Village, Makati City',
                'contact_number' => '(02) 8888-8999',
                'is_emergency_ready' => true,
                'is_active' => true,
            ],
            [
                'name' => 'The Medical City Ortigas',
                'facility_type' => 'Hospital',
                'region' => 'NCR',
                'address' => 'Ortigas Ave., Pasig City, Metro Manila',
                'contact_number' => '(02) 8988-1000',
                'is_emergency_ready' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Cardinal Santos Medical Center',
                'facility_type' => 'Hospital',
                'region' => 'NCR',
                'address' => '10 Wilson St., Greenhills West, San Juan City',
                'contact_number' => '(02) 8727-0001',
                'is_emergency_ready' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Asian Hospital and Medical Center',
                'facility_type' => 'Hospital',
                'region' => 'NCR',
                'address' => '2205 Civic Dr., Filinvest City, Alabang, Muntinlupa',
                'contact_number' => '(02) 8771-9000',
                'is_emergency_ready' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Cebu Doctors\' University Hospital',
                'facility_type' => 'Hospital',
                'region' => 'Region VII (Central Visayas)',
                'address' => 'Gov. M. Roa St., Capitol Site, Cebu City',
                'contact_number' => '(032) 255-5555',
                'is_emergency_ready' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Chong Hua Hospital Mandaue',
                'facility_type' => 'Hospital',
                'region' => 'Region VII (Central Visayas)',
                'address' => 'Mantawi International Dr., Subangdaku, Mandaue City',
                'contact_number' => '(032) 233-8000',
                'is_emergency_ready' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Davao Doctors Hospital',
                'facility_type' => 'Hospital',
                'region' => 'Region XI (Davao)',
                'address' => '118 E. Quirino Ave., Davao City',
                'contact_number' => '(082) 222-8000',
                'is_emergency_ready' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Hi-Precision Diagnostics Plus - BGC',
                'facility_type' => 'Diagnostic Center',
                'region' => 'NCR',
                'address' => '26th St., Bonifacio Global City, Taguig',
                'contact_number' => '(02) 8802-9900',
                'is_emergency_ready' => false,
                'is_active' => true,
            ],
            [
                'name' => 'QualiMed Clinic - TriNoma',
                'facility_type' => 'Clinic',
                'region' => 'NCR',
                'address' => 'Level 1 TriNoma Mall, EDSA cor. North Ave., Quezon City',
                'contact_number' => '(02) 7901-0900',
                'is_emergency_ready' => false,
                'is_active' => true,
            ],
        ];

        foreach ($facilities as $facility) {
            AccreditedFacility::updateOrCreate(
                ['name' => $facility['name']],
                $facility
            );
        }

        // 3. Seed HMO Policy Configuration (known.md §8.4)
        $hmoSettings = [
            'hmo_has_provider' => '1',
            'hmo_provider_name' => 'Maxicare Healthcare Corporation',
            'hmo_plan_type' => 'Comprehensive',
            'hmo_premium_shoulder_type' => 'shared', // company, shared
            'hmo_company_share_pct' => '80',
            'hmo_employee_share_pct' => '20',
            'hmo_coverage_start_months' => '6',
            'hmo_dependent_coverage' => '1',
            'hmo_max_dependents' => '4',
            'hmo_base_employee_premium' => '1800.00',
            'hmo_base_dependent_premium' => '1200.00',
        ];

        foreach ($hmoSettings as $key => $val) {
            CompanySetting::updateOrCreate(
                ['key' => $key],
                ['value' => $val, 'description' => 'HMO Policy Configuration']
            );
        }
    }
}
