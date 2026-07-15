<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Competency extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    public function positionStandards(): HasMany
    {
        return $this->hasMany(PositionCompetency::class);
    }

    public function employeeCompetencies(): HasMany
    {
        return $this->hasMany(EmployeeCompetency::class);
    }

    public function trainings(): HasMany
    {
        return $this->hasMany(Training::class);
    }
}
