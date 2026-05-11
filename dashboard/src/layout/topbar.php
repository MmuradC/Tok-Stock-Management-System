<!-- Main content wrapper (offset by sidebar width) -->
<div class="flex-1 ml-64 flex flex-col min-h-screen">

<!-- Top bar -->
<header class="sticky top-0 z-10 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between shadow-sm">
    <h1 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h1>
    <div class="flex items-center gap-3 text-sm text-gray-500">
        <?php if (($currentUser['role'] ?? '') === 'system_admin'): ?>
        <span class="bg-yellow-100 text-yellow-700 px-2.5 py-1 rounded-full text-xs font-medium">System Admin</span>
        <?php elseif (!empty($_SESSION['company_id'])): ?>
        <span class="bg-brand-light text-brand px-2.5 py-1 rounded-full text-xs font-medium">
            <?= htmlspecialchars($companyName ?? 'Company') ?>
        </span>
        <?php else: ?>
        <span class="bg-gray-100 text-gray-500 px-2.5 py-1 rounded-full text-xs font-medium">
            <?= ($currentUser['role'] ?? '') === 'company_admin' ? 'Company Admin' : 'Staff' ?>
        </span>
        <?php endif; ?>
        <span><?= htmlspecialchars($currentUser['name'] ?? '') ?></span>
    </div>
</header>

<!-- Page content -->
<main class="flex-1 p-6">
