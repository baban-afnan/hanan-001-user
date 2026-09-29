<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Helpers\ServiceManager;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Keystone Bank
        ServiceManager::getServiceWithFields('Keyston Bank', [
            ['name' => 'BVN Deletion Request', 'code' => '67', 'price' => 10000],
            ['name' => 'Date of birth update', 'code' => '68', 'price' => 7000],
            ['name' => 'Correction of name', 'code' => '69', 'price' => 6000],
            ['name' => 'Phone number and email update', 'code' => '70', 'price' => 6000],
            ['name' => 'Date of birth and name update', 'code' => '71', 'price' => 6000],
            ['name' => 'Gender Update', 'code' => '72', 'price' => 6000],
            ['name' => 'BVN Revalidation', 'code' => '73', 'price' => 6000],
        ]);

        // First Bank
        ServiceManager::getServiceWithFields('First Bank', [
            ['name' => 'Correction of name', 'code' => '003', 'price' => 8000],
            ['name' => 'Date of birth update', 'code' => '004', 'price' => 7000],
            ['name' => 'Phone Number Update', 'code' => '005', 'price' => 6000],
            ['name' => 'Correction of name and date of birth', 'code' => '006', 'price' => 10000],
            ['name' => 'Complete change of name', 'code' => '007', 'price' => 30000],
            ['name' => 'Gender Update', 'code' => '008', 'price' => 6000],
            ['name' => 'Bvn Revalidation', 'code' => '009', 'price' => 5000],
            ['name' => 'Whitelist BVN', 'code' => '010', 'price' => 10000],
            ['name' => 'BVN Deletion Request', 'code' => '060', 'price' => 6000],
            ['name' => 'Correction of Name, DOB and phone NO', 'code' => '050', 'price' => 12000],
        ]);

        // Agency Banking
        ServiceManager::getServiceWithFields('Agency Banking', [
            ['name' => 'Correction of name', 'code' => '022', 'price' => 6000],
            ['name' => 'Date of birth update', 'code' => '023', 'price' => 6000],
            ['name' => 'Correction of name and date of birth', 'code' => '024', 'price' => 6000],
            ['name' => 'Phone Number Update', 'code' => '025', 'price' => 6000],
            ['name' => 'Gender Update', 'code' => '026', 'price' => 6000],
            ['name' => 'Bvn Revalidation', 'code' => '027', 'price' => 6000],
            ['name' => 'BVN full Alienment With ID', 'code' => '028', 'price' => 6000],
            ['name' => 'BVN Deletion Request', 'code' => '66', 'price' => 6000],
        ]);

        // ELECTRICITY
        ServiceManager::getServiceWithFields('ELECTRICITY', [
            ['name' => 'Ikeja Electric (IKEDC)', 'code' => '108', 'price' => 0],
            ['name' => 'Eko Electric (EKEDC)', 'code' => '109', 'price' => 0],
            ['name' => 'Kano Electric (KEDCO)', 'code' => '200', 'price' => 0],
            ['name' => 'Port Harcourt Electric (PHED)', 'code' => '201', 'price' => 0],
            ['name' => 'Jos Electric (JED)', 'code' => '202', 'price' => 0],
            ['name' => 'Ibadan Electric (IBEDC)', 'code' => '203', 'price' => 0],
            ['name' => 'Kaduna Electric (KAEDCO)', 'code' => '204', 'price' => 0],
            ['name' => 'Abuja Electric (AEDC)', 'code' => '205', 'price' => 0],
            ['name' => 'Enugu Electric (EEDC)', 'code' => '206', 'price' => 0],
            ['name' => 'Benin Electric (BEDC)', 'code' => '207', 'price' => 0],
            ['name' => 'Aba Electric (ABA)', 'code' => '208', 'price' => 0],
            ['name' => 'Yola Electric (YEDC)', 'code' => '209', 'price' => 0],
        ]);

        // VERIFICATION
        ServiceManager::getServiceWithFields('Verification', [
            ['name' => 'Bvn verification', 'code' => '600', 'price' => 70],
            ['name' => 'standard slip', 'code' => '601', 'price' => 50],
            ['name' => 'preminum slip', 'code' => '602', 'price' => 100],
            ['name' => 'plastic slip', 'code' => '603', 'price' => 150],
            ['name' => 'nin verification', 'code' => '610', 'price' => 100],
            ['name' => 'standard slip', 'code' => '611', 'price' => 100],
            ['name' => 'preminum slip', 'code' => '612', 'price' => 150],
            ['name' => 'Individual slip', 'code' => '614', 'price' => 200],
            ['name' => 'certificate slip', 'code' => '615', 'price' => 200],
            ['name' => '1Vnin slip', 'code' => '616', 'price' => 100],
            ['name' => 'NIN Demo Verification', 'code' => '116', 'price' => 150],
        ]);

        // AIRTIME
        ServiceManager::getServiceWithFields('AIRTIME', [
            ['name' => 'Airtime', 'code' => '001', 'price' => 0],
            ['name' => 'MTN', 'code' => '101', 'price' => 0],
            ['name' => 'Airtel', 'code' => '100', 'price' => 0],
            ['name' => 'Glo', 'code' => '102', 'price' => 0],
            ['name' => 'Etisalat', 'code' => '103', 'price' => 0],
        ]);

          // tin registration
        ServiceManager::getServiceWithFields('TIN REGISTRATION', [
            ['name' => 'Individual', 'code' => '800', 'price' => 100],
            ['name' => 'Corporate', 'code' => '801', 'price' => 100],
        ]);

         // Phone number search Using bvn 
        ServiceManager::getServiceWithFields('BVN SEARCH', [
            ['name' => 'Search BVN', 'code' => '45', 'price' => 1500],
        ]);

         // IPE search Using bvn 
        ServiceManager::getServiceWithFields('IPE', [
            ['name' => 'IPE', 'code' => '100', 'price' => 800],
        ]);


            // CRM  
            ServiceManager::getServiceWithFields('CRM', [
                ['name' => 'Central Risk Management', 'code' => '021', 'price' => 1500],
            ]);

            // General Services (used for Affidavit, etc.)
            ServiceManager::getServiceWithFields('Affidavit', [
                ['name' => 'Affidavit', 'code' => '900', 'price' => 500],
            ]);


             // NIN Modification
            ServiceManager::getServiceWithFields('NIN Modification', [
                ['name' => 'Correction of name', 'code' => '032', 'price' => 8000],
                ['name' => 'Phone Number Update', 'code' => '033', 'price' => 8000],
                ['name' => 'Gender Update', 'code' => '034', 'price' => 20000],
                ['name' => 'Date of birth update below 5 year', 'code' => '035', 'price' => 45000],
                ['name' => 'Date of birth Update above 5 year', 'code' => '036', 'price' => 60000],
                ['name' => 'Change of Residence Address', 'code' => '037', 'price' => 8000],
            ]);


             // General Services (used for validation, etc.)
            ServiceManager::getServiceWithFields('Validation', [
                ['name' => 'No record found', 'code' => '015', 'price' => 1000],
                ['name' => 'Photographic Error', 'code' => '016', 'price' => 1000],
                ['name' => 'NIN Suspension', 'code' => '017', 'price' => 2500],
                ['name' => 'Record update', 'code' => '018', 'price' => 1000],
                ['name' => 'Modification Validation', 'code' => '019', 'price' => 1200],
                ['name' => 'NIN Migration', 'code' => '020', 'price' => 3000],
            ]);

            // NIN Personalisation
            ServiceManager::getServiceWithFields('NIN Personalisation', [
                ['name' => 'NIN Personalisation', 'code' => '005', 'price' => 1500],
            ]);
        
    }
}
