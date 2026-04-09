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
        "success" => false,
        "message" => "Unable to retrieve token from API"
    ]);
    exit;
}

$token_data = json_decode($token_response, true);

if (!isset($token_data['success']) || !$token_data['success']) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => $token_data['message'] ?? "No token available for this app"
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
$appName = '';

if (isset($appData['success']) && $appData['success'] && !empty($appData['data'])) {
    foreach ($appData['data'] as $app) {
        if ($app['id_app'] == $id_app) {
            $partnerId = $app['partner_id'];
            $partnerKey = $app['partner_key'];
            $shopId = $app['shop_id'];
            $appName = $app['nama_app'];
            break;
        }
    }
}

if (empty($partnerId) || empty($partnerKey) || empty($shopId)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Missing partner_id, partner_key, or shop_id"
    ]);
    exit;
}

// 3. Call get_item_list API
$apiPath = "/api/v2/product/get_item_list";
$timestamp = (string)time();
$baseString = $partnerId . $apiPath . $timestamp . $accessToken . $shopId;
$sign = hash_hmac('sha256', $baseString, $partnerKey);

$baseUrl = "https://openplatform.sandbox.test-stable.shopee.sg";
$itemListUrl = sprintf(
    "%s%s?partner_id=%s&sign=%s&timestamp=%s&shop_id=%s&access_token=%s&offset=0&page_size=10&item_status=NORMAL",
    $baseUrl,
    $apiPath,
    $partnerId,
    $sign,
    $timestamp,
    $shopId,
    $accessToken
);

$ch = curl_init($itemListUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);

if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch item list: " . curl_error($ch)
    ]);
    exit;
}
curl_close($ch);

$itemListData = json_decode($response, true);

if (!isset($itemListData['response']['item']) || empty($itemListData['response']['item'])) {
    echo json_encode([
        "success" => true,
        "message" => "No items found",
        "data" => []
    ]);
    exit;
}

// 4. Get item IDs and call get_item_base_info for each
$items = $itemListData['response']['item'];
$fullItems = [];

foreach ($items as $item) {
    $item_id = $item['item_id'];
    
    // Call get_item_base_info
    $apiPath2 = "/api/v2/product/get_item_base_info";
    $timestamp2 = (string)time();
    $baseString2 = $partnerId . $apiPath2 . $timestamp2 . $accessToken . $shopId;
    $sign2 = hash_hmac('sha256', $baseString2, $partnerKey);
    
    $baseInfoUrl = sprintf(
        "%s%s?partner_id=%s&sign=%s&timestamp=%s&shop_id=%s&access_token=%s&item_id_list=%s&need_tax_info=true&need_complaint_policy=true",
        $baseUrl,
        $apiPath2,
        $partnerId,
        $sign2,
        $timestamp2,
        $shopId,
        $accessToken,
        $item_id
    );
    
    $ch2 = curl_init($baseInfoUrl);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    $baseInfoResponse = curl_exec($ch2);
    curl_close($ch2);
    
    $baseInfoData = json_decode($baseInfoResponse, true);
    
    if (isset($baseInfoData['response']['item_list'][0])) {
        $baseInfo = $baseInfoData['response']['item_list'][0];
        
        // Merge into unified format
        $fullItems[] = [
            "item_id" => $baseInfo['item_id'],
            "item_name" => $baseInfo['item_name'] ?? '',
            "item_sku" => $baseInfo['item_sku'] ?? '',
            "price" => [
                "original_price" => $baseInfo['price_info'][0]['original_price'] ?? 0,
                "current_price" => $baseInfo['price_info'][0]['current_price'] ?? 0,
                "currency" => $baseInfo['price_info'][0]['currency'] ?? ''
            ],
            "weight" => $baseInfo['weight'] ?? '',
            "images" => [
                "image_id_list" => $baseInfo['image']['image_id_list'] ?? [],
                "image_url_list" => $baseInfo['image']['image_url_list'] ?? []
            ],
            "cover_image" => $baseInfo['image']['image_url_list'][0] ?? '',
            "condition" => $baseInfo['condition'] ?? '',
            "attributes" => $baseInfo['attribute_list'] ?? [],
            "stock" => [
                "total_available_stock" => $baseInfo['stock_info_v2']['summary_info']['total_available_stock'] ?? 0,
                "total_reserved_stock" => $baseInfo['stock_info_v2']['summary_info']['total_reserved_stock'] ?? 0
            ],
            "item_status" => $baseInfo['item_status'] ?? '',
            "category_id" => $baseInfo['category_id'] ?? 0,
            "description" => $baseInfo['description_info']['extended_description']['field_list'][0]['text'] ?? '',
            "logistic_info" => $baseInfo['logistic_info'] ?? [],
            "pre_order" => $baseInfo['pre_order'] ?? [],
            "brand" => $baseInfo['brand']['original_brand_name'] ?? '',
            "update_time" => $baseInfo['update_time'] ?? 0,
            "create_time" => $baseInfo['create_time'] ?? 0
        ];
    }
    
    // Small delay to avoid rate limiting
    usleep(100000); // 100ms
}

echo json_encode([
    "success" => true,
    "message" => "Items retrieved successfully",
    "app_name" => $appName,
    "total_items" => count($fullItems),
    "data" => $fullItems
]);
