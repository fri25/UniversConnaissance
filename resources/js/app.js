import './bootstrap';

import Alpine from 'alpinejs';

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
