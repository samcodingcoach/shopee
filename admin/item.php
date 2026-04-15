<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Shopee Admin - Products</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;family=Manrope:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "tertiary": "#006385",
                        "secondary-fixed": "#dfe2eb",
                        "on-primary-container": "#fffbff",
                        "on-primary": "#ffffff",
                        "on-error": "#ffffff",
                        "primary-fixed": "#ffdad3",
                        "on-secondary-container": "#61646c",
                        "error": "#ba1a1a",
                        "on-surface-variant": "#5b403b",
                        "on-tertiary-container": "#fbfcff",
                        "surface-variant": "#e2e2e2",
                        "outline": "#8f7069",
                        "on-primary-fixed-variant": "#8d1600",
                        "inverse-surface": "#2f3131",
                        "tertiary-fixed": "#c2e8ff",
                        "inverse-on-surface": "#f1f1f1",
                        "primary-container": "#d63c1e",
                        "surface-dim": "#dadada",
                        "on-tertiary-fixed-variant": "#004d67",
                        "on-secondary": "#ffffff",
                        "surface": "#f9f9f9",
                        "on-background": "#1a1c1c",
                        "secondary-container": "#dfe2eb",
                        "primary": "#b22204",
                        "error-container": "#ffdad6",
                        "secondary-fixed-dim": "#c3c6cf",
                        "surface-tint": "#b62506",
                        "on-error-container": "#93000a",
                        "on-surface": "#1a1c1c",
                        "surface-container-high": "#e8e8e8",
                        "tertiary-container": "#007ea7",
                        "primary-fixed-dim": "#ffb4a4",
                        "surface-container-lowest": "#ffffff",
                        "inverse-primary": "#ffb4a4",
                        "surface-container-low": "#f3f3f3",
                        "secondary": "#5b5e66",
                        "on-secondary-fixed": "#181c22",
                        "on-primary-fixed": "#3e0500",
                        "on-tertiary-fixed": "#001e2c",
                        "on-tertiary": "#ffffff",
                        "surface-bright": "#f9f9f9",
                        "surface-container": "#eeeeee",
                        "tertiary-fixed-dim": "#76d1ff",
                        "on-secondary-fixed-variant": "#43474e",
                        "surface-container-highest": "#e2e2e2",
                        "outline-variant": "#e3beb6",
                        "background": "#f9f9f9"
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
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f9f9f9;
            color: #1a1c1c;
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .font-manrope {
            font-family: 'Manrope', sans-serif;
        }
        .loading-spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 3px solid #f3f3f3;
            border-top-color: #b22204;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="flex min-h-screen">
    <!-- SideNavBar -->
    <aside class="w-64 h-screen fixed left-0 top-0 bg-white dark:bg-zinc-950 flex flex-col h-full py-6 border-r border-zinc-100/15 dark:border-zinc-800/15 shadow-sm dark:shadow-none z-50">
        <div class="px-6 mb-8">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-primary rounded flex items-center justify-center text-white font-bold">S</div>
                <div>
                    <h2 class="font-manrope font-semibold text-sm tracking-wide text-on-surface">Shopee Admin</h2>
                    <p class="text-[10px] text-zinc-500 uppercase tracking-widest">Enterprise Edition</p>
                </div>
            </div>
        </div>
        <nav class="flex-1 space-y-1">
            <!-- Dashboard Item -->
            <a class="flex items-center px-6 py-3 text-slate-500 dark:text-slate-400 font-medium hover:text-on-surface hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors duration-200" href="#">
                <span class="material-symbols-outlined mr-3">dashboard</span>
                <span class="font-manrope font-medium text-sm tracking-wide">Dashboard</span>
            </a>
            <!-- Application Item -->
            <a class="flex items-center px-6 py-3 text-slate-500 dark:text-slate-400 font-medium hover:text-on-surface hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors duration-200" href="partner.php">
                <span class="material-symbols-outlined mr-3">apps</span>
                <span class="font-manrope font-medium text-sm tracking-wide">Application</span>
            </a>
            <!-- Product Item (Active) -->
            <a class="flex items-center px-6 py-3 border-l-4 border-[#EE4D2D] text-on-surface font-semibold bg-slate-50 dark:bg-slate-900 transition-all duration-300 ease-in-out" href="item.php">
                <span class="material-symbols-outlined mr-3 text-[#EE4D2D]">inventory_2</span>
                <span class="font-manrope font-medium text-sm tracking-wide">Product</span>
            </a>
        </nav>
        <div class="mt-auto border-t border-zinc-100/15 pt-4">
            <a class="flex items-center px-6 py-3 text-zinc-500 hover:text-[#EE4D2D] hover:bg-zinc-50 transition-all" href="#">
                <span class="material-symbols-outlined mr-3">help</span>
                <span class="font-manrope font-medium text-sm tracking-wide">Support</span>
            </a>
            <a class="flex items-center px-6 py-3 text-zinc-500 hover:text-[#EE4D2D] hover:bg-zinc-50 transition-all" href="#">
                <span class="material-symbols-outlined mr-3">logout</span>
                <span class="font-manrope font-medium text-sm tracking-wide">Logout</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Canvas -->
    <main class="flex-1 ml-64 min-h-screen flex flex-col">
        <!-- TopNavBar -->
        <header class="w-full h-16 sticky top-0 z-50 flex justify-between items-center px-8 w-full bg-surface dark:bg-zinc-950 tonal-shift bg-surface-container-low dark:bg-zinc-900 shadow-none">
            <div class="flex items-center gap-6 flex-1">
                <span class="text-[#EE4D2D] font-manrope font-extrabold tracking-tighter text-2xl">Shopee Open Platform</span>
                <div class="relative max-w-md w-full">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-sm">search</span>
                    <input class="w-full bg-surface-container-high border-none rounded-sm pl-10 text-sm py-2 focus:ring-1 focus:ring-primary focus:bg-white transition-all" placeholder="Search Global" type="text" />
                </div>
            </div>
        </header>

        <!-- Body Canvas -->
        <div class="p-8 space-y-8 max-w-[1400px] mx-auto w-full">
            <!-- Header Section -->
            <div class="flex justify-between items-end">
                <div>
                    <nav class="flex gap-2 text-xs font-label uppercase tracking-widest text-zinc-400 mb-2">
                        <span>API</span>
                        <span>/</span>
                        <span class="text-on-surface">Products</span>
                    </nav>
                    <h1 class="font-manrope text-4xl font-extrabold tracking-tight text-on-background">Products</h1>
                </div>
                <a href="new-item.php" class="flex items-center gap-2 bg-gradient-to-br from-[#b22204] to-[#d63c1e] text-white px-6 py-2.5 rounded-md font-semibold text-sm shadow-lg shadow-primary/20 hover:scale-[1.02] active:scale-95 transition-all">
                    <span class="material-symbols-outlined text-sm">add</span>
                    Create Product
                </a>
            </div>

            <!-- Dashboard Stats Tonal Shift Layering -->
            <div id="statsContainer" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-surface-container-lowest p-6 rounded-lg shadow-sm border-l-4 border-primary">
                    <p class="text-xs font-label uppercase tracking-widest text-zinc-500 mb-1">Total Products</p>
                    <p id="statTotal" class="text-2xl font-manrope font-extrabold">-</p>
                </div>
                <div class="bg-surface-container-low p-6 rounded-lg">
                    <p class="text-xs font-label uppercase tracking-widest text-zinc-500 mb-1">Active Now</p>
                    <p id="statActive" class="text-2xl font-manrope font-extrabold">-</p>
                </div>
                <div class="bg-surface-container-low p-6 rounded-lg">
                    <p class="text-xs font-label uppercase tracking-widest text-zinc-500 mb-1">Out of Stock</p>
                    <p id="statOutOfStock" class="text-2xl font-manrope font-extrabold text-error">-</p>
                </div>
                <div class="bg-surface-container-low p-6 rounded-lg">
                    <p class="text-xs font-label uppercase tracking-widest text-zinc-500 mb-1">Total Value</p>
                    <p id="statTotalValue" class="text-2xl font-manrope font-extrabold">-</p>
                </div>
            </div>

            <!-- Table Section with "No-Line" Philosophy -->
            <div class="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden flex flex-col">
                <!-- Filters & Search Bar -->
                <div class="p-6 flex flex-wrap items-center justify-between gap-4 bg-surface-container-low/50">
                    <div class="flex items-center gap-4 flex-1 min-w-[300px]">
                        <div class="relative flex-1 max-w-sm">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400">search</span>
                            <input id="searchInput" class="w-full bg-white border border-outline-variant/20 rounded-md pl-10 pr-4 py-2 text-sm focus:ring-1 focus:ring-primary focus:border-primary" placeholder="Search product name, SKU..." type="text" onkeyup="filterItems()" />
                        </div>
                        <div class="flex items-center gap-4">
                            <select id="statusFilter" class="bg-white border border-outline-variant/20 rounded-md px-3 py-2 text-xs font-medium focus:ring-1 focus:ring-primary" onchange="filterItems()">
                                <option value="">Status: All</option>
                                <option value="NORMAL">Active</option>
                                <option value="UNLIST">Unlist</option>
                                <option value="BANNED">Banned</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="loadItems()" class="flex items-center gap-2 text-xs font-semibold px-3 py-2 text-zinc-600 hover:bg-surface-bright rounded">
                            <span class="material-symbols-outlined text-sm">refresh</span>
                            Refresh
                        </button>
                    </div>
                </div>

                <!-- Loading State -->
                <div id="loadingMessage" class="py-20 text-center">
                    <div class="loading-spinner mb-4"></div>
                    <div class="text-sm font-label uppercase tracking-widest text-zinc-400">Loading Products...</div>
                </div>

                <!-- Error State -->
                <div id="errorMessage" class="hidden p-8 text-center">
                    <span class="material-symbols-outlined text-4xl text-error mb-2">error</span>
                    <div id="errorText" class="text-sm text-error font-medium"></div>
                </div>

                <!-- Main Data Table -->
                <div id="tableContent" class="overflow-x-auto hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container-low/30 border-b border-outline-variant/10">
                                <th class="px-6 py-4 text-[10px] font-label uppercase tracking-widest text-zinc-500">Image</th>
                                <th class="px-6 py-4 text-[10px] font-label uppercase tracking-widest text-zinc-500">Product Name</th>
                                <th class="px-6 py-4 text-[10px] font-label uppercase tracking-widest text-zinc-500">SKU</th>
                                <th class="px-6 py-4 text-[10px] font-label uppercase tracking-widest text-zinc-500">Price</th>
                                <th class="px-6 py-4 text-[10px] font-label uppercase tracking-widest text-zinc-500">Stock</th>
                                <th class="px-6 py-4 text-[10px] font-label uppercase tracking-widest text-zinc-500">Status</th>
                                <th class="px-6 py-4 text-[10px] font-label uppercase tracking-widest text-zinc-500">Category</th>
                                <th class="px-6 py-4 text-[10px] font-label uppercase tracking-widest text-zinc-500 text-right">Last Updated</th>
                            </tr>
                        </thead>
                        <tbody id="itemsTableBody" class="divide-y divide-outline-variant/5">
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div id="emptyState" class="hidden py-20 text-center">
                    <span class="material-symbols-outlined text-6xl text-zinc-300 mb-4">inventory_2</span>
                    <div class="font-manrope font-bold text-sm text-zinc-500 mb-1">No Products Found</div>
                    <div class="text-xs text-zinc-400">Start by creating your first product</div>
                </div>

                <!-- Pagination -->
                <div id="paginationContainer" class="p-6 border-t border-outline-variant/10 flex items-center justify-between hidden">
                    <p id="paginationInfo" class="text-xs text-zinc-500 font-medium">Showing 0 results</p>
                    <div class="flex items-center gap-2">
                        <button class="flex items-center gap-2 text-xs font-semibold px-3 py-2 text-zinc-600 hover:bg-surface-bright rounded">
                            <span class="material-symbols-outlined text-sm">chevron_left</span>
                            Previous
                        </button>
                        <button class="flex items-center gap-2 text-xs font-semibold px-3 py-2 text-zinc-600 hover:bg-surface-bright rounded">
                            Next
                            <span class="material-symbols-outlined text-sm">chevron_right</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="js/item.js"></script>
</body>
</html>
