document.addEventListener('DOMContentLoaded', () => {
    const scheduleCard = document.querySelector('.dashboard-schedule');
    if (!scheduleCard) {
        return;
    }

    const filterButtons = scheduleCard.querySelectorAll('[data-instructor-filter]');
    const completionButton = scheduleCard.querySelector('[data-completed-filter]');
    const sessions = scheduleCard.querySelectorAll('.session[data-instructor-id]');
    const selectedCountEl = scheduleCard.querySelector('#selectedSessionCount');
    const instructorIds = new Set(
        Array.from(filterButtons).map((button) => button.dataset.instructorFilter)
    );

    const readFiltersFromQuery = () => {
        const params = new URLSearchParams(window.location.search);
        const instructorId = params.get('instructor') || 'all';
        const completedOnly = params.get('completed') === '1';

        return {
            instructorId: instructorIds.has(instructorId) ? instructorId : 'all',
            completedOnly,
        };
    };

    const filterState = readFiltersFromQuery();

    const syncQuery = () => {
        const url = new URL(window.location.href);

        if (filterState.instructorId && filterState.instructorId !== 'all') {
            url.searchParams.set('instructor', filterState.instructorId);
        } else {
            url.searchParams.delete('instructor');
        }

        if (filterState.completedOnly) {
            url.searchParams.set('completed', '1');
        } else {
            url.searchParams.delete('completed');
        }

        window.history.replaceState({}, '', url);
    };

    const updateFilters = () => {
        filterButtons.forEach((button) => {
            const isActive = button.dataset.instructorFilter === filterState.instructorId;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        if (completionButton) {
            completionButton.classList.toggle('is-active', filterState.completedOnly);
            completionButton.setAttribute('aria-pressed', filterState.completedOnly ? 'true' : 'false');
        }

        let visibleCount = 0;
        sessions.forEach((session) => {
            const isMatchInstructor = filterState.instructorId === 'all'
                || session.dataset.instructorId === filterState.instructorId;
            const isCompleted = session.dataset.completed === '1';
            const isMatchCompleted = !filterState.completedOnly || isCompleted;
            const isVisible = isMatchInstructor && isMatchCompleted;

            session.classList.toggle('is-hidden', !isVisible);
            session.classList.toggle(
                'is-highlighted',
                filterState.instructorId !== 'all' && isMatchInstructor && isVisible
            );

            if (isVisible) {
                visibleCount += 1;
            }
        });

        if (selectedCountEl) {
            selectedCountEl.textContent = visibleCount;
        }

        syncQuery();
    };

    filterButtons.forEach((button) => {
        button.addEventListener('click', () => {
            filterState.instructorId = button.dataset.instructorFilter;
            updateFilters();
        });
    });

    if (completionButton) {
        completionButton.addEventListener('click', () => {
            filterState.completedOnly = !filterState.completedOnly;
            updateFilters();
        });
    }

    updateFilters();
});
