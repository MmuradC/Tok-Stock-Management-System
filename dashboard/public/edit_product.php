<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../src/ProductService.php';

use TokStock\ProductService;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: products.php');
    exit;
}

$productService = new ProductService($companyId);
$product        = $productService->getProductById($id);

if (!$product) {
    header('Location: products.php?msg=not_found');
    exit;
}

$categories  = $productService->getCategories();
$pageTitle   = 'Edit Product';
$currentPage = 'products';
?>
<?php require_once __DIR__ . '/../src/layout/head.php'; ?>
<?php require_once __DIR__ . '/../src/layout/sidebar.php'; ?>
<?php require_once __DIR__ . '/../src/layout/topbar.php'; ?>

<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 px-7 py-7">

        <div class="flex items-center gap-3 mb-6">
            <a href="products.php" class="text-gray-400 hover:text-brand transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h2 class="text-lg font-semibold text-gray-800">Edit Product</h2>
                <p class="text-xs text-gray-400 font-mono"><?= htmlspecialchars($product['sku']) ?></p>
            </div>
        </div>

        <form action="process_product.php" method="POST" novalidate>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= $product['id'] ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                    <input type="text" value="<?= htmlspecialchars($product['sku']) ?>" disabled
                           class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-gray-50 text-gray-400 cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category_id"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand bg-white">
                        <option value="">— Select category —</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"
                            <?= $cat['id'] == $product['category_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Product Name <span class="text-red-500">*</span>
                </label>
                <input name="name" type="text" required value="<?= htmlspecialchars($product['name']) ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
                <input name="supplier" type="text" value="<?= htmlspecialchars($product['supplier'] ?? '') ?>"
                       placeholder="e.g. Acme Corp"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Stock</label>
                    <input name="stock_quantity" type="number" min="0"
                           value="<?= htmlspecialchars($product['stock_quantity']) ?>"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Min. Stock Level</label>
                    <input name="min_stock_level" type="number" min="0"
                           value="<?= htmlspecialchars($product['min_stock_level']) ?>"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Purchase Price (₺)</label>
                    <input name="price_purchase" type="number" step="0.01" min="0"
                           value="<?= htmlspecialchars($product['price_purchase']) ?>"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sale Price (₺)</label>
                    <input name="price_sale" type="number" step="0.01" min="0"
                           value="<?= htmlspecialchars($product['price_sale']) ?>"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Adjustment Notes <span class="text-gray-400 font-normal text-xs">(if stock quantity changed)</span>
                </label>
                <input name="notes" type="text" placeholder="Reason for stock change…"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
            </div>

            <div class="flex gap-3">
                <button type="submit"
                        class="bg-brand hover:bg-brand-mid text-white font-semibold py-2.5 px-6 rounded-lg text-sm transition-colors shadow-sm">
                    Save Changes
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
