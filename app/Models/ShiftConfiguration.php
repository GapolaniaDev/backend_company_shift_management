<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftConfiguration extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'shift_type_id',
        'employee_id',
        'shift_duration',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'shift_duration' => 'integer',
    ];

    /**
     * Get the shift type associated with the shift configuration.
     */
    public function shiftType()
    {
        return $this->belongsTo(ShiftType::class);
    }

    /**
     * Get the employee associated with the shift configuration.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
