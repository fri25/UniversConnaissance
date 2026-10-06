@if (session('status') || session('error') || session('import_errors'))
    <div class="container-page pt-6" x-data="{ show: true }" x-show="show" x-transition>
        @if (session('status') && ! in_array(session('status'), ['profile-updated', 'password-updated', 'verification-link-sent']))
            <div role="status" class="flex items-start gap-3 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-950 dark:border-brand-800 dark:bg-brand-950/60 dark:text-brand-100">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-brand-700 dark:text-brand-300" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/></svg>
                <p class="flex-1">{{ session('status') }}</p>
                <button type="button" @click="show = false" class="text-brand-800 hover:text-brand-950 dark:text-brand-200" aria-label="Fermer">&times;</button>
            </div>
        @endif
        @if (session('error'))
            <div role="alert" class="mt-2 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-900 dark:bg-red-950/50 dark:text-red-100">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-red-700 dark:text-red-300" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>
                <p class="flex-1">{{ session('error') }}</p>
                <button type="button" @click="show = false" class="text-red-800 dark:text-red-200" aria-label="Fermer">&times;</button>
            </div>
        @endif
        @if (session('import_errors'))
            <ul class="mt-2 list-inside list-disc rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100">
                @foreach (session('import_errors') as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        @endif
    </div>
@endif
