<?php
$redirect_url = "http://127.0.0.1/";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Kredensial TEST Anda
    $partnerId = $_POST['partner_id'] ?? 1231140;
    $partnerKey = $_POST['partner_key'] ?? "shpk4b64734f78634b7849537747794f4855686168577143656d4d5063694146";

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
                <label for="partner_id">Partner ID</label>
                <input type="text" id="partner_id" name="partner_id" value="1231140" required>
            </div>
            
            <div class="form-group">
                <label for="partner_key">Partner Key</label>
                <input type="password" id="partner_key" name="partner_key" value="shpk4b64734f78634b7849537747794f4855686168577143656d4d5063694146" required>
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
</body>
</html>