// Chart.js is loaded via CDN in the dashboard view.
// This module is reserved for future client-side behaviour (Livewire is the
// primary interactive layer of the client portal).
window.KelvCMC = window.KelvCMC || {};

// Reveal masked credentials.
document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-reveal]');
    if (!toggle) return;

    const target = document.querySelector(toggle.dataset.reveal);
    if (target) {
        const revealed = target.dataset.revealed === '1';
        target.dataset.revealed = revealed ? '0' : '1';
        target.textContent = revealed ? toggle.dataset.placeholder : toggle.dataset.secret;
        toggle.textContent = revealed ? 'Show' : 'Hide';
    }
});
