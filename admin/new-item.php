<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';

$query = "SELECT id_app, nama_app FROM app ORDER BY nama_app";
$result = $conn->query($query);
$apps = [];
while ($row = $result->fetch_assoc()) {
    $apps[] = $row;
}

$image_id = "";
$category_id = "";
$logistics = [];
$selected_logistic = "";
$error_message = "";
$success_message = "";
$product_result = "";
$attributes = [];
$form_data = [];
$debug_attr_error = "";
$step = 1; // 1: Upload, 2: Category, 3: Logistics, 4: Product Form

$categories_query = "SELECT category_id, display_category_name FROM category_api ORDER BY display_category_name ASC";
$categories_result = $conn->query($categories_query);
$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_image'])) {
    $id_app = $_POST['id_app'] ?? null;

    if (!$id_app) {
        $error_message = "Please select an app";
    } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $error_message = "Please select an image to upload";
    } else {
        $image_file = $_FILES['image'];

        if ($image_file['size'] > 1048576) {
            $error_message = "File size exceeds 1MB limit";
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $image_file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime_type, ['image/jpeg', 'image/jpg'])) {
                $error_message = "Only JPG files are allowed";
            } else {
                $query = "SELECT partner_id, partner_key, shop_id FROM app WHERE id_app = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $id_app);
                $stmt->execute();
                $result = $stmt->get_result();

                if (!$row = $result->fetch_assoc()) {
                    $error_message = "App not found";
                } else {
                    $partnerId = $row['partner_id'];
                    $partnerKey = $row['partner_key'];

                    $apiPath = "/api/v2/media_space/upload_image";
                    $timestamp = (string)time();
                    $baseString = $partnerId . $apiPath . $timestamp;
                    $sign = hash_hmac('sha256', $baseString, $partnerKey);

                    $baseUrl = "https://openplatform.sandbox.test-stable.shopee.sg";
                    $finalUrl = sprintf("%s%s?partner_id=%s&timestamp=%s&sign=%s",
                        $baseUrl, $apiPath, $partnerId, $timestamp, $sign
                    );

                    $cFile = new CURLFILE($image_file['tmp_name'], 'image/jpeg', $image_file['name']);
                    $postData = array('image' => $cFile);

                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $finalUrl,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST => true,
                        CURLOPT_POSTFIELDS => $postData,
                    ));

                    $response = curl_exec($curl);
                    $curl_error = curl_error($curl);
                    curl_close($curl);

                    if ($curl_error) {
                        $error_message = "cURL Error: " . $curl_error;
                    } else {
                        $response_data = json_decode($response, true);
                        if (isset($response_data['response']['image_info']['image_id'])) {
                            $image_id = $response_data['response']['image_info']['image_id'];
                            $step = 2;
                            $_POST['id_app_hidden'] = $id_app;
                        } else {
                            $error_message = "Failed to upload image. Response: " . $response;
                        }
                    }
                }
                $stmt->close();
            }
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_category'])) {
    $image_id = $_POST['image_id'] ?? '';
    $category_id = $_POST['category_id'] ?? '';
    $id_app = $_POST['id_app'] ?? '';
    $step = 2;

    if ($id_app) {
        $query = "SELECT a.partner_id, a.partner_key, a.shop_id, t.access_token
                  FROM app a
                  LEFT JOIN token t ON a.id_app = t.id_app
                  WHERE a.id_app = ?
                  ORDER BY t.created_date DESC
                  LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id_app);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $partnerId = $row['partner_id'];
            $partnerKey = $row['partner_key'];
            $shopId = $row['shop_id'];
            $accessToken = $row['access_token'];

            if ($accessToken) {
                // --- GET LOGISTICS ---
                $apiPath = "/api/v2/logistics/get_channel_list";
                $timestamp = (string)time();
                $baseString = $partnerId . $apiPath . $timestamp . $accessToken . $shopId;
                $sign = hash_hmac('sha256', $baseString, $partnerKey);

                $baseUrl = "https://openplatform.sandbox.test-stable.shopee.sg";
                $finalUrl = sprintf("%s%s?partner_id=%s&timestamp=%s&access_token=%s&shop_id=%s&sign=%s",
                    $baseUrl, $apiPath, $partnerId, $timestamp, $accessToken, $shopId, $sign
                );

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $finalUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                $curl_error = curl_error($ch);
                curl_close($ch);

                if ($curl_error) {
                    $error_message = "cURL Error: " . $curl_error;
                } else {
                    $response_data = json_decode($response, true);
                    if (isset($response_data['response']['logistics_channel_list'])) {
                        $logistics = $response_data['response']['logistics_channel_list'];
                    } elseif (isset($response_data['response']['logistics'])) {
                        $logistics = $response_data['response']['logistics'];
                    } elseif (isset($response_data['response'])) {
                        $logistics = $response_data['response'];
                    } elseif (isset($response_data['logistics'])) {
                        $logistics = $response_data['logistics'];
                    }

                    // --- GET ATTRIBUTE TREE ---
                    $apiPathAttr = "/api/v2/product/get_attribute_tree";
                    $timestampAttr = (string)time();
                    $baseStringAttr = $partnerId . $apiPathAttr . $timestampAttr . $accessToken . $shopId;
                    $signAttr = hash_hmac('sha256', $baseStringAttr, $partnerKey);

                    $finalUrlAttr = sprintf("%s%s?partner_id=%s&timestamp=%s&access_token=%s&shop_id=%s&sign=%s&language=id&category_id_list=%s",
                        $baseUrl, $apiPathAttr, $partnerId, $timestampAttr, $accessToken, $shopId, $signAttr, $category_id
                    );

                    $chAttr = curl_init();
                    curl_setopt($chAttr, CURLOPT_URL, $finalUrlAttr);
                    curl_setopt($chAttr, CURLOPT_RETURNTRANSFER, true);
                    $responseAttr = curl_exec($chAttr);
                    curl_close($chAttr);

                    // DATA CADANGAN (FALLBACK) UNTUK SANDBOX YANG CACAT
                    $sandbox_fallback_data = [
                        '301942' => [ // ID Kategori Laptop
                            200388 => [ // Tipe Laptop
                                ['value_id' => 11032792, 'display_value_name' => 'Thin and Light'],
                                ['value_id' => 11032793, 'display_value_name' => 'Gaming'],
                                ['value_id' => 11032794, 'display_value_name' => '2 in 1']
                            ],
                            200370 => [ // Jenis Garansi
                                ['value_id' => 10921, 'display_value_name' => 'Garansi Resmi'],
                                ['value_id' => 10922, 'display_value_name' => 'Garansi Distributor']
                            ]
                        ]
                    ];

                    if ($responseAttr) {
                        $attr_data = json_decode($responseAttr, true);
                        if (isset($attr_data['error']) && $attr_data['error'] !== "") {
                            $debug_attr_error = $attr_data['message'];
                        } elseif (isset($attr_data['response']['list'][0]['attribute_tree'])) {
                            foreach ($attr_data['response']['list'][0]['attribute_tree'] as $attr) {
                                
                                // Ambil nama atribut (utamakan bahasa Indonesia)
                                $display_name = $attr['name'];
                                if (isset($attr['multi_lang'][0]['value'])) {
                                    $display_name = $attr['multi_lang'][0]['value'];
                                }
                                $attr['display_attribute_name'] = $display_name;
                                $attr['is_mandatory'] = $attr['mandatory'] ?? false;

                                // PROSES NILAI (DROPDOWN)
                                $processed_values = [];
                                
                                // 1. Coba ambil dari API dulu
                                if (isset($attr['attribute_value_list']) && is_array($attr['attribute_value_list'])) {
                                    foreach ($attr['attribute_value_list'] as $val) {
                                        $val_name = $val['name'];
                                        if (isset($val['multi_lang'][0]['value'])) {
                                            $val_name = $val['multi_lang'][0]['value'];
                                        }
                                        $processed_values[] = [
                                            'value_id' => $val['value_id'],
                                            'display_value_name' => $val_name
                                        ];
                                    }
                                }

                                // 2. Jika dari API kosong (cacat), coba cek data cadangan kita
                                if (empty($processed_values) && isset($sandbox_fallback_data[$category_id][$attr['attribute_id']])) {
                                    $processed_values = $sandbox_fallback_data[$category_id][$attr['attribute_id']];
                                }

                                // Simpan kembali nilai yang sudah diproses ke array utama
                                $attr['attribute_value_list'] = $processed_values;
                                $attributes[] = $attr;
                            }
                            $step = 4;
                        }
                    } else {
                        $debug_attr_error = "Tidak ada respons dari API Atribut Shopee.";
                    }

                    $form_data = [
                        'item_name' => '', 'original_price' => '', 'description' => '',
                        'weight' => '', 'item_status' => 'NORMAL', 'item_sku' => '',
                        'condition' => 'NEW', 'stock' => '50', 'wholesale_min' => '10',
                        'wholesale_max' => '10', 'wholesale_price' => '', 'package_height' => '10',
                        'package_length' => '15', 'package_width' => '10', 'attributes' => []
                    ];
                }
            } else {
                $error_message = "No access token for this app (ID: $id_app)";
            }
        } else {
            $error_message = "App not found (ID: $id_app)";
        }
        $stmt->close();
    } else {
        $error_message = "id_app not provided in POST data";
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_logistic'])) {
    $image_id = $_POST['image_id'] ?? '';
    $category_id = $_POST['category_id'] ?? '';
    $selected_logistic = $_POST['logistic_id'] ?? '';
    $step = 3;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_product'])) {

    $product_data = [
        'id_app' => $_POST['id_app'] ?? '',
        'item_name' => $_POST['item_name'] ?? '',
        'original_price' => $_POST['original_price'] ?? 0,
        'description' => $_POST['description'] ?? '',
        'weight' => $_POST['weight'] ?? 0.3,
        'item_status' => $_POST['item_status'] ?? 'NORMAL',
        'item_sku' => $_POST['item_sku'] ?? '',
        'condition' => $_POST['condition'] ?? 'NEW',
        'stock' => $_POST['stock'] ?? 50,
        'wholesale_min' => $_POST['wholesale_min'] ?? 10,
        'wholesale_max' => $_POST['wholesale_max'] ?? 10,
        'wholesale_price' => $_POST['wholesale_price'] ?? 0,
        'package_height' => $_POST['package_height'] ?? 10,
        'package_length' => $_POST['package_length'] ?? 15,
        'package_width' => $_POST['package_width'] ?? 10,
        'logistic_id' => $_POST['logistic_id'] ?? 81017,
        'image_id' => $_POST['image_id'] ?? '',
        'category_id' => $_POST['category_id'] ?? 301034,
        'attributes' => $_POST['attributes'] ?? []
    ];

    if ($product_data['id_app']) {
        $ch = curl_init();
        $api_url = "http://" . $_SERVER['HTTP_HOST'] . "/shopee/shopee_api/5th_creatingproduct.php?id_app=" . $product_data['id_app'];

        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($product_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        $response = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            $error_message = "cURL Error: " . $curl_error;
        } else {
            $product_result = json_decode($response, true);
            if (isset($product_result['response']['item_id'])) {
                $success_message = "Produk berhasil dibuat dengan ID: " . $product_result['response']['item_id'];
            } else {
                $error_message = "Gagal membuat produk. Response: " . $response;
            }
        }
    }
}

