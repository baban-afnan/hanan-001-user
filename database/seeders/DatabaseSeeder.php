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

        // Temporarily disable foreign key checks so truncating referenced tables
        // doesn't fail when other tables still have foreign key constraints.
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        SiteSetting::truncate();
        Service::truncate();
        ClaimCount::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');


       User::updateOrCreate(
        ['email' => 'admin@hanan.com.ng'],
        [
            'name' => 'HANAN ADMIN',
            'email_verified_at' => now(),
            'password' => Hash::make('@passwd12345'),
            'role'=>'admin',
        ]
       );

        SiteSetting::factory(1)->create();

        foreach (Service::factory()->withCustomData() as $data) {
            Service::create($data);
        }

        ClaimCount::factory(1)->create();

         $this->call([
                ReferralBonusTableSeeder::class,
           ]);
    }
}
