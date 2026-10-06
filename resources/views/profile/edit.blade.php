<x-app-layout title="Mon profil">
    <x-slot name="header">
        <h1 class="text-3xl font-bold text-ink dark:text-white">
            {{ __('Profile') }}
        </h1>
    </x-slot>

    <div class="py-10">
        <div class="container-page space-y-6">
            <div class="card p-4 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="card p-4 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="card p-4 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
