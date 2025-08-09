<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="ShiftConfiguration",
 *     title="Shift Configuration",
 *     description="Employee-specific shift type configuration for scheduling",
 *     @OA\Property(property="id", type="integer", format="int64", example=1, description="Unique identifier"),
 *     @OA\Property(property="shift_type_id", type="integer", example=2, description="Associated shift type ID"),
 *     @OA\Property(property="employee_id", type="integer", example=5, description="Employee ID this configuration applies to"),
 *     @OA\Property(property="shift_duration", type="integer", example=8, description="Duration of shift in hours"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Timestamp when record was created"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Timestamp when record was last updated"),
 *     @OA\Property(
 *         property="shift_type",
 *         ref="#/components/schemas/ShiftType",
 *         description="Associated shift type details"
 *     ),
 *     @OA\Property(
 *         property="employee",
 *         ref="#/components/schemas/Employee",
 *         description="Associated employee details"
 *     )
 * )
 */
class ShiftConfiguration extends Model
{
    use BelongsToCompany, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'shift_type_id',
        'employee_id',
        'shift_duration',
        'company_id',
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
