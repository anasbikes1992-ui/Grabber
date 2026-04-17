<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaxiCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Economy', 'base_fare' => 300, 'per_km_rate' => 80, 'per_min_rate' => 15, 'min_fare' => 300, 'max_capacity' => 4],
            ['name' => 'Comfort', 'base_fare' => 350, 'per_km_rate' => 95, 'per_min_rate' => 18, 'min_fare' => 350, 'max_capacity' => 4],
            ['name' => 'SUV/4WD', 'base_fare' => 500, 'per_km_rate' => 130, 'per_min_rate' => 24, 'min_fare' => 500, 'max_capacity' => 6],
            ['name' => 'Tuk-tuk', 'base_fare' => 250, 'per_km_rate' => 70, 'per_min_rate' => 12, 'min_fare' => 250, 'max_capacity' => 3],
        ];

        foreach ($categories as $category) {
            DB::table('taxi_categories')->updateOrInsert(
                ['name' => $category['name']],
                $category + ['active' => true, 'surge_enabled' => true, 'created_at' => now()]
            );
        }
    }
}
