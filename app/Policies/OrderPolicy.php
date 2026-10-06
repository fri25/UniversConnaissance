<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $user->is_admin || $order->user_id === $user->id;
    }

    /**
     * Seul l'acheteur d'une commande payée peut télécharger le fichier.
     */
    public function download(User $user, Order $order): bool
    {
        return $order->user_id === $user->id && $order->isPaid();
    }
}
