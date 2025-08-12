<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Company",
 *     title="Company",
 *     description="Company model representing client companies",
 *     @OA\Property(property="id", type="integer", description="Company ID"),
 *     @OA\Property(property="name", type="string", description="Company name"),
 *     @OA\Property(property="description", type="string", description="Company description"),
 *     @OA\Property(property="address", type="string", description="Company address"),
 *     @OA\Property(property="phone", type="string", description="Company phone"),
 *     @OA\Property(property="email", type="string", description="Company email"),
 *     @OA\Property(property="is_active", type="boolean", description="Whether the company is active"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Last update timestamp")
 * )
 */
class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'address',
        'phone',
        'email',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the users for the company.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the employees for the company.
     */
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Get the shifts for the company.
     */
    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    /**
     * Scope a query to only include active companies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}