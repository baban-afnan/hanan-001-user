<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Services1 extends Model
{
    use HasFactory;

    protected $table = 'services1';

    protected $fillable = [
        'name',
        'description',
        'image',
        'is_active',
    ];

    // A Service has many ServiceFields
    public function fields()
    {
        return $this->hasMany(ServiceField::class, 'service_id');
    }

    // A Service has many ServicePrices
    public function prices()
    {
        return $this->hasMany(ServicePrice::class, 'service_id');
    }
}
