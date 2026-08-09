<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\DB;

trait HasWorkflow
{
    public function workflowTransition(callable $transition): void
    {
        DB::transaction(function () use ($transition): void {
            $model = static::query()->lockForUpdate()->findOrFail($this->id);
            $this->setRawAttributes($model->getAttributes(), true);
            $transition($model);
            $this->setRawAttributes($model->getAttributes(), true);
        }, 3);
    }
}
