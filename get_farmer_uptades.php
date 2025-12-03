<?php
session_start();
include '../php/db.php'; 

if (empty($_SESSION['user']) || $_SESSION['user']['role'] != 'farmer') {
    http_response_code(403);
    echo json_encode(['error' => 'Authentication required.']);
    exit;
}

$farmer_id = $_SESSION['user']['id'];
$last_order_id = (int)($_GET['last_order_id'] ?? 0);
// *NEW*: Get the ID of the last activity we know about
$last_activity_id = (int)($_GET['last_activity_id'] ?? 0);

// 1. Get fresh stats (Unchanged)
$statsStmt = $pdo->prepare("SELECT COALESCE(SUM(oi.qty * oi.price),0) AS total_income, COALESCE(SUM(oi.qty),0) AS total_sold, COUNT(DISTINCT o.id) AS total_orders FROM order_items oi INNER JOIN orders o ON oi.order_id = o.id INNER JOIN products p ON oi.product_id = p.id WHERE p.farmer_id = ?");
$statsStmt->execute([$farmer_id]);
$fresh_stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

$today_stmt = $pdo->prepare("SELECT COUNT(DISTINCT o.id) AS today_orders, COALESCE(SUM(oi.qty * oi.price), 0) AS today_income FROM orders o JOIN order_items oi ON o.id = oi.order_id JOIN products p ON oi.product_id = p.id WHERE p.farmer_id = ? AND DATE(o.created_at) = CURDATE()");
$today_stmt->execute([$farmer_id]);
$today_stats = $today_stmt->fetch(PDO::FETCH_ASSOC);
$fresh_stats = array_merge($fresh_stats, $today_stats);


// 2. Get new orders (Unchanged)
$newOrdersStmt = $pdo->prepare("SELECT o.id AS order_id, u.name AS customer_name, p.name AS product_name, oi.qty, oi.price, (oi.qty * oi.price) AS total_price, o.created_at FROM order_items oi INNER JOIN orders o ON oi.order_id = o.id INNER JOIN users u ON o.user_id = u.id INNER JOIN products p ON oi.product_id = p.id WHERE p.farmer_id = ? AND o.id > ? ORDER BY o.id DESC");
$newOrdersStmt->execute([$farmer_id, $last_order_id]);
$new_orders = $newOrdersStmt->fetchAll(PDO::FETCH_ASSOC);


// 3. *NEW*: Get new "add to cart" activities
$activityStmt = $pdo->prepare("SELECT id, product_name, created_at FROM live_activity WHERE farmer_id = ? AND id > ? ORDER BY id DESC LIMIT 5");
$activityStmt->execute([$farmer_id, $last_activity_id]);
$new_activities = $activityStmt->fetchAll(PDO::FETCH_ASSOC);


// Prepare the final JSON response with all data
$response = [
    'stats' => $fresh_stats,
    'new_orders' => $new_orders,
    'new_activities' => $new_activities // Add new activities to the response
];

header('Content-Type: application/json');
echo json_encode($response);