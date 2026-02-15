<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormRecord extends Model
{
    protected $table = 'formrecords';  // matches your table name
    protected $primaryKey = 'form_id';
    public $timestamps = true;

    protected $fillable = [
        'item_count',
        'student_name',
        'issued_by',
        'form_type',
        'reference_no',
        'status',
    ];
}
