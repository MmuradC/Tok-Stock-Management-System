<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../src/UserService.php';

use TokStock\AuthService;
use TokStock\UserService;

AuthService::requireRole('system_admin', 'company_admin');

$pageTitle   = 'Users & Roles';
$currentPage = 'users';

$userSvc  = new UserService();
$isSysAdmin = AuthService::isSystemAdmin();

try {
    $users     = $isSysAdmin ? $userSvc->getAllUsers() : $userSvc->getUsersByCompany($companyId);
    $companies = $isSysAdmin ? $userSvc->getAllCompanies() : [];
} catch (\Exception $e) {
    $users     = [];
    $companies = [];
    $dbError   = $e->getMessage();
}

$msg = $_GET['msg'] ?? '';
$flashMessages = [
    'created'    => ['bg-green-50 border-green-200 text-green-700',   'User created successfully.'],
    'updated'    => ['bg-blue-50 border-blue-200 text-blue-700',      'User updated successfully.'],
    'deleted'    => ['bg-yellow-50 border-yellow-200 text-yellow-700','User deleted.'],
    'toggled'    => ['bg-blue-50 border-blue-200 text-blue-700',      'User status toggled.'],
    'no_company' => ['bg-red-50 border-red-200 text-red-700',         'A company must be selected for Staff and Company Admin users.'],
    'dup_email'  => ['bg-red-50 border-red-200 text-red-700',         'That email address is already in use. Please choose a different one.'],
    'error'      => ['bg-red-50 border-red-200 text-red-700',         'Could not create user. Make sure all fields are filled correctly (password must be at least 8 characters).'],
];

