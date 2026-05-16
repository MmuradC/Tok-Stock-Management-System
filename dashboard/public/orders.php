<?php
require_once __DIR__ . '/auth_check.php';

use TokStock\AuthService;
use TokStock\Database;

$pageTitle   = 'Orders';
$currentPage = 'orders';
$isSysAdmin  = AuthService::isSystemAdmin();

$db     = Database::getConnection();
$error  = null;
$orders = [];

$statusFilter = $_GET['status'] ?? 'all';
$validStatuses = ['all', 'pending', 'completed', 'cancelled'];
if (!in_array($statusFilter, $validStatuses)) $statusFilter = 'all';

// Flash messages
$msg = $_GET['msg'] ?? '';
$flashMessages = [
    'created'  => ['bg-green-50 border-green-200 text-green-700',  'Order created successfully.'],
    'updated'  => ['bg-blue-50 border-blue-200 text-blue-700',     'Order status updated.'],
    'deleted'  => ['bg-yellow-50 border-yellow-200 text-yellow-700', 'Order deleted.'],
];

try {
    if ($isSysAdmin) {
        $sql = "SELECT o.*, u.name AS created_by_name, c.name AS company_name
                FROM orders o
                LEFT JOIN users u ON o.created_by = u.id
                LEFT JOIN companies c ON o.company_id = c.id"
             . ($statusFilter !== 'all' ? " WHERE o.status = :status" : "")
             . " ORDER BY o.created_at DESC";
    } else {
        $sql = "SELECT o.*, u.name AS created_by_name
                FROM orders o
                LEFT JOIN users u ON o.created_by = u.id
                WHERE o.company_id = :cid"
             . ($statusFilter !== 'all' ? " AND o.status = :status" : "")
             . " ORDER BY o.created_at DESC";
    }

    $stmt = $db->prepare($sql);
    if (!$isSysAdmin) $stmt->bindValue(':cid', $companyId, PDO::PARAM_INT);
    if ($statusFilter !== 'all') $stmt->bindValue(':status', $statusFilter);
    $stmt->execute();
    $orders = $stmt->fetchAll();

    // Load products for new order form
    $prodSql = $companyId
        ? "SELECT id, sku, name, price_sale FROM products WHERE company_id = :cid ORDER BY name"
        : "SELECT id, sku, name, price_sale FROM products ORDER BY name";
    $pStmt = $db->prepare($prodSql);
    if ($companyId) $pStmt->bindValue(':cid', $companyId, PDO::PARAM_INT);
    $pStmt->execute();
    $availableProducts = $pStmt->fetchAll();

} catch (\Exception $e) {
    $error = $e->getMessage();
}
?>
<?php require_once __DIR__ . '/../src/layout/head.php'; ?>
<?php require_once __DIR__ . '/../src/layout/sidebar.php'; ?>
<?php require_once __DIR__ . '/../src/layout/topbar.php'; ?>

<!-- Flash -->
<?php if ($msg && isset($flashMessages[$msg])): ?>
<div class="border px-4 py-3 rounded-lg mb-5 text-sm <?= $flashMessages[$msg][0] ?>">
    <?= $flashMessages[$msg][1] ?>
</div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-5 text-sm">
    <?= htmlspecialchars($_GET['error']) ?>
</div>
<?php endif; ?>

