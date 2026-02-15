<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IssuedLog extends Model
{
    protected $table = 'issuedlog';
    protected $primaryKey = 'issue_id';
    protected $fillable = [
        'student_id',
        'serial_no',
        'property_no',
        'form_type',
        'issued_date',
        'return_date',
        'actual_return_date',
        'reference_no',
        'usage_hours',
    ];
    public $timestamps = true;
}
