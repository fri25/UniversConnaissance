<x-guest-layout title="Créer un compte">
    <h1 class="text-2xl font-bold text-ink dark:text-white">Rejoignez l'univers</h1>
    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Créez votre compte en 30 secondes et commencez à lire.</p>

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-input-label for="name" value="Nom complet" />
            <x-text-input id="name" class="mt-1" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="phone" value="Téléphone (Mobile Money) — facultatif" />
            <x-text-input id="phone" class="mt-1" type="tel" name="phone" :value="old('phone')" autocomplete="tel" placeholder="+229 01 23 45 67 89" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="mt-1" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="mt-1" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <x-primary-button class="w-full py-3">Créer mon compte</x-primary-button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600 dark:text-slate-400">
        {{ __('Already registered?') }} <a href="{{ route('login') }}" class="link">Se connecter</a>
    </p>
</x-guest-layout>
