<?php
session_start();
include 'php/language_init.php';
include 'php/db.php';

// Redirect if not logged in
if(empty($_SESSION['user'])){ header('Location: login.php'); exit; }

$user_id = $_SESSION['user']['id'];
$role = $_SESSION['user']['role'];

// LOGIC: 
// If Farmer: Show orders containing THEIR products.
// If Customer: Redirect to my_orders.php (or show own orders)
if($role == 'customer'){
    header("Location: my_orders.php");
    exit;
}

// Handle Status Update (Farmer can update status)
if(isset($_POST['update_status'])){
    $oid = $_POST['order_id'];
    $status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $oid]);
    
    // Update Tracking table if it exists
    $stmt_track = $pdo->prepare("UPDATE deliveries SET status = ? WHERE order_id = ?");
    $status_msg = "Order is " . ucfirst(str_replace('_', ' ', $status));
    $stmt_track->execute([$status_msg, $oid]);
    
    $_SESSION['popup_message'] = "Order #$oid status updated!";
    header("Location: orders.php"); exit;
}

// FETCH ORDERS FOR FARMER
// This is a bit complex: We need orders that contain products belonging to this farmer
$sql = "SELECT DISTINCT o.*, u.name as customer_name 
        FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id 
        JOIN products p ON oi.product_id = p.id 
        JOIN users u ON o.user_id = u.id
        WHERE p.farmer_id = ? 
        ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();
?>
<!doctype html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Manage Orders — KisanX</title>
    
    <script>
    (function() {
        try {
            const theme = localStorage.getItem('theme');
            if (theme === 'light') { document.documentElement.classList.add('light-mode'); }
        } catch (e) {}
    })();
    </script>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #0d1117;
            --bg-card: #161b22;
            --border-color: #30363d;
            --accent-green: #4ade80;
            --text-main: #ffffff;
            --text-muted: #8b949e;
            --shadow: 0 4px 20px rgba(0,0,0,0.4);
            
            --status-pending: #fbbf24; 
            --status-transit: #60a5fa; 
            --status-delivered: #4ade80; 
            --status-cancelled: #ef4444; 
        }
        html.light-mode {
            --bg-body: #f3f4f6; --bg-card: #ffffff; --border-color: #e5e7eb;
            --accent-green: #10b981; --text-main: #111827; --text-muted: #6b7280;
            --shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        body { margin: 0; font-family: 'Poppins', sans-serif; background: var(--bg-body); color: var(--text-main); min-height: 100vh; display: flex; flex-direction: column; }
        .container { max-width: 1100px; margin: 0 auto; padding: 40px 20px; flex: 1; }

        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; margin-top: 20px; }
        .page-header h1 { font-size: 2rem; font-weight: 700; margin: 0; }
        .badge { background: var(--accent-green); color: #000; padding: 5px 15px; border-radius: 20px; font-size: 0.9rem; font-weight: 600; }

        /* Order Card */
        .order-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
        }

        .order-header {
            background: rgba(255,255,255,0.03);
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
        }

        .customer-info h4 { margin: 0; font-size: 1.1rem; font-weight: 600; }
        .meta-info { font-size: 0.85rem; color: var(--text-muted); margin-top: 4px; }

        .order-body { padding: 20px; }
        
        .order-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 10px 0; border-bottom: 1px dashed var(--border-color);
        }
        .order-item:last-child { border-bottom: none; }

        .order-footer {
            padding: 20px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(0,0,0,0.2);
        }

        /* Status Dropdown Styling */
        .status-form { display: flex; gap: 10px; align-items: center; }
        select.status-select {
            background: var(--bg-body);
            color: var(--text-main);
            border: 1px solid var(--border-color);
            padding: 8px 12px;
            border-radius: 6px;
            font-family: inherit;
        }
        .btn-update {
            background: var(--accent-green);
            color: #000;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        .price-tag { font-size: 1.2rem; font-weight: 700; color: var(--accent-green); }
        
        .empty-state { text-align: center; padding: 60px; color: var(--text-muted); border: 1px dashed var(--border-color); border-radius: 12px; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <main class="container">
        <div class="page-header">
            <h1>Incoming Orders</h1>
            <span class="badge"><?php echo count($orders); ?> Pending</span>
        </div>

        <?php if(empty($orders)): ?>
            <div class="empty-state">
                <h2>No orders yet 🌾</h2>
                <p>When customers buy your products, they will appear here.</p>
            </div>
        <?php else: ?>
            <?php foreach($orders as $order): 
                // Fetch items specifically for this farmer in this order
                $stmt_items = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ? AND p.farmer_id = ?");
                $stmt_items->execute([$order['id'], $user_id]);
                $items = $stmt_items->fetchAll();
            ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="customer-info">
                            <h4>Order #<?php echo $order['id']; ?> — <?php echo htmlspecialchars($order['customer_name']); ?></h4>
                            <div class="meta-info">
                                Date: <?php echo date('d M Y', strtotime($order['created_at'])); ?> | 
                                Payment: <?php echo strtoupper($order['payment_method']); ?>
                            </div>
                            <div class="meta-info">
                                📍 <?php echo htmlspecialchars($order['address'] . ', ' . $order['city']); ?>
                            </div>
                        </div>
                        
                        <form method="POST" class="status-form">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <select name="status" class="status-select">
                                <option value="pending" <?php if($order['status']=='pending') echo 'selected'; ?>>Pending</option>
                                <option value="dispatched" <?php if($order['status']=='dispatched') echo 'selected'; ?>>Dispatched</option>
                                <option value="in_transit" <?php if($order['status']=='in_transit') echo 'selected'; ?>>In Transit</option>
                                <option value="delivered" <?php if($order['status']=='delivered') echo 'selected'; ?>>Delivered</option>
                                <option value="cancelled" <?php if($order['status']=='cancelled') echo 'selected'; ?>>Cancelled</option>
                            </select>
                            <button type="submit" name="update_status" class="btn-update">Update</button>
                        </form>
                    </div>
                    
                    <div class="order-body">
                        <?php foreach($items as $item): ?>
                            <div class="order-item">
                                <span><?php echo htmlspecialchars($item['name']); ?> <span style="color:var(--text-muted);">x<?php echo $item['qty']; ?></span></span>
                                <span>₹<?php echo number_format($item['price'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="order-footer">
                        <div>Status: <strong style="color: var(--status-<?php echo $order['status']; ?>)"><?php echo ucfirst(str_replace('_',' ', $order['status'])); ?></strong></div>
                        <div class="price-tag">Total: ₹<?php echo number_format($order['total_amount'], 2); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>