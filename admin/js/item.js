const APP_ID = 1;

document.addEventListener('DOMContentLoaded', loadItems);

async function loadItems() {
    const loadingMsg = document.getElementById('loadingMessage');
    const errorMsg = document.getElementById('errorMessage');
    const tableContent = document.getElementById('tableContent');

    loadingMsg.style.display = 'block';
    errorMsg.style.display = 'none';
    tableContent.style.display = 'none';

    try {
        const response = await fetch(`../api/item/full_item.php?id_app=${APP_ID}`);
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || 'Gagal memuat data produk');
        }

        const items = data.data || [];
        renderTable(items);

        loadingMsg.style.display = 'none';
        tableContent.style.display = 'block';

    } catch (error) {
        loadingMsg.style.display = 'none';
        errorMsg.style.display = 'flex';
        errorMsg.innerHTML = `<span>⚠️</span><span>${escapeHtml(error.message)}</span>`;
        console.error('Error loading items:', error);
    }
}

function renderTable(items) {
    const tableBody = document.getElementById('itemsTableBody');
    tableBody.innerHTML = '';

    if (items.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="8">
                    <div class="empty-state">
                        <div class="empty-state-icon">📭</div>
                        <div class="empty-state-title">Tidak Ada Produk</div>
                        <div class="empty-state-text">Belum ada produk yang tersedia</div>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    items.forEach(item => {
        const row = createItemRow(item);
        tableBody.appendChild(row);
    });
}

function createItemRow(item) {
    const row = document.createElement('tr');

    const price = item.price?.current_price || 0;
    const currency = item.price?.currency || 'IDR';
    const stock = item.stock?.total_available_stock || 0;
    const updateTime = item.update_time ? formatTimestamp(item.update_time) : '-';
    const category = item.category_id || '-';

    row.innerHTML = `
        <td>${renderImage(item.cover_image, item.item_name)}</td>
        <td class="fw-semibold">${escapeHtml(item.item_name) || '-'}</td>
        <td class="text-muted">${escapeHtml(item.item_sku) || '-'}</td>
        <td class="text-right fw-semibold">${formatCurrency(price, currency)}</td>
        <td class="text-center">${stock}</td>
        <td>${getStatusBadge(item.item_status)}</td>
        <td class="text-muted">${category}</td>
        <td class="text-muted">${updateTime}</td>
    `;

    return row;
}

function renderImage(imageUrl, altText) {
    if (!imageUrl) {
        return '<div class="table-image-placeholder">🖼️</div>';
    }
    
    return `<img src="${escapeHtml(imageUrl)}" 
                    alt="${escapeHtml(altText)}" 
                    class="table-image" 
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="table-image-placeholder" style="display:none;">🖼️</div>`;
}

function getStatusBadge(status) {
    const statusMap = {
        'NORMAL': '<span class="badge-status badge-green">NORMAL</span>',
        'UNLIST': '<span class="badge-status badge-gray">UNLIST</span>',
        'BANNED': '<span class="badge-status badge-orange">BANNED</span>'
    };
    return statusMap[status] || '<span class="badge-status badge-gray">-</span>';
}

function formatCurrency(amount, currency) {
    const symbols = {
        'IDR': 'Rp',
        'USD': '$',
        'MYR': 'RM',
        'SGD': 'S$',
        'THB': '฿',
        'VND': '₫',
        'PHP': '₱'
    };
    
    const symbol = symbols[currency] || currency;
    const formatted = Number(amount).toLocaleString('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });

    return `${symbol} ${formatted}`;
}

function formatTimestamp(timestamp) {
    const date = new Date(timestamp * 1000);
    return date.toLocaleString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
