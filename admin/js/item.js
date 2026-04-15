const APP_ID = 1;
let allItems = [];

document.addEventListener('DOMContentLoaded', loadItems);

async function loadItems() {
    const loadingMsg = document.getElementById('loadingMessage');
    const errorMsg = document.getElementById('errorMessage');
    const tableContent = document.getElementById('tableContent');
    const emptyState = document.getElementById('emptyState');
    const statsContainer = document.getElementById('statsContainer');
    const paginationContainer = document.getElementById('paginationContainer');
    const tableBody = document.getElementById('itemsTableBody');

    // Show loading
    loadingMsg.classList.remove('hidden');
    errorMsg.classList.add('hidden');
    tableContent.classList.add('hidden');
    emptyState.classList.add('hidden');
    paginationContainer.classList.add('hidden');
    statsContainer.style.opacity = '0.5';

    try {
        const response = await fetch(`../api/item/full_item.php?id_app=${APP_ID}`);
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || 'Failed to load products');
        }

        allItems = data.data || [];
        renderTable(allItems);
        updateStats(allItems);

        loadingMsg.classList.add('hidden');
        statsContainer.style.opacity = '1';

        if (allItems.length > 0) {
            tableContent.classList.remove('hidden');
            paginationContainer.classList.remove('hidden');
            document.getElementById('paginationInfo').textContent = 
                `Showing 1 to ${allItems.length} of ${allItems.length} results`;
        } else {
            emptyState.classList.remove('hidden');
        }

    } catch (error) {
        loadingMsg.classList.add('hidden');
        statsContainer.style.opacity = '1';
        errorMsg.classList.remove('hidden');
        document.getElementById('errorText').textContent = error.message;
        console.error('Error loading items:', error);
    }
}

function renderTable(items) {
    const tableBody = document.getElementById('itemsTableBody');
    const emptyState = document.getElementById('emptyState');
    const tableContent = document.getElementById('tableContent');
    const paginationContainer = document.getElementById('paginationContainer');
    
    tableBody.innerHTML = '';

    if (items.length === 0) {
        tableContent.classList.add('hidden');
        paginationContainer.classList.add('hidden');
        emptyState.classList.remove('hidden');
        return;
    }

    emptyState.classList.add('hidden');
    tableContent.classList.remove('hidden');
    paginationContainer.classList.remove('hidden');

    items.forEach(item => {
        const row = createItemRow(item);
        tableBody.appendChild(row);
    });
}

