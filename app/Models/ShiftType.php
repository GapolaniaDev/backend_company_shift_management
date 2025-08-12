<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="ShiftType",
 *     title="Shift Type",
 *     description="Shift type definition with scheduling information",
 *
 *     @OA\Property(property="id", type="integer", format="int64", example=1, description="Unique identifier"),
 *     @OA\Property(property="name", type="string", example="Morning Shift", description="Name of the shift type"),
 *     @OA\Property(property="description", type="string", example="Standard morning shift from 8am-4pm", description="Detailed description of shift type"),
 *     @OA\Property(property="weekly_hours", type="number", format="float", example=40, description="Target weekly hours for this shift type"),
 *     @OA\Property(
 *         property="schedule",
 *         type="array",
 *         description="Weekly schedule configuration as a JSON array",
 *         example={"Monday": {"start": "08:00", "end": "16:00"}, "Tuesday": {"start": "08:00", "end": "16:00"}},
 *
 *         @OA\Items(type="object")
 *     ),
 *
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Timestamp when record was created"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Timestamp when record was last updated")
 * )
 */
class ShiftType extends Model
{
    use HasFactory;

    protected $casts = [
        'schedule' => 'array',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'description',
        'weekly_hours',
        'description',
        'schedule',
    ];

    /**
     * Get the shift configurations for the shift type.
     */
    public function shiftConfigurations()
    {
        return $this->hasMany(ShiftConfiguration::class);
    }
}
