<?php
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/ProductService.php';

use TokStock\Database;
use TokStock\ProductService;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    
    $file = $_FILES['csv_file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        header("Location: index.php?msg=import_error&detail=" . urlencode("Dosya yükleme hatası."));
        exit;
    }

    $filename = $file['tmp_name'];
    $fileInfo = pathinfo($file['name']);
    
    if (strtolower($fileInfo['extension']) !== 'csv') {
        header("Location: index.php?msg=import_error&detail=" . urlencode("Sadece CSV dosyaları desteklenmektedir."));
        exit;
    }

    if (($handle = fopen($filename, "r")) !== FALSE) {
        $productService = new ProductService();
        $db = Database::getConnection();
        
        $row = 0;
        $successCount = 0;
        $skipCount = 0;

        // Skip BOM if present
        $bom = fread($handle, 3);
        if ($bom !== b"\xEF\xBB\xBF") {
            rewind($handle); // Not BOM, rewind to start
        }

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $row++;
            if ($row === 1) {
                // Header satırını atla
                continue;
            }

            // Beklenen CSV Formatı (Sırası export_csv.php ile uyumlu olmalı):
            // 0: SKU, 1: Urun Adi, 2: Kategori, 3: Stok, 4: Kritik Stok, 5: Alis, 6: Satis

            $sku = trim($data[0] ?? '');
            $name = trim($data[1] ?? '');
            $categoryName = trim($data[2] ?? '');
            $stock = (int)($data[3] ?? 0);
            $minStock = (int)($data[4] ?? 5);
            $pricePurchase = (float)($data[5] ?? 0.0);
            $priceSale = (float)($data[6] ?? 0.0);

            if (empty($sku) || empty($name)) {
                $skipCount++;
                continue;
            }

            try {
                // Check if SKU exists
                $stmt = $db->prepare("SELECT id FROM products WHERE sku = :sku");
                $stmt->execute(['sku' => $sku]);
                $existingProduct = $stmt->fetch();

                if ($existingProduct) {
                    // Update existing
                    $updateData = [
                        'name' => $name,
                        'category_id' => null, // Kategori eşleştirmesi karmaşık olduğu için şimdilik boş bırakıyoruz
                        'stock_quantity' => $stock,
                        'price_purchase' => $pricePurchase,
                        'price_sale' => $priceSale
                    ];
                    $productService->updateProduct($existingProduct['id'], $updateData);
                } else {
                    // Create new
                    $createData = [
                        'sku' => $sku,
                        'name' => $name,
                        'category_id' => null,
                        'stock_quantity' => $stock,
                        'min_stock_level' => $minStock,
                        'price_purchase' => $pricePurchase,
                        'price_sale' => $priceSale
                    ];
                    $productService->createProduct($createData);
                }
                $successCount++;
            } catch (\Exception $e) {
                $skipCount++;
            }
        }
        fclose($handle);
        
        header("Location: index.php?msg=import_success&success={$successCount}&skipped={$skipCount}");
        exit;
    } else {
        header("Location: index.php?msg=import_error&detail=" . urlencode("Dosya okunamadı."));
        exit;
    }
} else {
    header("Location: index.php");
    exit;
}
