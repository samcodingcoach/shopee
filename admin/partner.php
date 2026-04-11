<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Application - Shopee Admin</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body class="admin-body">
    <?php include 'navbar.php'; ?>

    <main class="admin-main">
        <div class="admin-topbar">
            <h1 class="admin-topbar-title">Kelola Application</h1>
        </div>

        <div class="admin-content">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2 class="admin-card-title">Daftar Application</h2>
                    <button class="btn btn-orange btn-sm" onclick="openAddModal()">
                        ➕ Tambah
                    </button>
                </div>

                <div class="admin-card-body">
                    <div id="loadingMessage" class="loading-container">
                        <div class="loading-spinner"></div>
                        <div class="loading-text">Memuat data application...</div>
                    </div>

                    <div id="errorMessage" class="error-message" style="display: none;"></div>

                    <div id="tableContent" style="display: none; overflow-x: auto;">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">No</th>
                                    <th>Nama App</th>
                                    <th>Partner Key</th>
                                    <th>Partner ID</th>
                                    <th style="width: 150px;">Status</th>
                                    <th style="width: 120px;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="partnersTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Add/Edit Modal -->
    <div id="partnerModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 500px; text-align: left;">
            <h3 id="modalTitle" style="margin-bottom: 24px; font-size: 18px; font-weight: 700;">Tambah Application</h3>
            
            <form id="partnerForm">
                <input type="hidden" id="editIdApp" name="id_app">
                
                <div style="margin-bottom: 16px;">
                    <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px;">Nama App <span style="color: #ee4d2d;">*</span></label>
                    <input type="text" id="editNamaApp" name="nama_app" required
                           style="width: 100%; padding: 10px 12px; border: 1px solid #d9d9d9; border-radius: 6px; font-size: 14px;"
                           placeholder="Masukkan nama application">
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px;">Partner Key <span style="color: #ee4d2d;">*</span></label>
                    <textarea id="editPartnerKey" name="partner_key" required rows="3"
                              style="width: 100%; padding: 10px 12px; border: 1px solid #d9d9d9; border-radius: 6px; font-size: 14px; resize: vertical;"
                              placeholder="Masukkan partner key"></textarea>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px;">Partner ID <span style="color: #ee4d2d;">*</span></label>
                    <input type="text" id="editPartnerId" name="partner_id" required
                           style="width: 100%; padding: 10px 12px; border: 1px solid #d9d9d9; border-radius: 6px; font-size: 14px;"
                           placeholder="Masukkan partner ID">
                </div>

                <div style="margin-bottom: 24px;">
                    <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px;">Status App</label>
                    <select id="editStatusApp" name="status_app"
                            style="width: 100%; padding: 10px 12px; border: 1px solid #d9d9d9; border-radius: 6px; font-size: 14px;">
                        <option value="0">Developing</option>
                        <option value="1">Live Production</option>
                    </select>
                </div>

                <div style="display: flex; gap: 12px;">
                    <button type="button" class="btn btn-outline" onclick="closeModal()" style="flex: 1;">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-orange" style="flex: 1;">
                        💾 Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/partner.js"></script>
</body>
</html>