<!-- Actions bar -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <!-- Status filter tabs -->
    <div class="flex items-center gap-1 bg-white border border-gray-200 rounded-lg p-1 shadow-sm">
        <?php foreach (['all' => 'All', 'pending' => 'Pending', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $val => $label): ?>
        <a href="orders.php?status=<?= $val ?>"
           class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors
                  <?= $statusFilter === $val ? 'bg-brand text-white shadow-sm' : 'text-gray-500 hover:text-gray-800' ?>">
            <?= $label ?>
        </a>
        <?php endforeach; ?>
    </div>

    <button onclick="document.getElementById('newOrderForm').classList.toggle('hidden')"
            class="bg-brand hover:bg-brand-mid text-white text-sm font-medium py-2 px-4 rounded-lg shadow-sm transition-colors flex items-center gap-1.5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        New Order
    </button>
</div>

<!-- New Order Form (hidden by default) -->
<div id="newOrderForm" class="hidden bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
    <h2 class="text-sm font-semibold text-gray-800 mb-4">Create New Order</h2>
    <form action="process_order.php" method="POST">
        <input type="hidden" name="action" value="create">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Customer Name <span class="text-red-500">*</span></label>
                <input type="text" name="customer_name" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent"
                       placeholder="e.g. John Smith">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Customer Email</label>
                <input type="email" name="customer_email"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent"
                       placeholder="optional">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Notes</label>
                <textarea name="notes" rows="2"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent"
                          placeholder="Optional notes..."></textarea>
            </div>
        </div>

        <!-- Order Items -->
        <div class="mb-4">
            <div class="flex items-center justify-between mb-2">
                <label class="text-xs font-medium text-gray-600">Order Items</label>
                <button type="button" onclick="addItem()"
                        class="text-xs text-brand hover:underline font-medium">+ Add item</button>
            </div>
            <div id="orderItems" class="space-y-2">
                <div class="item-row flex items-center gap-2">
                    <select name="product_id[]"
                            class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                        <option value="">— Select product —</option>
                        <?php foreach ($availableProducts as $p): ?>
                        <option value="<?= $p['id'] ?>" data-price="<?= $p['price_sale'] ?>">
                            <?= htmlspecialchars($p['sku'] . ' — ' . $p['name']) ?> (<?= number_format($p['price_sale'], 2) ?> ₺)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="quantity[]" min="1" value="1"
                           class="w-24 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand"
                           placeholder="Qty">
                    <button type="button" onclick="this.closest('.item-row').remove()"
                            class="text-red-400 hover:text-red-600 text-lg leading-none px-1">×</button>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit"
                    class="bg-brand hover:bg-brand-mid text-white text-sm font-medium py-2 px-5 rounded-lg transition-colors">
                Create Order
            </button>
            <button type="button" onclick="document.getElementById('newOrderForm').classList.add('hidden')"
                    class="text-sm text-gray-500 hover:text-gray-700">Cancel</button>
        </div>
    </form>
</div>

<?php if ($error): ?>
<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
    <strong>Error:</strong> <?= htmlspecialchars($error) ?>
</div>
<?php else: ?>

<!-- Orders Table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">#</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Customer</th>
                    <?php if ($isSysAdmin): ?>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Company</th>
                    <?php endif; ?>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                    <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Total</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Created by</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
                    <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="<?= $isSysAdmin ? 8 : 7 ?>" class="text-center py-12 text-gray-400">
                        <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        No orders found.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($orders as $o):
                    $statusClass = match($o['status']) {
                        'pending'   => 'bg-yellow-100 text-yellow-700',
                        'completed' => 'bg-green-100 text-green-700',
                        'cancelled' => 'bg-red-100 text-red-600',
                        default     => 'bg-gray-100 text-gray-600',
                    };
                ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-5 py-3 font-mono text-xs text-gray-400">#<?= $o['id'] ?></td>
                    <td class="px-5 py-3">
                        <p class="font-medium text-gray-800"><?= htmlspecialchars($o['customer_name'] ?? '—') ?></p>
                        <?php if (!empty($o['customer_email'])): ?>
                        <p class="text-xs text-gray-400"><?= htmlspecialchars($o['customer_email']) ?></p>
                        <?php endif; ?>
                    </td>
                    <?php if ($isSysAdmin): ?>
                    <td class="px-5 py-3">
                        <span class="bg-brand-light text-brand text-xs font-medium px-2 py-0.5 rounded-full">
                            <?= htmlspecialchars($o['company_name'] ?? '—') ?>
                        </span>
                    </td>
                    <?php endif; ?>
                    <td class="px-5 py-3">
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full <?= $statusClass ?>">
                            <?= ucfirst($o['status']) ?>
                        </span>
                    </td>
                    <td class="px-5 py-3 text-right font-medium text-gray-800">
                        <?= number_format($o['total_amount'], 2) ?> ₺
                    </td>
                    <td class="px-5 py-3 text-gray-500 text-xs"><?= htmlspecialchars($o['created_by_name'] ?? 'System') ?></td>
                    <td class="px-5 py-3 text-gray-500 text-xs"><?= date('d M Y, H:i', strtotime($o['created_at'])) ?></td>
                    <td class="px-5 py-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <?php if ($o['status'] === 'pending'): ?>
                            <form action="process_order.php" method="POST" class="inline">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="id" value="<?= $o['id'] ?>">
                                <input type="hidden" name="status" value="completed">
                                <button type="submit" class="text-green-600 hover:text-green-800 text-xs font-medium transition-colors">Complete</button>
                            </form>
                            <form action="process_order.php" method="POST" class="inline">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="id" value="<?= $o['id'] ?>">
                                <input type="hidden" name="status" value="cancelled">
                                <button type="submit" class="text-yellow-600 hover:text-yellow-800 text-xs font-medium transition-colors">Cancel</button>
                            </form>
                            <?php endif; ?>
                            <form action="process_order.php" method="POST" class="inline"
                                  onsubmit="return confirm('Delete order #<?= $o['id'] ?>?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $o['id'] ?>">
                                <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-medium transition-colors">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (!empty($orders)): ?>
    <div class="px-5 py-3 border-t border-gray-100 text-xs text-gray-400">
        <?= count($orders) ?> order<?= count($orders) !== 1 ? 's' : '' ?>
        <?= $statusFilter !== 'all' ? "with status <strong>{$statusFilter}</strong>" : 'total' ?>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>

<script>
const productTemplate = `<div class="item-row flex items-center gap-2">
    <select name="product_id[]" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
        <option value="">— Select product —</option>
        <?php foreach ($availableProducts as $p): ?>
        <option value="<?= $p['id'] ?>" data-price="<?= $p['price_sale'] ?>"><?= htmlspecialchars(addslashes($p['sku'] . ' — ' . $p['name'])) ?> (<?= number_format($p['price_sale'], 2) ?> ₺)</option>
        <?php endforeach; ?>
    </select>
    <input type="number" name="quantity[]" min="1" value="1" class="w-24 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand" placeholder="Qty">
    <button type="button" onclick="this.closest('.item-row').remove()" class="text-red-400 hover:text-red-600 text-lg leading-none px-1">×</button>
</div>`;

function addItem() {
    document.getElementById('orderItems').insertAdjacentHTML('beforeend', productTemplate);
}
</script>

<?php require_once __DIR__ . '/../src/layout/footer.php'; ?>