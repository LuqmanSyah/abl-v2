<?php

namespace App\Models;

use App\Exceptions\BusinessRuleException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Competency extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    protected static function booted(): void
    {
        static::deleting(function (self $competency): void {
            if ($competency->isInUse()) {
                throw new BusinessRuleException('Kompetensi yang masih digunakan tidak dapat dihapus.');
            }
        });
    }

    public function isInUse(): bool
    {
        return $this->positionStandards()->exists()
            || $this->employeeCompetencies()->exists()
            || $this->trainings()->exists()
            || $this->mentorings()->exists();
    }

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

    public function mentorings(): HasMany
    {
        return $this->hasMany(Mentoring::class);
    }
}