// Determine current step
if ($category_id && empty($attributes)) {
    $step = 3;
}
if (!empty($attributes)) {
    $step = 4;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk - Shopee Admin</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .form-step {
            display: none;
        }
        .form-step.active {
            display: block;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        .form-label .required {
            color: #ee4d2d;
            margin-left: 4px;
        }
        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d9d9d9;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.2s;
        }
        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #ee4d2d;
            box-shadow: 0 0 0 2px rgba(238, 77, 45, 0.1);
        }
        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }
        .form-input[readonly] {
            background: #fafafa;
            cursor: not-allowed;
        }
        .form-help {
            margin-top: 6px;
            font-size: 12px;
            color: #8c8c8c;
        }
        .form-section {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #e8e8e8;
        }
        .form-section-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }
        .step-indicator {
            display: flex;
            gap: 16px;
            margin-bottom: 32px;
            padding: 20px;
            background: #fafafa;
            border-radius: 8px;
        }
        .step-item {
            flex: 1;
            text-align: center;
            position: relative;
        }
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e8e8e8;
            color: #8c8c8c;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .step-item.active .step-number {
            background: #ee4d2d;
            color: white;
        }
        .step-item.completed .step-number {
            background: #52c41a;
            color: white;
        }
        .step-label {
            font-size: 12px;
            color: #8c8c8c;
        }
        .step-item.active .step-label {
            color: #ee4d2d;
            font-weight: 600;
        }
        .logistic-table {
            width: 100%;
            border-collapse: collapse;
        }
        .logistic-table th,
        .logistic-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #e8e8e8;
        }
        .logistic-table th {
            background: #fafafa;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
        }
        .logistic-table tr:hover {
            background: #fafafa;
            cursor: pointer;
        }
        .logistic-table input[type="radio"] {
            cursor: pointer;
        }
        .success-box {
            background: #f6ffed;
            border: 1px solid #b7eb8f;
            border-radius: 8px;
            padding: 24px;
            text-align: center;
            margin: 32px 0;
        }
        .success-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }
        .success-title {
            font-size: 18px;
            font-weight: 600;
            color: #52c41a;
            margin-bottom: 8px;
        }
        .success-message {
            color: #666;
            margin-bottom: 24px;
        }
    </style>
