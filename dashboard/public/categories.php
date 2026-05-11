<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../src/ProductService.php';
require_once __DIR__ . '/../src/UserService.php';

use TokStock\AuthService;
use TokStock\ProductService;
use TokStock\UserService;

AuthService::requireRole('system_admin', 'company_admin');

$pageTitle   = 'Categories';
$currentPage = 'categories';

$isSysAdmin     = AuthService::isSystemAdmin();
$productService = new ProductService($companyId);
$userSvc        = new UserService();

try {
    $categories = $productService->getCategoriesWithCount();
    $companies  = $isSysAdmin ? $userSvc->getAllCompanies() : [];
} catch (\Exception $e) {
    $categories = [];
    $companies  = [];
    $dbError    = $e->getMessage();
}

$msg = $_GET['msg'] ?? '';
$flashMessages = [
    'created' => ['bg-green-50 border-green-200 text-green-700', 'Category created successfully.'],
    'deleted' => ['bg-yellow-50 border-yellow-200 text-yellow-700', 'Category deleted.'],
    'error'   => ['bg-red-50 border-red-200 text-red-700', 'Something went wrong. Please try again.'],
    'dup'     => ['bg-red-50 border-red-200 text-red-700', 'A category with that name already exists.'],
];
?>
<?php require_once __DIR__ . '/../src/layout/head.php'; ?>
<?php require_once __DIR__ . '/../src/layout/sidebar.php'; ?>
<?php require_once __DIR__ . '/../src/layout/topbar.php'; ?>

<?php if ($msg && isset($flashMessages[$msg])): ?>
<div class="border px-4 py-3 rounded-lg mb-5 text-sm <?= $flashMessages[$msg][0] ?>">
    <?= $flashMessages[$msg][1] ?>
</div>
<?php endif; ?>

<?php if (isset($dbError)): ?>
<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm mb-5">
    <?= htmlspecialchars($dbError) ?>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

    <!-- Category list -->
    <div class="xl:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800 text-sm">Categories</h2>
                <span class="text-xs text-gray-400"><?= count($categories) ?> categor<?= count($categories) !== 1 ? 'ies' : 'y' ?></span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Name</th>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Description</th>
                            <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Products</th>
                            <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-10 text-gray-400">No categories yet. Add one to get started.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-3 font-medium text-gray-800"><?= htmlspecialchars($cat['name']) ?></td>
                            <td class="px-5 py-3 text-gray-500 text-xs"><?= htmlspecialchars($cat['description'] ?? '—') ?></td>
                            <td class="px-5 py-3 text-center">
                                <span class="text-xs font-medium text-gray-600"><?= (int)$cat['product_count'] ?></span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <?php if ((int)$cat['product_count'] === 0): ?>
                                <a href="process_category.php?action=delete&id=<?= $cat['id'] ?>"
                                   onclick="return confirm('Delete category &quot;<?= htmlspecialchars(addslashes($cat['name'])) ?>&quot;?')"
                                   class="text-xs text-red-500 hover:text-red-700 transition-colors">Delete</a>
                                <?php else: ?>
                                <span class="text-xs text-gray-300" title="Cannot delete: category has products">In use</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add category form -->
    <div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-6">
            <h2 class="font-semibold text-gray-800 text-sm mb-5">Add New Category</h2>

            <form method="POST" action="process_category.php" novalidate>
                <input type="hidden" name="action" value="create">

                <?php if ($isSysAdmin && !empty($companies)): ?>
                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Company <span class="text-red-500">*</span></label>
                    <select name="company_id" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-brand">
                        <option value="">— Select company —</option>
                        <?php foreach ($companies as $co): ?>
                        <option value="<?= $co['id'] ?>"><?= htmlspecialchars($co['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                <input type="hidden" name="company_id" value="<?= $companyId ?? '' ?>">
                <?php endif; ?>

                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                    <input name="name" type="text" required placeholder="e.g. Electronics"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                </div>

                <div class="mb-5">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="3" placeholder="Optional description"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand resize-none"></textarea>
                </div>

                <button type="submit"
                        class="w-full bg-brand hover:bg-brand-mid text-white font-semibold py-2.5 px-4 rounded-lg text-sm transition-colors shadow-sm">
                    Create Category
                </button>
            </form>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../src/layout/footer.php'; ?>
