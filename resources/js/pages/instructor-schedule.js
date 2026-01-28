document.addEventListener('DOMContentLoaded', () => {
    const scheduleTable = document.getElementById('scheduleTable');
    if (!scheduleTable) {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) {
        return;
    }

    let draggedSessionId = null;

    scheduleTable.addEventListener('dragstart', (event) => {
        const target = event.target.closest('.session');
        if (!target) {
            return;
        }
        draggedSessionId = target.dataset.sessionId;
        event.dataTransfer.setData('text/plain', draggedSessionId);
    });

    scheduleTable.addEventListener('dragover', (event) => {
        const cell = event.target.closest('.schedule-slot');
        if (cell && !cell.querySelector('.session')) {
            event.preventDefault();
        }
    });

    scheduleTable.addEventListener('drop', async (event) => {
        const cell = event.target.closest('.schedule-slot');
        if (!cell || cell.querySelector('.session')) {
            return;
        }
        event.preventDefault();
        const sessionId = draggedSessionId || event.dataTransfer.getData('text/plain');
        const weekday = cell.dataset.weekday;
        const startTime = cell.dataset.time;

        try {
            const response = await fetch(`/schedule/sessions/${sessionId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ weekday, start_time: startTime }),
            });

            if (!response.ok) {
                const data = await response.json();
                alert(data.message || '이동할 수 없습니다.');
                return;
            }

            window.location.reload();
        } catch (error) {
            alert('이동 중 오류가 발생했습니다.');
        }
    });
});
