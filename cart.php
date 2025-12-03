<?php
session_start();
include 'php/language_init.php';
include 'php/db.php';

if(empty($_SESSION['user'])){ header('Location: login.php'); exit; }
$user_id = $_SESSION['user']['id'];

// Handle Remove Item
if(isset($_POST['remove_id'])){
    $del = $pdo->prepare("DELETE FROM cart_items WHERE user_id=? AND product_id=?");
    $del->execute([$user_id, $_POST['remove_id']]);
    header("Location: cart.php"); exit;
}

// Fetch Cart
$stmt = $pdo->prepare("SELECT c.*, p.name, p.price, p.image FROM cart_items c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

$total = 0;
foreach($cart_items as $item) { $total += $item['price'] * $item['qty']; }
?>
<!doctype html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>My Cart — KisanX</title>
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
        /* SAME CSS AS PREVIOUSLY PROVIDED FOR CONSISTENCY */
        :root {
            --bg-body: #0d1117;
            --bg-card: #161b22;
            --border-color: #30363d;
            --accent-green: #4ade80;
            --accent-hover: #22c55e;
            --text-main: #ffffff;
            --text-muted: #8b949e;
            --danger: #ef4444;
            --shadow: 0 4px 20px rgba(0,0,0,0.4);
        }
        html.light-mode {
            --bg-body: #f3f4f6; --bg-card: #ffffff; --border-color: #e5e7eb;
            --accent-green: #10b981; --accent-hover: #059669;
            --text-main: #111827; --text-muted: #6b7280; --shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        body { margin: 0; font-family: 'Poppins', sans-serif; background: var(--bg-body); color: var(--text-main); transition: background 0.3s; min-height: 100vh; display: flex; flex-direction: column; }
        .container { max-width: 1100px; margin: 0 auto; padding: 40px 20px; flex: 1; }
        .page-header { text-align: center; margin-bottom: 50px; margin-top: 10px; }
        .page-header h1 { font-size: 2.5rem; font-weight: 700; margin: 0; }
        .highlight-green { color: var(--accent-green); }
        .cart-layout { display: grid; grid-template-columns: 1fr 340px; gap: 30px; align-items: start; }
        @media (max-width: 900px) { .cart-layout { grid-template-columns: 1fr; } }
        .cart-item { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 20px; box-shadow: var(--shadow); }
        .cart-item img { width: 90px; height: 90px; object-fit: cover; border-radius: 8px; background: #000; }
        .item-details { flex: 1; }
        .item-name { font-size: 1.1rem; font-weight: 600; display: block; margin-bottom: 5px; }
        .item-meta { color: var(--text-muted); font-size: 0.9rem; }
        .item-total-price { font-weight: 700; font-size: 1.1rem; margin-top: 5px; }
        .remove-btn { background: transparent; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.5rem; }
        .remove-btn:hover { color: var(--danger); }
        .summary-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; position: sticky; top: 20px; box-shadow: var(--shadow); }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 15px; color: var(--text-muted); }
        .total-row { color: var(--text-main); font-weight: 700; font-size: 1.3rem; border-top: 1px solid var(--border-color); padding-top: 20px; }
        .total-amount { color: var(--accent-green); }
        
        /* LINK BUTTON STYLE */
        .checkout-btn {
            display: block; text-align: center; text-decoration: none;
            width: 100%; background: var(--accent-green); color: #000;
            padding: 15px; border-radius: 8px; font-weight: 700;
            margin-top: 20px; transition: 0.3s; box-sizing: border-box;
        }
        .checkout-btn:hover { background: var(--accent-hover); transform: translateY(-2px); }
        .empty-container { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 80px 20px; text-align: center; max-width: 800px; margin: 0 auto; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <main class="container">
        <div class="page-header"><h1><span class="highlight-green">My</span> Cart</h1></div>
        <?php if(empty($cart_items)): ?>
            <div class="empty-container">
                <h2>Your cart is empty.</h2>
                <a href="user_dashboard.php" style="color:var(--accent-green);">Start Shopping</a>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <div class="cart-items-list">
                    <?php foreach($cart_items as $item): ?>
                        <div class="cart-item">
                            <img src="images/<?php echo htmlspecialchars($item['image']); ?>">
                            <div class="item-details">
                                <span class="item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                <div class="item-meta">Qty: <?php echo $item['qty']; ?></div>
                                <div class="item-total-price">₹<?php echo number_format($item['price'] * $item['qty'], 2); ?></div>
                            </div>
                            <form method="POST"><input type="hidden" name="remove_id" value="<?php echo $item['product_id']; ?>"><button class="remove-btn">&times;</button></form>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="cart-summary">
                    <div class="summary-card">
                        <h3>Order Summary</h3>
                        <div class="summary-row"><span>Subtotal</span><span>₹<?php echo number_format($total, 2); ?></span></div>
                        <div class="summary-row total-row"><span>Total</span><span class="total-amount">₹<?php echo number_format($total, 2); ?></span></div>
                        <a href="checkout.php" class="checkout-btn">Proceed to Checkout</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
    <?php include 'footer.php'; ?>
</body>
</html>