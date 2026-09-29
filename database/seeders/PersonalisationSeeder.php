<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Helpers\ServiceManager;

class PersonalisationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ServiceManager::getServiceWithFields('NIN Personalisation', [
            [
                'name'  => 'NIN Personalisation',
                'code'  => '005',
                'price' => 1500,
            ],
        ]);
    }
}
