<?php
$code = $_GET['code'] ?? '';
$shopId = $_GET['shop_id'] ?? '';
$partnerId = $_GET['partner_id'] ?? '';

// Fetch app list for dropdown
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

// If code and shop_id exist from callback
if (!empty($code) && !empty($shopId)) {
    $app_id = null;
    $app_name = '';
    
    // Find matching app by partner_id
    if (!empty($partnerId)) {
        foreach ($app_list as $app) {
            if ($app['partner_id'] == $partnerId) {
                $app_id = $app['id_app'];
                $app_name = $app['nama_app'];
                break;
            }
        }
    }
    
    // Update app if found
    $updateSuccess = false;
    $updateMessage = '';
    
    if ($app_id) {
        $api_update_url = "http://" . $_SERVER['HTTP_HOST'] . "/shopee/api/app/update.php";
        $update_data = json_encode([
            "id_app" => $app_id,
            "code" => $code,
            "shop_id" => $shopId
        ]);
        
        $ch = curl_init($api_update_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $update_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $update_response = curl_exec($ch);
        curl_close($ch);
        
        if ($update_response) {
            $update_result = json_decode($update_response, true);
            $updateSuccess = $update_result['success'] ?? false;
            $updateMessage = $update_result['message'] ?? '';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Get Code - Shopee</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>Authorization Code</h1>
            <p>Shopee Open Platform</p>
        </div>

        <?php if (!empty($code) && !empty($shopId)): ?>
            <?php if ($updateSuccess): ?>
                <div class="notification success">
                    <div class="notif-icon">✅</div>
                    <div class="notif-content">
                        <h4>Berhasil!</h4>
                        <p>Code dan Shop ID untuk <strong><?php echo htmlspecialchars($app_name); ?></strong> telah diperbarui.</p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="token-result show">
                <div class="token-group">
                    <label>Authorization Code</label>
                    <div class="token-input-wrapper">
                        <input type="text" class="token-input" id="auth_code" value="<?php echo htmlspecialchars($code); ?>" readonly>
                        <button class="btn-copy" onclick="copyToken('auth_code')">Copy</button>
                    </div>
                </div>

                <div class="token-group">
                    <label>Shop ID</label>
                    <div class="token-input-wrapper">
                        <input type="text" class="token-input" id="shop_id" value="<?php echo htmlspecialchars($shopId); ?>" readonly>
                        <button class="btn-copy" onclick="copyToken('shop_id')">Copy</button>
                    </div>
                </div>

                <?php if (!empty($partnerId)): ?>
                    <div class="token-group">
                        <label>Partner ID</label>
                        <div class="token-input-wrapper">
                            <input type="text" class="token-input" value="<?php echo htmlspecialchars($partnerId); ?>" readonly>
                            <button class="btn-copy" onclick="copyValue('<?php echo htmlspecialchars($partnerId); ?>')">Copy</button>
                        </div>
                    </div>
                <?php endif; ?>

                <a href="get_token.php?code=<?php echo urlencode($code); ?>&shop_id=<?php echo urlencode($shopId); ?>&partner_id=<?php echo urlencode($partnerId); ?>" class="btn-next-step">Lanjut Get Token →</a>
                <a href="shopee_auth.php" style="display: block; text-align: center; margin-top: 12px; color: #ee4d2d; text-decoration: none; font-size: 13px;">← Kembali ke Authorization</a>
            </div>
        <?php else: ?>
            <form method="GET" action="">
                <div class="form-group">
                    <label for="app_select">Pilih Aplikasi</label>
                    <select id="app_select" class="form-select" onchange="updateCredentials()">
                        <option value="">-- Pilih Aplikasi --</option>
                        <?php if (!empty($app_list)): ?>
                            <?php foreach ($app_list as $app): ?>
                                <option value="<?php echo htmlspecialchars($app['partner_id']); ?>"
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
                    <label for="redirect_url">Redirect URL</label>
                    <input type="url" id="redirect_url" name="redirect_url" value="http://127.0.0.1/shopee/get_code.php" required>
                </div>

                <input type="hidden" id="app_partner_id" name="app_partner_id" value="">

                <button type="submit" class="btn-submit">Get Authorization Code</button>
            </form>
        <?php endif; ?>

        <div class="footer">
            Sandbox Environment
        </div>
    </div>

    <script src="js/get_code.js"></script>
</body>
</html>
