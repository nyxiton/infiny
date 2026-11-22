(() => {
    const board = document.getElementById('ib-order-board');
    const feedback = document.getElementById('ib-board-feedback');
    const columns = new Map(Array.from(board.querySelectorAll('.ib-lane'), lane => [lane.dataset.status, lane]));
    const number = new Intl.NumberFormat(document.documentElement.lang);
    let draggedCard;
    let highlightedLane;

    function highlight(lane) {
        highlightedLane?.classList.remove('ib-lane-target');
        highlightedLane = lane;
        highlightedLane?.classList.add('ib-lane-target');
    }

    function updateCount(lane, change) {
        const badge = lane.querySelector('.ib-lane-count');
        const total = Number(badge.dataset.count) + change;
        badge.dataset.count = total;
        badge.textContent = number.format(total);
        const visible = lane.querySelectorAll('.ib-order-card').length;
        lane.querySelector('.ib-lane-empty').hidden = total !== 0;
        lane.querySelector('.ib-lane-more').hidden = total <= visible;
    }

    async function move(card, lane, restoreFocus = false) {
        const origin = card.closest('.ib-lane');
        if (!lane || lane === origin || card.hasAttribute('aria-busy')) return;

        const form = card.querySelector('.ib-order-move');
        const select = form.elements.statut;
        const button = form.querySelector('button');
        const data = new FormData(form);
        data.set('statut', lane.dataset.status);
        data.set('format', 'json');

        card.setAttribute('aria-busy', 'true');
        card.draggable = false;
        select.disabled = true;
        button.disabled = true;
        feedback.textContent = 'Enregistrement du statut…';

        try {
            const response = await fetch(form.action, {method: 'POST', body: data});
            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.data?.message || 'Le statut n’a pas pu être enregistré.');
            }

            lane.querySelector('.ib-lane-cards').prepend(card);
            form.elements.statut_initial.value = lane.dataset.status;
            updateCount(origin, -1);
            updateCount(lane, 1);
            const heading = lane.querySelector('h3').textContent;
            const order = card.querySelector('.ib-order-card-top a').textContent;
            feedback.textContent = `Commande ${order} déplacée vers « ${heading} ».`;
        } catch (error) {
            feedback.textContent = error instanceof SyntaxError || error instanceof TypeError
                ? 'Impossible de confirmer le déplacement. Rechargez le tableau avant de réessayer.'
                : error.message;
        } finally {
            select.value = form.elements.statut_initial.value;
            select.disabled = false;
            button.disabled = false;
            card.draggable = true;
            card.removeAttribute('aria-busy');
            if (restoreFocus) select.focus();
        }
    }

    board.addEventListener('submit', event => {
        const form = event.target.closest('.ib-order-move');
        if (!form) return;
        event.preventDefault();
        move(form.closest('.ib-order-card'), columns.get(form.elements.statut.value), true);
    });

    board.addEventListener('dragstart', event => {
        const card = event.target.closest('.ib-order-card[draggable="true"]');
        if (!card) return;
        draggedCard = card;
        card.classList.add('ib-order-dragging');
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', card.querySelector('input[name="id"]').value);
    });

    board.addEventListener('dragover', event => {
        if (!draggedCard) return;
        const lane = event.target.closest('.ib-lane');
        if (!lane || lane.contains(draggedCard)) {
            highlight(null);
            return;
        }
        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
        highlight(lane);
    });

    board.addEventListener('dragleave', event => {
        if (highlightedLane && !highlightedLane.contains(event.relatedTarget)) highlight(null);
    });

    board.addEventListener('drop', event => {
        if (!draggedCard) return;
        event.preventDefault();
        move(draggedCard, event.target.closest('.ib-lane'));
        stopDragging();
    });

    function stopDragging() {
        draggedCard?.classList.remove('ib-order-dragging');
        draggedCard = null;
        highlight(null);
    }

    board.addEventListener('dragend', stopDragging);
})();
