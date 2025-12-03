<?php
session_start();
include 'php/language_init.php';
include 'php/db.php';

if(empty($_SESSION['user']) || $_SESSION['user']['role']!='customer'){ header('Location: login.php'); exit; }
$user_id = $_SESSION['user']['id'];

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if(isset($_POST['wishlist_action'])){
        $pid = (int)($_POST['product_id'] ?? 0);
        if($pid){
            if(!isset($_SESSION['wishlist'])) $_SESSION['wishlist'] = [];
            if($_POST['wishlist_action']==='add') { $_SESSION['wishlist'][$pid]=time(); $_SESSION['popup_message'] = $lang['toast_wishlist_add']; }
            if($_POST['wishlist_action']==='remove') { unset($_SESSION['wishlist'][$pid]); $_SESSION['popup_message'] = $lang['toast_wishlist_remove']; }
        }
    }
    if(isset($_POST['cart_action']) && $_POST['cart_action'] === 'add'){
        $product_id = (int)($_POST['product_id'] ?? 0);
        $qty = (int)($_POST['qty'] ?? 1);
        if($product_id > 0 && $qty > 0){
            $stmt = $pdo->prepare("SELECT * FROM cart_items WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            $existing_item = $stmt->fetch();
            if($existing_item){
                $new_qty = $existing_item['qty'] + $qty;
                $updateStmt = $pdo->prepare("UPDATE cart_items SET qty = ? WHERE user_id = ? AND product_id = ?");
                $updateStmt->execute([$new_qty, $user_id, $product_id]);
                $_SESSION['popup_message'] = $lang['toast_cart_update'];
            } else {
                $insertStmt = $pdo->prepare("INSERT INTO cart_items (user_id, product_id, qty) VALUES (?, ?, ?)");
                $insertStmt->execute([$user_id, $product_id, $qty]);
                $_SESSION['popup_message'] = $lang['toast_cart_add'];
            }
        }
    }
    if(isset($_POST['cart_action']) && $_POST['cart_action'] === 'remove'){
        $pid = (int)($_POST['product_id'] ?? 0);
        if($pid){ $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id=? AND product_id=?"); $stmt->execute([$user_id, $pid]); }
    }
    header('Location: user_dashboard.php'); exit;
}

$popup_message = '';
if(isset($_SESSION['popup_message'])){ $popup_message = $_SESSION['popup_message']; unset($_SESSION['popup_message']); }

$search = trim($_GET['q'] ?? '');
$category_filter = (int)($_GET['category'] ?? 0);
$params = []; $where = ' WHERE 1=1 ';
if($search){ $where .= ' AND (p.name LIKE ? OR p.description LIKE ?) '; $params[]="%$search%"; $params[]="%$search%"; }
if($category_filter){ $where .= ' AND p.category_id = ? '; $params[] = $category_filter; }

$sql = "SELECT p.*, c.name as category FROM products p LEFT JOIN categories c ON p.category_id=c.id $where ORDER BY p.created_at DESC LIMIT 60";
$stmt = $pdo->prepare($sql); $stmt->execute($params); $products = $stmt->fetchAll();

$bestStmt = $pdo->query("SELECT p.*, c.name as category, COALESCE(SUM(oi.qty),0) as total_sold FROM products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN order_items oi ON p.id = oi.product_id GROUP BY p.id ORDER BY total_sold DESC LIMIT 8");
$bestSellers = $bestStmt->fetchAll();

$cats = $pdo->query('SELECT * FROM categories')->fetchAll();

$ordersStmt = $pdo->prepare('SELECT o.*, COALESCE(SUM(oi.qty),0) as items FROM orders o LEFT JOIN order_items oi ON o.id=oi.order_id WHERE o.user_id=? GROUP BY o.id ORDER BY o.created_at DESC');
$ordersStmt->execute([$user_id]); $orders = $ordersStmt->fetchAll();
?>
<!doctype html>
<html lang="<?php echo $current_lang; ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo $lang['user_dashboard_title']; ?></title>
<script>
(function() {
    try {
        const theme = localStorage.getItem('theme');
        if (theme === 'light') { document.documentElement.classList.add('light-mode'); }
    } catch (e) {}
})();
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
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
        --card-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    }
    
    /* ===== PREMIUM LIGHT MODE ===== */
    html.light-mode {
        --kd-bg: #F3F4F6;
        --kd-bg-surface: #FFFFFF;
        --kd-earthy-green: #059669;
        --kd-warm-gold: #D97706;
        --kd-text: #111827;
        --kd-muted: #6B7280;
        --kd-danger: #DC2626;
        --glass-bg: rgba(255, 255, 255, 0.85);
        --glass-border: rgba(0, 0, 0, 0.06);
        --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    }

    *, *::before, *::after { box-sizing: border-box; }
    body { margin: 0; font-family: 'Poppins', sans-serif; background: var(--kd-bg); color: var(--kd-text); -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; overflow-x: hidden; }
    body.sidebar-open { overflow: hidden; }

    .page-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1040; }
    body.sidebar-open .page-overlay { display: block; }
    
    .hamburger { display: flex; position: fixed; top: 22px; left: 30px; z-index: 1200; width: 30px; height: 24px; flex-direction: column; justify-content: space-between; cursor: pointer; transition: left 0.4s ease; }
    .hamburger span { display: block; height: 3px; width: 100%; background: var(--kd-text); border-radius: 4px; transition: transform 0.3s ease, opacity 0.3s ease; }
    body.sidebar-open .hamburger { left: calc(var(--sidebar-width) + 30px); }
    body.sidebar-open .hamburger span:nth-child(1) { transform: translateY(10.5px) rotate(45deg); }
    body.sidebar-open .hamburger span:nth-child(2) { opacity: 0; }
    body.sidebar-open .hamburger span:nth-child(3) { transform: translateY(-10.5px) rotate(-45deg); }

    .sidebar { position: fixed; top: 0; left: 0; width: var(--sidebar-width); height: 100vh; background: var(--glass-bg); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border-right: 1px solid var(--glass-border); padding-top: 80px; transform: translateX(calc(-1 * var(--sidebar-width))); transition: transform 0.4s ease; z-index: 1050; box-shadow: var(--card-shadow); }
    body.sidebar-open .sidebar { transform: translateX(0); }
    .sidebar a { display: flex; align-items: center; gap: 15px; padding: 1rem 1.5rem; color: var(--kd-muted); font-weight: 500; text-decoration: none; transition: all 0.3s; border-left: 3px solid transparent; }
    .sidebar a:hover { background: rgba(104, 211, 145, 0.1); color: var(--kd-text); border-left-color: var(--kd-earthy-green); }
    html.light-mode .sidebar a:hover { background: rgba(5, 150, 105, 0.08); }
    
    header.sticky-header { position: sticky; top: 0; z-index: 1000; transition: transform 0.4s ease-out; }
    header.sticky-header.header-hidden { transform: translateY(-100%); }
    header .logo { padding-left: 50px; }

    main.container { padding: 2rem 5%; max-width: 1400px; margin: 0 auto; padding-top: 40px; }

    h2.welcome-title { font-family: 'Montserrat', sans-serif; font-size: clamp(2rem, 5vw, 2.5rem); font-weight: 700; margin-bottom: 2rem; text-align: center; display: flex; justify-content: center; gap: 0.25em; flex-wrap: wrap; }
    .welcome-title .h-word { display: inline-block; opacity: 0; transform: translateY(-80px) rotateZ(5deg); transition: transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94), opacity 0.8s ease-out; }
    .welcome-title.is-visible .h-word { opacity: 1; transform: translateY(0); }
    .welcome-title.is-visible .h-word:nth-child(2) { transition-delay: 0.2s; }

    .section-title { font-family: 'Montserrat', sans-serif; font-size: clamp(1.5rem, 4vw, 2rem); font-weight: 700; text-align: center; margin: 4rem 0 2rem; color: var(--kd-text); }
    .section-title span { color: var(--kd-earthy-green); }

    .scroll-trigger { opacity: 0; transform: translateY(50px); transition: opacity 0.7s ease-out, transform 0.7s ease-out; }
    .scroll-trigger.is-visible { opacity: 1; transform: translateY(0); }
    
    .search-box {
        display: flex; flex-wrap: wrap; gap: 1rem; padding: 1.5rem;
        background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 16px;
        margin-bottom: 2rem; transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        position: relative; box-shadow: var(--card-shadow);
    }
    .search-box:hover { transform: translateY(-5px); border-color: var(--kd-earthy-green); }
    .search-box.dropdown-active { z-index: 30; }
    .search-input {
        flex: 1; padding: 0.8rem 1rem; border-radius: 8px;
        border: 1px solid var(--glass-border); background: var(--kd-bg-surface); color: var(--kd-text);
        font-size: 1rem; transition: all .3s ease; min-width: 200px;
    }
    html.light-mode .search-input { background: #F9FAFB; }
    .search-input:focus { outline: none; border-color: var(--kd-earthy-green); box-shadow: 0 0 0 3px rgba(104, 211, 145, 0.3); }
    html.light-mode .search-input:focus { box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.2); }
    
    .search-btn { background: var(--kd-earthy-green); color: #fff; border: none; padding: 0.8rem 1.5rem; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all .3s ease; }
    .search-btn:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(104, 211, 145, 0.3); }
    html.light-mode .search-btn:hover { box-shadow: 0 5px 15px rgba(5, 150, 105, 0.3); }
    
    .category-modal-trigger { display: flex; justify-content: space-between; align-items: center; padding: 0.8rem 1rem; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--kd-bg-surface); color: var(--kd-text); font-size: 1rem; cursor: pointer; transition: all .3s ease; flex: 1; min-width: 200px; }
    html.light-mode .category-modal-trigger { background: #F9FAFB; }
    .category-modal-trigger:hover { border-color: var(--kd-earthy-green); }
    .category-modal-trigger .arrow { width: 12px; height: 12px; border-left: 2px solid var(--kd-muted); border-bottom: 2px solid var(--kd-muted); transform: rotate(-45deg); }
    .hidden-select { display: none; }

    .category-list { margin-top: 1.5rem; text-align: left; max-height: 50vh; overflow-y: auto; }
    .category-option { display: block; padding: 1rem; border-radius: 8px; cursor: pointer; font-weight: 500; transition: background-color 0.2s ease, color 0.2s ease; }
    .category-option:hover { background-color: rgba(104, 211, 145, 0.1); color: var(--kd-text); }
    html.light-mode .category-option:hover { background-color: rgba(5, 150, 105, 0.08); }
    .category-option:not(:last-child) { border-bottom: 1px solid var(--glass-border); }

    .grid { display: grid; gap: 1.5rem; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); }
    
    .card {
        background: var(--glass-bg); border: 1px solid var(--glass-border);
        border-radius: 16px; backdrop-filter: blur(12px); overflow: hidden;
        box-shadow: var(--card-shadow); transition: transform 0.4s ease, box-shadow 0.4s ease, border-color 0.4s ease;
        display: flex; flex-direction: column;
    }
    .card:hover {
        transform: translateY(-8px); border-color: var(--kd-earthy-green);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4), 0 0 15px rgba(104, 211, 145, 0.5);
    }
    html.light-mode .card:hover {
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        border-color: transparent;
    }
    
    .card img { width: 100%; height: 200px; object-fit: cover; }
    .card-content { padding: 1.25rem; flex-grow: 1; display: flex; flex-direction: column; }
    .card h4 { margin: 0 0 .5rem; font-size: 1.1rem; color: var(--kd-text); font-weight: 600; }
    .card p { margin: 0 0 1rem; color: var(--kd-muted); font-size: .9rem; line-height: 1.6; }
    .price { margin-bottom: 1rem; font-weight: 700; font-size: 1.15rem; color: var(--kd-warm-gold); }
    .card-actions { margin-top: auto; }
    
    .cart-btn, .wishlist-btn { display: block; width: 100%; padding: .8rem; border: 1px solid; border-radius: 8px; cursor: pointer; font-weight: 600; text-align: center; transition: all 0.3s ease; margin-top: 0.5rem; }
    .cart-btn { background: var(--kd-earthy-green); color: #fff; border-color: var(--kd-earthy-green); }
    .cart-btn:hover { background: transparent; color: var(--kd-earthy-green); }
    .wishlist-btn { background: transparent; color: var(--kd-warm-gold); border-color: var(--kd-warm-gold); }
    .wishlist-btn:hover { background: var(--kd-warm-gold); color: #fff; }
    .wishlist-btn.active { background: var(--kd-danger); color: #fff; border-color: var(--kd-danger); }
    .wishlist-btn.active:hover { background: transparent; color: var(--kd-danger); }
    
    .qty-selector { display: flex; align-items: center; justify-content: center; margin-bottom: 1rem; }
    .qty-selector button { width: 38px; height: 38px; font-size: 1.2rem; background: transparent; color: var(--kd-muted); border: 1px solid var(--glass-border); cursor: pointer; transition: .2s; }
    .qty-selector button:first-child { border-radius: 8px 0 0 8px; }
    .qty-selector button:last-child { border-radius: 0 8px 8px 0; }
    .qty-selector button:hover { background: var(--kd-bg-surface); color: var(--kd-text); }
    .qty-selector input { width: 50px; height: 38px; text-align: center; background: var(--kd-bg-surface); border: none; border-top: 1px solid var(--glass-border); border-bottom: 1px solid var(--glass-border); color: var(--kd-text); font-size: 1.1rem; appearance: textfield; -moz-appearance: textfield; }
    html.light-mode .qty-selector input { background: #F9FAFB; }
    
    .products-list .card, .bestsellers-list .card { opacity: 0; transform: translateY(50px); transition: opacity 0.6s ease-out, transform 0.6s ease-out; }
    .products-list.is-visible .card, .bestsellers-list.is-visible .card { opacity: 1; transform: translateY(0); }
    
    .table-wrapper { overflow-x: auto; background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 12px; padding: 0.5rem; box-shadow: var(--card-shadow); }
    table { width: 100%; border-collapse: collapse; }
    table th, table td { padding: 1rem; text-align: left; font-size: 0.95rem; white-space: nowrap; }
    table th { font-family: 'Montserrat', sans-serif; color: var(--kd-text); border-bottom: 2px solid var(--kd-earthy-green); font-weight: 700; }
    table td { color: var(--kd-muted); border-bottom: 1px solid var(--glass-border); }
    table tbody tr:last-child td { border-bottom: none; }
    .track-btn { background: transparent; color: var(--kd-warm-gold); border: 1px solid var(--kd-warm-gold); padding: 0.5rem 1rem; border-radius: 8px; text-decoration: none; font-weight: 600; transition: all .3s ease; display: inline-block; }
    .track-btn:hover { background: var(--kd-warm-gold); color: #fff; }
    
    .categories { margin-top: 4rem; overflow-x: hidden; }
    .category-grid { display: flex; justify-content: center; gap: 2rem; flex-wrap: wrap; }
    .category-item { display: flex; flex-direction: column; align-items: center; text-decoration: none; color: var(--kd-text); opacity: 0; transition: opacity 0.7s ease-out, transform 0.7s ease-out; }
    .categories .category-item:nth-child(1), .categories .category-item:nth-child(2) { transform: translateX(-100px); }
    .categories .category-item:nth-child(3), .categories .category-item:nth-child(4) { transform: translateX(100px); }
    .categories.is-visible .category-item:nth-child(1), .categories.is-visible .category-item:nth-child(4) { transition-delay: 0.2s; }
    .categories.is-visible .category-item:nth-child(2), .categories.is-visible .category-item:nth-child(3) { transition-delay: 0.4s; }
    .categories.is-visible .category-item { opacity: 1; transform: translateX(0); }
    .category-item:hover { transform: translateY(-8px) !important; }
    .category-circle { width: 140px; height: 140px; border-radius: 50%; background: var(--kd-bg-surface); display: flex; justify-content: center; align-items: center; margin-bottom: 1rem; overflow: hidden; border: 2px solid var(--glass-border); box-shadow: var(--card-shadow); transition: border-color 0.3s ease; }
    .category-item:hover .category-circle { border-color: var(--kd-earthy-green); }
    .category-circle img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s ease; }
    .category-item:hover .category-circle img { transform: scale(1.1); }
    .category-label { font-weight: 600; }
    
    .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(18, 24, 27, 0.7); backdrop-filter: blur(8px); align-items: center; justify-content: center; animation: fadeIn 0.3s ease; }
    html.light-mode .modal { background-color: rgba(255, 255, 255, 0.6); }
    .modal-content { padding: 2rem; width: 90%; max-width: 450px; text-align: center; background: var(--kd-bg-surface); border: 1px solid var(--glass-border); border-radius: 16px; box-shadow: 0 10px 50px rgba(0,0,0,0.5); }
    html.light-mode .modal-content { box-shadow: 0 20px 50px rgba(0,0,0,0.1); }
    .modal-content h4 { font-family: 'Montserrat',sans-serif; margin-top:0; color:var(--kd-text); }
    .modal-content p { color:var(--kd-muted); }
    .modal-content button { padding:.7rem 1.5rem; border-radius:8px; text-decoration:none; font-weight:600; border:none; cursor:pointer; margin:.5rem; transition:all .3s ease }
    .confirm-remove { background:var(--kd-danger); color:#fff }
    .confirm-remove:hover { background:#c53030 }
    html.light-mode .confirm-remove:hover { background:#DC2626; }
    .cancel-remove { background:var(--kd-muted); color:var(--kd-bg) }
    .cancel-remove:hover { background:#718096 }
    .confirm-btn { background:var(--kd-earthy-green); color:#fff }
    .confirm-btn:hover { background:#55b880 }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

    @media (max-width: 768px) {
        .search-box { flex-direction: column; }
        header .logo { padding-left: 0; justify-content: center; width: 100%; }
        header .menu-container { display: none; }
        .hamburger { left: 30px; }
        body.sidebar-open .hamburger { left: calc(var(--sidebar-width) + 30px); }
        .grid { grid-template-columns: repeat(2, 1fr); gap: 1rem; }
        .card img { height: 150px; }
        .card-content { padding: 1rem; }
        .card h4 { font-size: 0.95rem; }
        .card p { font-size: 0.8rem; }
        .price { font-size: 1rem; }
    }
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="page-overlay"></div>
<div class="hamburger"><span></span><span></span><span></span></div>
<div class="sidebar">
    <a href="user_dashboard.php">🏠 <?php echo $lang['sidebar_dashboard']; ?></a>
    <a href="wishlist.php">💖 <?php echo $lang['sidebar_wishlist']; ?></a>
    <a href="cart.php">🛒 <?php echo $lang['sidebar_cart']; ?></a>
    <a href="orders.php">📦 <?php echo $lang['sidebar_orders']; ?></a>
</div>

<main class="container">
    <h2 class="welcome-title scroll-trigger">
        <span class="h-word"><?php echo $lang['user_dashboard_welcome']; ?></span>
        <span class="h-word"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>
    </h2>

    <div id="search-section" class="scroll-trigger">
        <h3 class="section-title"><span><?php echo $lang['user_dashboard_search_title_span']; ?></span> <?php echo $lang['user_dashboard_search_title']; ?></h3>
        <form method="get" class="search-box">
            <input type="text" name="q" class="search-input" placeholder="<?php echo $lang['user_dashboard_search_placeholder']; ?>" value="<?php echo htmlspecialchars($search); ?>">
            
            <div class="category-modal-trigger">
                <span><?php echo $lang['user_dashboard_all_categories']; ?></span>
                <div class="arrow"></div>
            </div>
            
            <select name="category" class="hidden-select">
                <option value=""><?php echo $lang['user_dashboard_all_categories']; ?></option>
                <?php foreach($cats as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php if($category_filter==$c['id']) echo 'selected'; ?>><?php echo htmlspecialchars($c['name']); ?></option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit" class="search-btn"><?php echo $lang['user_dashboard_search_button']; ?></button>
        </form>
    </div>
    
    <section class="categories scroll-trigger">
        <h3 class="section-title"><span><?php echo $lang['user_dashboard_shop_by_title_span']; ?></span> <?php echo $lang['user_dashboard_shop_by_title']; ?></h3>
        <div class="category-grid">
            <a href="products.php?category=vegetables" class="category-item"><div class="category-circle"><img src="vegetables.jpg" alt="Vegetables"></div><div class="category-label"><?php echo $lang['user_dashboard_category_veg']; ?></div></a>
            <a href="products.php?category=fruits" class="category-item"><div class="category-circle"><img src="fruits.jpg" alt="Fruits"></div><div class="category-label"><?php echo $lang['user_dashboard_category_fruits']; ?></div></a>
            <a href="products.php?category=grains" class="category-item"><div class="category-circle"><img src="grains.jpg" alt="Grains"></div><div class="category-label"><?php echo $lang['user_dashboard_category_grains']; ?></div></a>
            <a href="products.php?category=spices" class="category-item"><div class="category-circle"><img src="spices.jpg" alt="Spices"></div><div class="category-label"><?php echo $lang['user_dashboard_category_spices']; ?></div></a>
        </div>
    </section>

    <div id="products-section" class="products-list scroll-trigger">
        <h3 class="section-title"><span><?php echo $lang['user_dashboard_available_title_span']; ?></span> <?php echo $lang['user_dashboard_available_title']; ?></h3>
        <div class="grid">
            <?php foreach($products as $p): $inWishlist = isset($_SESSION['wishlist'][$p['id']]); ?>
                <div class="card" style="transition-delay: <?php echo ($p['id'] % 4) * 0.05; ?>s;">
                    <?php if($p['image']): ?><img src="images/<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>"><?php endif; ?>
                    <div class="card-content">
                        <div>
                            <h4><?php echo htmlspecialchars($p['name']); ?></h4>
                            <p><?php echo htmlspecialchars($p['description']); ?></p>
                            <p class="price">₹<?php echo number_format($p['price']*(1-$p['discount_percent']/100),2); ?></p>
                        </div>
                        <div class="card-actions">
                            <form method="post" action="user_dashboard.php"><input type="hidden" name="product_id" value="<?php echo $p['id']; ?>"><input type="hidden" name="cart_action" value="add"><div class="qty-selector"><button type="button" class="minus-btn">-</button><input type="text" name="qty" value="1" min="1" readonly><button type="button" class="plus-btn">+</button></div><button type="submit" class="cart-btn"><?php echo $lang['card_add_to_cart']; ?></button></form>
                            <form method="post" action="" class="wishlist-form"><input type="hidden" name="product_id" value="<?php echo $p['id']; ?>"><input type="hidden" name="wishlist_action" value="<?php echo $inWishlist ? 'remove' : 'add'; ?>"><button type="submit" class="wishlist-btn <?php if($inWishlist) echo 'active'; ?>"><?php echo $inWishlist ? $lang['card_in_wishlist'] : $lang['card_add_wishlist']; ?></button></form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="bestsellers-section" class="bestsellers-list scroll-trigger">
        <h3 class="section-title"><span><?php echo $lang['user_dashboard_bestsellers_title_span']; ?></span> <?php echo $lang['user_dashboard_bestsellers_title']; ?></h3>
        <div class="grid">
            <?php foreach($bestSellers as $index => $p): $inWishlist = isset($_SESSION['wishlist'][$p['id']]); ?>
                <div class="card" style="transition-delay: <?php echo $index * 0.05; ?>s;">
                    <?php if($p['image']): ?><img src="images/<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>"><?php endif; ?>
                    <div class="card-content">
                        <div>
                            <h4><?php echo htmlspecialchars($p['name']); ?></h4>
                            <p><?php echo htmlspecialchars($p['description']); ?></p>
                            <p class="price">₹<?php echo number_format($p['price']*(1-$p['discount_percent']/100),2); ?></p>
                        </div>
                        <div class="card-actions">
                            <form method="post" action="user_dashboard.php"><input type="hidden" name="product_id" value="<?php echo $p['id']; ?>"><input type="hidden" name="cart_action" value="add"><div class="qty-selector"><button type="button" class="minus-btn">-</button><input type="text" name="qty" value="1" min="1" readonly><button type="button" class="plus-btn">+</button></div><button type="submit" class="cart-btn"><?php echo $lang['card_add_to_cart']; ?></button></form>
                            <form method="post" action="" class="wishlist-form"><input type="hidden" name="product_id" value="<?php echo $p['id']; ?>"><input type="hidden" name="wishlist_action" value="<?php echo $inWishlist ? 'remove' : 'add'; ?>"><button type="submit" class="wishlist-btn <?php if($inWishlist) echo 'active'; ?>"><?php echo $inWishlist ? $lang['card_in_wishlist'] : $lang['card_add_wishlist']; ?></button></form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="orders-section" class="scroll-trigger">
        <h3 class="section-title"><span><?php echo $lang['user_dashboard_orders_title_span']; ?></span> <?php echo $lang['user_dashboard_orders_title']; ?></h3>
        <?php if(!$orders): ?><p style="text-align:center; color: var(--kd-muted);"><?php echo $lang['user_dashboard_no_orders']; ?></p>
        <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead><tr>
                    <th><?php echo $lang['user_dashboard_order_table_id']; ?></th>
                    <th><?php echo $lang['user_dashboard_order_table_items']; ?></th>
                    <th><?php echo $lang['user_dashboard_order_table_total']; ?></th>
                    <th><?php echo $lang['user_dashboard_order_table_status']; ?></th>
                    <th><?php echo $lang['user_dashboard_order_table_date']; ?></th>
                    <th><?php echo $lang['user_dashboard_order_table_track']; ?></th>
                </tr></thead>
                <tbody>
                    <?php foreach($orders as $o): ?>
                        <tr>
                            <td>#<?php echo $o['id']; ?></td>
                            <td><?php echo $o['items']; ?></td>
                            <td><?php echo number_format($o['total_amount'] ?? 0, 2); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($o['status'])); ?></td>
                            <td><?php echo date("d M, Y", strtotime($o['created_at'])); ?></td>
                            <td><a href="track.php?order_id=<?php echo $o['id']; ?>" class="track-btn"><?php echo $lang['user_dashboard_track_button']; ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</main>

<div class="modal" id="removeModal">
    <div class="modal-content">
        <h4><?php echo $lang['user_dashboard_modal_remove_title']; ?></h4>
        <p><?php echo $lang['user_dashboard_modal_remove_p']; ?></p>
        <form method="post" id="confirmRemoveForm">
            <input type="hidden" name="product_id" id="removeProductId">
            <input type="hidden" name="wishlist_action" value="remove">
            <button type="button" class="cancel-remove" onclick="closeRemoveModal()"><?php echo $lang['user_dashboard_modal_cancel_button']; ?></button>
            <button type="submit" class="confirm-remove"><?php echo $lang['user_dashboard_modal_remove_button']; ?></button>
        </form>
    </div>
</div>

<div class="modal" id="successModal" style="<?php echo $popup_message ? 'display:flex;' : 'display:none;'; ?>">
    <div class="modal-content">
        <h4 id="successTitle"><?php echo $lang['user_dashboard_modal_success_title']; ?></h4>
        <p id="successMessage"><?php echo htmlspecialchars($popup_message); ?></p>
        <button class="confirm-btn" onclick="closeSuccessModal()"><?php echo $lang['user_dashboard_modal_ok_button']; ?></button>
    </div>
</div>

<div class="modal" id="categoryModal">
    <div class="modal-content">
        <h4><?php echo $lang['user_dashboard_modal_category_title']; ?></h4>
        <div class="category-list">
            <div class="category-option" data-value=""><?php echo $lang['user_dashboard_all_categories']; ?></div>
            <?php foreach($cats as $c): ?>
                <div class="category-option" data-value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const hamburger = document.querySelector('.hamburger');
        const pageOverlay = document.querySelector('.page-overlay');
        function toggleSidebar() { document.body.classList.toggle('sidebar-open'); }
        if (hamburger) hamburger.addEventListener('click', toggleSidebar);
        if (pageOverlay) pageOverlay.addEventListener('click', toggleSidebar);
        
        const successModal = document.getElementById('successModal');
        function closeSuccessModal(){ if(successModal) successModal.style.display = 'none'; }
        window.closeSuccessModal = closeSuccessModal;
        const removeModal = document.getElementById('removeModal');
        const removeProductIdInput = document.getElementById('removeProductId');
        function openRemoveModal(productId){ if(removeModal && removeProductIdInput){ removeProductIdInput.value = productId; removeModal.style.display = 'flex'; } }
        window.openRemoveModal = openRemoveModal;
        function closeRemoveModal(){ if(removeModal) removeModal.style.display = 'none'; }
        window.closeRemoveModal = closeRemoveModal;
        document.querySelectorAll('.wishlist-form').forEach(form => { 
            form.addEventListener('submit', function(e) {
                const action = this.querySelector('[name="wishlist_action"]').value;
                const productId = this.querySelector('[name="product_id"]').value;
                if (action === 'remove') { e.preventDefault(); openRemoveModal(productId); }
            });
         });
        document.querySelectorAll('.qty-selector').forEach(selector => { 
            const minusBtn = selector.querySelector('.minus-btn');
            const plusBtn = selector.querySelector('.plus-btn');
            const qtyInput = selector.querySelector('input[name="qty"]');
            minusBtn.addEventListener('click', () => { let qty = parseInt(qtyInput.value); if (qty > 1) qtyInput.value = qty - 1; });
            plusBtn.addEventListener('click', () => { let qty = parseInt(qtyInput.value); qtyInput.value = qty + 1; });
        });

        const header = document.querySelector('header');
        if(header) {
            let lastScrollY = window.scrollY;
            window.addEventListener('scroll', () => {
                if (lastScrollY < window.scrollY && window.scrollY > 150) { header.classList.add('header-hidden'); } 
                else { header.classList.remove('header-hidden'); }
                lastScrollY = window.scrollY;
            });
        }

        const scrollObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) { entry.target.classList.add('is-visible'); } 
                else { entry.target.classList.remove('is-visible'); }
            });
        }, { threshold: 0.1 });
        document.querySelectorAll('.scroll-trigger').forEach(el => { scrollObserver.observe(el); });

        const categoryModal = document.getElementById('categoryModal');
        const categoryTrigger = document.querySelector('.category-modal-trigger');
        const hiddenSelect = document.querySelector('.hidden-select');
        const categoryOptions = document.querySelectorAll('.category-option');
        
        const initialCategory = hiddenSelect.querySelector('option[selected]');
        if (initialCategory && initialCategory.value !== "") { categoryTrigger.querySelector('span').textContent = initialCategory.textContent; } 
        else { categoryTrigger.querySelector('span').textContent = "<?php echo $lang['user_dashboard_all_categories']; ?>"; }

        if (categoryTrigger) { categoryTrigger.addEventListener('click', () => { categoryModal.style.display = 'flex'; }); }

        categoryOptions.forEach(option => {
            option.addEventListener('click', () => {
                const selectedValue = option.dataset.value;
                const selectedText = option.textContent;
                hiddenSelect.value = selectedValue;
                categoryTrigger.querySelector('span').textContent = selectedText;
                categoryModal.style.display = 'none';
            });
        });

        window.addEventListener('click', (event) => { if (event.target == categoryModal) { categoryModal.style.display = 'none'; } });
    });
</script>

<?php include 'footer.php'; ?>

</body>
</html>