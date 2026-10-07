<?php

namespace App\Listeners;

use Illuminate\Auth\Events\PasswordReset;

/**
 * Un client créé automatiquement à l'achat devient un compte « normal » dès
 * qu'il choisit son mot de passe.
 */
class MarkGuestAccountAsClaimed
{
    public function handle(PasswordReset $event): void
    {
        if ($event->user->is_guest ?? false) {
            $event->user->forceFill(['is_guest' => false])->save();
        }
    }
}
