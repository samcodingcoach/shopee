<aside class="admin-sidebar">
    <div class="sidebar-header">
        <a href="#" class="sidebar-logo">
            <div class="sidebar-logo-icon">S</div>
            <span class="sidebar-logo-text">Shopee Admin</span>
        </a>
    </div>
    
    <ul class="sidebar-menu">
        <li class="sidebar-menu-item">
            <a href="item.php" class="sidebar-menu-link <?php echo basename($_SERVER['PHP_SELF']) === 'item.php' ? 'active' : ''; ?>">
                <span class="sidebar-menu-icon">📦</span>
                <span>Produk</span>
            </a>
        </li>

        <li class="sidebar-menu-item">
            <a href="partner.php" class="sidebar-menu-link <?php echo basename($_SERVER['PHP_SELF']) === 'partner.php' ? 'active' : ''; ?>">
                <span class="sidebar-menu-icon">📱</span>
                <span>Application</span>
            </a>
        </li>

    </ul>
</aside>
