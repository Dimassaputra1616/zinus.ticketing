const buildRefreshUrl = (container) => {
    const baseUrl = container.dataset.liveUrl || window.location.href;
    const url = new URL(baseUrl, window.location.origin);

    const baseQuery = container.dataset.liveQuery ?? '';

    if (baseQuery) {
        url.search = baseQuery;
    } else if (container.dataset.liveUrl) {
        // keep existing query string from provided URL
        const providedUrl = new URL(baseUrl, window.location.origin);
        url.search = providedUrl.search;
    } else {
        url.search = window.location.search;
    }

    const params = new URLSearchParams(url.search);
    params.set('refresh', '1');
    url.search = params.toString();

    return url.toString();
};

const initContainerRefresh = (container) => {
    const interval = Math.max(parseInt(container.dataset.liveInterval || '10000', 10), 2000);
    let checksum = container.dataset.liveChecksum || '';

    const applyFragments = (payload) => {
        const fragments = payload.fragments || {};
        Object.entries(fragments).forEach(([slot, html]) => {
            const target = container.querySelector(`[data-live-slot="${slot}"]`);
            if (target) {
                if (slot === 'ticket-table') {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const incomingResults = doc.querySelector('[data-ticket-results]');
                    const currentResults = target.querySelector('[data-ticket-results]');
                    if (incomingResults && currentResults) {
                        currentResults.innerHTML = incomingResults.innerHTML;
                        if (window.Alpine?.initTree) window.Alpine.initTree(currentResults);
                        return;
                    }
                }

                target.innerHTML = html;
                if (window.Alpine?.initTree) window.Alpine.initTree(target);
            }
        });
    };

    const fetchUpdates = async (overrideUrl) => {
        try {
            const response = await fetch(overrideUrl || buildRefreshUrl(container), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                return;
            }

            const payload = await response.json();
            if (!payload || !payload.checksum || payload.checksum === checksum) {
                return;
            }

            applyFragments(payload);

            checksum = payload.checksum;
            container.dataset.liveChecksum = checksum;
        } catch (error) {
            console.error('Gagal memuat pembaruan otomatis:', error);
        }
    };

    fetchUpdates();
    return window.setInterval(fetchUpdates, interval);
};

export default function initLiveUpdates() {
    const containers = document.querySelectorAll('[data-live-refresh]');

    async function fetchAndSwap(container, targetUrl) {
        const url = new URL(targetUrl, window.location.origin);
        url.searchParams.set('refresh', '1');

        const response = await fetch(url.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            return false;
        }

        const payload = await response.json();
        if (!payload || !payload.checksum) {
            return false;
        }

        const fragments = payload.fragments || {};
        if (!Object.keys(fragments).length) {
            return false;
        }

        const cleanUrl = new URL(url.toString());
        cleanUrl.searchParams.delete('refresh');
        container.dataset.liveUrl = cleanUrl.toString();
        container.dataset.liveQuery = cleanUrl.searchParams.toString();
        container.dataset.liveChecksum = payload.checksum;
        window.history.replaceState({}, '', cleanUrl.toString());

        Object.entries(fragments).forEach(([slot, html]) => {
            const target = container.querySelector(`[data-live-slot="${slot}"]`);
            if (target) {
                target.innerHTML = html;
                if (window.Alpine?.initTree) window.Alpine.initTree(target);
            }
        });

        return true;
    }

    containers.forEach((container) => {
        const timerId = initContainerRefresh(container);

        container.addEventListener(
            'live:refresh:stop',
            () => {
                window.clearInterval(timerId);
            },
            { once: true }
        );

        container.addEventListener('click', async (event) => {
            const anchor = event.target.closest('[data-live-link]');
            if (!anchor || !container.contains(anchor)) {
                return;
            }

            event.preventDefault();

            try {
                const ok = await fetchAndSwap(container, anchor.getAttribute('href'));
                if (!ok) {
                    window.location.href = anchor.href;
                }
            } catch (error) {
                console.error('Gagal memuat filter tiket:', error);
                window.location.href = anchor.href;
            }
        });

        container.addEventListener('submit', async (event) => {
            const form = event.target.closest('[data-live-form]');
            if (!form || !container.contains(form)) {
                return;
            }

            event.preventDefault();
            const url = new URL(form.getAttribute('action') || window.location.href, window.location.origin);
            const formData = new FormData(form);
            formData.forEach((value, key) => {
                url.searchParams.set(key, value.toString());
            });

            try {
                const ok = await fetchAndSwap(container, url.toString());
                if (!ok) {
                    form.submit();
                }
            } catch (error) {
                console.error('Gagal memuat filter tiket (form):', error);
                form.submit();
            }
        });
    });
}
