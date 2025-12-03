<?php
session_start();
include 'php/db.php';

// Ensure user is logged in as a customer
if (empty($_SESSION['user']) || $_SESSION['user']['role'] != 'customer') {
    header("Location: login.php");
    exit;
}

// Check if an order ID is provided
if (!isset($_GET['id'])) {
    // Redirecting to orders page is more user-friendly than dying
    header("Location: orders.php");
    exit;
}

$order_id = $_GET['id'];
$user_id = $_SESSION['user']['id'];

// --- Fetch order details securely ---

// 1. Fetch the main order, ensuring it belongs to the logged-in user
$order_sql = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
$stmt = $pdo->prepare($order_sql);
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

// If order doesn't exist or doesn't belong to user, stop
if (!$order) {
    // Redirecting is better than showing an error page
    header("Location: orders.php");
    exit;
}

// 2. Fetch the items for this order
$items_sql = "SELECT oi.qty, oi.price, p.name, p.image 
              FROM order_items oi 
              JOIN products p ON oi.product_id = p.id 
              WHERE oi.order_id = ?";
$stmt = $pdo->prepare($items_sql);
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Fetch shipping details
$shipping_sql = "SELECT * FROM shipping WHERE order_id = ?";
$stmt = $pdo->prepare($shipping_sql);
$stmt->execute([$order_id]);
$shipping = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?= htmlspecialchars($order['id']) ?> Details - KisanDirect</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <style>
        /* Copied styles from orders.php for consistency */
        :root {
            --sidebar-width: 240px;
            --kd-bg: #12181b;
            --kd-bg-surface: #1a2226;
            --kd-earthy-green: #68d391;
            --kd-warm-gold: #f5b041;
            --kd-text: #e6f1ff;
            --kd-muted: #a0aec0;
            --kd-danger: #e53e3e;
            --glass-bg: rgba(26, 34, 38, 0.6);
            --glass-border: rgba(160, 174, 192, 0.2);
        }
        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'Poppins',sans-serif;
            margin: 0;
            background: var(--kd-bg);
            color: var(--kd-text);
        }
        body.sidebar-open { overflow: hidden; }

        .container { max-width:1200px; margin:auto; padding:2rem 5%; padding-top: 80px; }
        
        h1, h2 {
            font-family: 'Montserrat', sans-serif;
            text-align: center;
            margin-bottom: 2rem;
        }
        h1 { font-size: clamp(1.8rem, 4vw, 2.5rem); margin-top: 0; }
        h2 { font-size: clamp(1.5rem, 3vw, 2rem); margin-top: 3rem; }
        h1 span, h2 span { color: var(--kd-earthy-green); }

        /* Sidebar & Hamburger */
        .page-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1040; }
        body.sidebar-open .page-overlay { display: block; }
        .hamburger { display: flex; position: fixed; top: 22px; left: 30px; z-index: 1200; width: 30px; height: 24px; flex-direction: column; justify-content: space-between; cursor: pointer; transition: left 0.4s ease; }
        .hamburger span { display: block; height: 3px; width: 100%; background: var(--kd-text); border-radius: 4px; transition: transform 0.3s ease, opacity 0.3s ease; }
        body.sidebar-open .hamburger { left: calc(var(--sidebar-width) + 30px); }
        body.sidebar-open .hamburger span:nth-child(1) { transform: translateY(10.5px) rotate(45deg); }
        body.sidebar-open .hamburger span:nth-child(2) { opacity: 0; }
        body.sidebar-open .hamburger span:nth-child(3) { transform: translateY(-10.5px) rotate(-45deg); }
        .sidebar { position: fixed; top: 0; left: 0; width: var(--sidebar-width); height: 100vh; background: var(--glass-bg); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border-right: 1px solid var(--glass-border); padding-top: 80px; transform: translateX(calc(-1 * var(--sidebar-width))); transition: transform 0.4s ease; z-index: 1050; }
        body.sidebar-open .sidebar { transform: translateX(0); }
        .sidebar a { display: flex; align-items: center; gap: 15px; padding: 1rem 1.5rem; color: var(--kd-muted); font-weight: 500; text-decoration: none; transition: all 0.3s; border-left: 3px solid transparent; }
        .sidebar a:hover { background: rgba(104, 211, 145, 0.1); color: var(--kd-text); border-left-color: var(--kd-earthy-green); }
        
        /* Table Styles */
        .table-wrapper { overflow-x: auto; background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 16px; padding: 1rem; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 1rem; text-align: left; font-size: 0.95rem; white-space: nowrap; }
        table th { font-family: 'Montserrat', sans-serif; color: var(--kd-text); border-bottom: 2px solid var(--kd-earthy-green); font-weight: 700; }
        table td { color: var(--kd-muted); border-bottom: 1px solid var(--glass-border); }
        table tbody tr:last-child td { border-bottom: none; }
        table img { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; vertical-align: middle; margin-right: 15px;}
        table .product-cell { display: flex; align-items: center; }
        table .subtotal { color: var(--kd-text); font-weight: 600; }

        /* New styles for order_details page */
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .detail-box {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 1.5rem;
        }
        .detail-box h3 {
            font-family: 'Montserrat', sans-serif;
            color: var(--kd-earthy-green);
            margin-top: 0;
            margin-bottom: 1rem;
            border-bottom: 1px solid var(--glass-border);
            padding-bottom: 0.5rem;
        }
        .detail-box p {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            color: var(--kd-muted);
        }
        .detail-box p span {
            color: var(--kd-text);
            font-weight: 500;
            text-align: right;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 3rem;
        }
        .btn {
            padding: 0.8rem 1.8rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .btn-primary {
            background-color: var(--kd-earthy-green);
            color: var(--kd-bg);
        }
        .btn-primary:hover {
            background-color: transparent;
            border-color: var(--kd-earthy-green);
            color: var(--kd-earthy-green);
            transform: translateY(-2px);
        }
        .btn-secondary {
            background-color: transparent;
            color: var(--kd-muted);
            border-color: var(--glass-border);
        }
        .btn-secondary:hover {
            border-color: var(--kd-muted);
            color: var(--kd-text);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="page-overlay"></div>
<div class="hamburger"><span></span><span></span><span></span></div>
<div class="sidebar">
    <a href="user_dashboard.php">🏠 Dashboard</a>
    <a href="wishlist.php">💖 Wishlist</a>
    <a href="cart.php">🛒 Cart</a>
    <a href="orders.php">📦 Orders</a>
</div>

<main class="container">
    <h1 data-aos="fade-down">Order Details for #<span><?= htmlspecialchars($order['id']) ?></span></h1>

    <div class="details-grid">
        <div class="detail-box" data-aos="fade-right">
            <h3>Order Summary</h3>
            <p>Placed On: <span><?= date('d M Y, H:i', strtotime($order['created_at'])) ?></span></p>
            <p>Total Amount: <span>₹<?= number_format($order['total'], 2) ?></span></p>
            <p>Order Status: <span><?= htmlspecialchars(ucfirst($order['status'])) ?></span></p>
        </div>
        
        <?php if($shipping): ?>
        <div class="detail-box" data-aos="fade-left">
            <h3>Shipping Details</h3>
            <p>Recipient: <span><?= htmlspecialchars($shipping['fullname'] ?? 'N/A') ?></span></p>
            <p>Contact: <span><?= htmlspecialchars($shipping['phone'] ?? 'N/A') ?></span></p>
            <?php
                // ===== FIX: Safely build the full address string =====
                // This prevents "Undefined array key" errors if a value is missing.
                $addressParts = [];
                if (!empty($shipping['address'])) {
                    $addressParts[] = htmlspecialchars($shipping['address']);
                }
                if (!empty($shipping['city'])) {
                    $addressParts[] = htmlspecialchars($shipping['city']);
                }
                if (!empty($shipping['pincode'])) {
                    $addressParts[] = htmlspecialchars($shipping['pincode']);
                }
                $fullAddress = implode(', ', $addressParts);
            ?>
            <p>Address: <span><?= $fullAddress ?: 'Not available' ?></span></p>
        </div>
        <?php endif; ?>
    </div>

    <h2 data-aos="fade-up">Items in this <span>Order</span></h2>
    <div class="table-wrapper" data-aos="fade-up" data-aos-delay="200">
        <table class="items-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th style="text-align:right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order_items as $item): ?>
                <tr>
                    <td>
                        <div class="product-cell">
                            <img src="images/<?= htmlspecialchars($item['image'] ?: 'default.png') ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                            <span><?= htmlspecialchars($item['name']) ?></span>
                        </div>
                    </td>
                    <td><?= $item['qty'] ?></td>
                    <td>₹<?= number_format($item['price'], 2) ?></td>
                    <td class="subtotal" style="text-align:right;">₹<?= number_format($item['price'] * $item['qty'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="action-buttons" data-aos="fade-up" data-aos-delay="100">
        <a href="orders.php" class="btn btn-secondary">Back to Orders</a>
        <a href="user_dashboard.php" class="btn btn-primary">Continue Shopping</a>
    </div>

</main>

<script src="https://unpkg.com/aos@next/dist/aos.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Initialize AOS
    AOS.init({
        duration: 800,
        once: true,
    });
    
    // Hamburger Menu Toggle Logic
    const hamburger = document.querySelector('.hamburger');
    const pageOverlay = document.querySelector('.page-overlay');
    function toggleSidebar() { 
        document.body.classList.toggle('sidebar-open'); 
    }
    if (hamburger) { 
        hamburger.addEventListener('click', toggleSidebar); 
    }
    if (pageOverlay) { 
        pageOverlay.addEventListener('click', toggleSidebar); 
    }
});
</script>

<?php include 'footer.php'; ?>
</body>
</html>