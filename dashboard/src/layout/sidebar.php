<?php
// $currentPage is set by each page to highlight the active nav link
$currentPage = $currentPage ?? '';
$isAdmin     = TokStock\AuthService::isAdmin();
$isSysAdmin  = TokStock\AuthService::isSystemAdmin();
$user        = TokStock\AuthService::currentUser();

$roleLabel = match($user['role']) {
    'system_admin'  => 'System Admin',
    'company_admin' => 'Company Admin',
    default         => 'Staff',
};

function sidebarLink(string $href, string $label, string $icon, string $current, string $page): string {
    $active = $current === $page ? 'bg-white/20 text-white font-semibold' : 'text-white/75 hover:bg-white/10 hover:text-white';
    return "<a href=\"{$href}\" class=\"flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm transition-colors {$active}\">
                <span class=\"w-5 h-5 shrink-0\">{$icon}</span>
                {$label}
            </a>";
}

$icons = [
    'dashboard'  => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>',
    'products'   => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>',
    'stock'      => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>',
    'orders'     => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>',
    'users'      => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>',
    'categories' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>',
    'companies'  => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>',
];
?>
<!-- Sidebar -->
<aside class="w-64 bg-brand-dark flex flex-col fixed inset-y-0 left-0 z-10 shadow-xl">

    <!-- Logo -->
    <div class="px-6 py-5 border-b border-white/10">
        <a href="index.php" class="flex items-center gap-3">
            <div class="w-9 h-9 bg-brand-accent rounded-lg flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-brand-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <div>
                <p class="text-white font-bold text-base leading-none">Tok-Stock</p>
                <p class="text-white/50 text-xs mt-0.5">Management System</p>
            </div>
        </a>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        <p class="text-white/30 text-xs uppercase tracking-widest px-4 mb-2">Main</p>

        <?= sidebarLink('index.php', 'Dashboard', $icons['dashboard'], $currentPage, 'dashboard') ?>
        <?= sidebarLink('products.php', 'Products', $icons['products'], $currentPage, 'products') ?>
        <?= sidebarLink('stock_movements.php', 'Stock Movements', $icons['stock'], $currentPage, 'stock') ?>
        <?= sidebarLink('orders.php', 'Orders', $icons['orders'], $currentPage, 'orders') ?>

        <?php if ($isAdmin): ?>
        <div class="pt-3">
            <p class="text-white/30 text-xs uppercase tracking-widest px-4 mb-2">Management</p>
            <?= sidebarLink('users.php', 'Users & Roles', $icons['users'], $currentPage, 'users') ?>
            <?= sidebarLink('categories.php', 'Categories', $icons['categories'], $currentPage, 'categories') ?>
            <?php if ($isSysAdmin): ?>
            <?= sidebarLink('companies.php', 'Companies', $icons['companies'], $currentPage, 'companies') ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </nav>

    <!-- User info at bottom -->
    <div class="px-4 py-4 border-t border-white/10">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-brand-accent flex items-center justify-center shrink-0">
                <span class="text-brand-dark font-bold text-sm">
                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                </span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-white text-sm font-medium truncate"><?= htmlspecialchars($user['name']) ?></p>
                <p class="text-white/50 text-xs"><?= $roleLabel ?></p>
            </div>
        </div>
        <a href="logout.php"
           class="mt-3 flex items-center gap-2 text-white/60 hover:text-white text-xs transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            Sign out
        </a>
    </div>
</aside>
