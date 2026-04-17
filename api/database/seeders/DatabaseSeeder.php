<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminRoleSeeder::class,
            PlatformConfigSeeder::class,
            FeatureFlagSeeder::class,
            TaxiCategorySeeder::class
