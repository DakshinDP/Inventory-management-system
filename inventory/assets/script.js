document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-search-input]').forEach((input) => {
        const tableId = input.getAttribute('data-search-input');
        const table = document.getElementById(tableId);
        if (!table) return;

        input.addEventListener('input', () => {
            const query = input.value.toLowerCase().trim();
            table.querySelectorAll('tbody tr').forEach((row) => {
                row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
            });
        });
    });

    const poTable = document.getElementById('po-items-table');
    const addRowBtn = document.getElementById('add-item-row');

    if (poTable && addRowBtn) {
        const rowTemplate = () => {
            const firstRow = poTable.querySelector('tbody tr');
            const clone = firstRow.cloneNode(true);
            clone.querySelectorAll('input').forEach((input) => {
                input.value = input.name.includes('quantity') ? '1' : '0';
            });
            clone.querySelectorAll('select').forEach((select) => {
                select.selectedIndex = 0;
            });
            clone.querySelector('.line-subtotal').textContent = '0.00';
            return clone;
        };

        const updateTotals = () => {
            let total = 0;
            poTable.querySelectorAll('tbody tr').forEach((row) => {
                const qty = parseFloat(row.querySelector('input[name="quantity[]"]').value || 0);
                const priceInput = row.querySelector('input[name="unit_price[]"]');
                const productSelect = row.querySelector('select[name="product_id[]"]');
                const selected = productSelect.options[productSelect.selectedIndex];

                if (priceInput.value === '0' && selected?.dataset.price) {
                    priceInput.value = parseFloat(selected.dataset.price).toFixed(2);
                }

                const unitPrice = parseFloat(priceInput.value || 0);
                const subtotal = qty * unitPrice;
                row.querySelector('.line-subtotal').textContent = subtotal.toFixed(2);
                total += subtotal;
            });
            const totalLabel = document.getElementById('po-total');
            if (totalLabel) totalLabel.textContent = total.toFixed(2);
        };

        poTable.addEventListener('input', updateTotals);
        poTable.addEventListener('change', updateTotals);

        addRowBtn.addEventListener('click', () => {
            poTable.querySelector('tbody').appendChild(rowTemplate());
            updateTotals();
        });

        poTable.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement)) return;
            if (!target.classList.contains('remove-row')) return;
            const rows = poTable.querySelectorAll('tbody tr');
            if (rows.length > 1) {
                target.closest('tr')?.remove();
                updateTotals();
            }
        });

        updateTotals();
    }
});