$roleBadge = [
    'system_admin'  => 'bg-purple-100 text-purple-700',
    'company_admin' => 'bg-blue-100 text-blue-700',
    'staff'         => 'bg-gray-100 text-gray-600',
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

    <!-- User list -->
    <div class="xl:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800 text-sm">
                    <?= $isSysAdmin ? 'All Users' : 'Company Users' ?>
                </h2>
                <span class="text-xs text-gray-400"><?= count($users) ?> user<?= count($users) !== 1 ? 's' : '' ?></span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Name</th>
                            <?php if ($isSysAdmin): ?>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Company</th>
                            <?php endif; ?>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Role</th>
                            <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                            <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="<?= $isSysAdmin ? 5 : 4 ?>" class="text-center py-10 text-gray-400">No users found.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-gray-50 transition-colors <?= !$u['is_active'] ? 'opacity-60' : '' ?>">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-brand-light flex items-center justify-center shrink-0">
                                        <span class="text-brand font-bold text-xs"><?= strtoupper(substr($u['name'], 0, 1)) ?></span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-800"><?= htmlspecialchars($u['name']) ?></p>
                                        <p class="text-xs text-gray-400"><?= htmlspecialchars($u['email']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <?php if ($isSysAdmin): ?>
                            <td class="px-5 py-3">
                                <?php if ($u['id'] !== $currentUser['id']): ?>
                                <form method="POST" action="process_user.php" class="inline">
                                    <input type="hidden" name="action" value="set_company">
                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                    <select name="company_id" onchange="this.form.submit()" title="Assign company"
                                            class="text-xs border border-gray-200 rounded-md px-1.5 py-1 bg-white text-gray-600 cursor-pointer focus:outline-none focus:ring-1 focus:ring-brand">
                                        <option value="">— System —</option>
                                        <?php foreach ($companies as $co): ?>
                                        <option value="<?= $co['id'] ?>" <?= ($u['company_id'] ?? null) == $co['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($co['name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                                <?php else: ?>
                                <span class="text-xs text-gray-400"><?= htmlspecialchars($u['company_name'] ?? '— System —') ?></span>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                            <td class="px-5 py-3">
                                <span class="text-xs font-medium px-2.5 py-0.5 rounded-full <?= $roleBadge[$u['role']] ?? 'bg-gray-100 text-gray-600' ?>">
                                    <?= str_replace('_', ' ', ucfirst($u['role'])) ?>
                                </span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <?php if ($u['is_active']): ?>
                                <span class="text-xs font-medium text-green-600">Active</span>
                                <?php else: ?>
                                <span class="text-xs font-medium text-gray-400">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <?php if ($u['id'] !== $currentUser['id']): ?>
                                <div class="flex items-center justify-center gap-2 flex-wrap">
                                    <?php if ($isSysAdmin): ?>
                                    <form method="POST" action="process_user.php" class="inline">
                                        <input type="hidden" name="action" value="change_role">
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                        <select name="role" onchange="this.form.submit()" title="Change role"
                                                class="text-xs border border-gray-200 rounded-md px-1.5 py-1 bg-white text-gray-600 cursor-pointer focus:outline-none focus:ring-1 focus:ring-brand">
                                            <option value="staff"         <?= $u['role'] === 'staff'         ? 'selected' : '' ?>>Staff</option>
                                            <option value="company_admin" <?= $u['role'] === 'company_admin' ? 'selected' : '' ?>>Co. Admin</option>
                                            <option value="system_admin"  <?= $u['role'] === 'system_admin'  ? 'selected' : '' ?>>Sys. Admin</option>
                                        </select>
                                    </form>
                                    <?php endif; ?>
                                    <a href="process_user.php?action=toggle&id=<?= $u['id'] ?>"
                                       class="text-xs text-blue-500 hover:text-blue-700 transition-colors">
                                        <?= $u['is_active'] ? 'Disable' : 'Enable' ?>
                                    </a>
                                    <a href="process_user.php?action=delete&id=<?= $u['id'] ?>"
                                       onclick="return confirm('Delete user <?= htmlspecialchars(addslashes($u['name'])) ?>?')"
                                       class="text-xs text-red-500 hover:text-red-700 transition-colors">Delete</a>
                                </div>
                                <?php else: ?>
                                <span class="text-xs text-gray-300">You</span>
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

    <!-- Add user form -->
    <div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-6">
            <h2 class="font-semibold text-gray-800 text-sm mb-5">Add New User</h2>

            <form method="POST" action="process_user.php" novalidate>
                <input type="hidden" name="action" value="create">

                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                    <input name="name" type="text" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                    <input name="email" type="email" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                    <input name="password" type="password" required minlength="8"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Role</label>
                    <select name="role" id="roleSelect"
                            <?= $isSysAdmin ? 'onchange="toggleCompany(this.value)"' : '' ?>
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-brand">
                        <option value="staff">Staff</option>
                        <option value="company_admin">Company Admin</option>
                        <?php if ($isSysAdmin): ?>
                        <option value="system_admin">System Admin</option>
                        <?php endif; ?>
                    </select>
                </div>

                <?php if ($isSysAdmin && !empty($companies)): ?>
                <div class="mb-5" id="companyField">
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Company <span class="text-red-500" id="companyRequired">*</span>
                    </label>
                    <select name="company_id" id="companySelect" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-brand">
                        <option value="">— Select company —</option>
                        <?php foreach ($companies as $co): ?>
                        <option value="<?= $co['id'] ?>"><?= htmlspecialchars($co['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <script>
                function toggleCompany(role) {
                    var field   = document.getElementById('companyField');
                    var select  = document.getElementById('companySelect');
                    var star    = document.getElementById('companyRequired');
                    var isSys   = role === 'system_admin';
                    field.style.display  = isSys ? 'none' : '';
                    select.required      = !isSys;
                    select.value         = isSys ? '' : select.value;
                    star.style.display   = isSys ? 'none' : '';
                }
                </script>
                <?php elseif ($isSysAdmin): ?>
                <div class="mb-5 bg-yellow-50 border border-yellow-200 text-yellow-700 text-xs px-3 py-2 rounded-lg">
                    No companies exist yet. <a href="companies.php" class="underline font-medium">Create one first</a> before adding users.
                </div>
                <?php else: ?>
                <input type="hidden" name="company_id" value="<?= $companyId ?? '' ?>">
                <?php endif; ?>

                <button type="submit"
                        class="w-full bg-brand hover:bg-brand-mid text-white font-semibold py-2.5 px-4 rounded-lg text-sm transition-colors shadow-sm">
                    Create User
                </button>
            </form>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../src/layout/footer.php'; ?>
