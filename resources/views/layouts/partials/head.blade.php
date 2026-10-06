<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ !empty($title) ? $title.' · '.config('store.name') : config('store.name').' — '.config('store.tagline') }}</title>
<meta name="description" content="{{ ($description ?? null) ?: 'Librairie en ligne d\'e-books PDF et EPUB en téléchargement instantané. Paiement Mobile Money sécurisé.' }}">
<meta name="theme-color" content="#10BAF1">
<meta property="og:site_name" content="{{ config('store.name') }}">
<meta property="og:title" content="{{ ($title ?? null) ?: config('store.name') }}">
<meta property="og:description" content="{{ ($description ?? null) ?: config('store.tagline') }}">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|playfair-display:600,700,800&display=swap" rel="stylesheet">
<script>
    (function () {
        try {
            var t = localStorage.getItem('theme');
            if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        } catch (e) {}
    })();
</script>
@vite(['resources/css/app.css', 'resources/js/app.js'])
