<?php

namespace App\Helpers;

use App\Models\ServiceField;
use App\Models\Services1;

class ServiceManager
{
    /**
     * Get a service by name and ensure it exists with the provided fields.
     *
     * @param string $serviceName
     * @param array $fields Array of fields ['name' => 'Field Name', 'code' => 'Field Code']
     * @return \App\Models\Services1
     */
    public static function getServiceWithFields(string $serviceName, array $fields)
    {
        $service = Services1::firstOrCreate(
            ['name' => $serviceName],
            ['is_active' => true]
        );

        foreach ($fields as $field) {
            // Skip the service field if it already exists; do not update
            if (ServiceField::where('field_code', $field['code'])->exists()) {
                continue;
            }

            ServiceField::create([
                'service_id' => $service->id,
                'field_name' => $field['name'],
                'field_code' => $field['code'],
                'is_active' => true,
                'base_price' => $field['price'] ?? 0,
            ]);
        }

        return $service;
    }
}
