<?php

namespace App\Policies;

use App\Models\QuestionCategory;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class QuestionCategoryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    private function isStaff(User $user): bool
    {
        return in_array($user->role, ['guidance_counselor', 'system_admin']);
    }

    public function viewAny(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function view(User $user, QuestionCategory $questionCategory): bool
    {
        return $this->isStaff($user);
    }

    public function create(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function update(User $user, QuestionCategory $questionCategory): bool
    {
        return $this->isStaff($user) && !$questionCategory->isDynamicallyLocked();
    }

    public function delete(User $user, QuestionCategory $questionCategory): bool
    {
        return $this->isStaff($user) && !$questionCategory->isDynamicallyLocked();
    }
}
