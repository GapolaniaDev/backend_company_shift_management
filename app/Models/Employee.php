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
        'user_id',
        'supervisor_id',
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
    
    /**
     * Get the user associated with the employee.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the supervisor of this employee.
     */
    public function supervisor()
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }
    
    /**
     * Get employees supervised by this employee.
     */
    public function supervisees()
    {
        return $this->hasMany(Employee::class, 'supervisor_id');
    }
    
    /**
     * Get full name of employee
     */
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
