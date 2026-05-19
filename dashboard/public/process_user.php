<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../src/UserService.php';

use TokStock\AuthService;
use TokStock\UserService;

AuthService::requireRole('system_admin', 'company_admin');

$userSvc    = new UserService();
$isSysAdmin = AuthService::isSystemAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'set_company') {
        $id  = (int)($_POST['id'] ?? 0);
        $cid = !empty($_POST['company_id']) ? (int)$_POST['company_id'] : null;

        if (!$isSysAdmin || $id <= 0 || $id === (int)$currentUser['id']) {
            header('Location: users.php');
            exit;
        }

        try {
            $userSvc->setUserCompany($id, $cid);
            header('Location: users.php?msg=updated');
            exit;
        } catch (\Exception $e) {
            header('Location: users.php');
            exit;
        }
    }

    if ($action === 'change_role') {
        $id   = (int)($_POST['id'] ?? 0);
        $role = $_POST['role'] ?? '';
        $validRoles = ['staff', 'company_admin'];

        if (!$isSysAdmin || $id <= 0 || !in_array($role, $validRoles, true) || $id === (int)$currentUser['id']) {
            header('Location: users.php');
            exit;
        }

        try {
            $userSvc->changeRole($id, $role);
            header('Location: users.php?msg=updated');
            exit;
        } catch (\Exception $e) {
            header('Location: users.php');
            exit;
        }
    }

    if ($action === 'create') {
        $name     = trim($_POST['name']     ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password']      ?? '';
        $role     = $_POST['role']          ?? 'staff';
        $cid      = !empty($_POST['company_id']) ? (int)$_POST['company_id'] : null;

        // Prevent anyone from creating a system_admin
        if ($role === 'system_admin') {
            $role = 'staff';
        }

        // Non-sysadmins can only create users for their own company
        if (!$isSysAdmin) {
            $cid = $companyId;
        }

        // company required for every role except system_admin
        if ($role !== 'system_admin' && empty($cid)) {
            header('Location: users.php?msg=no_company');
            exit;
        }

        if (empty($name) || empty($email) || strlen($password) < 8) {
            header('Location: users.php?msg=error');
            exit;
        }

        try {
            $userSvc->createUser([
                'company_id' => $cid,
                'name'       => $name,
                'email'      => $email,
                'password'   => $password,
                'role'       => $role,
            ]);
            header('Location: users.php?msg=created');
            exit;
        } catch (\Exception $e) {
            $isDuplicate = $e->getCode() == 23000 || str_contains($e->getMessage(), '1062');
            header('Location: users.php?msg=' . ($isDuplicate ? 'dup_email' : 'error'));
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    $id     = (int)($_GET['id'] ?? 0);

    // Prevent self-action
    if ($id === (int)$currentUser['id']) {
        header('Location: users.php');
        exit;
    }

    if ($action === 'toggle' && $id > 0) {
        try {
            // Ensure the target user belongs to the right company (for non-sysadmins)
            if (!$isSysAdmin) {
                $target = $userSvc->getUserById($id);
                if (!$target || (int)$target['company_id'] !== (int)$companyId) {
                    header('Location: users.php');
                    exit;
                }
            }
            $userSvc->toggleActive($id);
            header('Location: users.php?msg=toggled');
            exit;
        } catch (\Exception $e) {
            header('Location: users.php');
            exit;
        }
    }

    if ($action === 'delete' && $id > 0) {
        try {
            if (!$isSysAdmin) {
                $target = $userSvc->getUserById($id);
                if (!$target || (int)$target['company_id'] !== (int)$companyId) {
                    header('Location: users.php');
                    exit;
                }
            }
            $userSvc->deleteUser($id);
            header('Location: users.php?msg=deleted');
            exit;
        } catch (\Exception $e) {
            header('Location: users.php');
            exit;
        }
    }
}

header('Location: users.php');
exit;
