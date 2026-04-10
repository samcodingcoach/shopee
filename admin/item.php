<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk - Shopee Admin</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body class="admin-body">
    <?php include 'navbar.php'; ?>

    <main class="admin-main">
        <div class="admin-topbar">
            <h1 class="admin-topbar-title">Kelola Produk</h1>
        </div>

        <div class="admin-content">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2 class="admin-card-title">Daftar Produk</h2>
                    <button class="btn btn-orange btn-sm" onclick="loadItems()">
                        🔄 Muat Ulang
                    </button>
                </div>

                <div class="admin-card-body">
                    <div id="loadingMessage" class="loading-container">
                        <div class="loading-spinner"></div>
                        <div class="loading-text">Memuat data produk...</div>
                    </div>

                    <div id="errorMessage" class="error-message" style="display: none;"></div>

                    <div id="tableContent" style="display: none; overflow-x: auto;">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">Gambar</th>
                                    <th>Nama Produk</th>
                                    <th style="width: 120px;">SKU</th>
                                    <th style="width: 130px;" class="text-right">Harga</th>
                                    <th style="width: 80px;" class="text-center">Stok</th>
                                    <th style="width: 110px;">Status</th>
                                    <th style="width: 100px;">Kategori</th>
                                    <th style="width: 150px;">Diperbarui</th>
                                </tr>
                            </thead>
                            <tbody id="itemsTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="js/item.js"></script>
</body>
</html>
