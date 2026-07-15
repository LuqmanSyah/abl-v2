<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DutyLocation extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'address', 'latitude', 'longitude', 'radius_meters', 'is_active'];

    protected function casts(): array
    {
        return ['latitude' => 'decimal:7', 'longitude' => 'decimal:7', 'is_active' => 'boolean'];
    }

    public function dutyTrips(): HasMany
    {
        return $this->hasMany(DutyTrip::class);
    }
}
