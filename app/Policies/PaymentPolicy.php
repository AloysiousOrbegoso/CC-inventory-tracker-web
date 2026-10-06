<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Operational cost logging (rent, utilities, packaging, wages…).
 *
 * Managers record and maintain payments for their own branch; the super
 * admin is an overseer — full read access and retrospective edits/mark-paid/
 * deletes on any branch, but never the day-to-day recording of new payment
 * entries (that stays with the branch that incurred the cost).
 */
class PaymentPolicy
{
    use HandlesAuthorization;

    /**
     * Whether the user may edit, mark paid, or delete the payment.
     */
    public function manage(User $authUser, Payment $payment): bool
    {
        if ($authUser->isSuperAdmin()) {
            return true;
        }

        return $authUser->isManager()
            && $authUser->branch_id !== null
            && $authUser->branch_id === $payment->branch_id;
    }
}
