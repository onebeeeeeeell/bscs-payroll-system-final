<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
 
#[Fillable([
        'employee_id',
        'work_date',
        'time_in',
        'time_out',
        'hours_worked',
        'overtime_hours'
])]

class Attendance extends Model
{
        use HasFactory, SoftDeletes;

        protected $dates = ['deleted_at'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

}