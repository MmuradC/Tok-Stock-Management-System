<?php
/**
 * Include this at the top of every protected page.
 * Handles: first-run detection → setup.php, unauthenticated → login.php
 */
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/AuthService.php';
require_once __DIR__ . '/../src/UserService.php';

use TokStock\AuthService;
use TokStock\UserService;

// If no users exist at all → first-run setup
try {
    $userSvc = new UserService();
    if ($userSvc->countAll() === 0) {
        header('Location: setup.php');
        exit;
    }
} catch (\Exception $e) {
    // DB not ready yet; let login page handle it
}

AuthService::requireAuth();

$currentUser = AuthService::currentUser();
$companyId   = AuthService::companyId();

// Fetch company name for the topbar
$companyName = 'N/A';
if ($companyId) {
    try {
        $db   = TokStock\Database::getConnection();
        $stmt = $db->prepare("SELECT name FROM companies WHERE id = :id");
        $stmt->execute(['id' => $companyId]);
        $row = $stmt->fetch();
        if ($row) {
            $companyName = $row['name'];
        }
    } catch (\Exception $e) {
        // non-fatal
    }
}
