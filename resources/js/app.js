// Presentational client-side filter for repository findings.
// Native DOM only: reads [data-repository-filters] controls and toggles
// `hidden` on [data-filter-target="finding"] cards matching the active
// category/severity/status values. When no card is visible, shows the
// empty state and reports a live result count for screen readers.
// No state manager, no abstraction.
document.addEventListener('DOMContentLoaded', () => {
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

    const container = document.querySelector('[data-repository-filters]');

    if (!container) {
        return;
    }

    const cards = Array.from(document.querySelectorAll('[data-filter-target="finding"]'));
    const empty = document.querySelector('[data-finding-empty]');
    const controls = Array.from(container.querySelectorAll('[data-filter-key]'));
    const clearButton = container.querySelector('[data-filter-clear]');
    const status = container.querySelector('[data-filter-status]');

    if (cards.length === 0 || controls.length === 0) {
        return;
    }

    const focusTarget = container.querySelector('[data-filter-key]') || clearButton;

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
            // If the focused card is about to be hidden, move focus to a stable
            // control so keyboard users never lose their place to the body.
            if (!matches && !card.hidden && card.contains(document.activeElement) && focusTarget) {
                focusTarget.focus();
            }
            card.hidden = !matches;
            if (matches) {
                visibleCount += 1;
            }
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
