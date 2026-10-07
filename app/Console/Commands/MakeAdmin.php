<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Crée un compte administrateur, ou donne le rôle admin à un compte existant.
 */
class MakeAdmin extends Command
{
    protected $signature = 'uc:make-admin {email : Adresse email de l\'administrateur} {--name= : Nom affiché (nouveau compte)}';

    protected $description = 'Crée un administrateur ou promeut un compte existant';

    public function handle(): int
    {
        $email = mb_strtolower(trim($this->argument('email')));

        if (Validator::make(['email' => $email], ['email' => 'required|email'])->fails()) {
            $this->error('Adresse email invalide.');

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            $user->forceFill(['is_admin' => true])->save();
            $this->info("✓ {$email} est maintenant administrateur.");

            return self::SUCCESS;
        }

        $password = $this->secret('Mot de passe (8 caractères minimum, rien ne s\'affiche)');
        if (! is_string($password) || mb_strlen($password) < 8) {
            $this->error('Mot de passe trop court.');

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $this->option('name') ?: strstr($email, '@', true),
            'email' => $email,
            'password' => Hash::make($password),
        ]);
        $user->forceFill(['is_admin' => true, 'email_verified_at' => now()])->save();

        $this->info("✓ Administrateur {$email} créé. Connectez-vous sur /login.");

        return self::SUCCESS;
    }
}
