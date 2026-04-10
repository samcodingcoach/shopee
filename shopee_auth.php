<?php
$redirect_url = "http://127.0.0.1/shopee/get_code.php";

// Fetch app list from API
$api_url = "http://" . $_SERVER['HTTP_HOST'] . "/shopee/api/app/list.php";
$app_list = [];

$api_context = stream_context_create([
    'http' => [
        'timeout' => 5,
        'ignore_errors' => true
    ]
]);

$api_response = @file_get_contents($api_url, false, $api_context);
if ($api_response !== false) {
    $api_data = json_decode($api_response, true);
    if (isset($api_data['success']) && $api_data['success'] && !empty($api_data['data'])) {
        $app_list = $api_data['data'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $partnerId = $_POST['partner_id'] ?? '';
    $partnerKey = $_POST['partner_key'] ?? '';
    $app_code = $_POST['app_code'] ?? '';
    $app_shop_id = $_POST['app_shop_id'] ?? '';
    $redirectUrl = $_POST['redirect_url'] ?? $redirect_url;

    // Check if code and shop_id are both null/empty
    if (empty($app_code) && empty($app_shop_id)) {
        // Step 1: Redirect to get_code.php for authorization
        $apiPath = "/api/v2/shop/auth_partner";
        $timestamp = time();
        $baseString = $partnerId . $apiPath . $timestamp;
        $sign = hash_hmac('sha256', $baseString, $partnerKey);
        $baseUrl = "https://openplatform.sandbox.test-stable.shopee.sg";
        $redirectUrl = "http://" . $_SERVER['HTTP_HOST'] . "/shopee/get_code.php?partner_id=" . urlencode($partnerId);
        $finalUrl = $baseUrl . $apiPath . "?partner_id=" . $partnerId . "&timestamp=" . $timestamp . "&sign=" . $sign . "&redirect=" . urlencode($redirectUrl);
        header("Location: " . $finalUrl);
        exit();
    } else {
        // Step 2: Redirect to get_token.php with code and shop_id
        $url = "get_token.php?code=" . urlencode($app_code) . 
               "&shop_id=" . urlencode($app_shop_id) . 
               "&partner_id=" . urlencode($partnerId);
        header("Location: " . $url);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopee Authorization</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="auth-body">
    <div class="card">
        <div class="header">
            <h1>Shopee Authorization</h1>
            <p>Open Platform API</p>
        </div>

        <form method="POST" action="" id="authForm">
            <div class="form-group">
                <label for="app_select">Pilih Aplikasi</label>
                <select id="app_select" class="form-select" onchange="updateCredentials()">
                    <option value="">-- Pilih Aplikasi --</option>
                    <?php if (!empty($app_list)): ?>
                        <?php foreach ($app_list as $app): ?>
                            <option value="<?php echo htmlspecialchars($app['partner_id']); ?>"
                                    data-key="<?php echo htmlspecialchars($app['partner_key']); ?>"
                                    data-code="<?php echo htmlspecialchars($app['code'] ?? ''); ?>"
                                    data-shopid="<?php echo htmlspecialchars($app['shop_id'] ?? ''); ?>"
                                    data-name="<?php echo htmlspecialchars($app['nama_app']); ?>">
                                <?php echo htmlspecialchars($app['nama_app']); ?> (<?php echo htmlspecialchars($app['status_label']); ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>Data aplikasi tidak ditemukan</option>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="partner_id">Partner ID</label>
                <input type="text" id="partner_id" name="partner_id" value="" required>
            </div>

            <div class="form-group">
                <label for="partner_key">Partner Key</label>
                <input type="password" id="partner_key" name="partner_key" value="" required>
            </div>

            <div class="form-group">
                <label for="redirect_url">Redirect URL</label>
                <input type="url" id="redirect_url" name="redirect_url" value="<?php echo htmlspecialchars($redirect_url); ?>" required>
            </div>

            <input type="hidden" id="app_code" name="app_code" value="">
            <input type="hidden" id="app_shop_id" name="app_shop_id" value="">

            <button type="button" class="btn-submit" onclick="showConfirm()">Authorize</button>
        </form>

        <div class="footer">
            Sandbox Environment
        </div>
    </div>

    <!-- Modal Popup -->
    <div id="confirmModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-icon" id="modalIcon">⚠️</div>
            <h3 class="modal-title" id="modalTitle">Konfirmasi</h3>
            <p class="modal-message" id="modalMessage"></p>
            <div class="modal-buttons">
                <button class="modal-btn btn-cancel" onclick="closeModal()">Batal</button>
                <button class="modal-btn btn-confirm" onclick="submitForm()">Ya, Lanjutkan</button>
            </div>
        </div>
    </div>

    <script>
        function updateCredentials() {
            const select = document.getElementById('app_select');
            const selectedOption = select.options[select.selectedIndex];
            
            if (select.value) {
                document.getElementById('partner_id').value = select.value;
                document.getElementById('partner_key').value = selectedOption.getAttribute('data-key');
                document.getElementById('app_code').value = selectedOption.getAttribute('data-code') || '';
                document.getElementById('app_shop_id').value = selectedOption.getAttribute('data-shopid') || '';
            } else {
                document.getElementById('partner_id').value = '';
                document.getElementById('partner_key').value = '';
                document.getElementById('app_code').value = '';
                document.getElementById('app_shop_id').value = '';
            }
        }

        function showConfirm() {
            const form = document.getElementById('authForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const code = document.getElementById('app_code').value;
            const shopId = document.getElementById('app_shop_id').value;
            const modal = document.getElementById('confirmModal');
            const icon = document.getElementById('modalIcon');
            const title = document.getElementById('modalTitle');
            const message = document.getElementById('modalMessage');

            if (code === '' && shopId === '') {
                // Kondisi 1: Belum ada code & shop_id, redirect ke get_code.php
                icon.textContent = '🔐';
                title.textContent = 'Otorisasi Shopee';
                message.textContent = 'Anda akan diarahkan ke halaman otorisasi Shopee. Setelah selesai, kode otorisasi akan diterima. Lanjutkan?';
            } else {
                // Kondisi 2: Sudah ada code & shop_id, redirect ke get_token.php
                icon.textContent = '🎟️';
                title.textContent = 'Dapatkan Token Akses';
                message.textContent = 'Aplikasi sudah memiliki kode otorisasi. Sistem akan memproses untuk mendapatkan access_token dan refresh_token. Lanjutkan?';
            }

            modal.classList.add('show');
        }

        function closeModal() {
            document.getElementById('confirmModal').classList.remove('show');
        }

        function submitForm() {
            closeModal();
            document.getElementById('authForm').submit();
        }

        // Close modal when clicking outside
        document.getElementById('confirmModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>