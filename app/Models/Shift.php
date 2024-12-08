<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_type_id',
        'employee_id',
        'date_start',
        'date_end',
        'total_hours',
        'weekday_code',
        'comments',
        'replacement_id',
    ];

    protected $casts = [
        'date_start' => 'datetime',
        'date_end' => 'datetime',
        'total_hours' => 'decimal:2',
    ];

    public function shiftType()
    {
        return $this->belongsTo(ShiftType::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function replacement()
    {
        return $this->belongsTo(Employee::class, 'replacement_id');
    }
}
