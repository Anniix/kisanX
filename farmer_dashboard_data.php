<?php
session_start();
include 'php/db.php';

// Ensure user is logged in as farmer
if (empty($_SESSION['user']) || $_SESSION['user']['role'] != 'farmer') {
    header('Content-Type: application/json');
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$farmer_id = $_SESSION['user']['id'];

/* ===== DASHBOARD STATS ===== */
$statsStmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(oi.qty * oi.price),0) AS total_income,
        COALESCE(SUM(oi.qty),0) AS total_sold,
        COUNT(DISTINCT o.id) AS total_orders
    FROM order_items oi
    INNER JOIN orders o ON oi.order_id = o.id
    INNER JOIN products p ON oi.product_id = p.id
    WHERE p.farmer_id = ?
");
$statsStmt->execute([$farmer_id]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

/* ===== RECENT ORDERS ===== */
$ordersStmt = $pdo->prepare("
    SELECT o.id AS order_id, u.name AS customer_name, p.name AS product_name,
           oi.qty, oi.price, (oi.qty * oi.price) AS total_price, o.created_at
    FROM order_items oi
    INNER JOIN orders o ON oi.order_id = o.id
    INNER JOIN users u ON o.user_id = u.id
    INNER JOIN products p ON oi.product_id = p.id
    WHERE p.farmer_id = ?
    ORDER BY o.id DESC
    LIMIT 10
");
$ordersStmt->execute([$farmer_id]);
$recent_orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);

/* ===== SALES ANALYTICS ===== */
$salesStmt = $pdo->prepare("
    SELECT c.name AS category, COALESCE(SUM(oi.qty * oi.price),0) AS sales
    FROM order_items oi
    INNER JOIN products p ON oi.product_id = p.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.farmer_id = ?
    GROUP BY c.name
");
$salesStmt->execute([$farmer_id]);
$salesData = $salesStmt->fetchAll(PDO::FETCH_ASSOC);

/* ===== RETURN JSON ===== */
header('Content-Type: application/json');
echo json_encode([
    "stats" => $stats,
    "recent_orders" => $recent_orders,
    "sales" => $salesData
]);