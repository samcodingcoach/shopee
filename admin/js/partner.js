document.addEventListener('DOMContentLoaded', loadPartners);

async function loadPartners() {
    const loadingMsg = document.getElementById('loadingMessage');
    const errorMsg = document.getElementById('errorMessage');
    const tableContent = document.getElementById('tableContent');

    loadingMsg.style.display = 'block';
    errorMsg.style.display = 'none';
    tableContent.style.display = 'none';

    try {
        const response = await fetch('../api/partner/list.php');
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || 'Gagal memuat data application');
        }

        const partners = data.data || [];
        renderTable(partners);

        loadingMsg.style.display = 'none';
        tableContent.style.display = 'block';

    } catch (error) {
        loadingMsg.style.display = 'none';
        errorMsg.style.display = 'flex';
        errorMsg.innerHTML = `<span>⚠️</span><span>${escapeHtml(error.message)}</span>`;
        console.error('Error loading partners:', error);
    }
}

function renderTable(partners) {
    const tableBody = document.getElementById('partnersTableBody');
    tableBody.innerHTML = '';

    if (partners.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="6">
                    <div class="empty-state">
                        <div class="empty-state-icon">📭</div>
                        <div class="empty-state-title">Tidak Ada Application</div>
                        <div class="empty-state-text">Belum ada application yang tersedia</div>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    partners.forEach((partner, index) => {
        const row = createPartnerRow(partner, index + 1);
        tableBody.appendChild(row);
    });
}

function createPartnerRow(partner, index) {
    const row = document.createElement('tr');

    const statusClass = partner.status_app === 1 ? 'badge-green' : 'badge-gray';
    const statusText = partner.status_app === 1 ? 'Live Production' : 'Developing';
    const maskedKey = maskText(partner.partner_key);

    row.innerHTML = `
        <td class="text-center">${index}</td>
        <td class="fw-semibold">${escapeHtml(partner.nama_app)}</td>
        <td>
            <div style="display: flex; align-items: center; gap: 8px;">
                <span id="partnerKey-${index}" class="text-muted" style="max-width: 250px; word-break: break-all;">${maskedKey}</span>
                <button type="button" class="btn btn-outline btn-sm btn-icon" onclick="toggleMask(${index}, '${escapeHtml(partner.partner_key).replace(/'/g, "\\'")}')" title="Toggle visibility" style="padding: 4px 8px; font-size: 14px;">
                    👁️
                </button>
            </div>
        </td>
        <td class="text-muted">${escapeHtml(partner.partner_id)}</td>
        <td><span class="badge-status ${statusClass}">${statusText}</span></td>
        <td class="text-center">
            <button class="btn btn-outline btn-sm btn-icon" onclick='editPartner(${JSON.stringify(partner)})' title="Edit">
                ✏️
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
    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('show'), 10);
}

function closeModal() {
    const modal = document.getElementById('partnerModal');
    modal.classList.remove('show');
    setTimeout(() => modal.style.display = 'none', 300);
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
