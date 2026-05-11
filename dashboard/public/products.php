<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../src/ProductService.php';

use TokStock\AuthService;
use TokStock\ProductService;

$pageTitle   = 'Products';
$currentPage = 'products';
$isSysAdmin  = AuthService::isSystemAdmin();

$error    = null;
$products = [];

try {
    $productService = new ProductService($companyId);
    $products       = $productService->getAllProducts();
} catch (\Exception $e) {
    $error = 'Could not load products: ' . $e->getMessage();
}

// Flash messages
$msg = $_GET['msg'] ?? '';
$flashMessages = [
    'created'        => ['bg-green-50 border-green-200 text-green-700', 'Product added successfully.'],
    'updated'        => ['bg-blue-50 border-blue-200 text-blue-700',   'Product updated successfully.'],
    'deleted'        => ['bg-yellow-50 border-yellow-200 text-yellow-700', 'Product deleted.'],
    'import_success' => ['bg-green-50 border-green-200 text-green-700',
                         'Import complete. Imported: ' . (int)($_GET['success'] ?? 0)
                         . ', Skipped: ' . (int)($_GET['skipped'] ?? 0)],
    'import_error'   => ['bg-red-50 border-red-200 text-red-700',
                         'Import error: ' . htmlspecialchars($_GET['detail'] ?? '')],
];
?>
<?php require_once __DIR__ . '/../src/layout/head.php'; ?>
<?php require_once __DIR__ . '/../src/layout/sidebar.php'; ?>
<?php require_once __DIR__ . '/../src/layout/topbar.php'; ?>

<!-- Actions bar -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div class="relative">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input id="searchInput" type="text" placeholder="Search by SKU, name, or category…"
               class="pl-9 pr-4 py-2 border border-gray-300 rounded-lg text-sm w-64 focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent">
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <!-- CSV Import -->
        <form action="import_csv.php" method="POST" enctype="multipart/form-data"
              class="flex items-center gap-2 bg-white border border-gray-200 rounded-lg px-3 py-1.5 shadow-sm">
            <input type="file" name="csv_file" accept=".csv" required
                   class="text-xs text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0
                          file:text-xs file:font-medium file:bg-brand-light file:text-brand">
            <button type="submit" class="bg-brand hover:bg-brand-mid text-white text-xs font-medium py-1.5 px-3 rounded-md transition-colors shrink-0">
                Import CSV
            </button>
        </form>

        <a href="export_csv.php"
           class="bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 text-xs font-medium py-2 px-4 rounded-lg shadow-sm transition-colors">
            Export CSV
        </a>

        <a href="add_product.php"
           class="bg-brand hover:bg-brand-mid text-white text-sm font-medium py-2 px-4 rounded-lg shadow-sm transition-colors flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Product
        </a>
    </div>
</div>

<!-- Flash messages -->
<?php if ($msg && isset($flashMessages[$msg])): ?>
<div class="border px-4 py-3 rounded-lg mb-5 text-sm <?= $flashMessages[$msg][0] ?>">
    <?= $flashMessages[$msg][1] ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm mb-5">
    <strong>Error:</strong> <?= htmlspecialchars($error) ?>
</div>
<?php else: ?>

<!-- Table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm" id="productsTable">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">SKU</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Name</th>
                    <?php if ($isSysAdmin): ?>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Company</th>
                    <?php endif; ?>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Category</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Supplier</th>
                    <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Stock</th>
                    <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Sale Price</th>
                    <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($products)): ?>
                <tr>
                    <td colspan="<?= $isSysAdmin ? 8 : 7 ?>" class="text-center py-12 text-gray-400">
                        <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        No products found. <a href="add_product.php" class="text-brand hover:underline">Add the first one.</a>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($products as $p): ?>
                <?php
                    $isLow = $p['stock_quantity'] <= $p['min_stock_level'];
                ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-5 py-3 font-mono text-xs text-gray-500"><?= htmlspecialchars($p['sku']) ?></td>
                    <td class="px-5 py-3 font-medium text-gray-800"><?= htmlspecialchars($p['name']) ?></td>
                    <?php if ($isSysAdmin): ?>
                    <td class="px-5 py-3">
                        <span class="bg-brand-light text-brand text-xs font-medium px-2 py-0.5 rounded-full">
                            <?= htmlspecialchars($p['company_name'] ?? '—') ?>
                        </span>
                    </td>
                    <?php endif; ?>
                    <td class="px-5 py-3 text-gray-500"><?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
                    <td class="px-5 py-3 text-gray-500"><?= htmlspecialchars($p['supplier'] ?? '—') ?></td>
                    <td class="px-5 py-3 text-right">
                        <?php if ($isLow): ?>
                        <span class="inline-flex items-center gap-1 bg-red-100 text-red-700 text-xs font-semibold px-2 py-0.5 rounded-full">
                            <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                            <?= $p['stock_quantity'] ?>
                        </span>
                        <?php else: ?>
                        <span class="text-gray-800 font-medium"><?= $p['stock_quantity'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="px-5 py-3 text-right text-gray-800">
                        <?= number_format($p['price_sale'], 2) ?> ₺
                    </td>
                    <td class="px-5 py-3 text-center">
                        <div class="flex items-center justify-center gap-3">
                            <a href="edit_product.php?id=<?= $p['id'] ?>"
                               class="text-brand hover:text-brand-mid text-xs font-medium transition-colors">Edit</a>
                            <a href="process_product.php?action=delete&id=<?= $p['id'] ?>"
                               onclick="return confirm('Delete <?= htmlspecialchars(addslashes($p['name'])) ?>?')"
                               class="text-red-500 hover:text-red-700 text-xs font-medium transition-colors">Delete</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (!empty($products)): ?>
    <div class="px-5 py-3 border-t border-gray-100 text-xs text-gray-400">
        <?= count($products) ?> product<?= count($products) !== 1 ? 's' : '' ?> total
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>

<script>
document.getElementById('searchInput').addEventListener('input', function () {
    const filter = this.value.toLowerCase();
    const rows   = document.querySelectorAll('#productsTable tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});
</script>

<?php require_once __DIR__ . '/../src/layout/footer.php'; ?>
