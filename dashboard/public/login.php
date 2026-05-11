<?php
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/AuthService.php';
require_once __DIR__ . '/../src/UserService.php';

use TokStock\AuthService;
use TokStock\UserService;

// Redirect if already logged in
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
    } else {
        try {
            $auth = new AuthService();
            if ($auth->login($email, $password)) {
                header('Location: index.php');
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (\Exception $e) {
            $error = 'System error — please ensure the database is running.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In &mdash; Tok-Stock</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: { extend: { colors: {
          'brand': '#8A5F41', 'brand-dark': '#5C3D2A',
          'brand-mid': '#A77F60', 'brand-light': '#F3E4C9',
          'brand-bg': '#FAF7F2', 'brand-accent': '#CCD67F',
        }}}
      }
    </script>
</head>
<body class="bg-brand-bg min-h-screen flex items-center justify-center">

<div class="w-full max-w-md px-4">

    <!-- Logo -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 bg-brand rounded-xl shadow-lg mb-4">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">Tok-Stock</h1>
        <p class="text-gray-500 text-sm mt-1">Stock Management System</p>
    </div>

    <!-- Card -->
    <div class="bg-white rounded-2xl shadow-md px-8 py-8">
        <h2 class="text-lg font-semibold text-gray-800 mb-6">Sign in to your account</h2>

        <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg mb-5">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="login.php" novalidate>
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email address</label>
                <input id="email" name="email" type="email" required autocomplete="email"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm text-gray-800
                              focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition">
            </div>

            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input id="password" name="password" type="password" required autocomplete="current-password"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm text-gray-800
                              focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition">
            </div>

            <button type="submit"
                    class="w-full bg-brand hover:bg-brand-mid text-white font-semibold py-2.5 px-4 rounded-lg
                           transition-colors shadow-sm text-sm">
                Sign In
            </button>
        </form>
    </div>

    <p class="text-center text-xs text-gray-400 mt-6">
        &copy; <?= date('Y') ?> Tok-Stock &mdash; Internal Management System
    </p>
</div>

</body>
</html>
