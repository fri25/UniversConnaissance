@props([
    'countryName' => 'phone_country',
    'numberName' => 'phone_number',
    'country' => \App\Support\Phone::DEFAULT_COUNTRY,
    'number' => '',
])
@php
    $options = \App\Support\Phone::options();
    // Indicatifs propres à un seul pays : détection automatique quand le numéro commence par « + ».
    $uniqueDials = collect($options)->groupBy('dial')->filter(fn ($g) => $g->count() === 1)->map(fn ($g) => $g->first()['code']);
@endphp
<div x-data="phoneInput({
        options: @js($options),
        uniqueDials: @js($uniqueDials),
        selected: @js($country),
    })"
     @keydown.escape.window="close()" @click.outside="close()"
     {{ $attributes->merge(['class' => 'relative']) }}>
    <input type="hidden" name="{{ $countryName }}" :value="selected">

    <div class="flex gap-2">
        <button type="button" @click="toggle()" class="input flex !w-32 shrink-0 items-center justify-between gap-1 border px-3 py-2 text-left text-sm"
                aria-haspopup="listbox" :aria-expanded="open.toString()" aria-label="Pays de l'indicatif téléphonique">
            <span class="flex min-w-0 items-center gap-2">
                <img :src="flag(current().code)" :srcset="flag(current().code, 2) + ' 2x'" alt="" width="24" height="18"
                     class="h-[18px] w-6 shrink-0 rounded-[2px] object-cover shadow-[0_0_0_1px_rgba(0,0,0,0.08)]">
                <span class="truncate" x-text="'+' + current().dial"></span>
            </span>
            <svg class="h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/></svg>
        </button>
        <label for="{{ $numberName }}" class="sr-only">Numéro</label>
        <input id="{{ $numberName }}" type="tel" name="{{ $numberName }}" required autocomplete="tel" inputmode="tel"
               value="{{ $number }}" placeholder="01 97 00 00 00" class="input text-sm" @input="detect($event.target.value)">
    </div>
    <p class="mt-1 text-xs text-slate-500" x-text="current().name"></p>

    <div x-show="open" x-cloak
         class="absolute left-0 z-50 mt-1 w-full max-w-sm rounded-xl border border-slate-200 bg-white p-2 shadow-xl dark:border-ink-600 dark:bg-ink-800">
        <label class="sr-only" for="{{ $countryName }}-search">Rechercher un pays</label>
        <input id="{{ $countryName }}-search" x-ref="search" type="search" x-model="query" autocomplete="off"
               @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="choose(filtered()[active])"
               placeholder="Rechercher un pays ou un indicatif…" class="input text-sm">
        <ul x-ref="list" role="listbox" class="mt-2 max-h-64 overflow-y-auto">
            <template x-for="(option, index) in filtered()" :key="option.code">
                <li role="option" :aria-selected="(option.code === selected).toString()"
                    @click="choose(option)" @mouseenter="active = index"
                    :class="index === active ? 'bg-brand-50 dark:bg-ink-700' : ''"
                    class="flex cursor-pointer items-center justify-between gap-3 rounded-lg px-3 py-2 text-sm">
                    <span class="flex min-w-0 items-center gap-3">
                        <img :src="flag(option.code)" :srcset="flag(option.code, 2) + ' 2x'" alt="" width="24" height="18" loading="lazy"
                             class="h-[18px] w-6 shrink-0 rounded-[2px] object-cover shadow-[0_0_0_1px_rgba(0,0,0,0.08)]">
                        <span class="truncate text-slate-800 dark:text-slate-100" x-text="option.name"></span>
                    </span>
                    <span class="shrink-0 tabular-nums text-slate-500" x-text="'+' + option.dial"></span>
                </li>
            </template>
            <li x-show="filtered().length === 0" class="px-3 py-2 text-sm text-slate-500">Aucun pays trouvé.</li>
        </ul>
    </div>
</div>

@once
    @push('scripts')
        <script>
            function phoneInput({ options, uniqueDials, selected }) {
                const normalize = (s) => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
                const indexed = options.map((o) => ({ ...o, haystack: normalize(o.search) }));

                return {
                    open: false,
                    query: '',
                    active: 0,
                    selected,
                    // Drapeaux : petites images (~0,5 Ko), identiques sur tous les appareils
                    // (les drapeaux emoji ne s'affichent pas sous Windows).
                    flag(code, scale = 1) {
                        return `https://flagcdn.com/${24 * scale}x${18 * scale}/${code.toLowerCase()}.png`;
                    },
                    current() {
                        return indexed.find((o) => o.code === this.selected) || indexed[0];
                    },
                    filtered() {
                        const q = normalize(this.query.trim()).replace(/^\+/, '');
                        if (! q) { return indexed; }
                        // Recherche par indicatif (« 225 ») ou par nom / code / autre nom.
                        if (/^\d+$/.test(q)) { return indexed.filter((o) => o.dial.startsWith(q)); }
                        return indexed.filter((o) => o.haystack.includes(q));
                    },
                    toggle() {
                        this.open ? this.close() : this.show();
                    },
                    show() {
                        this.open = true;
                        this.query = '';
                        this.active = Math.max(0, this.filtered().findIndex((o) => o.code === this.selected));
                        this.$nextTick(() => {
                            this.$refs.search.focus();
                            this.scrollToActive();
                        });
                    },
                    close() {
                        this.open = false;
                    },
                    move(step) {
                        const max = this.filtered().length - 1;
                        this.active = Math.min(max, Math.max(0, this.active + step));
                        this.scrollToActive();
                    },
                    scrollToActive() {
                        this.$nextTick(() => this.$refs.list.children[this.active + 1]?.scrollIntoView({ block: 'nearest' }));
                    },
                    choose(option) {
                        if (! option) { return; }
                        this.selected = option.code;
                        this.close();
                    },
                    // Numéro saisi au format international : on sélectionne le pays correspondant.
                    detect(value) {
                        const digits = value.trim().startsWith('+') ? value.replace(/\D/g, '') : (value.trim().startsWith('00') ? value.replace(/\D/g, '').slice(2) : '');
                        for (let length = 4; length >= 1; length--) {
                            const code = uniqueDials[digits.slice(0, length)];
                            if (code) { this.selected = code; return; }
                        }
                    },
                    init() {
                        this.$watch('query', () => {
                            this.active = 0;
                            this.$refs.list.scrollTop = 0;
                        });
                    },
                };
            }
        </script>
    @endpush
@endonce
