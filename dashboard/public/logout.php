<?php
require_once __DIR__ . '/../src/AuthService.php';
TokStock\AuthService::logout();
header('Location: login.php');
exit;
