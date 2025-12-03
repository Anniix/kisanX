<?php
session_start();
// The '../' is important because this file is inside the 'api' folder
include '../php/db.php'; 

// Set the response header to JSON
header('Content-Type: application/json');

// --- 1. Security and Validation ---

// Check if user is logged in as a customer
if (empty($_SESSION['user']) || $_SESSION['user']['role'] != 'customer') {
    // Not logged in, send error
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit;
}

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Not a POST request, send error
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Check if the required data was sent
if (!isset($_POST['product_id']) || !isset($_POST['qty'])) {
    // Missing data, send error
    echo json_encode(['success' => false, 'message' => 'Missing product ID or quantity.']);
    exit;
}

// --- 2. Sanitize and Process Input ---

$user_id = $_SESSION['user']['id'];
$product_id = filter_var($_POST['product_id'], FILTER_VALIDATE_INT);
$qty = filter_var($_POST['qty'], FILTER_VALIDATE_INT);

// Validate the sanitized data
if ($product_id === false || $qty === false || $product_id <= 0 || $qty <= 0) {
    // Invalid data, send error
    echo json_encode(['success' => false, 'message' => 'Invalid product ID or quantity. Quantity must be at least 1.']);
    exit;
}

// --- 3. Update Database ---

try {
    // Prepare the SQL statement to update the quantity for the specific user and product
    $stmt = $pdo->prepare("UPDATE cart_items SET qty = ? WHERE user_id = ? AND product_id = ?");
    
    // Execute the statement
    $success = $stmt->execute([$qty, $user_id, $product_id]);

    if ($success) {
        // If the update was successful, send a success response
        echo json_encode(['success' => true, 'message' => 'Cart updated successfully.']);
    } else {
        // If the update failed for some reason
        echo json_encode(['success' => false, 'message' => 'Failed to update cart in the database.']);
    }

} catch (Exception $e) {
    // If there was a database error, send a server error message
    // In a real production environment, you would log this error instead of showing it
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

?>