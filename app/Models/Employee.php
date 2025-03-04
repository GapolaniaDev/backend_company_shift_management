<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Employee",
 *     title="Employee",
 *     description="Employee model with personal, financial, and employment details",
 *     @OA\Property(property="id", type="integer", format="int64", example=1, description="Unique identifier"),
 *     @OA\Property(property="user_id", type="integer", example=1, description="Associated user ID"),
 *     @OA\Property(property="supervisor_id", type="integer", nullable=true, example=5, description="ID of the supervising employee"),
 *     @OA\Property(property="first_name", type="string", example="John", description="First name"),
 *     @OA\Property(property="last_name", type="string", example="Doe", description="Last name"),
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com", description="Contact email"),
 *     @OA\Property(property="phone_number", type="string", example="555-123-4567", description="Contact phone number"),
 *     @OA\Property(property="address", type="string", example="123 Main St, Anytown", description="Physical address"),
 *     @OA\Property(property="tax_number", type="string", example="123-45-6789", description="Tax identification number"),
 *     @OA\Property(property="abn", type="string", example="12345678901", description="Australian Business Number"),
 *     @OA\Property(property="bsb", type="string", example="123456", description="Bank State Branch number"),
 *     @OA\Property(property="account", type="string", example="12345678", description="Bank account number"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Timestamp when record was created"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Timestamp when record was last updated"),
 *     @OA\Property(
 *         property="user",
 *         ref="#/components/schemas/User",
 *         description="Associated user account"
 *     ),
 *     @OA\Property(
 *         property="supervisor",
 *         ref="#/components/schemas/Employee",
 *         description="Supervising employee"
 *     )
 * )
 */
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
