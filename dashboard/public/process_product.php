<?php
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/ProductService.php';

use TokStock\ProductService;

// Hataları ekrana basması için (Geliştirme ortamı için)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $productService = new ProductService();
        
        $data = [
            'sku' => trim($_POST['sku'] ?? ''),
            'name' => trim($_POST['name'] ?? ''),
            'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
            'stock_quantity' => (int)($_POST['stock_quantity'] ?? 0),
            'min_stock_level' => (int)($_POST['min_stock_level'] ?? 5),
            'price_purchase' => (float)($_POST['price_purchase'] ?? 0.0),
            'price_sale' => (float)($_POST['price_sale'] ?? 0.0),
        ];

        try {
            $productService->createProduct($data);
            header("Location: index.php?msg=created");
            exit;
        } catch (\PDOException $e) {
            // Eğer SKU duplicate hatası verirse
            if ($e->getCode() == 23000) {
                die("Hata: Bu Stok Kodu (SKU) zaten kullanılıyor. Lütfen geri dönüp farklı bir SKU girin.");
            }
            die("Veritabanı Hatası: " . $e->getMessage());
        } catch (\Exception $e) {
            die("Sistem Hatası: " . $e->getMessage());
        }
    } elseif ($action === 'update') {
        $productService = new ProductService();
        
        $id = (int)($_POST['id'] ?? 0);
        
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
            'stock_quantity' => (int)($_POST['stock_quantity'] ?? 0),
            'price_purchase' => (float)($_POST['price_purchase'] ?? 0.0),
            'price_sale' => (float)($_POST['price_sale'] ?? 0.0),
        ];

        try {
            $productService->updateProduct($id, $data);
            header("Location: index.php?msg=updated");
            exit;
        } catch (\Exception $e) {
            die("Güncelleme Hatası: " . $e->getMessage());
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'delete' && isset($_GET['id'])) {
        $productService = new ProductService();
        try {
            $productService->deleteProduct((int)$_GET['id']);
            header("Location: index.php?msg=deleted");
            exit;
        } catch (\Exception $e) {
            die("Silme Hatası: " . $e->getMessage());
        }
    }
}

// Tanımsız bir istek gelirse ana sayfaya yönlendir
header("Location: index.php");
exit;
