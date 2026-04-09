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
    $app_code = '';
    $app_shop_id = '';
    
    // Find matching app by partner_id
    if (!empty($partnerId)) {
        foreach ($app_list as $app) {
            if ($app['partner_id'] == $partnerId) {
                $app_code = $code;
                $app_shop_id = $shopId;
                break;
            }
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
