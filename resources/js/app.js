import './bootstrap';

import Alpine from 'alpinejs';
import initLiveUpdates from './live-updates';

function formatFileSize(bytes) {
    if (!Number.isFinite(bytes) || bytes <= 0) {
        return '0 B';
    }

    const units = ['B', 'KB', 'MB', 'GB'];
    const exponent = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
    const size = bytes / 1024 ** exponent;

    return `${size.toFixed(size >= 10 || exponent === 0 ? 0 : 1)} ${units[exponent]}`;
}

function initFilePreviews() {
    const containers = document.querySelectorAll('[data-file-preview]');

    containers.forEach((container) => {
        const input = container.querySelector('[data-file-preview-input]');
        const list = container.querySelector('[data-file-preview-list]');

        if (!input || !list) {
            return;
        }

        const renderPreviews = (files) => {
            list.innerHTML = '';

            if (!files || !files.length) {
                list.hidden = true;
                return;
            }

            Array.from(files).forEach((file) => {
                const wrapper = document.createElement('div');
                wrapper.className = 'flex items-center gap-3 rounded-xl border border-slate-200/60 bg-white/80 px-3 py-2 shadow-sm shadow-slate-100';

                if (file.type.startsWith('image/')) {
                    const preview = document.createElement('img');
                    preview.className = 'h-10 w-10 flex-shrink-0 rounded-lg object-cover ring-1 ring-slate-200';
                    const previewUrl = URL.createObjectURL(file);
                    preview.src = previewUrl;
                    preview.alt = file.name;
                    preview.onload = () => URL.revokeObjectURL(previewUrl);
                    wrapper.appendChild(preview);
                } else {
                    const icon = document.createElement('div');
                    icon.className = 'flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-600';
                    icon.innerHTML = '<svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V7.414A2 2 0 0017.414 6L13 1.586A2 2 0 0011.586 1H4zm7 1.414L15.586 9H12a1 1 0 01-1-1V4.414zM5 11a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1zm0 3a1 1 0 011-1h5a1 1 0 110 2H6a1 1 0 01-1-1z" clip-rule="evenodd" /></svg>';
                    wrapper.appendChild(icon);
                }

                const meta = document.createElement('div');
                meta.className = 'min-w-0 flex-1';
                meta.innerHTML = `
                    <p class="text-sm font-medium text-slate-700 truncate" title="${file.name}">${file.name}</p>
                    <p class="text-xs text-slate-400">${formatFileSize(file.size)}</p>
                `;

                wrapper.appendChild(meta);
                list.appendChild(wrapper);
            });

            list.hidden = false;
        };

        input.addEventListener('change', (event) => {
            renderPreviews(event.target.files);
        });
    });
}

function initNotificationPolling() {
    const endpoint = document.body?.dataset?.notificationsEndpoint;

    if (!endpoint) {
        return;
    }

    let timerId = null;

    const updateBadge = (type, count) => {
        const badges = document.querySelectorAll(`[data-notification-badge="${type}"]`);
        const formatted = count > 9 ? '9+' : String(count);
        const shouldShow = count > 0;

        badges.forEach((badge) => {
            badge.hidden = !shouldShow;
            if (shouldShow) {
                badge.removeAttribute('hidden');
                badge.style.display = 'inline-flex';
            } else {
                badge.style.display = 'none';
            }
        });

        document
            .querySelectorAll(`[data-notification-count="${type}"]`)
            .forEach((element) => {
                element.textContent = formatted;
            });
    };

    const fetchCounts = async () => {
        try {
            const response = await fetch(endpoint, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();

            if (!data) {
                return;
            }

            updateBadge('tickets', Number(data.tickets ?? 0));
            updateBadge('users', Number(data.users ?? 0));
        } catch (error) {
            console.error('Gagal memuat ringkasan notifikasi:', error);
        }
    };

    const startPolling = () => {
        if (timerId) {
            window.clearInterval(timerId);
        }
        timerId = window.setInterval(fetchCounts, 3000);
    };

    fetchCounts();
    startPolling();

    window.addEventListener('focus', fetchCounts);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            fetchCounts();
        }
    });
}

function initScrollReveal() {
    const elements = document.querySelectorAll('[data-scroll-animate]');

    if (!elements.length) {
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in-view');
                    observer.unobserve(entry.target);
                }
            });
        },
        {
            threshold: 0.15,
        }
    );

    elements.forEach((el) => {
        el.classList.add('reveal-on-scroll');

        const delay = el.dataset.scrollDelay ? Number(el.dataset.scrollDelay) : 0;
        if (delay > 0) {
            el.style.transitionDelay = `${delay}ms`;
        }

        observer.observe(el);
    });
}

function initCounters() {
    const counters = document.querySelectorAll('.counter[data-counter]');
    if (!counters.length) return;

    counters.forEach((counter) => {
        const target = Number(counter.dataset.counter ?? counter.textContent ?? 0);
        if (!Number.isFinite(target)) return;

        let current = 0;
        const duration = 900;
        const start = performance.now();

        const step = (now) => {
            const progress = Math.min((now - start) / duration, 1);
            const value = Math.round(current + (target - current) * progress);
            counter.textContent = value.toLocaleString('id-ID');
            if (progress < 1) requestAnimationFrame(step);
        };

        // start from 0 visually
        counter.textContent = '0';
        requestAnimationFrame(step);
    });
}

function initStickyHeader() {
    const header = document.querySelector('.sticky-header');
    if (!header) {
        return;
    }

    const toggleShadow = () => {
        if (window.scrollY > 2) {
            header.classList.add('is-scrolled');
        } else {
            header.classList.remove('is-scrolled');
        }
    };

    toggleShadow();
    window.addEventListener('scroll', toggleShadow, { passive: true });
}

window.Alpine = Alpine;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    initLiveUpdates();
    initFilePreviews();
    initNotificationPolling();
    initScrollReveal();
    initCounters();
    initStickyHeader();
});
