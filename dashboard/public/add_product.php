<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../src/ProductService.php';
require_once __DIR__ . '/../src/UserService.php';

use TokStock\AuthService;
use TokStock\ProductService;
use TokStock\UserService;

$pageTitle   = 'Add Product';
$currentPage = 'products';

$isSysAdmin = AuthService::isSystemAdmin();
$userSvc    = new UserService();

// System admin picks a company via GET param; company users always use their own
$selectedCompanyId = $companyId;
$companies         = [];
if ($isSysAdmin) {
    $companies         = $userSvc->getAllCompanies();
    $selectedCompanyId = !empty($_GET['company_id']) ? (int)$_GET['company_id'] : null;
}

if (!$isSysAdmin && !$selectedCompanyId) {
    // Non-sysadmin with no company assigned — block and redirect
    header('Location: products.php?error=no_company');
    exit;
}

$productService = new ProductService($selectedCompanyId);
$categories     = $selectedCompanyId ? $productService->getCategories() : [];
?>
<?php require_once __DIR__ . '/../src/layout/head.php'; ?>
<?php require_once __DIR__ . '/../src/layout/sidebar.php'; ?>
<?php require_once __DIR__ . '/../src/layout/topbar.php'; ?>

<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 px-7 py-7">

        <?php if (!empty($_GET['error'])): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg mb-5">
            <?= htmlspecialchars($_GET['error']) ?>
        </div>
        <?php endif; ?>

        <div class="flex items-center gap-3 mb-6">
            <a href="products.php" class="text-gray-400 hover:text-brand transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h2 class="text-lg font-semibold text-gray-800">New Product</h2>
        </div>

        <?php if ($isSysAdmin && !empty($companies)): ?>
        <div class="mb-5">
            <label class="block text-sm font-medium text-gray-700 mb-1">Company <span class="text-red-500">*</span></label>
            <select onchange="location.href='add_product.php?company_id='+this.value"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand bg-white">
                <option value="">— Select company first —</option>
                <?php foreach ($companies as $co): ?>
                <option value="<?= $co['id'] ?>" <?= $selectedCompanyId === (int)$co['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($co['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if (!$selectedCompanyId): ?>
        <p class="text-sm text-gray-400 text-center py-4">Select a company above to continue.</p>
        </div></div>
        <?php require_once __DIR__ . '/../src/layout/footer.php'; ?>
        <?php exit; ?>
        <?php endif; ?>
        <?php endif; ?>

        <form action="process_product.php" method="POST" novalidate>
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="company_id" value="<?= $selectedCompanyId ?? $companyId ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        SKU <span class="text-red-500">*</span>
                    </label>
                    <input name="sku" type="text" required placeholder="e.g. ELK-001"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category_id"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand bg-white">
                        <option value="">— Select category —</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Product Name <span class="text-red-500">*</span>
                </label>
                <input name="name" type="text" required placeholder="e.g. Wireless Mouse"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
                <input name="supplier" type="text" placeholder="e.g. Acme Corp"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Initial Stock</label>
                    <input name="stock_quantity" type="number" min="0" value="0"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Min. Stock Level</label>
                    <input name="min_stock_level" type="number" min="0" value="5"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Purchase Price (₺)</label>
                    <input name="price_purchase" type="number" step="0.01" min="0" value="0.00"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sale Price (₺)</label>
                    <input name="price_sale" type="number" step="0.01" min="0" value="0.00"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit"
                        class="bg-brand hover:bg-brand-mid text-white font-semibold py-2.5 px-6 rounded-lg text-sm transition-colors shadow-sm">
                    Save Product
                </button>
                <a href="products.php"
                   class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2.5 px-6 rounded-lg text-sm transition-colors">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../src/layout/footer.php'; ?>
