<?php
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/ProductService.php';

use TokStock\ProductService;

$productService = new ProductService();
$products = $productService->getAllProducts();

// Set headers to force download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="tok_stock_export_' . date('Ymd_His') . '.csv"');

// Open file pointer to output stream
$output = fopen('php://output', 'w');

// UTF-8 BOM for Excel compatibility
fputs($output, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF)));

// Output column headings
fputcsv($output, array('SKU', 'Urun Adi', 'Kategori', 'Stok Miktari', 'Kritik Stok', 'Alis Fiyati', 'Satis Fiyati'));

// Output data rows
foreach ($products as $product) {
    fputcsv($output, array(
        $product['sku'],
        $product['name'],
        $product['category_name'] ?? '',
        $product['stock_quantity'],
        $product['min_stock_level'],
        $product['price_purchase'],
        $product['price_sale']
    ));
}

fclose($output);
exit;
