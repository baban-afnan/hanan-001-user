<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Verification extends Model
{
    use HasFactory;

    protected $casts = [
        'response_data' => 'array',
        'modification_data' => 'array',
        'submission_date' => 'datetime',
    ];

    protected $fillable = [
        'user_id',
        'service_field_id',
        'service_id',
        'transaction_id',
        'reference',
        'field_code',
        'field_name',
        'service_name',
        'service_type',
        'description',
        'amount',
        'firstname',
        'middlename',
        'surname',
        'gender',
        'birthdate',
        'birthstate',
        'birthlga',
        'birthcountry',
        'maritalstatus',
        'email',
        'type',
        'telephoneno',
        'residence_address',
        'residence_state',
        'residence_lga',
        'residence_town',
        'religion',
        'employmentstatus',
        'educationallevel',
        'profession',
        'height',
        'title',
        'nin',
        'number_nin',
        'idno',
        'vnin',
        'photo_path',
        'signature_path',
        'trackingId',
        'userid',
        'performed_by',
        'approved_by',
        'tax_id',
        'comment',
        'response_data',
        'modification_data',
        'nok_firstname',
        'nok_middlename',
        'nok_surname',
        'nok_address1',
        'nok_address2',
        'nok_lga',
        'nok_state',
        'nok_town',
        'nok_postalcode',
        'self_origin_state',
        'self_origin_lga',
        'self_origin_place',
        'status',
        'submission_date',
        'enrollment_bank',
        'enrollment_branch',
        'registration_date',
        'address',
        'state',
        'lga',
        'town',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function serviceField()
    {
        return $this->belongsTo(ServiceField::class);
    }

    public function service()
    {
        return $this->belongsTo(Services1::class, 'service_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
