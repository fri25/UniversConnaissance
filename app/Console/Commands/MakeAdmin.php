<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Crée un compte administrateur, ou promeut un compte existant, et définit son
 * mot de passe (y compris pour un compte créé automatiquement lors d'un achat,
 * dont personne ne connaît le mot de passe).
 */
class MakeAdmin extends Command
{
    protected $signature = 'uc:make-admin
        {email : Adresse email de l\'administrateur}
        {--name= : Nom affiché (nouveau compte)}
        {--keep-password : Compte existant : ne pas changer le mot de passe}';

    protected $description = 'Crée un administrateur (ou promeut un compte existant) et définit son mot de passe';

    public function handle(): int
    {
        $email = mb_strtolower(trim($this->argument('email')));

        if (Validator::make(['email' => $email], ['email' => 'required|email'])->fails()) {
            $this->error('Adresse email invalide.');

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();
        $mustSetPassword = ! $user || ! $this->option('keep-password');

        $password = null;
        if ($mustSetPassword) {
            $this->line($user ? "Le compte {$email} existe déjà : choisissez son nouveau mot de passe." : "Création du compte {$email}.");
            $password = $this->secret('Mot de passe (8 caractères minimum, rien ne s\'affiche)');
            if (! is_string($password) || mb_strlen($password) < 8) {
                $this->error('Mot de passe trop court (8 caractères minimum).');

                return self::FAILURE;
            }
            if ($this->secret('Confirmez le mot de passe') !== $password) {
                $this->error('Les deux mots de passe ne correspondent pas.');

                return self::FAILURE;
            }
        }

        if (! $user) {
            $user = User::create([
                'name' => $this->option('name') ?: strstr($email, '@', true),
                'email' => $email,
                'password' => Hash::make($password),
            ]);
        } elseif ($password !== null) {
            $user->forceFill(['password' => Hash::make($password)]);
        }

        $user->forceFill(['is_admin' => true, 'is_guest' => false, 'email_verified_at' => $user->email_verified_at ?? now()])->save();

        $this->info("✓ {$email} est administrateur".($password !== null ? ' avec le nouveau mot de passe.' : '.'));
        $this->line('  Connexion : '.route('login').'  (puis menu → Administration, ou '.route('admin.dashboard').')');

        return self::SUCCESS;
    }
}
