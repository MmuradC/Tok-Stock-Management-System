<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../src/ProductService.php';

use TokStock\Database;

$pageTitle   = 'Dashboard';
$currentPage = 'dashboard';

// ── Summary stats ──────────────────────────────────────────
$db          = Database::getConnection();
$companyFilter = $companyId ? 'WHERE company_id = :cid' : '';
$params        = $companyId ? ['cid' => $companyId] : [];

try {
    // Total products
    $stmt = $db->prepare("SELECT COUNT(*) FROM products $companyFilter");
    $stmt->execute($params);
    $totalProducts = (int)$stmt->fetchColumn();

    // Low stock (stock_quantity <= min_stock_level)
    $lowStockSql = $companyId
        ? "SELECT COUNT(*) FROM products WHERE company_id = :cid AND stock_quantity <= min_stock_level"
        : "SELECT COUNT(*) FROM products WHERE stock_quantity <= min_stock_level";
    $stmt = $db->prepare($lowStockSql);
    $stmt->execute($params);
    $lowStock = (int)$stmt->fetchColumn();

    // Today's orders
    $todayOrdersSql = $companyId
        ? "SELECT COUNT(*) FROM orders WHERE company_id = :cid AND DATE(created_at) = CURDATE()"
        : "SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()";
    $stmt = $db->prepare($todayOrdersSql);
    $stmt->execute($params);
    $todayOrders = (int)$stmt->fetchColumn();

    // Pending orders
    $pendingOrdersSql = $companyId
        ? "SELECT COUNT(*) FROM orders WHERE company_id = :cid AND status = 'pending'"
        : "SELECT COUNT(*) FROM orders WHERE status = 'pending'";
    $stmt = $db->prepare($pendingOrdersSql);
    $stmt->execute($params);
    $pendingOrders = (int)$stmt->fetchColumn();

    // Recent stock movements (last 8)
    $recentSql = $companyId
        ? "SELECT sl.action_type, sl.change_amount, sl.created_at, sl.notes,
                  p.name AS product_name, p.sku,
                  u.name AS user_name
           FROM stock_logs sl
           JOIN products p ON sl.product_id = p.id
           LEFT JOIN users u ON sl.user_id = u.id
           WHERE sl.company_id = :cid
           ORDER BY sl.created_at DESC LIMIT 8"
        : "SELECT sl.action_type, sl.change_amount, sl.created_at, sl.notes,
                  p.name AS product_name, p.sku,
                  u.name AS user_name
           FROM stock_logs sl
           JOIN products p ON sl.product_id = p.id
           LEFT JOIN users u ON sl.user_id = u.id
           ORDER BY sl.created_at DESC LIMIT 8";
    $stmt = $db->prepare($recentSql);
    $stmt->execute($params);
    $recentMovements = $stmt->fetchAll();

    // Low stock items list (up to 5)
    $lowItemsSql = $companyId
        ? "SELECT sku, name, stock_quantity, min_stock_level FROM products
           WHERE company_id = :cid AND stock_quantity <= min_stock_level
           ORDER BY stock_quantity ASC LIMIT 5"
        : "SELECT sku, name, stock_quantity, min_stock_level FROM products
           WHERE stock_quantity <= min_stock_level
           ORDER BY stock_quantity ASC LIMIT 5";
    $stmt = $db->prepare($lowItemsSql);
    $stmt->execute($params);
    $lowStockItems = $stmt->fetchAll();

} catch (\Exception $e) {
    $totalProducts   = $lowStock = $todayOrders = $pendingOrders = 0;
    $recentMovements = $lowStockItems = [];
    $dbError = $e->getMessage();
}
?>
<?php require_once __DIR__ . '/../src/layout/head.php'; ?>
<?php require_once __DIR__ . '/../src/layout/sidebar.php'; ?>
<?php require_once __DIR__ . '/../src/layout/topbar.php'; ?>

<?php if (isset($dbError)): ?>
<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 text-sm">
    <strong>Database error:</strong> <?= htmlspecialchars($dbError) ?>
</div>
<?php endif; ?>

