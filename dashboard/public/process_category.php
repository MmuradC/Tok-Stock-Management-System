<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../src/ProductService.php';

use TokStock\AuthService;
use TokStock\ProductService;

AuthService::requireRole('system_admin', 'company_admin');

$productService = new ProductService($companyId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name        = trim($_POST['name']        ?? '');
        $description = trim($_POST['description'] ?? '');
        $cid         = !empty($_POST['company_id']) ? (int)$_POST['company_id'] : $companyId;

        if (empty($name) || $cid === null) {
            header('Location: categories.php?msg=error');
            exit;
        }

        try {
            $productService->createCategory($name, $description, $cid);
            header('Location: categories.php?msg=created');
            exit;
        } catch (\Exception $e) {
            $isDuplicate = str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), '1062');
            header('Location: categories.php?msg=' . ($isDuplicate ? 'dup' : 'error'));
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    $id     = (int)($_GET['id'] ?? 0);

    if ($action === 'delete' && $id > 0) {
        try {
            $productService->deleteCategory($id);
            header('Location: categories.php?msg=deleted');
            exit;
        } catch (\Exception $e) {
            header('Location: categories.php?msg=error');
            exit;
        }
    }
}

header('Location: categories.php');
exit;
