<?php
namespace TokStock;

use PDO;

class AuthService {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function login(string $email, string $password): bool {
        $stmt = $this->db->prepare(
            "SELECT id, name, email, password_hash, role, company_id, is_active
             FROM users WHERE email = :email LIMIT 1"
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !$user['is_active']) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        self::startSession();
        session_regenerate_id(true);

        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role']  = $user['role'];
        $_SESSION['company_id'] = $user['company_id'];

        return true;
    }

    public static function logout(): void {
        self::startSession();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function requireAuth(): void {
        self::startSession();
        if (empty($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }
    }

    public static function requireRole(string ...$roles): void {
        self::requireAuth();
        if (!in_array($_SESSION['user_role'] ?? '', $roles, true)) {
            http_response_code(403);
            echo '<!DOCTYPE html><html><body style="font-family:sans-serif;padding:2rem">'
               . '<h1>403 &mdash; Forbidden</h1>'
               . '<p>You do not have permission to access this page.</p>'
               . '<a href="index.php">Back to Dashboard</a></body></html>';
            exit;
        }
    }

    public static function currentUser(): array {
        self::startSession();
        return [
            'id'         => $_SESSION['user_id']    ?? null,
            'name'       => $_SESSION['user_name']  ?? '',
            'email'      => $_SESSION['user_email'] ?? '',
            'role'       => $_SESSION['user_role']  ?? '',
            'company_id' => $_SESSION['company_id'] ?? null,
        ];
    }

    public static function companyId(): ?int {
        self::startSession();
        return isset($_SESSION['company_id']) ? (int)$_SESSION['company_id'] : null;
    }

    public static function isSystemAdmin(): bool {
        self::startSession();
        return ($_SESSION['user_role'] ?? '') === 'system_admin';
    }

    public static function isAdmin(): bool {
        self::startSession();
        return in_array($_SESSION['user_role'] ?? '', ['system_admin', 'company_admin'], true);
    }

    private static function startSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
