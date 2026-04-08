<?php
session_start();

$code = $_GET['code'] ?? '';
$shopId = $_GET['shop_id'] ?? '';
$partnerId = $_GET['partner_id'] ?? '';

$responseData = null;
$error = null;

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
    
    if ($api_response !== false) {
        $api_data = json_decode($api_response, true);
        if (isset($api_data['success']) && $api_data['success'] && !empty($api_data['data'])) {
            foreach ($api_data['data'] as $app) {
                if ($app['partner_id'] == $partnerId) {
                    $partnerKey = $app['partner_key'];
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
    <style>
        .token-result {
            display: none;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .token-result.show {
            display: block;
        }

        .token-group {
            margin-bottom: 14px;
        }

        .token-group label {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #555;
            font-size: 12px;
            margin-bottom: 6px;
        }

        .token-input-wrapper {
            display: flex;
            gap: 8px;
        }

        .token-input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 13px;
            background: #f9f9f9;
            font-family: 'Courier New', monospace;
        }

        .btn-copy {
            padding: 8px 12px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            transition: background 0.2s;
            white-space: nowrap;
        }

        .btn-copy:hover {
            background: #45a049;
        }

        .btn-copy.copied {
            background: #2196F3;
        }

        .btn-export {
            width: 100%;
            background: #2196F3;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 12px;
        }

        .btn-export:hover {
            background: #1976D2;
        }

        .error-msg {
            background: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 4px;
            font-size: 13px;
            margin-bottom: 14px;
            border-left: 3px solid #c62828;
        }

        .info-badge {
            display: inline-block;
            background: #e3f2fd;
            color: #1976D2;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            margin-left: 8px;
        }
    </style>
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

    <script>
        function copyToken(id) {
            const input = document.getElementById(id);
            input.select();
            input.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(input.value);
            
            const btn = input.nextElementSibling;
            btn.textContent = '✓ Copied';
            btn.classList.add('copied');
            
            setTimeout(() => {
                btn.textContent = 'Copy';
                btn.classList.remove('copied');
            }, 2000);
        }
    </script>
</body>
</html>
