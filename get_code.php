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

    <script>
        function updateCredentials() {
            const select = document.getElementById('app_select');
            const selectedOption = select.options[select.selectedIndex];
            
            if (select.value) {
                document.getElementById('partner_id').value = select.value;
            } else {
                document.getElementById('partner_id').value = '';
            }
        }

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

        function copyValue(value) {
            navigator.clipboard.writeText(value);
        }
    </script>

    <style>
        .notification {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
            animation: slideDown 0.4s ease;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-10px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .notification.success {
            background: #e8f5e9;
            border-left: 4px solid #4CAF50;
        }

        .notif-icon {
            font-size: 32px;
            flex-shrink: 0;
        }

        .notif-content h4 {
            color: #2e7d32;
            font-size: 14px;
            font-weight: 600;
            margin: 0 0 4px 0;
        }

        .notif-content p {
            color: #555;
            font-size: 12px;
            margin: 0;
        }

        .btn-next-step {
            display: block;
            width: 100%;
            background: #ee4d2d;
            color: white;
            text-align: center;
            padding: 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            margin-top: 12px;
            transition: background 0.2s;
        }

        .btn-next-step:hover {
            background: #d4411d;
        }

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
            display: block;
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
    </style>
</body>
</html>
