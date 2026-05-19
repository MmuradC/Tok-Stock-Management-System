<?php
require_once __DIR__ . '/auth_check.php';

use TokStock\AuthService;
use TokStock\Database;

$db     = Database::getConnection();
$userId = $currentUser['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: orders.php');
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $customerName  = trim($_POST['customer_name'] ?? '');
    $customerEmail = trim($_POST['customer_email'] ?? '') ?: null;
    $notes         = trim($_POST['notes'] ?? '') ?: null;

    if (empty($customerName)) {
        header('Location: orders.php?error=' . urlencode('Customer name is required.'));
        exit;
    }

    $productIds = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity']   ?? [];

    // Filter out rows where no product was selected
    $items = [];
    foreach ($productIds as $i => $pid) {
        $pid = (int)$pid;
        $qty = max(1, (int)($quantities[$i] ?? 1));
        if ($pid > 0) {
            $items[] = ['product_id' => $pid, 'quantity' => $qty];
        }
    }

    try {
        $db->beginTransaction();

        // Resolve company_id for the order
        if (AuthService::isSystemAdmin() && !empty($_POST['company_id'])) {
            $orderCompanyId = (int)$_POST['company_id'];
        } else {
            $orderCompanyId = $companyId;
        }
        if (!$orderCompanyId) {
            throw new \RuntimeException('Could not determine company for this order.');
        }

        // Calculate total from current sale prices
        $total = 0.0;
        $priceStmt = $db->prepare("SELECT price_sale FROM products WHERE id = :pid AND company_id = :cid");
        foreach ($items as &$item) {
            $priceStmt->execute([':pid' => $item['product_id'], ':cid' => $orderCompanyId]);
            $price = (float)$priceStmt->fetchColumn();
            $item['price_at_sale'] = $price;
            $total += $price * $item['quantity'];
        }
        unset($item);

        // Insert order
        $stmt = $db->prepare(
            "INSERT INTO orders (company_id, customer_name, customer_email, status, total_amount, notes, created_by)
             VALUES (:cid, :name, :email, 'pending', :total, :notes, :uid)"
        );
        $stmt->execute([
            ':cid'   => $orderCompanyId,
            ':name'  => $customerName,
            ':email' => $customerEmail,
            ':total' => $total,
            ':notes' => $notes,
            ':uid'   => $userId,
        ]);
        $orderId = (int)$db->lastInsertId();

        // Insert order items, deduct stock, and log each movement
        if (!empty($items)) {
            $itemStmt = $db->prepare(
                "INSERT INTO order_items (order_id, product_id, quantity, price_at_sale)
                 VALUES (:oid, :pid, :qty, :price)"
            );
            $stockStmt = $db->prepare(
                "UPDATE products SET stock_quantity = stock_quantity - :qty
                 WHERE id = :pid AND company_id = :cid"
            );
            $logStmt = $db->prepare(
                "INSERT INTO stock_logs (company_id, product_id, change_amount, action_type, user_id, notes)
                 VALUES (:cid, :pid, :qty, 'OUT', :uid, :notes)"
            );
            foreach ($items as $item) {
                $itemStmt->execute([
                    ':oid'   => $orderId,
                    ':pid'   => $item['product_id'],
                    ':qty'   => $item['quantity'],
                    ':price' => $item['price_at_sale'],
                ]);
                $stockStmt->execute([
                    ':qty' => $item['quantity'],
                    ':pid' => $item['product_id'],
                    ':cid' => $orderCompanyId,
                ]);
                $logStmt->execute([
                    ':cid'   => $orderCompanyId,
                    ':pid'   => $item['product_id'],
                    ':qty'   => $item['quantity'],
                    ':uid'   => $userId,
                    ':notes' => 'Order #' . $orderId,
                ]);
            }
        }

        $db->commit();
        header('Location: orders.php?msg=created');
        exit;

    } catch (\Exception $e) {
        $db->rollBack();
        header('Location: orders.php?error=' . urlencode($e->getMessage()));
        exit;
    }
}

if ($action === 'update_status') {
    $id     = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';

    if ($id <= 0 || !in_array($status, ['pending', 'completed', 'cancelled'])) {
        header('Location: orders.php');
        exit;
    }

    try {
        $sql = $companyId
            ? "UPDATE orders SET status = :status WHERE id = :id AND company_id = :cid"
            : "UPDATE orders SET status = :status WHERE id = :id";
        $stmt = $db->prepare($sql);
        $params = [':status' => $status, ':id' => $id];
        if ($companyId) $params[':cid'] = $companyId;
        $stmt->execute($params);

        header('Location: orders.php?msg=updated');
        exit;
    } catch (\Exception $e) {
        header('Location: orders.php?error=' . urlencode($e->getMessage()));
        exit;
    }
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        header('Location: orders.php');
        exit;
    }

    try {
        $sql = $companyId
            ? "DELETE FROM orders WHERE id = :id AND company_id = :cid"
            : "DELETE FROM orders WHERE id = :id";
        $stmt = $db->prepare($sql);
        $params = [':id' => $id];
        if ($companyId) $params[':cid'] = $companyId;
        $stmt->execute($params);

        header('Location: orders.php?msg=deleted');
        exit;
    } catch (\Exception $e) {
        header('Location: orders.php?error=' . urlencode($e->getMessage()));
        exit;
    }
}

header('Location: orders.php');
exit;