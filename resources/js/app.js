// Presentational client-side filter for repository findings.
// Native DOM only: reads [data-repository-filters] controls and toggles
// `hidden` on [data-filter-target="finding"] cards matching the active
// category/severity/status values. When no card is visible, shows the
// empty state. No state manager, no abstraction.
document.addEventListener('DOMContentLoaded', () => {
    const container = document.querySelector('[data-repository-filters]');

    if (!container) {
        return;
    }

    const cards = Array.from(document.querySelectorAll('[data-filter-target="finding"]'));
    const empty = document.querySelector('[data-finding-empty]');
    const controls = Array.from(container.querySelectorAll('[data-filter-key]'));
    const clearButton = container.querySelector('[data-filter-clear]');

    if (cards.length === 0 || controls.length === 0) {
        return;
    }

    const resetValues = () => controls.forEach((control) => { control.value = ''; });

    const applyFilters = () => {
        const active = {};
        controls.forEach((control) => {
            if (control.value) {
                active[control.dataset.filterKey] = control.value;
            }
        });

        const keys = Object.keys(active);
        let visibleCount = 0;

        cards.forEach((card) => {
            const matches = keys.every((key) => card.dataset[key] === active[key]);
            card.hidden = !matches;
            if (matches) {
                visibleCount += 1;
            }
        });

        if (empty) {
            empty.hidden = visibleCount !== 0;
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
