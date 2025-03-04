<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="PayPeriod",
 *     title="Pay Period",
 *     description="Pay period definition for payroll processing",
 *     @OA\Property(property="id", type="integer", format="int64", example=1, description="Unique identifier"),
 *     @OA\Property(property="start_date", type="string", format="date", example="2025-03-01", description="Start date of pay period"),
 *     @OA\Property(property="end_date", type="string", format="date", example="2025-03-15", description="End date of pay period"),
 *     @OA\Property(property="fiscal_week", type="integer", example=10, description="Fiscal week number"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Timestamp when record was created"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Timestamp when record was last updated")
 * )
 */
class PayPeriod extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'start_date',
        'end_date',
        'fiscal_week',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];
}
