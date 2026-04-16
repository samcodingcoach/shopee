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

                    // Simpan logistics untuk step 3, jangan fetch attributes dulu
                    $step = 3;
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
    $id_app = $_POST['id_app'] ?? '';
    $step = 3;

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
                // --- GET ATTRIBUTE TREE ---
                $apiPathAttr = "/api/v2/product/get_attribute_tree";
                $timestampAttr = (string)time();
                $baseStringAttr = $partnerId . $apiPathAttr . $timestampAttr . $accessToken . $shopId;
                $signAttr = hash_hmac('sha256', $baseStringAttr, $partnerKey);

                $baseUrl = "https://openplatform.sandbox.test-stable.shopee.sg";
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
        }
        $stmt->close();
    }
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
if (!empty($attributes)) {
    $step = 4;
}
?>
<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Tambah Produk - Shopee Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "tertiary-fixed": "#c2e8ff",
                        "surface-container-lowest": "#ffffff",
                        "tertiary-fixed-dim": "#76d1ff",
                        "on-error": "#ffffff",
                        "secondary": "#5b5e66",
                        "outline": "#8f7069",
                        "outline-variant": "#e3beb6",
                        "on-secondary-fixed-variant": "#43474e",
                        "surface": "#f9f9f9",
                        "on-primary": "#ffffff",
                        "error": "#ba1a1a",
                        "tertiary-container": "#007ea7",
                        "on-secondary-container": "#61646c",
                        "inverse-primary": "#ffb4a4",
                        "on-surface": "#1a1c1c",
                        "on-error-container": "#93000a",
                        "on-primary-fixed-variant": "#8d1600",
                        "primary-fixed": "#ffdad3",
                        "secondary-container": "#dfe2eb",
                        "inverse-surface": "#2f3131",
                        "surface-variant": "#e2e2e2",
                        "on-tertiary-fixed": "#001e2c",
                        "on-primary-fixed": "#3e0500",
                        "on-background": "#1a1c1c",
                        "background": "#f9f9f9",
                        "on-tertiary": "#ffffff",
                        "on-primary-container": "#fffbff",
                        "tertiary": "#006385",
                        "surface-container-high": "#e8e8e8",
                        "surface-container-highest": "#e2e2e2",
                        "on-surface-variant": "#5b403b",
                        "primary-fixed-dim": "#ffb4a4",
                        "surface-container": "#eeeeee",
                        "secondary-fixed": "#dfe2eb",
                        "on-tertiary-fixed-variant": "#004d67",
                        "error-container": "#ffdad6",
                        "surface-tint": "#b62506",
                        "on-secondary-fixed": "#181c22",
                        "inverse-on-surface": "#f1f1f1",
                        "secondary-fixed-dim": "#c3c6cf",
                        "surface-dim": "#dadada",
                        "primary": "#EE4D2D",
                        "surface-container-low": "#f3f3f3",
                        "surface-bright": "#f9f9f9",
                        "primary-container": "#d63c1e",
                        "on-tertiary-container": "#fbfcff",
                        "on-secondary": "#ffffff"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.125rem",
                        "lg": "0.25rem",
                        "xl": "0.5rem",
                        "full": "0.75rem"
                    },
                    "fontFamily": {
                        "headline": ["Manrope"],
                        "body": ["Inter"],
                        "label": ["Inter"]
                    }
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .step-active {
            color: #EE4D2D;
        }
    </style>
