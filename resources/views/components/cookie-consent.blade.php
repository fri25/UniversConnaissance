{{-- Bandeau de consentement : affiché seulement si un traceur publicitaire est configuré. --}}
@if (app(\App\Services\MetaPixel::class)->enabled())
    <div x-data="{
            show: false,
            init() { try { this.show = ! localStorage.getItem('uc-consent') } catch (e) { this.show = true } },
            choose(value) {
                try { localStorage.setItem('uc-consent', value) } catch (e) {}
                if (value === 'granted' && window.ucLoadPixel) { window.ucLoadPixel() }
                this.show = false
            },
         }"
         x-show="show" x-cloak x-transition.opacity
         role="dialog" aria-live="polite" aria-label="Cookies"
         class="fixed inset-x-3 bottom-3 z-50 mx-auto max-w-2xl rounded-2xl border border-slate-200 bg-white p-4 shadow-2xl sm:p-5 dark:border-ink-600 dark:bg-ink-800">
        <p class="text-sm text-slate-700 dark:text-slate-300">
            Nous utilisons des cookies de mesure publicitaire (Meta) pour comprendre d'où viennent nos lecteurs et améliorer nos annonces.
            Les cookies techniques nécessaires au site restent actifs dans tous les cas.
            <a href="{{ route('pages.privacy') }}" class="link">En savoir plus</a>
        </p>
        <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:justify-end">
            <button type="button" @click="choose('denied')" class="btn-outline !py-2">Refuser</button>
            <button type="button" @click="choose('granted')" class="btn-primary !py-2">Accepter</button>
        </div>
    </div>
@endif
