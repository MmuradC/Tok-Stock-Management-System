<?php
namespace TokStock;

use PDO;

class ProductService {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Tüm ürünleri listeler
     */
    public function getAllProducts(): array {
        $stmt = $this->db->query("
            SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            ORDER BY p.id DESC
        ");
        return $stmt->fetchAll();
    }

    /**
     * ID'ye göre tek bir ürün getirir
     */
    public function getProductById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $product = $stmt->fetch();
        return $product ?: null;
    }

    /**
     * Yeni ürün ekler
     */
    public function createProduct(array $data): bool {
        $sql = "INSERT INTO products (sku, name, category_id, stock_quantity, min_stock_level, price_purchase, price_sale) 
                VALUES (:sku, :name, :category_id, :stock_quantity, :min_stock_level, :price_purchase, :price_sale)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'sku' => $data['sku'],
            'name' => $data['name'],
            'category_id' => $data['category_id'] ?? null,
            'stock_quantity' => $data['stock_quantity'] ?? 0,
            'min_stock_level' => $data['min_stock_level'] ?? 5,
            'price_purchase' => $data['price_purchase'] ?? 0.00,
            'price_sale' => $data['price_sale'] ?? 0.00
        ]);
    }

    /**
     * Ürün günceller
     */
    public function updateProduct(int $id, array $data): bool {
        $sql = "UPDATE products SET 
                    name = :name, 
                    category_id = :category_id, 
                    stock_quantity = :stock_quantity, 
                    price_purchase = :price_purchase, 
                    price_sale = :price_sale 
                WHERE id = :id";
                
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'category_id' => $data['category_id'] ?? null,
            'stock_quantity' => $data['stock_quantity'],
            'price_purchase' => $data['price_purchase'],
            'price_sale' => $data['price_sale']
        ]);
    }

    /**
     * Ürün siler
     */
    public function deleteProduct(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM products WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
