<fieldset class="mt-3">
    <legend class="label">Votre note</legend>
    <div class="flex gap-1">
        @for ($i = 1; $i <= 5; $i++)
            <label class="cursor-pointer">
                <input type="radio" name="rating" value="{{ $i }}" class="peer sr-only" x-model.number="rating">
                <svg class="h-8 w-8 transition peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500" :class="rating >= {{ $i }} ? 'text-amber-400' : 'text-slate-300 dark:text-ink-600'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.07 3.29a1 1 0 0 0 .95.69h3.46c.97 0 1.37 1.24.59 1.81l-2.8 2.03a1 1 0 0 0-.36 1.12l1.07 3.29c.3.92-.75 1.69-1.54 1.12l-2.8-2.03a1 1 0 0 0-1.18 0l-2.8 2.03c-.78.57-1.84-.2-1.54-1.12l1.07-3.29a1 1 0 0 0-.36-1.12L2.98 8.72c-.78-.57-.38-1.81.59-1.81h3.46a1 1 0 0 0 .95-.69l1.07-3.29Z"/>
                </svg>
                <span class="sr-only">{{ $i }} étoile{{ $i > 1 ? 's' : '' }}</span>
            </label>
        @endfor
    </div>
    <x-input-error :messages="$errors->get('rating')" class="mt-1" />
</fieldset>
<div class="mt-3">
    <label for="comment" class="label">Commentaire (facultatif)</label>
    <textarea id="comment" name="comment" rows="3" maxlength="2000" class="input">{{ old('comment', $comment ?? '') }}</textarea>
    <x-input-error :messages="$errors->get('comment')" class="mt-1" />
</div>
