<?php
session_start();
include '../php/db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (empty($_SESSION['user']) || $_SESSION['user']['role'] != 'customer') {
    http_response_code(403);
    echo json_encode(['message' => 'Please log in to add items to your cart.']);
    exit;
}

$user_id = $_SESSION['user']['id'];
$product_id = (int)($_POST['product_id'] ?? 0);
$qty = (int)($_POST['qty'] ?? 0);

if ($product_id <= 0 || $qty <= 0) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid product or quantity.']);
    exit;
}

// Check current cart
$stmt = $pdo->prepare("SELECT * FROM cart_items WHERE user_id=? AND product_id=?");
$stmt->execute([$user_id, $product_id]);
$existing = $stmt->fetch();

if ($existing) {
    // Update quantity
    $updateStmt = $pdo->prepare("UPDATE cart_items SET qty = qty + ? WHERE id=?");
    $updateStmt->execute([$qty, $existing['id']]);
} else {
    // Insert new item
    $insertStmt = $pdo->prepare("INSERT INTO cart_items (user_id, product_id, qty) VALUES (?,?,?)");
    $insertStmt->execute([$user_id, $product_id, $qty]);
}

// ===== NEW CODE TO LOG ACTIVITY FOR THE FARMER =====
try {
    // Find the farmer_id and product name for the product that was added
    $productStmt = $pdo->prepare("SELECT farmer_id, name FROM products WHERE id = ?");
    $productStmt->execute([$product_id]);
    $product_info = $productStmt->fetch(PDO::FETCH_ASSOC);

    if ($product_info) {
        // Insert a record into our new live_activity table
        $activityStmt = $pdo->prepare("INSERT INTO live_activity (farmer_id, product_name) VALUES (?, ?)");
        $activityStmt->execute([$product_info['farmer_id'], $product_info['name']]);
    }
} catch (PDOException $e) {
    // Fail silently if activity logging fails, the cart action is more important.
    // You could log this error to a file in a real production environment.
}
// ===== END OF NEW CODE =====


echo json_encode(['message' => 'Product added to cart!']);