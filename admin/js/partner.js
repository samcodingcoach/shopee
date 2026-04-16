document.addEventListener('DOMContentLoaded', loadPartners);

async function loadPartners() {
    const loadingMsg = document.getElementById('loadingMessage');
    const errorMsg = document.getElementById('errorMessage');
    const tableContent = document.getElementById('tableContent');
    const emptyState = document.getElementById('emptyState');

    loadingMsg.classList.remove('hidden');
    errorMsg.classList.add('hidden');
    tableContent.classList.add('hidden');
    emptyState.classList.add('hidden');

    try {
        const response = await fetch('../api/partner/list.php');
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || 'Gagal memuat data application');
        }

        const partners = data.data || [];
        renderTable(partners);

        loadingMsg.classList.add('hidden');

        if (partners.length > 0) {
            tableContent.classList.remove('hidden');
        } else {
            emptyState.classList.remove('hidden');
        }

    } catch (error) {
        loadingMsg.classList.add('hidden');
        errorMsg.classList.remove('hidden');
        document.getElementById('errorText').innerHTML = `<span>${escapeHtml(error.message)}</span>`;
        console.error('Error loading partners:', error);
    }
}

function renderTable(partners) {
    const tableBody = document.getElementById('partnersTableBody');
    tableBody.innerHTML = '';

    if (partners.length === 0) {
        return;
    }

    partners.forEach((partner, index) => {
        const row = createPartnerRow(partner, index + 1);
        tableBody.appendChild(row);
    });
}

function createPartnerRow(partner, index) {
    const row = document.createElement('tr');
    row.className = 'hover:bg-surface-bright transition-colors group';

    const statusBadge = partner.status_app === 1 
        ? '<span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-green-50 text-green-700">Live Production</span>' 
        : '<span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-zinc-100 text-zinc-500">Developing</span>';
    
    const maskedKey = maskText(partner.partner_key);

    row.innerHTML = `
        <td class="px-6 py-4 text-center text-xs font-mono text-zinc-500">${index}</td>
        <td class="px-6 py-4">
            <div class="font-manrope font-bold text-sm text-on-background">${escapeHtml(partner.nama_app)}</div>
        </td>
        <td class="px-6 py-4">
            <div class="flex items-center gap-2">
                <span id="partnerKey-${index}" class="text-xs font-mono text-zinc-500 truncate max-w-[200px]" style="display:inline-block;">${maskedKey}</span>
                <button type="button" class="text-zinc-400 hover:text-primary transition-colors flex items-center justify-center p-1 rounded hover:bg-surface-container" onclick="toggleMask(${index}, '${escapeHtml(partner.partner_key).replace(/'/g, "\\'")}')" title="Toggle visibility">
                    <span class="material-symbols-outlined text-[16px]">visibility</span>
                </button>
            </div>
        </td>
        <td class="px-6 py-4 text-xs font-mono text-zinc-500 uppercase">${escapeHtml(partner.partner_id)}</td>
        <td class="px-6 py-4">${statusBadge}</td>
        <td class="px-6 py-4 text-center">
            <button class="text-zinc-400 hover:text-primary transition-colors p-2 rounded hover:bg-surface-container flex items-center justify-center mx-auto" onclick='editPartner(${JSON.stringify(partner)})' title="Edit">
                <span class="material-symbols-outlined text-[18px]">edit</span>
            </button>
        </td>
    `;

    return row;
}

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Application';
    document.getElementById('partnerForm').reset();
    document.getElementById('editIdApp').value = '';
    showModal();
}

function editPartner(partner) {
    document.getElementById('modalTitle').textContent = 'Edit Application';
    document.getElementById('editIdApp').value = partner.id_app || '';
    document.getElementById('editNamaApp').value = partner.nama_app || '';
    document.getElementById('editPartnerKey').value = partner.partner_key || '';
    document.getElementById('editPartnerId').value = partner.partner_id || '';
    document.getElementById('editStatusApp').value = partner.status_app || 0;
    showModal();
}

function showModal() {
    const modal = document.getElementById('partnerModal');
    const modalBox = modal.querySelector('.modal-box-content');
    modal.classList.remove('pointer-events-none', 'opacity-0');
    modalBox.classList.remove('scale-95');
    modalBox.classList.add('scale-100');
}

function closeModal() {
    const modal = document.getElementById('partnerModal');
    const modalBox = modal.querySelector('.modal-box-content');
    modal.classList.add('pointer-events-none', 'opacity-0');
    modalBox.classList.add('scale-95');
    modalBox.classList.remove('scale-100');
}

document.getElementById('partnerForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const idApp = formData.get('id_app');
    const url = idApp ? '../api/partner/update.php' : '../api/partner/new.php';

    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || 'Gagal menyimpan data');
        }

        closeModal();
        loadPartners();

    } catch (error) {
        alert('Error: ' + error.message);
        console.error('Error saving partner:', error);
    }
});

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function maskText(text) {
    if (!text) return '';
    if (text.length <= 8) return '••••••••';
    const visiblePart = text.substring(0, 4);
    const hiddenPart = '•'.repeat(Math.min(text.length - 4, 12));
    return visiblePart + hiddenPart;
}

function toggleMask(index, fullText) {
    const element = document.getElementById(`partnerKey-${index}`);
    const isMasked = element.textContent.includes('•');
    
    if (isMasked) {
        element.textContent = fullText;
    } else {
        element.textContent = maskText(fullText);
    }
}
