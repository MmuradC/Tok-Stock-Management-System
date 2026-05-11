<?php
namespace TokStock;

use PDO;

class ProductService {
    private PDO $db;
    private ?int $companyId;

    public function __construct(?int $companyId = null) {
        $this->db        = Database::getConnection();
        $this->companyId = $companyId;
    }

    private function companyFilter(string $alias = 'p'): string {
        return $this->companyId !== null ? "AND {$alias}.company_id = :company_id" : '';
    }

    private function bindCompany(array $params): array {
        if ($this->companyId !== null) {
            $params['company_id'] = $this->companyId;
        }
        return $params;
    }

    public function getAllProducts(): array {
        $filter = $this->companyId !== null ? 'WHERE p.company_id = :company_id' : '';
        $stmt   = $this->db->prepare(
            "SELECT p.*, c.name AS category_name, co.name AS company_name
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN companies co ON p.company_id = co.id
             $filter
             ORDER BY p.id DESC"
        );
        $stmt->execute($this->bindCompany([]));
        return $stmt->fetchAll();
    }

    public function getProductById(int $id): ?array {
        $filter = $this->companyId !== null ? 'AND company_id = :company_id' : '';
        $stmt   = $this->db->prepare("SELECT * FROM products WHERE id = :id $filter");
        $stmt->execute($this->bindCompany(['id' => $id]));
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function skuExistsGlobally(string $sku, ?int $excludeId = null): bool {
        $sql    = "SELECT COUNT(*) FROM products WHERE sku = :sku" . ($excludeId !== null ? " AND id != :exclude_id" : "");
        $params = ['sku' => $sku];
        if ($excludeId !== null) {
            $params['exclude_id'] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function createProduct(array $data): int {
        $cid = $data['company_id'] ?? $this->companyId;

        if ($this->skuExistsGlobally($data['sku'])) {
            throw new \RuntimeException('SKU "' . $data['sku'] . '" already exists. SKUs must be unique across all companies.');
        }

        $sql = "INSERT INTO products
                    (company_id, sku, name, category_id, stock_quantity, min_stock_level,
                     price_purchase, price_sale, supplier)
                VALUES
                    (:company_id, :sku, :name, :category_id, :stock_quantity, :min_stock_level,
                     :price_purchase, :price_sale, :supplier)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'company_id'     => $cid,
            'sku'            => $data['sku'],
            'name'           => $data['name'],
            'category_id'    => $data['category_id'] ?? null,
            'stock_quantity' => $data['stock_quantity'] ?? 0,
            'min_stock_level'=> $data['min_stock_level'] ?? 5,
            'price_purchase' => $data['price_purchase'] ?? 0.00,
            'price_sale'     => $data['price_sale'] ?? 0.00,
            'supplier'       => $data['supplier'] ?? null,
        ]);

        $productId = (int)$this->db->lastInsertId();

        if (($data['stock_quantity'] ?? 0) > 0) {
            $this->logStockMovement($productId, $cid, (int)$data['stock_quantity'], 'IN',
                $data['user_id'] ?? null, 'Initial stock on product creation');
        }

        return $productId;
    }

    public function updateProduct(int $id, array $data): bool {
        $filter = $this->companyId !== null ? 'AND company_id = :company_id' : '';

        // Fetch current quantity to calculate delta for stock log
        $current = $this->getProductById($id);

        $sql = "UPDATE products SET
                    name           = :name,
                    category_id    = :category_id,
                    stock_quantity = :stock_quantity,
                    min_stock_level= :min_stock_level,
                    price_purchase = :price_purchase,
                    price_sale     = :price_sale,
                    supplier       = :supplier
                WHERE id = :id $filter";

        $params = $this->bindCompany([
            'id'             => $id,
            'name'           => $data['name'],
            'category_id'    => $data['category_id'] ?? null,
            'stock_quantity' => $data['stock_quantity'],
            'min_stock_level'=> $data['min_stock_level'] ?? ($current['min_stock_level'] ?? 5),
            'price_purchase' => $data['price_purchase'],
            'price_sale'     => $data['price_sale'],
            'supplier'       => $data['supplier'] ?? null,
        ]);

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($params);

        // Log stock adjustment if quantity changed
        if ($result && $current && (int)$current['stock_quantity'] !== (int)$data['stock_quantity']) {
            $delta  = (int)$data['stock_quantity'] - (int)$current['stock_quantity'];
            $type   = $delta > 0 ? 'IN' : 'OUT';
            $cid    = $this->companyId ?? $current['company_id'];
            $this->logStockMovement($id, $cid, $delta, 'ADJUSTMENT',
                $data['user_id'] ?? null, $data['notes'] ?? 'Manual adjustment');
        }

        return $result;
    }

    public function deleteProduct(int $id): bool {
        $filter = $this->companyId !== null ? 'AND company_id = :company_id' : '';
        $stmt   = $this->db->prepare("DELETE FROM products WHERE id = :id $filter");
        return $stmt->execute($this->bindCompany(['id' => $id]));
    }

    public function getCategories(): array {
        $filter = $this->companyId !== null ? 'WHERE company_id = :company_id' : '';
        $stmt   = $this->db->prepare("SELECT id, name FROM categories $filter ORDER BY name ASC");
        $stmt->execute($this->bindCompany([]));
        return $stmt->fetchAll();
    }

    public function getCategoriesWithCount(): array {
        $filter = $this->companyId !== null ? 'WHERE c.company_id = :company_id' : '';
        $stmt   = $this->db->prepare(
            "SELECT c.id, c.name, c.description, c.created_at,
                    COUNT(p.id) AS product_count
             FROM categories c
             LEFT JOIN products p ON p.category_id = c.id
             $filter
             GROUP BY c.id
             ORDER BY c.name ASC"
        );
        $stmt->execute($this->bindCompany([]));
        return $stmt->fetchAll();
    }

    public function createCategory(string $name, string $description = '', ?int $companyId = null): int {
        $cid = $companyId ?? $this->companyId;
        $stmt = $this->db->prepare(
            "INSERT INTO categories (company_id, name, description) VALUES (:company_id, :name, :description)"
        );
        $stmt->execute([
            'company_id'  => $cid,
            'name'        => $name,
            'description' => $description ?: null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function deleteCategory(int $id): bool {
        $filter = $this->companyId !== null ? 'AND company_id = :company_id' : '';
        $stmt   = $this->db->prepare("DELETE FROM categories WHERE id = :id $filter");
        return $stmt->execute($this->bindCompany(['id' => $id]));
    }

    public function ensureCategory(string $name): ?int {
        if (empty($name) || $this->companyId === null) {
            return null;
        }
        $stmt = $this->db->prepare(
            "SELECT id FROM categories WHERE company_id = :cid AND name = :name LIMIT 1"
        );
        $stmt->execute(['cid' => $this->companyId, 'name' => $name]);
        $row = $stmt->fetch();
        if ($row) {
            return (int)$row['id'];
        }
        $stmt = $this->db->prepare(
            "INSERT INTO categories (company_id, name) VALUES (:cid, :name)"
        );
        $stmt->execute(['cid' => $this->companyId, 'name' => $name]);
        return (int)$this->db->lastInsertId();
    }

    private function logStockMovement(int $productId, ?int $companyId, int $amount,
                                      string $type, ?int $userId, ?string $notes): void {
        if ($companyId === null) {
            return;
        }
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO stock_logs (company_id, product_id, change_amount, action_type, user_id, notes)
                 VALUES (:company_id, :product_id, :change_amount, :action_type, :user_id, :notes)"
            );
            $stmt->execute([
                'company_id'    => $companyId,
                'product_id'    => $productId,
                'change_amount' => $amount,
                'action_type'   => $type,
                'user_id'       => $userId,
                'notes'         => $notes,
            ]);
        } catch (\Exception $e) {
            // Non-fatal: don't break the main operation
        }
    }
}
