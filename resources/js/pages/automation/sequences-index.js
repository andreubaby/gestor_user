export function registerSequenceDashboardComponent(Alpine) {
    Alpine.data('sequenceDashboard', () => ({
        storageKey: 'automation-sequence-collapsed-v1',
        compactDefaultKey: 'automation-sequence-compact-default-v1',
        compactDefault: false,
        collapsedMap: {},

        init() {
            this.loadState();
        },

        loadState() {
            try {
                this.collapsedMap = JSON.parse(localStorage.getItem(this.storageKey) || '{}') || {};
            } catch (_) {
                this.collapsedMap = {};
            }

            try {
                this.compactDefault = localStorage.getItem(this.compactDefaultKey) === '1';
            } catch (_) {
                this.compactDefault = false;
            }
        },

        isCollapsed(id) {
            const key = String(id);
            if (Object.prototype.hasOwnProperty.call(this.collapsedMap, key)) {
                return Boolean(this.collapsedMap[key]);
            }
            return this.compactDefault;
        },

        toggleSequence(id) {
            const key = String(id);
            this.collapsedMap[key] = !this.isCollapsed(key);
            this.persistCollapsed();
        },

        collapseAll() {
            this.setAllSequences(true);
        },

        expandAll() {
            this.setAllSequences(false);
        },

        setAllSequences(collapsed) {
            document.querySelectorAll('.sequence-card[data-sequence-id]').forEach((card) => {
                const id = card.dataset.sequenceId;
                if (id) this.collapsedMap[id] = collapsed;
            });
            this.persistCollapsed();
        },

        persistCollapsed() {
            try {
                localStorage.setItem(this.storageKey, JSON.stringify(this.collapsedMap));
            } catch (_) {
                // LocalStorage opcional: sin persistencia, la UI sigue funcionando.
            }
        },

        persistCompactDefault() {
            try {
                localStorage.setItem(this.compactDefaultKey, this.compactDefault ? '1' : '0');
            } catch (_) {
                // Preferencia global opcional.
            }
        },
    }));
}

export function initAutomationSequencesIndexPage() {
    const dashboard = document.querySelector('[data-sequence-dashboard]');
    if (!dashboard) return;

    initLenis();
    initServerClock();
    initLiveStatusPolling(dashboard.dataset.liveStatusEndpoint || '');
}

function initLenis() {
    if (typeof window.Lenis === 'undefined') return;

    const reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
    const lowCores = typeof navigator.hardwareConcurrency === 'number' && navigator.hardwareConcurrency <= 2;
    const lowMemory = typeof navigator.deviceMemory === 'number' && navigator.deviceMemory <= 2;

    let lenis = null;
    let rafId = null;

    const shouldUseSmoothScroll = () => !reducedMotionQuery.matches && !lowCores && !lowMemory;

    const startLenis = () => {
        if (lenis || !shouldUseSmoothScroll()) return;

        lenis = new window.Lenis({
            duration: 1.0,
            smoothWheel: true,
            wheelMultiplier: 0.85,
            touchMultiplier: 1.15,
        });

        const raf = (time) => {
            if (!lenis) return;
            lenis.raf(time);
            rafId = requestAnimationFrame(raf);
        };

        rafId = requestAnimationFrame(raf);
    };

    const stopLenis = () => {
        if (rafId) {
            cancelAnimationFrame(rafId);
            rafId = null;
        }

        if (lenis) {
            lenis.destroy();
            lenis = null;
        }
    };

    const handlePreferenceChange = () => {
        if (shouldUseSmoothScroll()) {
            startLenis();
        } else {
            stopLenis();
        }
    };

    startLenis();

    if (typeof reducedMotionQuery.addEventListener === 'function') {
        reducedMotionQuery.addEventListener('change', handlePreferenceChange);
    } else if (typeof reducedMotionQuery.addListener === 'function') {
        reducedMotionQuery.addListener(handlePreferenceChange);
    }
}

function initServerClock() {
    const el = document.getElementById('server-time');
    if (!el) return;

    const start = new Date();
    const startedAt = Date.now();

    setInterval(() => {
        const now = new Date(start.getTime() + (Date.now() - startedAt));
        const pad = (value) => String(value).padStart(2, '0');
        el.textContent = `${pad(now.getDate())}/${pad(now.getMonth() + 1)}/${now.getFullYear()} ${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
    }, 1000);
}

function initLiveStatusPolling(endpoint) {
    const cards = Array.from(document.querySelectorAll('.sequence-card[data-sequence-id]'));
    if (!endpoint || cards.length === 0) return;

    const ids = cards.map((card) => card.dataset.sequenceId).filter(Boolean);

    const refreshStatus = async () => {
        try {
            const response = await fetch(`${endpoint}?ids=${encodeURIComponent(ids.join(','))}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) return;
            const payload = await response.json();
            const data = payload?.data || {};

            cards.forEach((card) => {
                const id = card.dataset.sequenceId;
                const row = data[id] || {};
                ['queued', 'executed', 'failed', 'duplicate_blocked'].forEach((status) => {
                    const target = card.querySelector(`[data-live-status="${status}"]`);
                    if (target) {
                        target.textContent = String(row[status] || 0);
                    }
                });
            });
        } catch (_) {
            // Fallo silencioso: conserva ultimos valores sin romper la experiencia.
        }
    };

    refreshStatus();
    setInterval(refreshStatus, 10000);
}

