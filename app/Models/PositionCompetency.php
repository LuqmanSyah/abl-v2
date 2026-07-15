<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PositionCompetency extends Model
{
    use HasFactory;

    protected $table = 'position_competency';

    protected $fillable = ['position_id', 'competency_id', 'required_level'];

    protected static function booted(): void
    {
        static::saving(function (self $standard): void {
            if ($standard->required_level < 1 || $standard->required_level > 5) {
                throw new DomainException('Level kompetensi jabatan harus 1 sampai 5.');
            }
        });
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class);
    }
}
