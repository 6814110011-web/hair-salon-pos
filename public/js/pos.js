(() => {
    'use strict';
    const source = document.getElementById('pos-data');
    if (!source) return;
    const data = JSON.parse(source.textContent);
    const form = document.getElementById('checkout-form');
    const cartBody = document.getElementById('cart-items');
    const catalogueBody = document.getElementById('catalog-items');
    const search = document.getElementById('catalog-search');
    const method = document.getElementById('method');
    const received = document.getElementById('received');
    const promotion = document.getElementById('promotion_id');
    const submit = document.getElementById('checkout-submit');
    let category = 'all';
    let submitting = false;
    const cents = value => {
        const text = String(value ?? '');
        if (!/^\d{1,8}(\.\d{1,2})?$/.test(text)) return 0;
        const [whole, fraction = ''] = text.split('.');
        return Number(whole) * 100 + Number(fraction.padEnd(2, '0'));
    };
    const decimal = value => `${Math.floor(value / 100)}.${String(value % 100).padStart(2, '0')}`;
    const display = value => (value / 100).toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    const find = item => data.catalogue.find(entry => entry.type === item.type && String(entry.id) === String(item.id));
    const cart = Array.isArray(data.oldItems) ? data.oldItems.filter(item => item && typeof item === 'object').map(item => ({...item, qty: Number(item.qty) || 1})) : [];
    const element = (tag, text, className) => {
        const node = document.createElement(tag);
        if (text !== undefined) node.textContent = text;
        if (className) node.className = className;
        return node;
    };
    const hidden = (name, value) => { const input = element('input'); input.type = 'hidden'; input.name = name; input.value = String(value ?? ''); return input; };
    function catalogue() {
        catalogueBody.replaceChildren();
        const query = search.value.toLocaleLowerCase();
        data.catalogue.filter(entry => (category === 'all' || entry.type === category) && `${entry.name} ${entry.sku || ''}`.toLocaleLowerCase().includes(query)).forEach(entry => {
            const button = element('button', undefined, 'catalog-item'); button.type = 'button';
            button.append(element('small', entry.type === 'product' ? `สินค้า · คงเหลือ ${entry.stock} ${entry.unit}` : `บริการ · ${entry.minutes} นาที`), element('strong', entry.name), element('span', `${display(cents(entry.price))} บาท`));
            button.disabled = entry.type === 'product' && entry.stock < 1;
            button.addEventListener('click', () => {
                const existing = cart.find(item => item.type === entry.type && String(item.id) === String(entry.id));
                if (existing) existing.qty = Math.min(999, existing.qty + 1);
                else cart.push({type: entry.type, id: entry.id, qty: 1, expected_price: entry.price, stylist_id: ''});
                render();
            }); catalogueBody.append(button);
        });
        if (!catalogueBody.children.length) catalogueBody.append(element('p', 'ยังไม่มีรายการตามเงื่อนไข', 'empty-state'));
    }
    function totals() {
        let subtotal = 0;
        cart.forEach(item => { const entry = find(item); if (entry) subtotal += cents(entry.price) * (Number(item.qty) || 0); });
        const selected = data.promotions.find(entry => String(entry.id) === promotion.value);
        let discount = 0;
        if (selected) discount = selected.reward_type === 'percent' ? Math.floor((subtotal * cents(selected.reward_value) + 5000) / 10000) : cents(selected.reward_value);
        discount = Math.min(subtotal, discount);
        const total = subtotal - discount;
        document.getElementById('subtotal-preview').textContent = display(subtotal);
        document.getElementById('discount-preview').textContent = display(discount);
        document.getElementById('total-preview').textContent = display(total);
        const noncash = method.value !== 'cash';
        received.readOnly = noncash;
        if (noncash) received.value = decimal(total);
        document.getElementById('reference-field').hidden = !noncash;
        document.getElementById('ref_no').required = noncash;
        document.getElementById('change-preview').textContent = display(Math.max(0, cents(received.value) - total));
        const stale = cart.some(item => !find(item) || cents(item.expected_price) !== cents(find(item).price));
        const notice = document.getElementById('cart-notice');
        notice.hidden = !stale; notice.textContent = 'บางรายการถูกปิดใช้หรือราคาเปลี่ยน กรุณาลบรายการหรือยืนยันราคาใหม่';
        submit.disabled = submitting || !cart.length || stale;
    }
    function render() {
        cartBody.replaceChildren();
        cart.forEach((item, index) => {
            const entry = find(item); const row = element('tr'); const name = element('td');
            name.append(element('strong', entry?.name || `รายการไม่พร้อมใช้งาน (${item.id ?? '?'})`));
            const prefix = `items[${index}]`;
            name.append(hidden(`${prefix}[type]`, item.type), hidden(`${prefix}[id]`, item.id), hidden(`${prefix}[expected_price]`, item.expected_price));
            if (entry) name.append(element('small', `${display(cents(entry.price))} บาท/รายการ`));
            if (entry && cents(item.expected_price) !== cents(entry.price)) {
                const refresh = element('button', 'ยืนยันราคาใหม่', 'button-secondary compact'); refresh.type = 'button';
                refresh.addEventListener('click', () => { item.expected_price = entry.price; render(); }); name.append(refresh);
            }
            if (item.type === 'service') {
                const label = element('label', 'ช่าง *'); const select = element('select'); select.name = `${prefix}[stylist_id]`; select.required = true;
                const prompt = element('option', 'เลือกช่าง'); prompt.value = ''; select.append(prompt);
                data.stylists.forEach(stylist => { const option = element('option', stylist.name); option.value = stylist.id; option.selected = String(item.stylist_id) === String(stylist.id); select.append(option); });
                select.addEventListener('change', () => { item.stylist_id = select.value; }); label.append(select); name.append(label);
            }
            const quantityCell = element('td'); const quantity = element('input'); quantity.type = 'number'; quantity.name = `${prefix}[qty]`; quantity.min = '1'; quantity.max = '999'; quantity.required = true; quantity.value = item.qty; quantity.setAttribute('aria-label', `จำนวน ${entry?.name || 'รายการ'}`);
            const sum = element('td', entry ? display(cents(entry.price) * item.qty) : '—');
            quantity.addEventListener('input', () => { item.qty = Number(quantity.value); sum.textContent = display(cents(entry?.price) * item.qty); totals(); }); quantityCell.append(quantity);
            const removeCell = element('td'); const remove = element('button', 'ลบ', 'button-secondary compact'); remove.type = 'button'; remove.setAttribute('aria-label', `ลบ ${entry?.name || 'รายการ'}`); remove.addEventListener('click', () => { cart.splice(index, 1); render(); }); removeCell.append(remove);
            row.append(name, quantityCell, sum, removeCell); cartBody.append(row);
        });
        if (!cart.length) { const row = element('tr'); const cell = element('td', 'เลือกสินค้าหรือบริการเพื่อเริ่มขาย', 'empty-state'); cell.colSpan = 4; row.append(cell); cartBody.append(row); }
        totals();
    }
    search.addEventListener('input', catalogue);
    document.querySelectorAll('[data-catalog-type]').forEach(button => button.addEventListener('click', () => { category = button.dataset.catalogType; catalogue(); }));
    method.addEventListener('change', totals); promotion.addEventListener('change', totals); received.addEventListener('input', totals);
    form.addEventListener('submit', event => {
        if (!cart.length) { event.preventDefault(); return; }
        submitting = true; totals(); submit.textContent = 'กำลังบันทึก…';
    });
    window.addEventListener('pageshow', () => { submitting = false; submit.textContent = 'ยืนยันชำระเงิน'; totals(); });
    catalogue(); render();
})();
