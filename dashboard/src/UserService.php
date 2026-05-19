<?php
namespace TokStock;

use PDO;

class UserService {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function countAll(): int {
        return (int)$this->db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    }

    public function getUsersByCompany(int $companyId): array {
        $stmt = $this->db->prepare(
            "SELECT id, name, email, role, is_active, created_at
             FROM users WHERE company_id = :company_id ORDER BY name ASC"
        );
        $stmt->execute(['company_id' => $companyId]);
        return $stmt->fetchAll();
    }

    public function getAllUsers(): array {
        $stmt = $this->db->query(
            "SELECT u.id, u.name, u.email, u.role, u.is_active, u.created_at,
                    u.company_id, c.name AS company_name
             FROM users u
             LEFT JOIN companies c ON u.company_id = c.id
             ORDER BY c.name ASC, u.name ASC"
        );
        return $stmt->fetchAll();
    }

    public function getUserById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createUser(array $data): bool {
        $hash = password_hash($data['password'], PASSWORD_BCRYPT);
        $stmt = $this->db->prepare(
            "INSERT INTO users (company_id, name, email, password_hash, role)
             VALUES (:company_id, :name, :email, :password_hash, :role)"
        );
        return $stmt->execute([
            'company_id'    => $data['company_id'] ?: null,
            'name'          => $data['name'],
            'email'         => $data['email'],
            'password_hash' => $hash,
            'role'          => $data['role'],
        ]);
    }

    public function updateUser(int $id, array $data): bool {
        $sql = "UPDATE users SET name = :name, email = :email, role = :role WHERE id = :id";
        $params = [
            'id'    => $id,
            'name'  => $data['name'],
            'email' => $data['email'],
            'role'  => $data['role'],
        ];

        if (!empty($data['password'])) {
            $sql = "UPDATE users SET name = :name, email = :email, role = :role,
                        password_hash = :password_hash WHERE id = :id";
            $params['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function toggleActive(int $id): bool {
        $stmt = $this->db->prepare(
            "UPDATE users SET is_active = IF(is_active = 1, 0, 1) WHERE id = :id"
        );
        return $stmt->execute(['id' => $id]);
    }

    public function deleteUser(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function changeRole(int $id, string $role): bool {
        $stmt = $this->db->prepare("UPDATE users SET role = :role WHERE id = :id");
        return $stmt->execute(['id' => $id, 'role' => $role]);
    }

    public function setUserCompany(int $id, ?int $companyId): bool {
        $stmt = $this->db->prepare("UPDATE users SET company_id = :company_id WHERE id = :id");
        return $stmt->execute(['company_id' => $companyId, 'id' => $id]);
    }

    public function getAllCompanies(): array {
        return $this->db->query("SELECT id, name FROM companies ORDER BY name ASC")->fetchAll();
    }

    public function getAllCompaniesWithCount(): array {
        $stmt = $this->db->query(
            "SELECT c.id, c.name, c.created_at,
                    COUNT(u.id) AS user_count
             FROM companies c
             LEFT JOIN users u ON u.company_id = c.id
             GROUP BY c.id
             ORDER BY c.name ASC"
        );
        return $stmt->fetchAll();
    }

    public function createCompany(string $name): int {
        $stmt = $this->db->prepare("INSERT INTO companies (name) VALUES (:name)");
        $stmt->execute(['name' => $name]);
        return (int)$this->db->lastInsertId();
    }

    public function deleteCompany(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM companies WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
