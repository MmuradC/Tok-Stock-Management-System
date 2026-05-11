<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../src/ProductService.php';

use TokStock\ProductService;

$productService = new ProductService($companyId);
$products       = $productService->getAllProducts();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="tok_stock_export_' . date('Ymd_His') . '.csv"');

$output = fopen('php://output', 'w');

// UTF-8 BOM for Excel compatibility
fputs($output, "\xEF\xBB\xBF");

fputcsv($output, ['SKU', 'Name', 'Category', 'Stock', 'Min Stock', 'Purchase Price', 'Sale Price', 'Supplier']);

foreach ($products as $p) {
    fputcsv($output, [
        $p['sku'],
        $p['name'],
        $p['category_name'] ?? '',
        $p['stock_quantity'],
        $p['min_stock_level'],
        $p['price_purchase'],
        $p['price_sale'],
        $p['supplier'] ?? '',
    ]);
}

fclose($output);
exit;
