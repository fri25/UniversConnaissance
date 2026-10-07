{{--
    Pixel Meta, chargé uniquement après consentement (bandeau cookies).
    Les pages ajoutent leurs événements avec :
        @push('pixel-events') ucTrack('ViewContent', {...}); @endpush
--}}
@php($metaPixelId = app(\App\Services\MetaPixel::class)->pixelId())
@if ($metaPixelId)
<script>
    window.ucPixelQueue = window.ucPixelQueue || [];
    // Enregistre un événement ; envoyé tout de suite si le pixel est chargé, sinon après consentement.
    window.ucTrack = function (name, data, options) {
        if (window.fbq && window.ucPixelLoaded) { fbq('track', name, data || {}, options || {}); }
        else { window.ucPixelQueue.push([name, data || {}, options || {}]); }
    };
    window.ucLoadPixel = function () {
        if (window.ucPixelLoaded) { return; }
        window.ucPixelLoaded = true;
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
        n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
        document,'script','https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', @js($metaPixelId));
        fbq('track', 'PageView');
        window.ucPixelQueue.forEach(function (e) { fbq('track', e[0], e[1], e[2]); });
        window.ucPixelQueue = [];
    };
    try { if (localStorage.getItem('uc-consent') === 'granted') { window.ucLoadPixel(); } } catch (e) {}

    // Clic sur « Acheter » : InitiateCheckout (avant la redirection vers Chariow).
    document.addEventListener('click', function (event) {
        var link = event.target.closest && event.target.closest('[data-pixel-checkout]');
        if (!link) { return; }
        try { ucTrack('InitiateCheckout', JSON.parse(link.getAttribute('data-pixel-checkout'))); } catch (e) {}
    });
</script>
@else
<script>window.ucTrack = function () {};</script>
@endif
<script>
    document.addEventListener('DOMContentLoaded', function () {
        @stack('pixel-events')
    });
</script>
