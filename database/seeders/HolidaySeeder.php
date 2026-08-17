<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $holidays = [
            // Regular Holidays (200% worked, 100% unworked)
            [
                'name' => "New Year's Day",
                'holiday_date' => '2026-01-01',
                'holiday_type' => 'regular',
                'proclamation_number' => 'Proclamation No. 368',
                'year' => 2026,
            ],
            [
                'name' => 'Maundy Thursday',
                'holiday_date' => '2026-04-02',
                'holiday_type' => 'regular',
                'proclamation_number' => 'Proclamation No. 368',
                'year' => 2026,
            ],
            [
                'name' => 'Good Friday',
                'holiday_date' => '2026-04-03',
                'holiday_type' => 'regular',
                'proclamation_number' => 'Proclamation No. 368',
                'year' => 2026,
            ],
            [
                'name' => 'Araw ng Kagitingan',
                'holiday_date' => '2026-04-09',
                'holiday_type' => 'regular',
                'proclamation_number' => 'Proclamation No. 368',
                'year' => 2026,
            ],
            [
                'name' => 'Labor Day',
                'holiday_date' => '2026-05-01',
                'holiday_type' => 'regular',
                'proclamation_number' => 'Proclamation No. 368',
                'year' => 2026,
            ],
            [
                'name' => 'Independence Day',
                'holiday_date' => '2026-06-12',
                'holiday_type' => 'regular',
                'proclamation_number' => 'Proclamation No. 368',
                'year' => 2026,
            ],
            [
                'name' => 'National Heroes Day',
                'holiday_date' => '2026-08-31',
                'holiday_type' => 'regular',
                'proclamation_number' => 'Proclamation No. 368',
                'year' => 2026,
            ],
            [
                'name' => 'Bonifacio Day',
                'holiday_date' => '2026-11-30',
                'holiday_type' => 'regular',
                'proclamation_number' => 'Proclamation No. 368',
                'year' => 2026,
            ],
            [
                'name' => 'Christmas Day',
                'holiday_date' => '2026-12-25',
                'holiday_type' => 'regular',
                'proclamation_number' => 'Proclamation No. 368',
                'year' => 2026,
            ],
            [
                'name' => 'Rizal Day',
                'holiday_date' => '2026-12-30',
                'holiday_type' => 'regular',
                'proclamation_number' => 'Proclamation No. 368',
                'year' => 2026,
            ],

            // Special Non-Working Days (130% worked, 0% unworked)
            [
                'name' => 'Chinese New Year',
                'holiday_date' => '2026-02-17',
                'holiday_type' => 'special_non_working',
                'proclamation_number' => 'Proclamation No. 368',
                'year' => 2026,
            ],
            [
                'name' => 'Black Saturday',
                'holiday_date' => '2026-04-04',
                'holiday_type' => 'special_non_working',
                'proclamation_number' => 'Proclamation No. 368',
                'year' => 2026,
            ],
            [
                'name' => 'Ninoy Aquino Day',
                'holiday_date' => '2026-08-21',
                'holiday_type' => 'special_non_working',
                'proclamation_number' => 'Proclamation No. 368',
                'year' => 2026,
            ],
            [
                'name' => "All Saints' Day",
                'holiday_date' => '2026-11-01',
                'holiday_type' => 'special_non_working',
                'proclamation_number' => 'Proclamation No. 368',
                'year' => 2026,
            ],
            [
                'name' => "All Souls' Day",
                'holiday_date' => '2026-11-02',
                'holiday_type' => 'special_non_working',
                'proclamation_number' => 'Proclamation No. 368',
                'year' => 2026,
            ],
            [
                'name' => 'Feast of the Immaculate Conception',
                'holiday_date' => '2026-12-08',
                'holiday_type' => 'special_non_working',
                'proclamation_number' => 'Proclamation No. 368',
                'year' => 2026,
            ],
            [
                'name' => 'Christmas Eve',
                'holiday_date' => '2026-12-24',
                'holiday_type' => 'special_non_working',
                'proclamation_number' => 'Proclamation No. 368',
                'year' => 2026,
            ],
            [
                'name' => 'Last Day of the Year',
                'holiday_date' => '2026-12-31',
                'holiday_type' => 'special_non_working',
                'proclamation_number' => 'Proclamation No. 368',
                'year' => 2026,
            ],
        ];

        foreach ($holidays as $data) {
            Holiday::updateOrCreate(
                ['holiday_date' => $data['holiday_date']],
                [
                    'name' => $data['name'],
                    'holiday_type' => $data['holiday_type'],
                    'proclamation_number' => $data['proclamation_number'],
                    'year' => $data['year'],
                    'is_active' => true,
                ]
            );
        }
    }
}
