<?php

namespace App\Models\Concerns;

use App\Models\User;
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

    protected function guardRole(User $user, string $role): void
    {
        if ($this->delegateCanAct($user, $role)) {
            return;
        }

        if ($user->role->value !== $role) {
            throw new \App\Exceptions\BusinessRuleException('Aksi tidak diizinkan untuk pengguna ini.');
        }
    }

    protected function delegateCanAct(User $user, string $role): bool
    {
        if ($user->role->value !== $role) {
            return false;
        }

        $isDelegate = $this->manager_id
            && $this->manager_id !== $user->id
            && $user->delegate_id === $this->manager_id;

        if ($isDelegate) {
            activity()
                ->performedOn($this)
                ->causedBy($user)
                ->withProperties(['action' => 'delegated_approval', 'delegate_of' => $this->manager_id])
                ->log(class_basename(static::class).'.delegated');
        }

        return $isDelegate;
    }
}
