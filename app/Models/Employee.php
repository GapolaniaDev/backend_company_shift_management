<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'address',
        'tax_number',
        'abn',
        'bsb',
        'account'
    ];

    /**
     * Get the shift configurations for the employee.
     */
    public function shiftConfigurations()
    {
        return $this->hasMany(ShiftConfiguration::class, 'employee_id');
    }

    /**
     * Get the shifts for the employee.
     */
    public function shifts()
    {
        return $this->hasMany(Shift::class, 'employee_id');
    }
}
