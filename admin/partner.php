<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Shopee Admin - Application</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
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
    <?php include 'navbar.php'; ?>

    <!-- Main Content Canvas -->
    <main class="flex-1 ml-64 min-h-screen flex flex-col">
        <!-- TopNavBar -->
        <header class="w-full h-16 sticky top-0 z-50 flex justify-between items-center px-8 bg-surface dark:bg-zinc-950 tonal-shift bg-surface-container-low dark:bg-zinc-900 shadow-none">
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
                        <span class="text-on-surface">Application</span>
                    </nav>
                    <h1 class="font-manrope text-4xl font-extrabold tracking-tight text-on-background">Application</h1>
                </div>
                <div class="flex items-center gap-3">
                    <a href="../shopee_auth.php" class="flex items-center gap-2 bg-gradient-to-br from-emerald-600 to-emerald-500 text-white px-6 py-2.5 rounded-md font-semibold text-sm shadow-lg shadow-emerald-500/20 hover:scale-[1.02] active:scale-95 transition-all">
                        <span class="material-symbols-outlined text-sm">vpn_key</span>
                        Authorize
                    </a>
                    <button onclick="openAddModal()" class="flex items-center gap-2 bg-gradient-to-br from-[#b22204] to-[#d63c1e] text-white px-6 py-2.5 rounded-md font-semibold text-sm shadow-lg shadow-primary/20 hover:scale-[1.02] active:scale-95 transition-all">
                        <span class="material-symbols-outlined text-sm">add</span>
                        Create Application
                    </button>
                </div>
            </div>

            <!-- Table Section with "No-Line" Philosophy -->
            <div class="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden flex flex-col">
                <!-- Filters & Search Bar -->
                <div class="p-6 flex flex-wrap items-center justify-between gap-4 bg-surface-container-low/50">
                    <div class="flex items-center gap-4 flex-1 min-w-[300px]">
                        <div class="relative flex-1 max-w-sm">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400">search</span>
                            <input id="searchInput" class="w-full bg-white border border-outline-variant/20 rounded-md pl-10 pr-4 py-2 text-sm focus:ring-1 focus:ring-primary focus:border-primary" placeholder="Search app name, partner ID..." type="text" />
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="loadPartners()" class="flex items-center gap-2 text-xs font-semibold px-3 py-2 text-zinc-600 hover:bg-surface-bright rounded">
                            <span class="material-symbols-outlined text-sm">refresh</span>
                            Refresh
                        </button>
                    </div>
                </div>

                <!-- Loading State -->
                <div id="loadingMessage" class="py-20 text-center">
                    <div class="loading-spinner mb-4"></div>
                    <div class="text-sm font-label uppercase tracking-widest text-zinc-400">Loading Application...</div>
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
                                <th class="px-6 py-4 text-[10px] font-label uppercase tracking-widest text-zinc-500 text-center w-16">No</th>
                                <th class="px-6 py-4 text-[10px] font-label uppercase tracking-widest text-zinc-500">Nama App</th>
                                <th class="px-6 py-4 text-[10px] font-label uppercase tracking-widest text-zinc-500">Partner Key</th>
                                <th class="px-6 py-4 text-[10px] font-label uppercase tracking-widest text-zinc-500">Partner ID</th>
                                <th class="px-6 py-4 text-[10px] font-label uppercase tracking-widest text-zinc-500">Status</th>
                                <th class="px-6 py-4 text-[10px] font-label uppercase tracking-widest text-zinc-500 text-center w-24">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="partnersTableBody" class="divide-y divide-outline-variant/5">
                        </tbody>
                    </table>
                </div>
                
                <!-- Empty State -->
                <div id="emptyState" class="hidden py-20 text-center">
                    <span class="material-symbols-outlined text-6xl text-zinc-300 mb-4">apps</span>
                    <div class="font-manrope font-bold text-sm text-zinc-500 mb-1">No Applications Found</div>
                    <div class="text-xs text-zinc-400">Start by creating your first application</div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal for Adding/Editing -->
    <div id="partnerModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 opacity-0 pointer-events-none transition-opacity duration-300">
        <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full transform scale-95 transition-transform duration-300 modal-box-content p-8 mx-4">
            <h3 id="modalTitle" class="font-manrope text-xl font-bold text-on-background mb-6">Tambah Application</h3>
            
            <form id="partnerForm" class="space-y-5">
                <input type="hidden" id="editIdApp" name="id_app">
                
                <div>
                    <label class="block text-xs font-bold tracking-widest text-zinc-500 uppercase mb-2">Nama App <span class="text-[#ee4d2d]">*</span></label>
                    <input type="text" id="editNamaApp" name="nama_app" required
                           class="w-full h-12 bg-surface-container-low border-0 rounded-lg px-4 font-body focus:ring-2 focus:ring-primary/20 appearance-none bg-none"
                           placeholder="Masukkan nama application">
                </div>

                <div>
                    <label class="block text-xs font-bold tracking-widest text-zinc-500 uppercase mb-2">Partner Key <span class="text-[#ee4d2d]">*</span></label>
                    <textarea id="editPartnerKey" name="partner_key" required rows="3"
                              class="w-full bg-surface-container-low border-0 rounded-lg p-4 font-body focus:ring-2 focus:ring-primary/20 resize-none"
                              placeholder="Masukkan partner key"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold tracking-widest text-zinc-500 uppercase mb-2">Partner ID <span class="text-[#ee4d2d]">*</span></label>
                    <input type="text" id="editPartnerId" name="partner_id" required
                           class="w-full h-12 bg-surface-container-low border-0 rounded-lg px-4 font-body focus:ring-2 focus:ring-primary/20 appearance-none bg-none"
                           placeholder="Masukkan partner ID">
                </div>

                <div>
                    <label class="block text-xs font-bold tracking-widest text-zinc-500 uppercase mb-2">Status App</label>
                    <div class="relative">
                        <select id="editStatusApp" name="status_app"
                                class="w-full h-12 bg-surface-container-low border-0 rounded-lg px-4 font-body focus:ring-2 focus:ring-primary/20 appearance-none bg-none">
                            <option value="0">Developing</option>
                            <option value="1">Live Production</option>
                        </select>
                        <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-zinc-400">expand_more</span>
                    </div>
                </div>

                <div class="flex gap-4 pt-4 mt-6 border-t border-outline-variant/10">
                    <button type="button" onclick="closeModal()" class="flex-1 py-3 bg-surface-container-high text-zinc-600 font-semibold rounded-lg hover:bg-surface-dim transition-colors text-sm">
                        Batal
                    </button>
                    <button type="submit" class="flex-1 py-3 bg-gradient-to-br from-[#b22204] to-[#d63c1e] text-white font-semibold rounded-lg shadow-lg shadow-primary/20 hover:scale-[1.02] active:scale-95 transition-all text-sm">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/partner.js"></script>
</body>
</html>