</head>
<body class="admin-body">
    <?php include 'navbar.php'; ?>

    <main class="admin-main">
        <div class="admin-topbar" style="margin: 0;">
            <h1 class="admin-topbar-title">Tambah Produk Baru</h1>
        </div>

        <div class="admin-content" style="margin: 0;">
            <?php if ($success_message): ?>
                <div class="success-box">
                    <div class="success-icon">✅</div>
                    <div class="success-title">Berhasil!</div>
                    <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
                    <a href="item.php" class="btn btn-orange">
                        ← Kembali ke Daftar Produk
                    </a>
                </div>
            <?php else: ?>
                <?php if ($error_message): ?>
                    <div class="error-message" style="margin-bottom: 20px;">
                        <span>⚠️</span>
                        <span><?php echo htmlspecialchars($error_message); ?></span>
                    </div>
                <?php endif; ?>

                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">Form Tambah Produk</h2>
                    </div>

                    <div class="admin-card-body" style="padding: 24px;">
                        <!-- Step Indicator -->
                        <div class="step-indicator">
                            <div class="step-item <?php echo $step >= 1 ? ($step > 1 ? 'completed' : 'active') : ''; ?>">
                                <div class="step-number"><?php echo $step > 1 ? '✓' : '1'; ?></div>
                                <div class="step-label">Upload Gambar</div>
                            </div>
                            <div class="step-item <?php echo $step >= 2 ? ($step > 2 ? 'completed' : 'active') : ''; ?>">
                                <div class="step-number"><?php echo $step > 2 ? '✓' : '2'; ?></div>
                                <div class="step-label">Pilih Kategori</div>
                            </div>
                            <div class="step-item <?php echo $step >= 3 ? ($step > 3 ? 'completed' : 'active') : ''; ?>">
                                <div class="step-number"><?php echo $step > 3 ? '✓' : '3'; ?></div>
                                <div class="step-label">Pilih Kurir</div>
                            </div>
                            <div class="step-item <?php echo $step >= 4 ? 'active' : ''; ?>">
                                <div class="step-number">4</div>
                                <div class="step-label">Isi Detail</div>
                            </div>
                        </div>

                        <!-- Step 1: Upload Image -->
                        <?php if ($step === 1): ?>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label class="form-label">
                                    Pilih Aplikasi <span class="required">*</span>
                                </label>
                                <select name="id_app" class="form-select" required>
                                    <option value="">-- Pilih Aplikasi --</option>
                                    <?php foreach ($apps as $app): ?>
                                        <option value="<?php echo $app['id_app']; ?>" <?php echo (isset($_POST['id_app']) && $_POST['id_app'] == $app['id_app']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($app['nama_app']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    Gambar Produk (JPG, maks 1MB) <span class="required">*</span>
                                </label>
                                <input type="file" name="image" accept=".jpg,.jpeg" class="form-input" required>
                                <div class="form-help">Format: JPG, Maksimal ukuran: 1MB</div>
                            </div>

                            <div style="margin-top: 24px;">
                                <button type="submit" name="upload_image" class="btn btn-orange">
                                    Upload Gambar →
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>

                        <!-- Step 2: Select Category -->
                        <?php if ($step === 2): ?>
                        <form method="POST">
                            <input type="hidden" name="image_id" value="<?php echo htmlspecialchars($image_id); ?>">
                            <input type="hidden" name="id_app" value="<?php echo htmlspecialchars($_POST['id_app_hidden'] ?? ''); ?>">

                            <div class="form-group">
                                <label class="form-label">Image ID</label>
                                <input type="text" value="<?php echo htmlspecialchars($image_id); ?>" class="form-input" readonly>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    Pilih Kategori <span class="required">*</span>
                                </label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat['category_id']); ?>" <?php echo ($category_id == $cat['category_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['display_category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div style="margin-top: 24px;">
                                <button type="submit" name="select_category" class="btn btn-orange">
                                    Pilih Kategori →
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>

                        <!-- Step 3: Select Logistics -->
                        <?php if ($step === 3): ?>
                        <form method="POST">
                            <input type="hidden" name="image_id" value="<?php echo htmlspecialchars($image_id); ?>">
                            <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($category_id); ?>">
                            <input type="hidden" name="id_app" value="<?php echo htmlspecialchars($_POST['id_app'] ?? ''); ?>">

                            <div class="form-group">
                                <label class="form-label">Image ID</label>
                                <input type="text" value="<?php echo htmlspecialchars($image_id); ?>" class="form-input" readonly>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Category ID</label>
                                <input type="text" value="<?php echo htmlspecialchars($category_id); ?>" class="form-input" readonly>
                            </div>

                            <?php if (!empty($logistics)): ?>
                            <div class="form-section">
                                <h3 class="form-section-title">Pilih Kurir Logistik</h3>
                                <table class="logistic-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 50px;">Pilih</th>
                                            <th>Logistic ID</th>
                                            <th>Nama Kurir</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($logistics as $logistic):
                                            $logistic_id = $logistic['logistics_channel_id'] ?? ($logistic['logistic_id'] ?? ($logistic['channel_id'] ?? 'N/A'));
                                            $logistic_name = $logistic['logistics_channel_name'] ?? ($logistic['logistic_name'] ?? ($logistic['name'] ?? '-'));
                                        ?>
                                            <tr onclick="this.querySelector('input[type=radio]').checked = true; document.getElementById('hidden_logistic_id').value = '<?php echo htmlspecialchars($logistic_id); ?>';">
                                                <td>
                                                    <input type="radio" name="logistic_radio" value="<?php echo htmlspecialchars($logistic_id); ?>"
                                                           onclick="document.getElementById('hidden_logistic_id').value = this.value;"
                                                           <?php echo ($selected_logistic == $logistic_id) ? 'checked' : ''; ?>>
                                                </td>
                                                <td><?php echo htmlspecialchars($logistic_id); ?></td>
                                                <td><?php echo htmlspecialchars($logistic_name); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <input type="hidden" id="hidden_logistic_id" name="logistic_id" value="<?php echo htmlspecialchars($selected_logistic); ?>">
                            </div>
                            <?php endif; ?>

                            <div style="margin-top: 24px;">
                                <button type="submit" name="select_logistic" class="btn btn-orange">
                                    Lanjut ke Detail Produk →
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>

                        <!-- Step 4: Product Details -->
                        <?php if ($step === 4): ?>
                        <form method="POST">
                            <input type="hidden" name="image_id" value="<?php echo htmlspecialchars($image_id); ?>">
                            <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($category_id); ?>">
                            <input type="hidden" name="id_app" value="<?php echo htmlspecialchars($_POST['id_app'] ?? ''); ?>">
                            <input type="hidden" name="logistic_id" value="<?php echo htmlspecialchars($selected_logistic); ?>">

                            <div class="form-group">
                                <label class="form-label">
                                    Nama Produk <span class="required">*</span>
                                </label>
                                <input type="text" name="item_name" class="form-input" required value="<?php echo htmlspecialchars($form_data['item_name'] ?? ''); ?>" placeholder="Contoh: Laptop Gaming Pro 15">
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    Harga Asli (Rp) <span class="required">*</span>
                                </label>
                                <input type="number" name="original_price" class="form-input" required placeholder="Contoh: 350000" value="<?php echo htmlspecialchars($form_data['original_price'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    Deskripsi Produk <span class="required">*</span>
                                </label>
                                <textarea name="description" class="form-textarea" required placeholder="Jelaskan detail produk, spesifikasi, dan keunggulan..."><?php echo htmlspecialchars($form_data['description'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    Berat (kg) <span class="required">*</span>
                                </label>
                                <input type="number" step="0.01" name="weight" class="form-input" required placeholder="Contoh: 0.3" value="<?php echo htmlspecialchars($form_data['weight'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Status Produk</label>
                                <select name="item_status" class="form-select">
                                    <option value="NORMAL" <?php echo ($form_data['item_status'] ?? 'NORMAL') == 'NORMAL' ? 'selected' : ''; ?>>NORMAL (Aktif)</option>
                                    <option value="UNLIST" <?php echo ($form_data['item_status'] ?? '') == 'UNLIST' ? 'selected' : ''; ?>>UNLIST (Tidak Aktif)</option>
                                </select>
                            </div>

                            <div class="form-section">
                                <h3 class="form-section-title">Informasi Tambahan</h3>

                                <div class="form-group">
                                    <label class="form-label">SKU Produk</label>
                                    <input type="text" name="item_sku" class="form-input" placeholder="Contoh: JAM-PRIA-001" value="<?php echo htmlspecialchars($form_data['item_sku'] ?? ''); ?>">
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Kondisi Produk</label>
                                    <select name="condition" class="form-select">
                                        <option value="NEW" <?php echo ($form_data['condition'] ?? 'NEW') == 'NEW' ? 'selected' : ''; ?>>Baru (NEW)</option>
                                        <option value="USED" <?php echo ($form_data['condition'] ?? '') == 'USED' ? 'selected' : ''; ?>>Bekas (USED)</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        Stok <span class="required">*</span>
                                    </label>
                                    <input type="number" name="stock" class="form-input" required value="<?php echo htmlspecialchars($form_data['stock'] ?? '50'); ?>">
                                </div>
                            </div>

                            <div class="form-section">
                                <h3 class="form-section-title">Grosir (Opsional)</h3>
                                <p class="form-help" style="margin-bottom: 16px;">Harga grosir harus antara 50% - 99% dari harga asli. Kosongkan untuk menonaktifkan grosir.</p>

                                <div class="form-group">
                                    <label class="form-label">Minimum Pembelian Grosir</label>
                                    <input type="number" name="wholesale_min" class="form-input" value="<?php echo htmlspecialchars($form_data['wholesale_min'] ?? '10'); ?>">
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Maksimum Pembelian Grosir</label>
                                    <input type="number" name="wholesale_max" class="form-input" value="<?php echo htmlspecialchars($form_data['wholesale_max'] ?? '10'); ?>">
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Harga Grosir per Unit</label>
                                    <input type="number" name="wholesale_price" class="form-input" placeholder="Contoh: 175000" value="<?php echo htmlspecialchars($form_data['wholesale_price'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="form-section">
                                <h3 class="form-section-title">Dimensi Paket</h3>

                                <div class="form-group">
                                    <label class="form-label">Tinggi Paket (cm)</label>
                                    <input type="number" name="package_height" class="form-input" value="<?php echo htmlspecialchars($form_data['package_height'] ?? '10'); ?>">
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Panjang Paket (cm)</label>
                                    <input type="number" name="package_length" class="form-input" value="<?php echo htmlspecialchars($form_data['package_length'] ?? '15'); ?>">
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Lebar Paket (cm)</label>
                                    <input type="number" name="package_width" class="form-input" value="<?php echo htmlspecialchars($form_data['package_width'] ?? '10'); ?>">
                                </div>
                            </div>

                            <?php if (!empty($attributes)): ?>
                            <div class="form-section">
                                <h3 class="form-section-title">Atribut Kategori</h3>
                                <p class="form-help" style="margin-bottom: 16px;">Isi atribut yang ditandai bintang merah (*). Kosongkan yang tidak perlu.</p>

                                <?php foreach ($attributes as $attr): ?>
                                    <?php
                                    $is_req = ($attr['is_mandatory'] ?? false) ? true : false;
                                    $display_name = $attr['display_attribute_name'] ?? $attr['name'] ?? 'Atribut';
                                    ?>
                                    <div class="form-group">
                                        <label class="form-label">
                                            <?php echo htmlspecialchars($display_name); ?>
                                            <?php echo $is_req ? '<span class="required">*</span>' : ''; ?>
                                        </label>

                                        <?php if (!empty($attr['attribute_value_list'])): ?>
                                            <select name="attributes[<?php echo $attr['attribute_id']; ?>]" class="form-select" <?php echo $is_req ? 'required' : ''; ?>>
                                                <option value="">-- Pilih --</option>
                                                <?php foreach ($attr['attribute_value_list'] as $val): ?>
                                                    <?php $vName = $val['display_value_name'] ?? $val['name'] ?? 'Opsi'; ?>
                                                    <option value="<?php echo $val['value_id'] . '|' . htmlspecialchars($vName); ?>"
                                                        <?php echo ($form_data['attributes'][$attr['attribute_id']] ?? '') == ($val['value_id'] . '|' . $vName) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($vName); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php else: ?>
                                            <input type="text" name="attributes[<?php echo $attr['attribute_id']; ?>]" class="form-input" <?php echo $is_req ? 'required' : ''; ?>
                                                   value="<?php echo htmlspecialchars($form_data['attributes'][$attr['attribute_id']] ?? ''); ?>"
                                                   placeholder="Masukkan <?php echo htmlspecialchars($display_name); ?>">
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>

                            <div style="margin-top: 32px; display: flex; gap: 12px;">
                                <button type="submit" name="create_product" class="btn btn-orange" style="flex: 1;">
                                    ✓ Buat Produk
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>

                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Auto-select logistic when clicking row
        document.addEventListener('DOMContentLoaded', function() {
            const logisticRows = document.querySelectorAll('.logistic-table tbody tr');
            logisticRows.forEach(row => {
                row.addEventListener('click', function() {
                    const radio = this.querySelector('input[type="radio"]');
                    radio.checked = true;
                    const logisticId = radio.value;
                    document.getElementById('hidden_logistic_id').value = logisticId;
                });
            });
        });
    </script>
</body>
</html>
