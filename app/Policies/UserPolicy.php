<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Authorization for managing workers (staff + managers). One decision table
 * replaces the copy-pasted isManager()/isSuperAdmin() + branch_id comparisons
 * that were repeated across WorkersController, AttendanceController and
 * ActivityController:
 *
 *   super_admin → any branch, any managed worker
 *   manager     → own branch only
 *   staff       → nothing (workers are managed from the admin side)
 *
 * "Managed worker" means role staff or manager — the owner account itself is
 * never a target of worker management, so attempts resolve to 404 upstream.
 */
class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Whether the user may manage (create/update/delete/clock) the given worker.
     */
    public function manageWorker(User $authUser, User $worker): bool
    {
        if (! in_array($worker->role, [User::ROLE_STAFF, User::ROLE_MANAGER], true)) {
            return false;
        }

        if ($authUser->isSuperAdmin()) {
            return true;
        }

        return $authUser->isManager()
            && $authUser->branch_id !== null
            && $authUser->branch_id === $worker->branch_id;
    }

    /**
     * Whether the user may view a worker's activity feed
     * (transactions, shifts, discrepancies).
     */
    public function viewActivity(User $authUser, User $worker): bool
    {
        if ($authUser->isSuperAdmin()) {
            return true;
        }

        return $authUser->isManager()
            && $authUser->branch_id !== null
            && $authUser->branch_id === $worker->branch_id;
    }
}
