<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../src/UserService.php';

use TokStock\AuthService;
use TokStock\UserService;

AuthService::requireRole('system_admin');

$pageTitle   = 'Companies';
$currentPage = 'companies';

$userSvc = new UserService();

try {
    $companies = $userSvc->getAllCompaniesWithCount();
} catch (\Exception $e) {
    $companies = [];
    $dbError   = $e->getMessage();
}

$msg = $_GET['msg'] ?? '';
$flashMessages = [
    'created' => ['bg-green-50 border-green-200 text-green-700', 'Company created successfully.'],
    'deleted' => ['bg-yellow-50 border-yellow-200 text-yellow-700', 'Company deleted.'],
    'error'   => ['bg-red-50 border-red-200 text-red-700', 'Something went wrong. Please try again.'],
    'dup'     => ['bg-red-50 border-red-200 text-red-700', 'A company with that name already exists.'],
    'in_use'  => ['bg-red-50 border-red-200 text-red-700', 'Cannot delete: company has users or products assigned.'],
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

    <!-- Company list -->
    <div class="xl:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800 text-sm">Companies</h2>
                <span class="text-xs text-gray-400"><?= count($companies) ?> compan<?= count($companies) !== 1 ? 'ies' : 'y' ?></span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Company Name</th>
                            <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Users</th>
                            <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($companies)): ?>
                        <tr>
                            <td colspan="3" class="text-center py-10 text-gray-400">No companies yet.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($companies as $co): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-brand-light flex items-center justify-center shrink-0">
                                        <span class="text-brand font-bold text-xs"><?= strtoupper(substr($co['name'], 0, 1)) ?></span>
                                    </div>
                                    <span class="font-medium text-gray-800"><?= htmlspecialchars($co['name']) ?></span>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-center text-xs text-gray-600"><?= (int)$co['user_count'] ?></td>
                            <td class="px-5 py-3 text-center">
                                <?php if ((int)$co['user_count'] === 0): ?>
                                <a href="process_company.php?action=delete&id=<?= $co['id'] ?>"
                                   onclick="return confirm('Delete company &quot;<?= htmlspecialchars(addslashes($co['name'])) ?>&quot;? This cannot be undone.')"
                                   class="text-xs text-red-500 hover:text-red-700 transition-colors">Delete</a>
                                <?php else: ?>
                                <span class="text-xs text-gray-300" title="Cannot delete: company has users">In use</span>
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

    <!-- Add company form -->
    <div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-6">
            <h2 class="font-semibold text-gray-800 text-sm mb-5">Add New Company</h2>

            <form method="POST" action="process_company.php" novalidate>
                <input type="hidden" name="action" value="create">

                <div class="mb-5">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Company Name <span class="text-red-500">*</span></label>
                    <input name="name" type="text" required placeholder="e.g. Acme Corp"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                </div>

                <button type="submit"
                        class="w-full bg-brand hover:bg-brand-mid text-white font-semibold py-2.5 px-4 rounded-lg text-sm transition-colors shadow-sm">
                    Create Company
                </button>
            </form>
        </div>

        <div class="bg-blue-50 border border-blue-100 rounded-xl px-5 py-4 mt-4 text-xs text-blue-600 leading-relaxed">
            <strong>Tip:</strong> After creating a company, go to <a href="users.php" class="underline">Users &amp; Roles</a> to add users to it.
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../src/layout/footer.php'; ?>