</head>
<body class="bg-surface font-body text-on-surface">
    <?php include 'navbar.php'; ?>

    <main class="ml-64 pt-12 px-8 pb-12 min-h-screen bg-surface">
        <div class="max-w-5xl mx-auto">
            <!-- Header Section -->
            <header class="mb-10">
                <h1 class="font-headline text-4xl font-extrabold text-on-surface tracking-tight mb-2">Create New Product</h1>
                <p class="text-secondary font-medium uppercase tracking-[0.1em] text-xs">Product Management &bull; Step-by-Step Curation</p>
            </header>

            <?php if ($success_message): ?>
                <div class="bg-[#f6ffed] border border-[#b7eb8f] rounded-xl p-8 text-center mb-8 shadow-sm">
                    <div class="text-6xl mb-4">✅</div>
                    <div class="text-xl font-semibold text-[#52c41a] mb-2">Berhasil!</div>
                    <div class="text-[#666] mb-6"><?php echo htmlspecialchars($success_message); ?></div>
                    <a href="item.php" class="px-8 py-3 bg-gradient-to-br from-primary to-primary-container text-white font-bold uppercase tracking-widest text-xs rounded-lg shadow-xl shadow-primary/20 hover:scale-105 active:scale-95 transition-all inline-block">
                        &larr; Kembali ke Daftar Produk
                    </a>
                </div>
            <?php else: ?>
                <?php if ($error_message): ?>
                    <div class="bg-[#fff2f0] border border-[#ffccc7] rounded-lg p-4 mb-8 flex items-center gap-3 text-[#ff4d4f]">
                        <span class="material-symbols-outlined">error</span>
                        <span class="font-medium text-sm"><?php echo htmlspecialchars($error_message); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Stepper Indicator -->
                <div class="grid grid-cols-4 gap-4 mb-12">
                    <div class="relative group">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="w-8 h-8 rounded-full <?php echo $step >= 1 ? 'bg-primary text-white ring-4 ring-primary-fixed' : 'bg-surface-container-high text-on-surface-variant'; ?> flex items-center justify-center text-xs font-bold">01</span>
                            <span class="text-sm font-semibold <?php echo $step >= 1 ? 'text-primary' : 'text-secondary'; ?>">Upload Image</span>
                        </div>
                        <div class="h-1.5 w-full <?php echo $step > 1 ? 'bg-primary' : ($step == 1 ? 'bg-primary' : 'bg-surface-container-high'); ?> rounded-full"></div>
                    </div>
                    <div class="relative group">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="w-8 h-8 rounded-full <?php echo $step >= 2 ? 'bg-primary text-white ring-4 ring-primary-fixed' : 'bg-surface-container-high text-on-surface-variant'; ?> flex items-center justify-center text-xs font-bold">02</span>
                            <span class="text-sm font-semibold <?php echo $step >= 2 ? 'text-primary' : 'text-secondary'; ?>">Select Category</span>
                        </div>
                        <div class="h-1.5 w-full <?php echo $step > 2 ? 'bg-primary' : ($step == 2 ? 'bg-primary' : 'bg-surface-container-high'); ?> rounded-full"></div>
                    </div>
                    <div class="relative group">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="w-8 h-8 rounded-full <?php echo $step >= 3 ? 'bg-primary text-white ring-4 ring-primary-fixed' : 'bg-surface-container-high text-on-surface-variant'; ?> flex items-center justify-center text-xs font-bold">03</span>
                            <span class="text-sm font-semibold <?php echo $step >= 3 ? 'text-primary' : 'text-secondary'; ?>">Select Pengiriman</span>
                        </div>
                        <div class="h-1.5 w-full <?php echo $step > 3 ? 'bg-primary' : ($step == 3 ? 'bg-primary' : 'bg-surface-container-high'); ?> rounded-full"></div>
                    </div>
                    <div class="relative group">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="w-8 h-8 rounded-full <?php echo $step >= 4 ? 'bg-primary text-white ring-4 ring-primary-fixed' : 'bg-surface-container-high text-on-surface-variant'; ?> flex items-center justify-center text-xs font-bold">04</span>
                            <span class="text-sm font-semibold <?php echo $step >= 4 ? 'text-primary' : 'text-secondary'; ?>">Detail Product</span>
                        </div>
                        <div class="h-1.5 w-full <?php echo $step >= 4 ? 'bg-primary' : 'bg-surface-container-high'; ?> rounded-full"></div>
                    </div>
                </div>

                <!-- Bento Form Content -->
                <div class="grid grid-cols-12">
                    <div class="col-span-12 lg:col-span-12 space-y-6">
                        <div class="bg-surface-container-lowest p-8 rounded-xl shadow-[0_12px_32px_-4px_rgba(26,28,28,0.06)] border border-outline-variant/10">
                            
                            <?php if ($step === 1): ?>
                            <form method="POST" enctype="multipart/form-data">
                                <div class="space-y-8">
                                    <div class="space-y-3">
                                        <label class="text-xs font-bold tracking-widest text-secondary uppercase block">Pilih App <span class="text-primary">*</span></label>
                                        <div class="relative">
                                            <select name="id_app" class="w-full h-12 bg-surface-container-low border-0 rounded-lg px-4 font-body focus:ring-2 focus:ring-primary/20 appearance-none bg-none" required>
                                                <option value="">-- Pilih Aplikasi --</option>
                                                <?php foreach ($apps as $app): ?>
                                                    <option value="<?php echo $app['id_app']; ?>" <?php echo (isset($_POST['id_app']) && $_POST['id_app'] == $app['id_app']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($app['nama_app']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-secondary">expand_more</span>
                                        </div>
                                    </div>
                                    <div class="space-y-4">
                                        <label class="text-xs font-bold tracking-widest text-secondary uppercase block">Upload Image <span class="text-primary">*</span></label>
                                        <div class="border-2 border-dashed border-outline-variant/30 rounded-xl p-12 flex flex-col items-center justify-center bg-surface-container-lowest hover:bg-primary-fixed/10 transition-colors duration-300 group relative overflow-hidden" id="upload-container">
                                            <input type="file" name="image" id="image-upload-input" accept=".jpg,.jpeg" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" required>
                                            
                                            <!-- Default State -->
                                            <div id="upload-placeholder" class="flex flex-col items-center pointer-events-none">
                                                <div class="w-16 h-16 rounded-full bg-surface-container-low flex items-center justify-center mb-4 group-hover:bg-primary/10 transition-colors">
                                                    <span class="material-symbols-outlined text-primary text-3xl">cloud_upload</span>
                                                </div>
                                                <h4 class="text-on-surface font-semibold mb-1">Click to upload image</h4>
                                                <p class="text-secondary text-sm mb-6">Support JPG, JPEG (Max 1MB)</p>
                                                <button type="button" class="px-6 py-2.5 bg-primary text-white font-semibold rounded-lg shadow-lg shadow-primary/20 hover:primary-container active:scale-95 transition-all text-sm">
                                                    Browse Image
                                                </button>
                                            </div>

                                            <!-- Preview State -->
                                            <img id="image-preview" class="absolute inset-0 w-full h-full object-contain hidden z-0 bg-surface-container-lowest" />
                                        </div>
                                    </div>
                                    <div class="flex justify-end pt-6">
                                        <button type="submit" name="upload_image" class="px-10 py-3 bg-gradient-to-br from-primary to-primary-container text-white font-bold uppercase tracking-widest text-xs rounded-lg shadow-xl shadow-primary/20 active:scale-95 transition-all">
                                            Upload & Selanjutnya &rarr;
                                        </button>
                                    </div>
                                </div>
                                <script>
                                    document.addEventListener('DOMContentLoaded', function() {
                                        const input = document.getElementById('image-upload-input');
                                        if(input) {
                                            input.addEventListener('change', function(event) {
                                                const file = event.target.files[0];
                                                if (file) {
                                                    const reader = new FileReader();
                                                    reader.onload = function(e) {
                                                        const preview = document.getElementById('image-preview');
                                                        const placeholder = document.getElementById('upload-placeholder');
                                                        preview.src = e.target.result;
                                                        preview.classList.remove('hidden');
                                                        placeholder.classList.add('hidden');
                                                    }
                                                    reader.readAsDataURL(file);
                                                }
                                            });
                                        }
                                    });
                                </script>
                                </div>
                            </form>
                            <?php endif; ?>

                            <?php if ($step === 2): ?>
                            <form method="POST">
                                <input type="hidden" name="image_id" value="<?php echo htmlspecialchars($image_id); ?>">
                                <input type="hidden" name="id_app_hidden" value="<?php echo htmlspecialchars($_POST['id_app_hidden'] ?? ''); ?>">
                                <input type="hidden" name="id_app" value="<?php echo htmlspecialchars($_POST['id_app_hidden'] ?? ''); ?>">

                                <div class="space-y-6">
                                    <div class="space-y-3">
                                        <label class="text-xs font-bold tracking-widest text-secondary uppercase block">Image ID</label>
                                        <input type="text" value="<?php echo htmlspecialchars($image_id); ?>" class="w-full h-12 bg-surface-container-high border-0 rounded-lg px-4 font-body text-secondary" readonly>
                                    </div>
                                    <div class="space-y-3">
                                        <label class="text-xs font-bold tracking-widest text-secondary uppercase block">Pilih Kategori <span class="text-primary">*</span></label>
                                        <div class="relative">
                                            <select name="category_id" class="w-full h-12 bg-surface-container-low border-0 rounded-lg px-4 font-body focus:ring-2 focus:ring-primary/20 appearance-none bg-none" required>
                                                <option value="">-- Pilih Kategori --</option>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?php echo htmlspecialchars($cat['category_id']); ?>" <?php echo ($category_id == $cat['category_id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($cat['display_category_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-secondary">category</span>
                                        </div>
                                    </div>
                                    <div class="flex justify-end pt-6">
                                        <button type="submit" name="select_category" class="px-10 py-3 bg-gradient-to-br from-primary to-primary-container text-white font-bold uppercase tracking-widest text-xs rounded-lg shadow-xl shadow-primary/20 active:scale-95 transition-all">
                                            Selanjutnya &rarr;
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <?php endif; ?>

                            <?php if ($step === 3): ?>
                            <form method="POST">
                                <input type="hidden" name="image_id" value="<?php echo htmlspecialchars($image_id); ?>">
                                <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($category_id); ?>">
                                <input type="hidden" name="id_app" value="<?php echo htmlspecialchars($_POST['id_app'] ?? ''); ?>">

                                <div class="space-y-6">
                                    <div class="grid grid-cols-2 gap-6">
                                        <div class="space-y-3">
                                            <label class="text-xs font-bold tracking-widest text-secondary uppercase block">Image ID</label>
                                            <input type="text" value="<?php echo htmlspecialchars($image_id); ?>" class="w-full h-12 bg-surface-container-high border-0 rounded-lg px-4 font-body text-secondary" readonly>
                                        </div>
                                        <div class="space-y-3">
                                            <label class="text-xs font-bold tracking-widest text-secondary uppercase block">Category ID</label>
                                            <input type="text" value="<?php echo htmlspecialchars($category_id); ?>" class="w-full h-12 bg-surface-container-high border-0 rounded-lg px-4 font-body text-secondary" readonly>
                                        </div>
                                    </div>

                                    <?php if (!empty($logistics)): ?>
                                    <div class="space-y-3">
                                        <label class="text-xs font-bold tracking-widest text-secondary uppercase block">Pilih Pengiriman</label>
                                        <div class="overflow-hidden rounded-xl border border-surface-container-high">
                                            <table class="w-full text-left border-collapse">
                                                <thead class="bg-surface-container-low">
                                                    <tr>
                                                        <th class="px-6 py-4 text-xs font-bold text-secondary uppercase tracking-wider">Pilih</th>
                                                        <th class="px-6 py-4 text-xs font-bold text-secondary uppercase tracking-wider">Logistic ID</th>
                                                        <th class="px-6 py-4 text-xs font-bold text-secondary uppercase tracking-wider">Nama Kurir</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-surface-container-low">
                                                    <?php foreach ($logistics as $logistic):
                                                        $logistic_id = $logistic['logistics_channel_id'] ?? ($logistic['logistic_id'] ?? ($logistic['channel_id'] ?? 'N/A'));
                                                        $logistic_name = $logistic['logistics_channel_name'] ?? ($logistic['logistic_name'] ?? ($logistic['name'] ?? '-'));
                                                    ?>
                                                    <tr class="hover:bg-surface-container-lowest transition-colors cursor-pointer" onclick="document.getElementById('logistic_<?php echo htmlspecialchars($logistic_id); ?>').checked = true; document.getElementById('hidden_logistic_id').value = '<?php echo htmlspecialchars($logistic_id); ?>';">
                                                        <td class="px-6 py-4">
                                                            <input id="logistic_<?php echo htmlspecialchars($logistic_id); ?>" class="w-4 h-4 text-primary focus:ring-primary border-outline-variant" name="logistic_radio" type="radio" value="<?php echo htmlspecialchars($logistic_id); ?>" onclick="document.getElementById('hidden_logistic_id').value = this.value;" <?php echo ($selected_logistic == $logistic_id) ? 'checked' : ''; ?> />
                                                        </td>
                                                        <td class="px-6 py-4 font-mono text-sm">#<?php echo htmlspecialchars($logistic_id); ?></td>
                                                        <td class="px-6 py-4">
                                                            <div class="flex items-center gap-3">
                                                                <div class="w-8 h-8 rounded bg-surface-container-high flex items-center justify-center text-xs font-bold text-primary">
                                                                    <?php echo substr(strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $logistic_name)), 0, 2); ?>
                                                                </div>
                                                                <span class="font-medium"><?php echo htmlspecialchars($logistic_name); ?></span>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <input type="hidden" id="hidden_logistic_id" name="logistic_id" value="<?php echo htmlspecialchars($selected_logistic); ?>">
                                    </div>
                                    <?php else: ?>
                                    <div class="bg-[#fff2f0] border border-[#ffccc7] rounded-lg p-4 text-[#ff4d4f] flex items-center gap-3">
                                        <span class="material-symbols-outlined">info</span>
                                        <span>Tidak ada data logistik ditemukan untuk toko ini.</span>
                                    </div>
                                    <?php endif; ?>

                                    <div class="flex justify-end pt-6">
                                        <button type="submit" name="select_logistic" class="px-10 py-3 bg-gradient-to-br from-primary to-primary-container text-white font-bold uppercase tracking-widest text-xs rounded-lg shadow-xl shadow-primary/20 active:scale-95 transition-all">
                                            Selanjutnya &rarr;
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <?php endif; ?>

                            <?php if ($step === 4): ?>
                            <form method="POST">
                                <input type="hidden" name="image_id" value="<?php echo htmlspecialchars($image_id); ?>">
                                <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($category_id); ?>">
                                <input type="hidden" name="id_app" value="<?php echo htmlspecialchars($_POST['id_app'] ?? ''); ?>">
                                <input type="hidden" name="logistic_id" value="<?php echo htmlspecialchars($selected_logistic); ?>">

                                <div class="grid grid-cols-2 gap-6">
                                    <div class="col-span-2 space-y-3">
                                        <label class="text-xs font-bold tracking-widest text-secondary uppercase block">Product Name <span class="text-primary">*</span></label>
                                        <input type="text" name="item_name" class="w-full h-12 bg-surface-container-low border-0 rounded-lg px-4 font-body focus:ring-2 focus:ring-primary/20" required value="<?php echo htmlspecialchars($form_data['item_name'] ?? ''); ?>" placeholder="e.g. Minimalist Oak Table" />
                                    </div>
                                    <div class="space-y-3">
                                        <label class="text-xs font-bold tracking-widest text-secondary uppercase block">Price (IDR) <span class="text-primary">*</span></label>
                                        <div class="relative">
                                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-secondary font-bold text-sm">Rp</span>
                                            <input type="number" name="original_price" class="w-full h-12 bg-surface-container-low border-0 rounded-lg pl-12 pr-4 font-body focus:ring-2 focus:ring-primary/20" required placeholder="0.00" value="<?php echo htmlspecialchars($form_data['original_price'] ?? ''); ?>" />
                                        </div>
                                    </div>
                                    <div class="space-y-3">
                                        <label class="text-xs font-bold tracking-widest text-secondary uppercase block">Weight (kg) <span class="text-primary">*</span></label>
                                        <input type="number" step="0.01" name="weight" class="w-full h-12 bg-surface-container-low border-0 rounded-lg px-4 font-body focus:ring-2 focus:ring-primary/20" required placeholder="e.g. 0.3" value="<?php echo htmlspecialchars($form_data['weight'] ?? ''); ?>" />
                                    </div>
                                    <div class="col-span-2 space-y-3">
                                        <label class="text-xs font-bold tracking-widest text-secondary uppercase block">Description <span class="text-primary">*</span></label>
                                        <textarea name="description" class="w-full bg-surface-container-low border-0 rounded-lg p-4 font-body focus:ring-2 focus:ring-primary/20 resize-none" required placeholder="Enter product description here..." rows="4"><?php echo htmlspecialchars($form_data['description'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="space-y-3">
                                        <label class="text-xs font-bold tracking-widest text-secondary uppercase block">Stock <span class="text-primary">*</span></label>
                                        <input type="number" name="stock" class="w-full h-12 bg-surface-container-low border-0 rounded-lg px-4 font-body focus:ring-2 focus:ring-primary/20" required value="<?php echo htmlspecialchars($form_data['stock'] ?? '50'); ?>" />
                                    </div>
                                    <div class="space-y-3">
                                        <label class="text-xs font-bold tracking-widest text-secondary uppercase block">Item SKU</label>
                                        <input type="text" name="item_sku" class="w-full h-12 bg-surface-container-low border-0 rounded-lg px-4 font-body focus:ring-2 focus:ring-primary/20" placeholder="e.g. SKU-12345" value="<?php echo htmlspecialchars($form_data['item_sku'] ?? ''); ?>" />
                                    </div>
                                    <div class="space-y-3">
                                        <label class="text-xs font-bold tracking-widest text-secondary uppercase block">Condition</label>
                                        <div class="relative">
                                            <select name="condition" class="w-full h-12 bg-surface-container-low border-0 rounded-lg px-4 font-body focus:ring-2 focus:ring-primary/20 appearance-none bg-none">
                                                <option value="NEW" <?php echo ($form_data['condition'] ?? 'NEW') == 'NEW' ? 'selected' : ''; ?>>NEW</option>
                                                <option value="USED" <?php echo ($form_data['condition'] ?? '') == 'USED' ? 'selected' : ''; ?>>USED</option>
                                            </select>
                                            <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-secondary">expand_more</span>
                                        </div>
                                    </div>
                                    <div class="space-y-3">
                                        <label class="text-xs font-bold tracking-widest text-secondary uppercase block">Status</label>
                                        <div class="relative">
                                            <select name="item_status" class="w-full h-12 bg-surface-container-low border-0 rounded-lg px-4 font-body focus:ring-2 focus:ring-primary/20 appearance-none bg-none">
                                                <option value="NORMAL" <?php echo ($form_data['item_status'] ?? 'NORMAL') == 'NORMAL' ? 'selected' : ''; ?>>NORMAL</option>
                                                <option value="UNLIST" <?php echo ($form_data['item_status'] ?? '') == 'UNLIST' ? 'selected' : ''; ?>>UNLIST</option>
                                            </select>
                                            <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-secondary">expand_more</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-8 pt-8 border-t border-surface-container-high grid grid-cols-3 gap-6">
                                    <div class="col-span-3">
                                        <h3 class="text-sm font-bold tracking-widest text-secondary uppercase">Wholesale (Grosir)</h3>
                                        <p class="text-xs text-secondary mt-1">Kosongkan untuk menonaktifkan grosir.</p>
                                    </div>
                                    <div class="space-y-3">
                                        <label class="text-xs font-bold tracking-widest text-secondary uppercase block">Min Qty</label>
                                        <input type="number" name="wholesale_min" class="w-full h-12 bg-surface-container-low border-0 rounded-lg px-4 font-body focus:ring-2 focus:ring-primary/20" value="<?php echo htmlspecialchars($form_data['wholesale_min'] ?? '10'); ?>" />
                                    </div>
                                    <div class="space-y-3">
                                        <label class="text-xs font-bold tracking-widest text-secondary uppercase block">Max Qty</label>
                                        <input type="number" name="wholesale_max" class="w-full h-12 bg-surface-container-low border-0 rounded-lg px-4 font-body focus:ring-2 focus:ring-primary/20" value="<?php echo htmlspecialchars($form_data['wholesale_max'] ?? '10'); ?>" />
                                    </div>
                                    <div class="space-y-3">
                                        <label class="text-xs font-bold tracking-widest text-secondary uppercase block">Wholesale Price (Rp)</label>
                                        <input type="number" name="wholesale_price" class="w-full h-12 bg-surface-container-low border-0 rounded-lg px-4 font-body focus:ring-2 focus:ring-primary/20" value="<?php echo htmlspecialchars($form_data['wholesale_price'] ?? ''); ?>" />
                                    </div>
                                </div>

                                <div class="mt-8 pt-8 border-t border-surface-container-high grid grid-cols-3 gap-6">
                                    <div class="col-span-3">
                                        <h3 class="text-sm font-bold tracking-widest text-secondary uppercase">Dimensi Paket</h3>
                                    </div>
                                    <div class="space-y-3">
                                        <label class="text-xs font-bold tracking-widest text-secondary uppercase block">Height (cm)</label>
                                        <input type="number" name="package_height" class="w-full h-12 bg-surface-container-low border-0 rounded-lg px-4 font-body focus:ring-2 focus:ring-primary/20" value="<?php echo htmlspecialchars($form_data['package_height'] ?? '10'); ?>" />
                                    </div>
                                    <div class="space-y-3">
                                        <label class="text-xs font-bold tracking-widest text-secondary uppercase block">Length (cm)</label>
                                        <input type="number" name="package_length" class="w-full h-12 bg-surface-container-low border-0 rounded-lg px-4 font-body focus:ring-2 focus:ring-primary/20" value="<?php echo htmlspecialchars($form_data['package_length'] ?? '15'); ?>" />
                                    </div>
                                    <div class="space-y-3">
                                        <label class="text-xs font-bold tracking-widest text-secondary uppercase block">Width (cm)</label>
                                        <input type="number" name="package_width" class="w-full h-12 bg-surface-container-low border-0 rounded-lg px-4 font-body focus:ring-2 focus:ring-primary/20" value="<?php echo htmlspecialchars($form_data['package_width'] ?? '10'); ?>" />
                                    </div>
                                </div>

                                <?php if (!empty($attributes)): ?>
                                <div class="mt-8 pt-8 border-t border-surface-container-high">
                                    <h3 class="text-sm font-bold tracking-widest text-secondary uppercase mb-6">Atribut Kategori</h3>
                                    <div class="grid grid-cols-2 gap-6">
                                        <?php foreach ($attributes as $attr): ?>
                                            <?php
                                            $is_req = ($attr['is_mandatory'] ?? false) ? true : false;
                                            $display_name = $attr['display_attribute_name'] ?? $attr['name'] ?? 'Atribut';
                                            ?>
                                            <div class="space-y-3">
                                                <label class="text-xs font-bold tracking-widest text-secondary uppercase block">
                                                    <?php echo htmlspecialchars($display_name); ?>
                                                    <?php echo $is_req ? '<span class="text-primary">*</span>' : ''; ?>
                                                </label>
                                                <?php if (!empty($attr['attribute_value_list'])): ?>
                                                    <div class="relative">
                                                        <select name="attributes[<?php echo $attr['attribute_id']; ?>]" class="w-full h-12 bg-surface-container-low border-0 rounded-lg px-4 font-body focus:ring-2 focus:ring-primary/20 appearance-none bg-none" <?php echo $is_req ? 'required' : ''; ?>>
                                                            <option value="">-- Pilih --</option>
                                                            <?php foreach ($attr['attribute_value_list'] as $val): ?>
                                                                <?php $vName = $val['display_value_name'] ?? $val['name'] ?? 'Opsi'; ?>
                                                                <option value="<?php echo $val['value_id'] . '|' . htmlspecialchars($vName); ?>" <?php echo ($form_data['attributes'][$attr['attribute_id']] ?? '') == ($val['value_id'] . '|' . $vName) ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($vName); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-secondary">expand_more</span>
                                                    </div>
                                                <?php else: ?>
                                                    <input type="text" name="attributes[<?php echo $attr['attribute_id']; ?>]" class="w-full h-12 bg-surface-container-low border-0 rounded-lg px-4 font-body focus:ring-2 focus:ring-primary/20" <?php echo $is_req ? 'required' : ''; ?> value="<?php echo htmlspecialchars($form_data['attributes'][$attr['attribute_id']] ?? ''); ?>" placeholder="Masukkan <?php echo htmlspecialchars($display_name); ?>">
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Action Buttons -->
                                <div class="mt-8 flex justify-end">
                                    <button type="submit" name="create_product" class="px-10 py-3 bg-gradient-to-br from-primary to-primary-container text-white font-bold uppercase tracking-widest text-xs rounded-lg shadow-xl shadow-primary/20 active:scale-95 transition-all">
                                        Publish Product
                                    </button>
                                </div>
                            </form>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check if there are logistic rows to bind click to
            const logisticRows = document.querySelectorAll('tr[onclick]');
            logisticRows.forEach(row => {
                row.addEventListener('click', function() {
                    const radio = this.querySelector('input[type="radio"]');
                    if(radio) {
                        radio.checked = true;
                        document.getElementById('hidden_logistic_id').value = radio.value;
                    }
                });
            });
        });
    </script>
</body>
</html>