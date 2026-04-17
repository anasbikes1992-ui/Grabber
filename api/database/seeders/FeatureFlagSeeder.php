<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeatureFlagSeeder extends Seeder
{
    public function run(): void
    {
        $flags = [
            'service.stays.enabled',
            'service.vehicles.enabled',
            'service.taxi.enabled',
            'service.events.enabled',
            'service.experiences.enabled',
            'service.properties.enabled',
            'service.social.enabled',
            'service.sme.enabled',
            'service.flash_deals.enabled',
            'payment.card.enabled',
            'payment.bank_transfer.enabled',
            'payment.cash.enabled',
        ];

        foreach ($flags as $key) {
            DB::table('feature_flags')->updateOrInsert(
                ['key' => $key],
                ['enabled' => true, 'description' => 'Seeded in Sprint 0', 'updated_at' => now()]
            );
        }
    }
}
