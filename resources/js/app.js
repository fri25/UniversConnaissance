import './bootstrap';

import Alpine from 'alpinejs';

// Thème clair / sombre mémorisé (la classe initiale est posée par un script
// inline dans <head> pour éviter le flash).
Alpine.store('theme', {
    dark: document.documentElement.classList.contains('dark'),
    toggle() {
        this.dark = !this.dark;
        document.documentElement.classList.toggle('dark', this.dark);
        try {
            localStorage.setItem('theme', this.dark ? 'dark' : 'light');
        } catch (e) {
            // stockage indisponible (navigation privée) : on ignore
        }
    },
});

window.Alpine = Alpine;
Alpine.start();

// Apparition au scroll.
const revealed = document.querySelectorAll('[data-reveal]');
if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { rootMargin: '0px 0px -40px 0px' });
    revealed.forEach((el) => observer.observe(el));
} else {
    revealed.forEach((el) => el.classList.add('is-visible'));
}
