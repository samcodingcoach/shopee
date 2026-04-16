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
        <a class="flex items-center px-6 py-3 border-l-4 <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'border-[#EE4D2D] text-on-surface font-semibold bg-slate-50 dark:bg-slate-900' : 'border-transparent text-slate-500 dark:text-slate-400 font-medium hover:text-on-surface hover:bg-slate-50 dark:hover:bg-slate-900/50'; ?> transition-colors duration-200" href="#">
            <span class="material-symbols-outlined mr-3 <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'text-[#EE4D2D]' : ''; ?>">dashboard</span>
            <span class="font-manrope font-medium text-sm tracking-wide">Dashboard</span>
        </a>

        <!-- Application Item -->
        <a href="partner.php" class="flex items-center px-6 py-3 border-l-4 <?php echo basename($_SERVER['PHP_SELF']) === 'partner.php' ? 'border-[#EE4D2D] text-on-surface font-semibold bg-slate-50 dark:bg-slate-900' : 'border-transparent text-slate-500 dark:text-slate-400 font-medium hover:text-on-surface hover:bg-slate-50 dark:hover:bg-slate-900/50'; ?> transition-all duration-300 ease-in-out">
            <span class="material-symbols-outlined mr-3 <?php echo basename($_SERVER['PHP_SELF']) === 'partner.php' ? 'text-[#EE4D2D]' : ''; ?>">apps</span>
            <span class="font-manrope font-medium text-sm tracking-wide">Application</span>
        </a>
        
        <!-- Product Item (Active for item.php & new-item.php) -->
        <?php $is_product_active = in_array(basename($_SERVER['PHP_SELF']), ['item.php', 'new-item.php']); ?>
        <a href="item.php" class="flex items-center px-6 py-3 border-l-4 <?php echo $is_product_active ? 'border-[#EE4D2D] text-on-surface font-semibold bg-slate-50 dark:bg-slate-900' : 'border-transparent text-slate-500 dark:text-slate-400 font-medium hover:text-on-surface hover:bg-slate-50 dark:hover:bg-slate-900/50'; ?> transition-all duration-300 ease-in-out">
            <span class="material-symbols-outlined mr-3 <?php echo $is_product_active ? 'text-[#EE4D2D]' : ''; ?>">inventory_2</span>
            <span class="font-manrope font-medium text-sm tracking-wide">Product</span>
        </a>
    </nav>
    
    <div class="mt-auto border-t border-zinc-100/15 pt-4">
        <a href="#" class="flex items-center px-6 py-3 text-zinc-500 hover:text-[#EE4D2D] hover:bg-zinc-50 transition-all">
            <span class="material-symbols-outlined mr-3">help</span>
            <span class="font-manrope font-medium text-sm tracking-wide">Support</span>
        </a>
        <a href="#" class="flex items-center px-6 py-3 text-zinc-500 hover:text-[#EE4D2D] hover:bg-zinc-50 transition-all">
            <span class="material-symbols-outlined mr-3">logout</span>
            <span class="font-manrope font-medium text-sm tracking-wide">Logout</span>
        </a>
    </div>
</aside>