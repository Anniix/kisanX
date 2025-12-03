<?php
session_start();
include 'php/language_init.php';
include 'php/db.php';

// 1. Force Error Reporting (For debugging)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if(empty($_SESSION['user'])){ header('Location: login.php'); exit; }
$user_id = $_SESSION['user']['id'];

// 2. Fetch Cart Items
$stmt = $pdo->prepare("SELECT c.*, p.price FROM cart_items c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

// If cart empty, go back
if(empty($cart_items) && !isset($_POST['place_order'])) { header("Location: cart.php"); exit; }

$total = 0;
foreach($cart_items as $item) { $total += $item['price'] * $item['qty']; }

// 3. Handle Order Submission
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])){
    $name = $_POST['full_name'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $zip = $_POST['zip'];
    $payment = $_POST['payment_method']; 

    try {
        $pdo->beginTransaction();

        // A. INSERT INTO orders
        // Note: We use 'total_amount' and 'zip_code' to match your specific SQL
        $sql_order = "INSERT INTO orders (user_id, total_amount, full_name, address, city, zip_code, payment_method, status) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
        $stmt = $pdo->prepare($sql_order);
        $stmt->execute([$user_id, $total, $name, $address, $city, $zip, $payment]);
        $order_id = $pdo->lastInsertId();

        // B. INSERT INTO order_items
        $stmt_item = $pdo->prepare("INSERT INTO order_items (order_id, product_id, qty, price) VALUES (?, ?, ?, ?)");
        foreach($cart_items as $item){
            // Note: using 'qty' column based on your SQL
            $stmt_item->execute([$order_id, $item['product_id'], $item['qty'], $item['price']]);
        }

        // C. INSERT INTO deliveries (Fixes "Not Tracking" issue)
        // We initialize the delivery status as 'Preparing' and set default coordinates (e.g., Farmer's location or 0,0)
        $stmt_del = $pdo->prepare("INSERT INTO deliveries (order_id, status, lat, lng) VALUES (?, 'Preparing', 28.7041, 77.1025)");
        $stmt_del->execute([$order_id]);

        // D. CLEAR Cart
        $pdo->prepare("DELETE FROM cart_items WHERE user_id=?")->execute([$user_id]);

        $pdo->commit();
        
        // Success!
        $_SESSION['popup_message'] = "Order #$order_id placed successfully!";
        header("Location: my_orders.php"); // Redirect to Orders page to see it
        exit;

    } catch(Exception $e) {
        $pdo->rollBack();
        // SHOW THE ERROR ON SCREEN SO WE KNOW WHY IT FAILED
        die("<div style='background:red; color:white; padding:20px;'>SQL Error: " . $e->getMessage() . "</div>");
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Checkout — KisanX</title>
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
            --bg-body: #0d1117; --bg-card: #161b22; --border-color: #30363d;
            --accent-green: #4ade80; --accent-hover: #22c55e;
            --text-main: #ffffff; --text-muted: #8b949e; --input-bg: #0d1117;
        }
        html.light-mode {
            --bg-body: #f3f4f6; --bg-card: #ffffff; --border-color: #e5e7eb;
            --accent-green: #10b981; --accent-hover: #059669;
            --text-main: #111827; --text-muted: #6b7280; --input-bg: #f9fafb;
        }
        body { margin: 0; font-family: 'Poppins', sans-serif; background: var(--bg-body); color: var(--text-main); }
        .container { max-width: 1100px; margin: 0 auto; padding: 40px 20px; }
        .page-header { text-align: center; margin-bottom: 40px; }
        .checkout-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 40px; }
        @media(max-width: 800px){ .checkout-grid { grid-template-columns: 1fr; } }
        .card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 30px; }
        h3 { margin-top: 0; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; color: var(--text-muted); margin-bottom: 8px; font-size: 0.9rem; }
        .form-control { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-main); box-sizing: border-box; }
        .row { display: flex; gap: 20px; } .col { flex: 1; }
        .payment-options { display: flex; gap: 15px; margin-bottom: 20px; }
        .payment-radio { display: none; }
        .payment-label { flex: 1; padding: 15px; border: 1px solid var(--border-color); border-radius: 8px; cursor: pointer; text-align: center; font-weight: 600; transition: 0.3s; }
        .payment-radio:checked + .payment-label { background: rgba(74, 222, 128, 0.1); border-color: var(--accent-green); color: var(--accent-green); }
        .place-order-btn { width: 100%; background: var(--accent-green); color: #000; padding: 15px; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 1rem; }
        .place-order-btn:hover { background: var(--accent-hover); }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 10px; color: var(--text-muted); }
        .total { font-size: 1.4rem; color: var(--text-main); font-weight: 700; border-top: 1px solid var(--border-color); padding-top: 15px; margin-top: 15px; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="container">
        <div class="page-header"><h1>Checkout</h1></div>
        <form method="POST" class="checkout-grid">
            <div class="card">
                <h3>Shipping Details</h3>
                <div class="form-group"><label>Full Name</label><input type="text" name="full_name" class="form-control" required value="<?php echo htmlspecialchars($_SESSION['user']['name'] ?? ''); ?>"></div>
                <div class="form-group"><label>Address</label><input type="text" name="address" class="form-control" placeholder="House No, Street Name" required></div>
                <div class="row">
                    <div class="col form-group"><label>City</label><input type="text" name="city" class="form-control" required></div>
                    <div class="col form-group"><label>Zip Code</label><input type="text" name="zip" class="form-control" required></div>
                </div>
                <h3 style="margin-top: 30px;">Payment Method</h3>
                <div class="payment-options">
                    <input type="radio" name="payment_method" value="cod" id="cod" class="payment-radio" checked><label for="cod" class="payment-label">Cash on Delivery</label>
                    <input type="radio" name="payment_method" value="card" id="card" class="payment-radio"><label for="card" class="payment-label">Online Payment</label>
                </div>
            </div>
            <div class="card" style="height: fit-content;">
                <h3>Order Summary</h3>
                <div class="summary-row"><span>Items Total</span><span>₹<?php echo number_format($total, 2); ?></span></div>
                <div class="summary-row"><span>Shipping</span><span style="color: var(--accent-green);">Free</span></div>
                <div class="summary-row total"><span>Total Pay</span><span style="color: var(--accent-green);">₹<?php echo number_format($total, 2); ?></span></div>
                <button type="submit" name="place_order" class="place-order-btn">Place Order</button>
            </div>
        </form>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>