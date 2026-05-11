<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../src/UserService.php';

use TokStock\AuthService;
use TokStock\UserService;

AuthService::requireRole('system_admin');

$userSvc = new UserService();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');

        if (empty($name)) {
            header('Location: companies.php?msg=error');
            exit;
        }

        try {
            $userSvc->createCompany($name);
            header('Location: companies.php?msg=created');
            exit;
        } catch (\Exception $e) {
            $isDuplicate = str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), '1062');
            header('Location: companies.php?msg=' . ($isDuplicate ? 'dup' : 'error'));
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    $id     = (int)($_GET['id'] ?? 0);

    if ($action === 'delete' && $id > 0) {
        try {
            $userSvc->deleteCompany($id);
            header('Location: companies.php?msg=deleted');
            exit;
        } catch (\Exception $e) {
            $msg = str_contains($e->getMessage(), 'foreign key') ? 'in_use' : 'error';
            header('Location: companies.php?msg=' . $msg);
            exit;
        }
    }
}

header('Location: companies.php');
exit;
