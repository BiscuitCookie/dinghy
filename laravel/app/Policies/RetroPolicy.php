<?php

namespace App\Policies;

use App\Models\Retro;
use App\Models\User;

class RetroPolicy
{
    public function view(User $user, Retro $retro): bool
    {
        return $retro->isParticipant($user);
    }

    public function update(User $user, Retro $retro): bool
    {
        return $retro->isFacilitator($user);
    }

    public function advancePhase(User $user, Retro $retro): bool
    {
        return $retro->isFacilitator($user);
    }

    public function manageTimer(User $user, Retro $retro): bool
    {
        return $retro->isFacilitator($user);
    }

    public function delete(User $user, Retro $retro): bool
    {
        return $retro->user_id === $user->id;
    }
}
