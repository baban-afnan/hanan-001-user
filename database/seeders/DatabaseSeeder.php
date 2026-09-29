<?php

namespace Database\Seeders;

use App\Models\ClaimCount;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
     public function run(): void
    {

        User::updateOrCreate(
            ['email' => 'admin@hanan.com.ng'],
            [
                'name' => 'HANAN ADMIN',
                'email_verified_at' => now(),
                'password' => Hash::make('@passwd12345'),
                'role' => 'admin',
            ]
        );

        if (!SiteSetting::exists()) {
            SiteSetting::factory(1)->create();
        }

        foreach (Service::factory()->withCustomData() as $data) {
            // Skip the service if it already exists; do not update
            if (Service::where('service_code', $data['service_code'])->exists()) {
                continue;
            }
            Service::create($data);
        }

        if (!ClaimCount::exists()) {
            ClaimCount::factory(1)->create();
        }

        $this->call([
            ReferralBonusTableSeeder::class,
            CrmSeeder::class,
            ServiceSeeder::class,
            PersonalisationSeeder::class,
        ]);
    }
}