function createItemRow(item) {
    const row = document.createElement('tr');
    row.className = 'hover:bg-surface-bright transition-colors cursor-pointer group';

    const price = item.price?.current_price || 0;
    const currency = item.price?.currency || 'IDR';
    const stock = item.stock?.total_available_stock || 0;
    const updateTime = item.update_time ? formatRelativeTime(item.update_time) : '-';
    const category = item.category_id || '-';
    const hasStock = stock > 0;
    const isLowStock = stock > 0 && stock <= 10;

    // Status badge logic - based on API item_status
    const statusMap = {
        'NORMAL': '<span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-green-50 text-green-700">NORMAL</span>',
        'UNLIST': '<span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-orange-50 text-orange-700">UNLIST</span>',
        'BANNED': '<span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-red-50 text-red-700">BANNED</span>'
    };
    const statusBadge = statusMap[item.item_status] || '<span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-zinc-100 text-zinc-500">' + escapeHtml(item.item_status) + '</span>';

    const stockDisplay = `<span class="text-sm font-medium">${stock}</span>`;

    const conditionDisplay = item.condition
        ? `<div class="text-[10px] text-zinc-400">${escapeHtml(item.condition)}</div>`
        : '';

    const imageDisplay = item.cover_image
        ? `<div class="w-12 h-12 rounded bg-surface-container-high overflow-hidden">
             <img alt="${escapeHtml(item.item_name)}" class="w-full h-full object-cover" src="${escapeHtml(item.cover_image)}" onerror="this.style.display='none'; this.parentElement.innerHTML='<div class=\\'w-full h-full bg-surface-container flex items-center justify-center text-zinc-400\\'><span class=\\'material-symbols-outlined\\'>image</span></div>';" />
           </div>`
        : `<div class="w-12 h-12 rounded bg-surface-container-high overflow-hidden flex items-center justify-center">
             <span class="material-symbols-outlined text-zinc-400">image</span>
           </div>`;

    row.innerHTML = `
        <td class="px-6 py-4">${imageDisplay}</td>
        <td class="px-6 py-4">
            <div class="font-manrope font-bold text-sm text-on-background">${escapeHtml(item.item_name)}</div>
            ${conditionDisplay}
        </td>
        <td class="px-6 py-4 text-xs font-mono text-zinc-500 uppercase">${escapeHtml(item.item_sku) || '-'}</td>
        <td class="px-6 py-4 text-sm font-semibold">${formatCurrency(price, currency)}</td>
        <td class="px-6 py-4">${stockDisplay}</td>
        <td class="px-6 py-4">${statusBadge}</td>
        <td class="px-6 py-4 text-sm text-zinc-600">${category}</td>
        <td class="px-6 py-4 text-xs text-right text-zinc-400">${updateTime}</td>
    `;

    return row;
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

function formatRelativeTime(timestamp) {
    const now = Date.now() / 1000;
    const diff = now - timestamp;
    
    if (diff < 60) {
        return 'Just now';
    } else if (diff < 3600) {
        const minutes = Math.floor(diff / 60);
        return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
    } else if (diff < 86400) {
        const hours = Math.floor(diff / 3600);
        return `${hours} hour${hours > 1 ? 's' : ''} ago`;
    } else if (diff < 604800) {
        const days = Math.floor(diff / 86400);
        return `${days} day${days > 1 ? 's' : ''} ago`;
    } else {
        const date = new Date(timestamp * 1000);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }
}

function updateStats(items) {
    const total = items.length;
    const active = items.filter(item => item.item_status === 'NORMAL').length;
    const outOfStock = items.filter(item => {
        const stock = item.stock?.total_available_stock || 0;
        return stock === 0;
    }).length;
    const totalValue = items.reduce((sum, item) => {
        const price = item.price?.current_price || 0;
        const stock = item.stock?.total_available_stock || 0;
        return sum + (price * stock);
    }, 0);

    document.getElementById('statTotal').textContent = total.toLocaleString();
    document.getElementById('statActive').textContent = active.toLocaleString();
    document.getElementById('statOutOfStock').textContent = outOfStock.toLocaleString();
    
    // Format total value
    if (totalValue >= 1000000000) {
        document.getElementById('statTotalValue').textContent = `Rp ${(totalValue / 1000000000).toFixed(1)}B`;
    } else if (totalValue >= 1000000) {
        document.getElementById('statTotalValue').textContent = `Rp ${(totalValue / 1000000).toFixed(1)}M`;
    } else if (totalValue >= 1000) {
        document.getElementById('statTotalValue').textContent = `Rp ${(totalValue / 1000).toFixed(1)}K`;
    } else {
        document.getElementById('statTotalValue').textContent = `Rp ${totalValue.toLocaleString()}`;
    }
}

function filterItems() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value;

    const filtered = allItems.filter(item => {
        const matchesSearch = !searchTerm || 
            (item.item_name && item.item_name.toLowerCase().includes(searchTerm)) ||
            (item.item_sku && item.item_sku.toLowerCase().includes(searchTerm));
        
        const matchesStatus = !statusFilter || item.item_status === statusFilter;

        return matchesSearch && matchesStatus;
    });

    renderTable(filtered);
    
    // Update pagination info
    if (filtered.length === allItems.length) {
        document.getElementById('paginationInfo').textContent = 
            `Showing 1 to ${allItems.length} of ${allItems.length} results`;
    } else {
        document.getElementById('paginationInfo').textContent = 
            `Showing ${filtered.length} of ${allItems.length} results`;
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
