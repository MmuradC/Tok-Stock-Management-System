<?php
require_once __DIR__ . '/auth_check.php';

use TokStock\Database;

$pageTitle   = 'Stock Movements';
$currentPage = 'stock';

$db       = Database::getConnection();
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 25;
$offset   = ($page - 1) * $perPage;

$filterType    = $_GET['type']    ?? '';
$filterProduct = trim($_GET['product'] ?? '');

// Build WHERE clause
$where  = $companyId ? ['sl.company_id = :cid'] : [];
$params = $companyId ? ['cid' => $companyId] : [];

if ($filterType && in_array($filterType, ['IN', 'OUT', 'ADJUSTMENT'], true)) {
    $where[] = 'sl.action_type = :type';
    $params['type'] = $filterType;
}
if ($filterProduct !== '') {
    $where[] = '(p.name LIKE :search OR p.sku LIKE :search)';
    $params['search'] = '%' . $filterProduct . '%';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    // Count
    $countStmt = $db->prepare(
        "SELECT COUNT(*) FROM stock_logs sl
         JOIN products p ON sl.product_id = p.id $whereSql"
    );
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $pages = (int)ceil($total / $perPage);

    // Data
    $dataStmt = $db->prepare(
        "SELECT sl.id, sl.action_type, sl.change_amount, sl.notes, sl.created_at,
                p.name AS product_name, p.sku,
                u.name AS user_name
         FROM stock_logs sl
         JOIN products p ON sl.product_id = p.id
         LEFT JOIN users u ON sl.user_id = u.id
         $whereSql
         ORDER BY sl.created_at DESC
         LIMIT :limit OFFSET :offset"
    );
    foreach ($params as $k => $v) {
        $dataStmt->bindValue(':' . $k, $v);
    }
    $dataStmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
    $dataStmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
    $dataStmt->execute();
    $logs = $dataStmt->fetchAll();

} catch (\Exception $e) {
    $logs  = [];
    $total = 0;
    $pages = 0;
    $dbError = $e->getMessage();
}

function buildPageUrl(int $p): string {
    $params = $_GET;
    $params['page'] = $p;
    return '?' . http_build_query($params);
}
?>
<?php require_once __DIR__ . '/../src/layout/head.php'; ?>
<?php require_once __DIR__ . '/../src/layout/sidebar.php'; ?>
<?php require_once __DIR__ . '/../src/layout/topbar.php'; ?>

<?php if (isset($dbError)): ?>
<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 text-sm">
    <strong>Error:</strong> <?= htmlspecialchars($dbError) ?>
</div>
<?php endif; ?>

<!-- Filters -->
<form method="GET" class="flex flex-wrap items-center gap-3 mb-6">
    <div class="relative">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input name="product" type="text" placeholder="Search product…"
               value="<?= htmlspecialchars($filterProduct) ?>"
               class="pl-9 pr-4 py-2 border border-gray-300 rounded-lg text-sm w-52 focus:outline-none focus:ring-2 focus:ring-brand">
    </div>

    <select name="type"
            class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-brand">
        <option value="">All Types</option>
        <?php foreach (['IN', 'OUT', 'ADJUSTMENT'] as $t): ?>
        <option value="<?= $t ?>" <?= $filterType === $t ? 'selected' : '' ?>><?= $t ?></option>
        <?php endforeach; ?>
    </select>

    <button type="submit" class="bg-brand hover:bg-brand-mid text-white text-sm font-medium py-2 px-4 rounded-lg transition-colors">
        Filter
    </button>
    <?php if ($filterType || $filterProduct): ?>
    <a href="stock_movements.php" class="text-sm text-gray-500 hover:text-brand">Clear</a>
    <?php endif; ?>

    <span class="text-xs text-gray-400 ml-auto"><?= $total ?> record<?= $total !== 1 ? 's' : '' ?></span>
</form>

<!-- Table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Product</th>
                    <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Type</th>
                    <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Change</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">By</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Notes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="6" class="text-center py-12 text-gray-400">No stock movements found.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($logs as $log): ?>
                <?php
                    $badge = match($log['action_type']) {
                        'IN'         => 'bg-green-100 text-green-700',
                        'OUT'        => 'bg-red-100 text-red-700',
                        'ADJUSTMENT' => 'bg-yellow-100 text-yellow-700',
                        default      => 'bg-gray-100 text-gray-600',
                    };
                    $sign  = $log['action_type'] === 'IN' ? '+' : ($log['action_type'] === 'OUT' ? '−' : '±');
                    $color = $log['action_type'] === 'IN' ? 'text-green-600' : ($log['action_type'] === 'OUT' ? 'text-red-600' : 'text-yellow-600');
                ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-5 py-3 text-gray-500 whitespace-nowrap">
                        <?= date('d M Y, H:i', strtotime($log['created_at'])) ?>
                    </td>
                    <td class="px-5 py-3">
                        <p class="font-medium text-gray-800"><?= htmlspecialchars($log['product_name']) ?></p>
                        <p class="text-xs text-gray-400 font-mono"><?= htmlspecialchars($log['sku']) ?></p>
                    </td>
                    <td class="px-5 py-3 text-center">
                        <span class="text-xs font-medium px-2.5 py-0.5 rounded-full <?= $badge ?>">
                            <?= $log['action_type'] ?>
                        </span>
                    </td>
                    <td class="px-5 py-3 text-right font-bold <?= $color ?>">
                        <?= $sign ?><?= abs($log['change_amount']) ?>
                    </td>
                    <td class="px-5 py-3 text-gray-500"><?= htmlspecialchars($log['user_name'] ?? 'System') ?></td>
                    <td class="px-5 py-3 text-gray-400 text-xs max-w-xs truncate">
                        <?= htmlspecialchars($log['notes'] ?? '—') ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($pages > 1): ?>
<div class="flex items-center justify-center gap-2">
    <?php if ($page > 1): ?>
    <a href="<?= buildPageUrl($page - 1) ?>"
       class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition-colors">
        &larr; Prev
    </a>
    <?php endif; ?>

    <?php
    $start = max(1, $page - 2);
    $end   = min($pages, $page + 2);
    for ($i = $start; $i <= $end; $i++):
    ?>
    <a href="<?= buildPageUrl($i) ?>"
       class="px-3 py-1.5 rounded-lg text-sm transition-colors
              <?= $i === $page ? 'bg-brand text-white' : 'border border-gray-300 hover:bg-gray-50' ?>">
        <?= $i ?>
    </a>
    <?php endfor; ?>

    <?php if ($page < $pages): ?>
    <a href="<?= buildPageUrl($page + 1) ?>"
       class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition-colors">
        Next &rarr;
    </a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../src/layout/footer.php'; ?>
