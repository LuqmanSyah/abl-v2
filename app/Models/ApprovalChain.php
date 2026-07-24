<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalChain extends Model
{
    protected $fillable = [
        'module',
        'name',
        'steps',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'steps' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public static function forModule(string $module): ?self
    {
        return static::where('module', $module)->where('is_active', true)->first();
    }

    public function getStepRoles(): array
    {
        return array_map(fn (array $step): string => $step['role'], $this->steps ?? []);
    }
}
