<x-guest-layout title="Connexion">
    <h1 class="text-2xl font-bold text-ink dark:text-white">Bon retour parmi nous</h1>
    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Connectez-vous pour retrouver vos e-books.</p>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" :value="__('Password')" />
                @if (Route::has('password.request'))
                    <a class="link text-xs" href="{{ route('password.request') }}">{{ __('Forgot your password?') }}</a>
                @endif
            </div>
            <x-text-input id="password" class="mt-1" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
            <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-brand-700 focus:ring-brand-500" name="remember">
            {{ __('Remember me') }}
        </label>

        <x-primary-button class="w-full py-3">{{ __('Log in') }}</x-primary-button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600 dark:text-slate-400">
        Pas encore de compte ? <a href="{{ route('register') }}" class="link">Rejoindre {{ config('store.name') }}</a>
    </p>
</x-guest-layout>