<!-- Summary cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">

    <!-- Total Products -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
        <div class="w-12 h-12 bg-brand-light rounded-xl flex items-center justify-center shrink-0">
            <svg class="w-6 h-6 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-800"><?= $totalProducts ?></p>
            <p class="text-sm text-gray-500">Total Products</p>
        </div>
    </div>

    <!-- Low Stock -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
        <div class="w-12 h-12 <?= $lowStock > 0 ? 'bg-red-100' : 'bg-green-100' ?> rounded-xl flex items-center justify-center shrink-0">
            <svg class="w-6 h-6 <?= $lowStock > 0 ? 'text-red-500' : 'text-green-500' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <div>
            <p class="text-2xl font-bold <?= $lowStock > 0 ? 'text-red-600' : 'text-gray-800' ?>"><?= $lowStock ?></p>
            <p class="text-sm text-gray-500">Low Stock Alerts</p>
        </div>
    </div>

    <!-- Today's Orders -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center shrink-0">
            <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-800"><?= $todayOrders ?></p>
            <p class="text-sm text-gray-500">Today's Orders</p>
        </div>
    </div>

    <!-- Pending Orders -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
        <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center shrink-0">
            <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-800"><?= $pendingOrders ?></p>
            <p class="text-sm text-gray-500">Pending Orders</p>
        </div>
    </div>
</div>

<!-- Bottom panels -->
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

    <!-- Recent Stock Movements -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800 text-sm">Recent Stock Movements</h2>
            <a href="stock_movements.php" class="text-xs text-brand hover:underline">View all</a>
        </div>
        <div class="divide-y divide-gray-50">
            <?php if (empty($recentMovements)): ?>
            <p class="text-center text-gray-400 text-sm py-8">No stock movements yet.</p>
            <?php else: ?>
            <?php foreach ($recentMovements as $mov): ?>
            <?php
                $badgeClass = match($mov['action_type']) {
                    'IN'         => 'bg-green-100 text-green-700',
                    'OUT'        => 'bg-red-100 text-red-700',
                    'ADJUSTMENT' => 'bg-yellow-100 text-yellow-700',
                    default      => 'bg-gray-100 text-gray-600',
                };
                $sign = $mov['action_type'] === 'IN' ? '+' : ($mov['action_type'] === 'OUT' ? '-' : '±');
            ?>
            <div class="flex items-center justify-between px-5 py-3">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate"><?= htmlspecialchars($mov['product_name']) ?></p>
                    <p class="text-xs text-gray-400"><?= htmlspecialchars($mov['sku']) ?> &bull; <?= htmlspecialchars($mov['user_name'] ?? 'System') ?></p>
                </div>
                <div class="flex items-center gap-3 shrink-0 ml-3">
                    <span class="text-sm font-semibold <?= $mov['action_type'] === 'IN' ? 'text-green-600' : ($mov['action_type'] === 'OUT' ? 'text-red-600' : 'text-yellow-600') ?>">
                        <?= $sign ?><?= abs($mov['change_amount']) ?>
                    </span>
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium <?= $badgeClass ?>">
                        <?= $mov['action_type'] ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Low Stock Alerts -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800 text-sm">Low Stock Alerts</h2>
            <a href="products.php" class="text-xs text-brand hover:underline">View products</a>
        </div>
        <div class="divide-y divide-gray-50">
            <?php if (empty($lowStockItems)): ?>
            <div class="text-center py-8">
                <svg class="w-8 h-8 text-green-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-gray-400 text-sm">All products are well-stocked.</p>
            </div>
            <?php else: ?>
            <?php foreach ($lowStockItems as $item): ?>
            <div class="flex items-center justify-between px-5 py-3">
                <div>
                    <p class="text-sm font-medium text-gray-800"><?= htmlspecialchars($item['name']) ?></p>
                    <p class="text-xs text-gray-400"><?= htmlspecialchars($item['sku']) ?></p>
                </div>
                <div class="text-right shrink-0 ml-3">
                    <p class="text-sm font-bold text-red-600"><?= $item['stock_quantity'] ?> left</p>
                    <p class="text-xs text-gray-400">Min: <?= $item['min_stock_level'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../src/layout/footer.php'; ?>
