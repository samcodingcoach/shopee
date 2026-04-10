<?php
session_start();

$code = $_GET['code'] ?? '';
$shopId = $_GET['shop_id'] ?? '';
$partnerId = $_GET['partner_id'] ?? '';

$responseData = null;
$error = null;
$tokenSaved = false;
$tokenSaveMessage = '';
$appName = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($code) && !empty($shopId) && !empty($partnerId)) {
    // Fetch app data for partner_key
    $api_url = "http://" . $_SERVER['HTTP_HOST'] . "/shopee/api/app/list.php";
    $api_context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'ignore_errors' => true
        ]
    ]);
    $api_response = @file_get_contents($api_url, false, $api_context);
    $partnerKey = '';
    $appId = null;

    if ($api_response !== false) {
        $api_data = json_decode($api_response, true);
        if (isset($api_data['success']) && $api_data['success'] && !empty($api_data['data'])) {
            foreach ($api_data['data'] as $app) {
                if ($app['partner_id'] == $partnerId) {
                    $partnerKey = $app['partner_key'];
                    $appId = $app['id_app'];
                    $appName = $app['nama_app'];
                    break;
                }
            }
        }
    }

    if (empty($partnerKey)) {
        $error = "Partner key tidak ditemukan";
    } else {
        // 3. Endpoint Token
        $apiPath = "/api/v2/auth/token/get";
        $timestamp = (string)time();

        // 4. Generate Signature
        $baseString = $partnerId . $apiPath . $timestamp;
        $sign = hash_hmac('sha256', $baseString, $partnerKey);

        // 5. URL Sandbox
        $baseUrl = "https://openplatform.sandbox.test-stable.shopee.sg";
        $finalUrl = sprintf("%s%s?partner_id=%s&timestamp=%s&sign=%s",
            $baseUrl, $apiPath, $partnerId, $timestamp, $sign
        );

        // 6. Siapkan Body JSON
        $bodyData = [
            "code" => $code,
            "shop_id" => (int)$shopId,
            "partner_id" => (int)$partnerId
        ];
        $jsonBody = json_encode($bodyData);

        // 7. Eksekusi Request menggunakan cURL
        $ch = curl_init($finalUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);

        if(curl_errno($ch)){
            $error = 'cURL Error: ' . curl_error($ch);
        } else {
            $responseData = json_decode($response, true);
            
            // Save token to database if successful
            if (isset($responseData['access_token']) && isset($responseData['refresh_token']) && $appId) {
                $api_token_url = "http://" . $_SERVER['HTTP_HOST'] . "/shopee/api/token/new.php";
                $token_data = json_encode([
                    "id_app" => $appId,
                    "access_token" => $responseData['access_token'],
                    "refresh_token" => $responseData['refresh_token']
                ]);
                
                $ch2 = curl_init($api_token_url);
                curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch2, CURLOPT_POST, true);
                curl_setopt($ch2, CURLOPT_POSTFIELDS, $token_data);
                curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                
                $token_response = curl_exec($ch2);
                curl_close($ch2);
                
                if ($token_response) {
                    $token_result = json_decode($token_response, true);
                    $tokenSaved = $token_result['success'] ?? false;
                    $tokenSaveMessage = $token_result['message'] ?? '';
                }
            }
        }

        curl_close($ch);
    }
}

// Handle export to .txt
if (isset($_POST['export']) && !empty($_POST['token_data'])) {
    $data = json_decode($_POST['token_data'], true);
    $expireDate = $_POST['expire_date'] ?? 'N/A';
    if ($data) {
        $txtContent = "SHOPEE TOKEN EXPORT\n";
        $txtContent .= "==================\n";
        $txtContent .= "Date: " . date('Y-m-d H:i:s') . "\n\n";
        $txtContent .= "Access Token:\n" . ($data['access_token'] ?? 'N/A') . "\n\n";
        $txtContent .= "Refresh Token:\n" . ($data['refresh_token'] ?? 'N/A') . "\n\n";
        $txtContent .= "Partner ID: " . ($data['partner_id'] ?? 'N/A') . "\n";
        $txtContent .= "Merchant ID: " . ($data['merchant_id'] ?? 'N/A') . "\n";
        $txtContent .= "Expire At: " . $expireDate . "\n";

        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="shopee_token_' . date('Ymd_His') . '.txt"');
        echo $txtContent;
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Get Token - Shopee</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>Get Token</h1>
            <p>Shopee Open Platform</p>
        </div>

        <?php if ($error): ?>
            <div class="error-msg">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (empty($code) && empty($shopId)): ?>
            <form method="GET" action="">
                <div class="form-group">
                    <label for="partner_id">Partner ID</label>
                    <input type="text" id="partner_id" name="partner_id" value="" required>
                </div>

                <div class="form-group">
                    <label for="code">Authorization Code</label>
                    <input type="text" id="code" name="code" value="" required>
                </div>

                <div class="form-group">
                    <label for="shop_id">Shop ID</label>
                    <input type="text" id="shop_id" name="shop_id" value="" required>
                </div>

                <button type="submit" class="btn-submit">Get Token</button>
            </form>
        <?php else: ?>
            <?php if ($responseData): ?>
                <?php if ($tokenSaved): ?>
                    <div class="notification success">
                        <div class="notif-icon">✅</div>
                        <div class="notif-content">
                            <h4>Token Tersimpan!</h4>
                            <p>Access token dan refresh token untuk <strong><?php echo htmlspecialchars($appName); ?></strong> telah disimpan.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php
                $expireIn = $responseData['expire_in'] ?? 0;
                $expireDate = date('d/m/Y H:i:s', strtotime('+' . $expireIn . ' seconds'));
                ?>
                <div class="token-result show">
                    <div class="token-group">
                        <label>
                            Access Token
                            <span class="info-badge">Expire: <?php echo $expireDate; ?></span>
                        </label>
                        <div class="token-input-wrapper">
                            <input type="text" class="token-input" id="access_token" value="<?php echo htmlspecialchars($responseData['access_token'] ?? ''); ?>" readonly>
                            <button class="btn-copy" onclick="copyToken('access_token')">Copy</button>
                        </div>
                    </div>

                    <div class="token-group">
                        <label>Refresh Token</label>
                        <div class="token-input-wrapper">
                            <input type="text" class="token-input" id="refresh_token" value="<?php echo htmlspecialchars($responseData['refresh_token'] ?? ''); ?>" readonly>
                            <button class="btn-copy" onclick="copyToken('refresh_token')">Copy</button>
                        </div>
                    </div>

                    <form method="POST" action="" style="margin-top: 12px;">
                        <input type="hidden" name="token_data" value='<?php echo json_encode($responseData); ?>'>
                        <input type="hidden" name="expire_date" value="<?php echo $expireDate; ?>">
                        <button type="submit" name="export" value="1" class="btn-export">📄 Export to .txt</button>
                    </form>
                </div>
            <?php elseif (empty($error)): ?>
                <div class="error-msg">⏳ Memproses request...</div>
            <?php endif; ?>

            <a href="shopee_auth.php" style="display: block; text-align: center; margin-top: 16px; color: #ee4d2d; text-decoration: none; font-size: 13px;">← Kembali ke Authorization</a>
        <?php endif; ?>

        <div class="footer">
            Sandbox Environment
        </div>
    </div>

    <script src="js/get_token.js"></script>
</body>
</html>
