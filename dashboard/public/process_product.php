<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../src/ProductService.php';

use TokStock\ProductService;

$productService = new ProductService($companyId);
$userId         = $currentUser['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $sku  = trim($_POST['sku']  ?? '');
        $name = trim($_POST['name'] ?? '');
        $cid  = !empty($_POST['company_id']) ? (int)$_POST['company_id'] : $companyId;

        if (empty($sku) || empty($name) || $cid === null) {
            header('Location: add_product.php?error=' . urlencode('Missing required fields or no company selected.'));
            exit;
        }

        $data = [
            'company_id'      => $cid,
            'sku'             => $sku,
            'name'            => $name,
            'category_id'     => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
            'stock_quantity'  => (int)($_POST['stock_quantity']  ?? 0),
            'min_stock_level' => (int)($_POST['min_stock_level'] ?? 5),
            'price_purchase'  => (float)($_POST['price_purchase'] ?? 0.0),
            'price_sale'      => (float)($_POST['price_sale']     ?? 0.0),
            'supplier'        => trim($_POST['supplier'] ?? '') ?: null,
            'user_id'         => $userId,
        ];

        $backUrl = $cid ? 'add_product.php?company_id=' . $cid : 'add_product.php';
        try {
            $productService->createProduct($data);
            header('Location: products.php?msg=created');
            exit;
        } catch (\RuntimeException $e) {
            header('Location: ' . $backUrl . '&error=' . urlencode($e->getMessage()));
            exit;
        } catch (\PDOException $e) {
            $msg = $e->getCode() == 23000
                ? 'This SKU already exists. SKUs must be unique across all companies.'
                : 'Database error: ' . $e->getMessage();
            header('Location: ' . $backUrl . '&error=' . urlencode($msg));
            exit;
        }

    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            header('Location: products.php');
            exit;
        }

        $data = [
            'name'            => trim($_POST['name'] ?? ''),
            'category_id'     => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
            'stock_quantity'  => (int)($_POST['stock_quantity']  ?? 0),
            'min_stock_level' => (int)($_POST['min_stock_level'] ?? 5),
            'price_purchase'  => (float)($_POST['price_purchase'] ?? 0.0),
            'price_sale'      => (float)($_POST['price_sale']     ?? 0.0),
            'supplier'        => trim($_POST['supplier'] ?? '') ?: null,
            'notes'           => trim($_POST['notes'] ?? '') ?: null,
            'user_id'         => $userId,
        ];

        try {
            $productService->updateProduct($id, $data);
            header('Location: products.php?msg=updated');
            exit;
        } catch (\Exception $e) {
            header('Location: edit_product.php?id=' . $id . '&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    if ($action === 'delete' && isset($_GET['id'])) {
        try {
            $productService->deleteProduct((int)$_GET['id']);
            header('Location: products.php?msg=deleted');
            exit;
        } catch (\Exception $e) {
            header('Location: products.php?error=' . urlencode($e->getMessage()));
            exit;
        }
    }
}

header('Location: products.php');
exit;
