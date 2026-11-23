(() => {
    const shell = document.querySelector('.ib-shell');
    const toggle = shell.querySelector('.ib-menu-toggle');

    function setMenu(open) {
        shell.dataset.menu = open ? 'open' : 'closed';
        toggle.setAttribute('aria-expanded', String(open));
    }

    setMenu(false);
    toggle.addEventListener('click', () => setMenu(toggle.getAttribute('aria-expanded') !== 'true'));
    shell.addEventListener('keydown', event => {
        if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true' && toggle.getClientRects().length) {
            setMenu(false);
            toggle.focus();
        }
    });

    for (const table of document.querySelectorAll('.ib-table > table')) {
        const head = table.tHead;
        const headers = head?.rows.length === 1 ? Array.from(head.rows[0].cells) : [];
        const rows = Array.from(table.tBodies).flatMap(body => Array.from(body.rows));
        if (!headers.length || !rows.length || rows.some(row => row.cells.length !== headers.length ||
            Array.from(row.cells).some(cell => cell.colSpan !== 1 || cell.rowSpan !== 1))) continue;

        // Explicit roles keep table navigation available when the mobile layout stacks the cells.
        table.setAttribute('role', 'table');
        head.setAttribute('role', 'rowgroup');
        head.rows[0].setAttribute('role', 'row');
        headers.forEach(header => header.setAttribute('role', 'columnheader'));
        Array.from(table.tBodies).forEach(body => body.setAttribute('role', 'rowgroup'));
        for (const row of rows) {
            row.setAttribute('role', 'row');
            Array.from(row.cells).forEach((cell, index) => {
                cell.setAttribute('role', 'cell');
                const label = document.createElement('span');
                label.className = 'ib-cell-label';
                label.textContent = headers[index].textContent.trim();
                label.setAttribute('aria-hidden', 'true');
                const content = document.createElement('div');
                content.className = 'ib-cell-content';
                content.append(...Array.from(cell.childNodes));
                cell.append(label, content);
            });
        }
        table.classList.add('ib-table-stacked');
    }
})();
