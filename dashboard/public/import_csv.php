<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../src/ProductService.php';

use TokStock\ProductService;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['csv_file'])) {
    header('Location: products.php');
    exit;
}

$file = $_FILES['csv_file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    header('Location: products.php?msg=import_error&detail=' . urlencode('File upload error.'));
    exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if ($ext !== 'csv') {
    header('Location: products.php?msg=import_error&detail=' . urlencode('Only CSV files are supported.'));
    exit;
}

$handle = fopen($file['tmp_name'], 'r');
if ($handle === false) {
    header('Location: products.php?msg=import_error&detail=' . urlencode('Could not read file.'));
    exit;
}

$productService = new ProductService($companyId);
$row            = 0;
$successCount   = 0;
$skipCount      = 0;

// Strip UTF-8 BOM if present
$bom = fread($handle, 3);
if ($bom !== "\xEF\xBB\xBF") {
    rewind($handle);
}

// Expected CSV columns (header row):
// SKU | Name | Category | Stock | Min Stock | Purchase Price | Sale Price | Supplier
while (($data = fgetcsv($handle, 2000, ',')) !== false) {
    $row++;
    if ($row === 1) {
        continue; // skip header
    }

    $sku          = trim($data[0] ?? '');
    $name         = trim($data[1] ?? '');
    $categoryName = trim($data[2] ?? '');
    $stock        = (int)($data[3] ?? 0);
    $minStock     = (int)($data[4] ?? 5);
    $pricePurch   = (float)($data[5] ?? 0.0);
    $priceSale    = (float)($data[6] ?? 0.0);
    $supplier     = trim($data[7] ?? '');

    if (empty($sku) || empty($name)) {
        $skipCount++;
        continue;
    }

    try {
        $existing = null;
        if ($companyId) {
            $db   = TokStock\Database::getConnection();
            $stmt = $db->prepare(
                "SELECT id FROM products WHERE company_id = :cid AND sku = :sku"
            );
            $stmt->execute(['cid' => $companyId, 'sku' => $sku]);
            $existing = $stmt->fetch();
        }

        $categoryId = $productService->ensureCategory($categoryName) ?: null;

        if ($existing) {
            $productService->updateProduct($existing['id'], [
                'name'            => $name,
                'category_id'     => $categoryId,
                'stock_quantity'  => $stock,
                'min_stock_level' => $minStock,
                'price_purchase'  => $pricePurch,
                'price_sale'      => $priceSale,
                'supplier'        => $supplier ?: null,
                'user_id'         => $currentUser['id'],
                'notes'           => 'CSV import update',
            ]);
        } else {
            $productService->createProduct([
                'sku'             => $sku,
                'name'            => $name,
                'category_id'     => $categoryId,
                'stock_quantity'  => $stock,
                'min_stock_level' => $minStock,
                'price_purchase'  => $pricePurch,
                'price_sale'      => $priceSale,
                'supplier'        => $supplier ?: null,
                'user_id'         => $currentUser['id'],
            ]);
        }
        $successCount++;
    } catch (\Exception $e) {
        $skipCount++;
    }
}

fclose($handle);

header("Location: products.php?msg=import_success&success={$successCount}&skipped={$skipCount}");
exit;
