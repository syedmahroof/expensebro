<?php

namespace App\Actions;

use App\Models\User;
use App\Models\Wallet;

class CreateDefaultWallet
{
    /**
     * Create the default "Cash" wallet for a newly created user.
     */
    public function create(User $user): Wallet
    {
        return Wallet::create([
            'user_id' => $user->id,
            'name' => 'Cash',
            'type' => 'cash',
            'currency' => $user->default_currency ?? 'PKR',
            'balance' => 0,
            'color' => '#10b981',
            'icon' => 'wallet',
            'is_default' => true,
            'is_active' => true,
        ]);
    }
}
