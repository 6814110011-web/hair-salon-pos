<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StaffAccountService
{
    public function create(User $actor, array $attributes): User
    {
        abort_unless($actor->fresh()?->is_active && $actor->fresh()->hasRole('admin'), 403);

        return DB::transaction(function () use ($attributes) {
            $user = new User;
            $user->forceFill(Arr::only($attributes, ['name', 'email', 'phone', 'role', 'commission_rate', 'is_active', 'password']));
            $user->save();

            return $user;
        });
    }

    public function update(User $actor, User $target, array $attributes): User
    {
        return DB::transaction(function () use ($actor, $target, $attributes) {
            $accounts = User::orderBy('id')->lockForUpdate()->get();
            $operator = $accounts->firstWhere('id', $actor->id);
            abort_unless($operator?->is_active && $operator->hasRole('admin'), 403);
            $user = $accounts->firstWhere('id', $target->id);
            abort_unless($user, 404);
            $this->protectLastAdmin($user, $accounts->where('role', 'admin')->where('is_active', true)->count(), $attributes);
            $fields = Arr::only($attributes, ['name', 'email', 'phone', 'role', 'commission_rate', 'is_active', 'password']);
            if (empty($fields['password'])) {
                unset($fields['password']);
            }
            if (array_key_exists('is_active', $fields) && ! $fields['is_active']) {
                $fields['remember_token'] = null;
            }
            $user->forceFill($fields)->save();

            return $user;
        });
    }

    public function deactivateSelf(User $user): void
    {
        DB::transaction(function () use ($user) {
            $accounts = User::orderBy('id')->lockForUpdate()->get();
            $target = $accounts->firstWhere('id', $user->id);
            abort_unless($target, 404);
            try {
                $this->protectLastAdmin($target, $accounts->where('role', 'admin')->where('is_active', true)->count(), ['is_active' => false]);
            } catch (ValidationException $exception) {
                throw $exception->errorBag('userDeletion');
            }
            $target->forceFill(['is_active' => false, 'remember_token' => null])->save();
        });
    }

    private function protectLastAdmin(User $target, int $activeAdmins, array $attributes): void
    {
        if ($target->is_active && $target->role === 'admin' && $activeAdmins <= 1
            && (($attributes['role'] ?? $target->role) !== 'admin' || ! ($attributes['is_active'] ?? $target->is_active))) {
            throw ValidationException::withMessages(['account' => 'ต้องมีผู้ดูแลระบบที่เปิดใช้งานอย่างน้อยหนึ่งคน']);
        }
    }
}
