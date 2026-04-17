<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlatformConfigSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['category' => 'branding', 'key' => 'app_name', 'value' => 'Grabber'],
            ['category' => 'branding', 'key' => 'company_name', 'value' => 'Grabber Mobility Solutions Pvt Ltd'],
            ['category' => 'branding', 'key' => 'website', 'value' => 'https://grabber.lk'],
            ['category' => 'commissions', 'key' => 'commission_stays', 'value' => '0.12'],
            ['category' => 'commissions', 'key' => 'commission_vehicles', 'value' => '0.10'],
            ['category' => 'commissions', 'key' => 'commission_taxi', 'value' => '0.15'],
            ['category' => 'payments', 'key' => 'card_enabled', 'value' => 'true'],
            ['category' => 'payments', 'key' => 'bank_transfer_enabled', 'value' => 'true'],
            ['category' => 'payments', 'key' => 'cash_enabled', 'value' => 'true'],
            ['category' => 'tax', 'key' => 'vat_rate', 'value' => '0.18'],
            ['category' => 'tax', 'key' => 'tdl_rate', 'value' => '0.01'],
            ['category' => 'pearl_points', 'key' => 'earn_per_100_lkr', 'value' => '1'],
        ];

        foreach ($rows as $row) {
            DB::table('platform_config')->updateOrInsert(
                ['category' => $row['category'], 'key' => $row['key']],
                ['value' => $row['value'], 'updated_at' => now()]
            );
        }
    }
}
