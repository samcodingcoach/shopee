<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// ID App (bisa diubah via GET parameter)
$id_app = $_GET['id_app'] ?? 1;

// 1. Get token from API
$token_url = "http://" . $_SERVER['HTTP_HOST'] . "/shopee/api/token/get.php?id_app=" . $id_app;
$token_response = @file_get_contents($token_url);

if ($token_response === false) {
    http_response_code(500);
    echo json_encode([
        "error" => "Failed to fetch token",
        "message" => "Unable to retrieve token from API",
        "request_id" => "",
        "response" => []
    ]);
    exit;
}

$token_data = json_decode($token_response, true);

if (!isset($token_data['success']) || !$token_data['success']) {
    http_response_code(401);
    echo json_encode([
        "error" => "Token not found",
        "message" => $token_data['message'] ?? "No token available for this app",
        "request_id" => "",
        "response" => []
    ]);
    exit;
}

$accessToken = $token_data['data']['access_token'];

// 2. Get app data for partner_id, partner_key, shop_id
$app_list_url = "http://" . $_SERVER['HTTP_HOST'] . "/shopee/api/app/list.php";
$app_response = @file_get_contents($app_list_url);
$appData = json_decode($app_response, true);

$partnerId = '';
$partnerKey = '';
$shopId = '';

if (isset($appData['success']) && $appData['success'] && !empty($appData['data'])) {
    foreach ($appData['data'] as $app) {
        if ($app['id_app'] == $id_app) {
            $partnerId = $app['partner_id'];
            $partnerKey = $app['partner_key'];
            $shopId = $app['shop_id'];
            break;
        }
    }
}

if (empty($partnerId) || empty($partnerKey) || empty($shopId)) {
    http_response_code(400);
    echo json_encode([
        "error" => "App configuration incomplete",
        "message" => "Missing partner_id, partner_key, or shop_id",
        "request_id" => "",
        "response" => []
    ]);
    exit;
}

// 3. Setup API request
$apiPath = "/api/v2/product/get_item_list";
$timestamp = (string)time();

// 4. Generate Signature
// baseString = partner_id + api_path + timestamp + access_token + shop_id
$baseString = $partnerId . $apiPath . $timestamp . $accessToken . $shopId;
$sign = hash_hmac('sha256', $baseString, $partnerKey);

// 5. Build URL with all required parameters
$baseUrl = "https://openplatform.sandbox.test-stable.shopee.sg";
$finalUrl = sprintf(
    "%s%s?partner_id=%s&sign=%s&timestamp=%s&shop_id=%s&access_token=%s&offset=0&page_size=10&item_status=NORMAL",
    $baseUrl,
    $apiPath,
    $partnerId,
    $sign,
    $timestamp,
    $shopId,
    $accessToken
);

// 6. Execute cURL GET request
$ch = curl_init($finalUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode([
        "error" => "cURL Error",
        "message" => curl_error($ch),
        "request_id" => "",
        "response" => []
    ]);
    exit;
}

curl_close($ch);

// 7. Return response
if ($response) {
    echo $response;
} else {
    http_response_code(500);
    echo json_encode([
        "error" => "Empty response",
        "message" => "No data returned from Shopee API",
        "request_id" => "",
        "response" => []
    ]);
}
