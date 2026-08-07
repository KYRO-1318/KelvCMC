// Chart.js is loaded via CDN in the dashboard view.
// This module is reserved for future client-side behaviour (Livewire is the
// primary interactive layer of the client portal).
window.KelvCMC = window.KelvCMC || {};

// Reveal masked credentials only after an authenticated request.
document.addEventListener('click', async (event) => {
    const toggle = event.target.closest('[data-reveal]');
    if (!toggle || toggle.dataset.loading === '1') return;

    const target = document.querySelector(toggle.dataset.reveal);
    if (!target) return;

    const revealed = target.dataset.revealed === '1';

    if (revealed) {
        target.dataset.revealed = '0';
        target.textContent = target.dataset.placeholder;
        toggle.textContent = 'Show';
        return;
    }

    toggle.dataset.loading = '1';
    toggle.disabled = true;

    try {
        const response = await fetch(target.dataset.endpoint, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (!response.ok) throw new Error('Unable to load credentials');

        const data = await response.json();
        target.dataset.revealed = '1';
        target.textContent = data.password || target.dataset.placeholder;
        toggle.textContent = 'Hide';
    } catch (error) {
        target.textContent = 'Unavailable';
        toggle.textContent = 'Retry';
    } finally {
        toggle.dataset.loading = '0';
        toggle.disabled = false;
    }
});
