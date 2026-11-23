(() => {
    const root = document.getElementById('ib-cashier');
    const pay = document.getElementById('ib-cashier-pay');
    const totalNode = document.getElementById('ib-cashier-total');
    const confirmButton = document.getElementById('ib-cashier-confirm');
    const feedback = document.getElementById('ib-cashier-feedback');
    const search = document.getElementById('ib-cashier-search');
    const query = document.getElementById('ib-cashier-query');
    const results = document.getElementById('ib-cashier-results');
    const searchFeedback = document.getElementById('ib-cashier-search-feedback');
    const lines = document.getElementById('ib-cashier-lines');
    const empty = document.getElementById('ib-cashier-empty');
    const cash = document.getElementById('ib-cashier-cash');
    const mobile = document.getElementById('ib-cashier-mobile');
    const exact = root.querySelector('.ib-cashier-exact');
    const change = document.getElementById('ib-cashier-change');
    const existing = Number(root.dataset.order);
    const decimals = Number(root.dataset.decimals);
    const scale = 10 ** decimals;
    const currency = new Intl.NumberFormat('fr', {
        style: 'currency',
        currency: root.dataset.currency,
        currencyDisplay: 'narrowSymbol',
        minimumFractionDigits: 0,
        maximumFractionDigits: decimals,
    });
    const storageKey = 'infiny-caisse-' + window.location.pathname;
    let token = root.dataset.token;
    let cart = new Map();
    let totalOverride = existing ? Number(totalNode.dataset.total) : null;
    let searching = 0;
    let searchTimer;
    let busy = false;

    if (!existing) {
        try {
            const saved = JSON.parse(sessionStorage.getItem(storageKey));
            if (saved?.token && Array.isArray(saved.items)) {
                token = saved.token;
                cart = new Map(saved.items.map(item => [item.id, item]));
            }
        } catch {
            // The cashier works even when browser storage is disabled.
        }
    }

    function retainCart() {
        try {
            sessionStorage.setItem(storageKey, JSON.stringify({token, items: [...cart.values()]}));
        } catch {
            // Keep the current cart in memory.
        }
    }

    function total() {
        const amount = totalOverride ?? [...cart.values()].reduce((sum, item) => sum + item.price * item.quantity, 0);
        return Math.round(amount * scale) / scale;
    }

    function localAmount(value) {
        const cleaned = value.replace(/[\s\u00a0\u202f]+/g, '').replace(',', '.');
        return /^\d+(?:\.\d+)?$/.test(cleaned) ? Number(cleaned) : null;
    }

    function displayPayment() {
        const amount = total();
        totalNode.textContent = currency.format(amount);
        const isCash = pay.elements.methode.value === 'especes';
        cash.hidden = exact.hidden = !isCash;
        mobile.hidden = isCash;
        pay.elements.recu.required = isCash;
        pay.elements.transaction.required = !isCash;
        const received = localAmount(pay.elements.recu.value);
        const difference = received === null ? null : Math.round((received - amount) * scale) / scale;
        change.hidden = !isCash || difference === null || (!existing && cart.size === 0);
        if (!change.hidden) {
            change.textContent = difference < 0
                ? 'Il manque ' + currency.format(-difference)
                : 'Monnaie à rendre : ' + currency.format(difference);
            change.classList.toggle('ib-cashier-error', difference < 0);
        }
        confirmButton.disabled = busy || (!existing && cart.size === 0) ||
            (isCash ? difference === null || difference < 0 : pay.elements.transaction.value.trim() === '');
        confirmButton.textContent = busy ? 'Encaissement en cours…' : 'Encaisser ' + currency.format(amount);
    }

    function setItem(product, quantity) {
        if (busy) return;
        if (quantity > 999 || (product.stock !== null && quantity > product.stock)) {
            feedback.textContent = 'Stock insuffisant pour « ' + product.name + ' ».';
            return;
        }
        feedback.textContent = '';
        totalOverride = null;
        if (quantity <= 0) {
            cart.delete(product.id);
        } else {
            cart.set(product.id, {...product, quantity});
        }
        retainCart();
        displayCart();
    }

    function quantityButton(label, text, action) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'button';
        button.setAttribute('aria-label', label);
        button.textContent = text;
        button.addEventListener('click', action);
        return button;
    }

    function displayCart() {
        if (lines) {
            lines.replaceChildren();
            empty.hidden = cart.size !== 0;
            for (const item of cart.values()) {
                const row = document.createElement('li');
                row.className = 'ib-cashier-cart-row';
                const name = document.createElement('span');
                name.textContent = item.name;
                const controls = document.createElement('div');
                controls.className = 'ib-cashier-quantity';
                const count = document.createElement('span');
                count.textContent = item.quantity;
                const amount = document.createElement('strong');
                amount.textContent = currency.format(item.price * item.quantity);
                controls.append(
                    quantityButton('Retirer une unité de ' + item.name, '−', () => setItem(item, item.quantity - 1)),
                    count,
                    quantityButton('Ajouter une unité de ' + item.name, '+', () => setItem(item, item.quantity + 1)),
                );
                row.append(name, controls, amount);
                lines.append(row);
            }
        }
        displayPayment();
    }

    async function request(action, values) {
        const data = new URLSearchParams(values);
        data.set('action', action);
        data.set('nonce', root.dataset.nonce);
        const response = await fetch(root.dataset.endpoint, {method: 'POST', body: data});
        const result = await response.json();
        return result;
    }

    function displayProducts(products) {
        results.replaceChildren();
        for (const item of products) {
            const tile = document.createElement('button');
            tile.type = 'button';
            tile.className = 'ib-cashier-product';
            tile.disabled = item.stock !== null && item.stock < 1;
            if (item.image) {
                const image = document.createElement('img');
                image.src = item.image;
                image.alt = '';
                image.loading = 'lazy';
                tile.append(image);
            }
            const name = document.createElement('strong');
            name.textContent = item.name;
            const amount = document.createElement('span');
            amount.textContent = currency.format(item.price);
            const stock = document.createElement('small');
            stock.textContent = [
                item.sku,
                item.stock === null ? '' : (item.stock < 1 ? 'Rupture' : item.stock + ' en stock'),
            ].filter(Boolean).join(' · ');
            tile.append(name, amount);
            if (stock.textContent) tile.append(stock);
            tile.addEventListener('click', () => {
                setItem(item, (cart.get(item.id)?.quantity ?? 0) + 1);
                if (window.matchMedia('(pointer: fine)').matches) query.focus();
            });
            results.append(tile);
        }
    }

    async function findProducts(addExact = false, value = query.value.trim()) {
        const sequence = ++searching;
        searchFeedback.textContent = 'Recherche en cours…';
        try {
            const response = await request('infiny_cashier_products', {q: value});
            if (!response.success) {
                if (sequence === searching) searchFeedback.textContent = response.data?.message || 'Recherche indisponible.';
                return;
            }
            const {products, exact: exactMatch} = response.data;
            if (addExact && exactMatch && products.length === 1) {
                const item = products[0];
                setItem(item, (cart.get(item.id)?.quantity ?? 0) + 1);
            }
            if (sequence !== searching) return;
            displayProducts(products);
            searchFeedback.textContent = products.length ? '' : 'Aucun article trouvé.';
            if (addExact && !exactMatch) query.value = value;
        } catch {
            if (sequence === searching) searchFeedback.textContent = 'Recherche indisponible. Réessayez.';
        }
    }

    search?.addEventListener('submit', event => {
        event.preventDefault();
        clearTimeout(searchTimer);
        const code = query.value.trim();
        query.value = '';
        findProducts(true, code);
    });
    query?.addEventListener('input', () => {
        ++searching;
        clearTimeout(searchTimer);
        searchTimer = setTimeout(findProducts, 220);
    });

    pay.addEventListener('input', displayPayment);
    exact.addEventListener('click', () => {
        pay.elements.recu.value = total().toFixed(decimals);
        displayPayment();
        confirmButton.focus();
    });
    pay.addEventListener('submit', async event => {
        event.preventDefault();
        if (confirmButton.disabled) return;
        const data = Object.fromEntries(new FormData(pay));
        data.commande = existing;
        data.session_id = root.dataset.session;
        data.token = token;
        data.panier = JSON.stringify([...cart.values()].map(({id, quantity}) => ({id, quantity})));
        data.total_attendu = total().toFixed(decimals);
        busy = true;
        feedback.textContent = '';
        displayPayment();
        try {
            const result = await request('infiny_cashier_checkout', data);
            if (result.success && result.data.ticket) {
                if (!existing) {
                    try { sessionStorage.removeItem(storageKey); } catch {}
                }
                window.location.assign(result.data.ticket);
                return;
            }
            if (typeof result.data?.total === 'number') totalOverride = result.data.total;
            feedback.textContent = result.data?.message || 'Encaissement indisponible. Réessayez.';
        } catch {
            feedback.textContent = 'La caisse n’a pas reçu de confirmation. Réessayez sans créer une nouvelle vente.';
        } finally {
            busy = false;
            displayPayment();
        }
    });

    displayCart();
    if (search) {
        findProducts();
        if (window.matchMedia('(pointer: fine)').matches) query.focus();
    }
})();
