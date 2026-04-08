<?php
$redirect_url = "http://127.0.0.1/";

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
    // 1. Kredensial TEST Anda
    $partnerId = $_POST['partner_id'] ?? '';
    $partnerKey = $_POST['partner_key'] ?? '';

    // 2. Endpoint Otorisasi
    $apiPath = "/api/v2/shop/auth_partner";
    $timestamp = time();

    // 3. Generate Signature
    $baseString = $partnerId . $apiPath . $timestamp;
    $sign = hash_hmac('sha256', $baseString, $partnerKey);

    // 4. URL FINAL (Menggunakan environment Sandbox Test Shopee Terbaru)
    $baseUrl = "https://openplatform.sandbox.test-stable.shopee.sg";

    // 5. Setup Redirect
    $redirectUrl = urlencode($_POST['redirect_url'] ?? $redirect_url);

    // 6. Rakit URL
    $finalUrl = $baseUrl . $apiPath . "?partner_id=" . $partnerId . "&timestamp=" . $timestamp . "&sign=" . $sign . "&redirect=" . $redirectUrl;

    // 7. Eksekusi Redirect
    header("Location: " . $finalUrl);
    exit();
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
<body>
    <div class="card">
        <div class="header">
            <h1>Shopee Authorization</h1>
            <p>Open Platform API</p>
        </div>

        <form method="POST" action="">
            <div class="form-group">
                <label for="app_select">Pilih Aplikasi</label>
                <select id="app_select" class="form-select" onchange="updateCredentials()">
                    <option value="">-- Pilih Aplikasi --</option>
                    <?php if (!empty($app_list)): ?>
                        <?php foreach ($app_list as $app): ?>
                            <option value="<?php echo htmlspecialchars($app['partner_id']); ?>" 
                                    data-key="<?php echo htmlspecialchars($app['partner_key']); ?>"
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

            <button type="submit" class="btn-submit">Authorize</button>
        </form>

        <div class="footer">
            Sandbox Environment
        </div>
    </div>

    <script>
        function updateCredentials() {
            const select = document.getElementById('app_select');
            const selectedOption = select.options[select.selectedIndex];
            
            if (select.value) {
                document.getElementById('partner_id').value = select.value;
                document.getElementById('partner_key').value = selectedOption.getAttribute('data-key');
            } else {
                document.getElementById('partner_id').value = '';
                document.getElementById('partner_key').value = '';
            }
        }
    </script>
</body>
</html>