<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'schedule'
    ];

    /**
     * Get the shift configurations for the shift type.
     */
    public function shiftConfigurations()
    {
        return $this->hasMany(ShiftConfiguration::class);
    }
}
