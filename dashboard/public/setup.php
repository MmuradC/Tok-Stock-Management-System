<?php
/**
 * First-run setup wizard.
 * Accessible only when zero users exist in the database.
 * Creates the initial System Administrator account.
 */
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/UserService.php';

use TokStock\UserService;
use TokStock\Database;

$error   = null;
$success = false;

try {
    $userSvc = new UserService();

    // Block access if users already exist
    if ($userSvc->countAll() > 0) {
        header('Location: login.php');
        exit;
    }
} catch (\Exception $e) {
    $error = 'Database not reachable: ' . $e->getMessage();
}

if (!$error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        try {
            // Ensure the default company exists
            $db   = Database::getConnection();
            $row  = $db->query("SELECT id FROM companies LIMIT 1")->fetch();
            if (!$row) {
                $db->exec("INSERT INTO companies (name) VALUES ('Tok-Stock Inc.')");
            }

            $userSvc->createUser([
                'company_id' => null,   // system_admin has no company
                'name'       => $name,
                'email'      => $email,
                'password'   => $password,
                'role'       => 'system_admin',
            ]);
            $success = true;
        } catch (\Exception $e) {
            $error = 'Could not create account: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>First-Run Setup &mdash; Tok-Stock</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{colors:{
        'brand':'#8A5F41','brand-dark':'#5C3D2A','brand-mid':'#A77F60',
        'brand-light':'#F3E4C9','brand-bg':'#FAF7F2','brand-accent':'#CCD67F'
    }}}}</script>
</head>
<body class="bg-brand-bg min-h-screen flex items-center justify-center">
<div class="w-full max-w-md px-4">

    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 bg-brand rounded-xl shadow-lg mb-4">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">Tok-Stock Setup</h1>
        <p class="text-gray-500 text-sm mt-1">Create your System Administrator account</p>
    </div>

    <div class="bg-white rounded-2xl shadow-md px-8 py-8">

        <?php if ($success): ?>
        <div class="text-center">
            <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h2 class="text-lg font-semibold text-gray-800 mb-2">Setup Complete!</h2>
            <p class="text-gray-500 text-sm mb-6">Your administrator account has been created.</p>
            <a href="login.php"
               class="inline-block bg-brand hover:bg-brand-mid text-white font-semibold py-2.5 px-6 rounded-lg text-sm transition-colors">
                Go to Sign In
            </a>
        </div>

        <?php else: ?>
        <h2 class="text-lg font-semibold text-gray-800 mb-2">Administrator Account</h2>
        <p class="text-sm text-gray-500 mb-6">This wizard runs only once. You can create company accounts after signing in.</p>

        <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg mb-5">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                <input name="name" type="text" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <input name="email" type="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-gray-400 font-normal">(min 8 chars)</span></label>
                <input name="password" type="password" required minlength="8"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                <input name="confirm" type="password" required minlength="8"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
            </div>
            <button type="submit"
                    class="w-full bg-brand hover:bg-brand-mid text-white font-semibold py-2.5 px-4 rounded-lg text-sm transition-colors shadow-sm">
                Create Account &amp; Finish Setup
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
