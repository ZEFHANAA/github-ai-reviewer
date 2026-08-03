// Presentational client-side filter for repository findings.
// Native DOM only: reads [data-repository-filters] controls and toggles
// `hidden` on [data-filter-target="finding"] cards. Toast for rule-ID copy.
// No state manager, no abstraction.
document.addEventListener('DOMContentLoaded', () => {
    // ───── Analyze form loading state ─────
    document.querySelectorAll('[data-analyze-form]').forEach((form) => {
        form.addEventListener('submit', () => {
            const submit = form.querySelector('[data-analyze-submit]');
            const status = form.querySelector('[data-analyze-status]');

            if (submit instanceof HTMLButtonElement) {
                submit.disabled = true;
                submit.textContent = submit.dataset.loadingLabel || 'Analyzing…';
            }

            if (status) {
                status.textContent = 'Fetching repository signals and preparing your report…';
            }
        });
    });

    // ───── Ring gauge animation ─────
    const ringArc = document.querySelector('[data-ring-arc]');
    if (ringArc) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    const offset = ringArc.getAttribute('style').match(/--ring-offset:([\d.]+)/)?.[1];
                    if (offset) {
                        // Delay slightly so the component is fully painted
                        requestAnimationFrame(() => {
                            requestAnimationFrame(() => {
                                ringArc.style.strokeDashoffset = offset;
                            });
                        });
                    }
                    observer.unobserve(ringArc);
                }
            });
        }, { threshold: 0.1 });
        observer.observe(ringArc);
    }

    // ───── Category progress bar animation ─────
    const bars = document.querySelectorAll('[data-category-bar]');
    const barObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                const bar = entry.target;
                const w = bar.style.width;
                bar.style.width = '0%';
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        bar.style.width = w;
                    });
                });
                barObserver.unobserve(bar);
            }
        });
    }, { threshold: 0.2 });
    bars.forEach((b) => barObserver.observe(b));

    // ───── Toast system ─────
    function showToast(text) {
        const old = document.querySelector('.toast');
        if (old) old.remove();

        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.textContent = text;
        document.body.appendChild(toast);

        toast.addEventListener('animationend', () => {
            if (toast.style.opacity === '0') toast.remove();
        });
        // fallback remove after 2.5s
        setTimeout(() => {
            if (toast.parentNode) toast.remove();
        }, 2500);
    }

    // ───── Copy rule ID to clipboard ─────
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-copy-rule-id]');
        if (!btn) return;

        const ruleId = btn.getAttribute('data-copy-rule-id');
        if (!ruleId) return;

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(ruleId).then(() => {
                showToast(`Copied ${ruleId} to clipboard`);
            }).catch(() => {
                // fallback for older browsers
                fallbackCopy(ruleId);
            });
        } else {
            fallbackCopy(ruleId);
        }
    });

    function fallbackCopy(text) {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        try {
            document.execCommand('copy');
            showToast(`Copied ${text} to clipboard`);
        } catch (_) {
            // silently fail
        }
        document.body.removeChild(ta);
    }

    // ───── Filter system ─────
    const container = document.querySelector('[data-repository-filters]');
    if (!container) return;

    const cards = Array.from(document.querySelectorAll('[data-filter-target="finding"]'));
    const empty = document.querySelector('[data-finding-empty]');
    const controls = Array.from(container.querySelectorAll('[data-filter-key]'));
    const clearButton = container.querySelector('[data-filter-clear]');
    const status = container.querySelector('[data-filter-status]');

    if (cards.length === 0 || controls.length === 0) return;

    const focusTarget = container.querySelector('[data-filter-key]') || clearButton;
    const resetValues = () => controls.forEach((control) => { control.value = ''; });

    const applyFilters = () => {
        const active = {};
        controls.forEach((control) => {
            if (control.value) active[control.dataset.filterKey] = control.value;
        });

        const keys = Object.keys(active);
        let visibleCount = 0;

        cards.forEach((card) => {
            const matches = keys.every((key) => card.dataset[key] === active[key]);

            // move focus if the active card is about to hide
            if (!matches && !card.hidden && card.contains(document.activeElement) && focusTarget) {
                focusTarget.focus();
            }

            const wasHidden = card.hidden;
            card.hidden = !matches;

            // animate newly visible cards
            if (matches && wasHidden) {
                card.classList.remove('filter-match');
                // force reflow
                void card.offsetWidth;
                card.classList.add('filter-match');
            }

            if (matches) visibleCount += 1;
        });

        if (empty) {
            empty.hidden = visibleCount !== 0;
        }

        if (status) {
            status.textContent = visibleCount === 0
                ? 'No matching repository checks.'
                : `Showing ${visibleCount} of ${cards.length} repository checks.`;
        }
    };

    controls.forEach((control) => control.addEventListener('change', applyFilters));

    if (clearButton) {
        clearButton.addEventListener('click', () => {
            resetValues();
            applyFilters();
        });
    }
});