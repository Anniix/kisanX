<?php
session_start();
// Assumes language_init.php and db.php exist.
// If testing locally without them, comment out these includes and the DB queries.
include 'php/language_init.php';
include 'php/db.php';

// --- AUTH CHECK ---
if (empty($_SESSION['user']) || $_SESSION['user']['role'] != 'farmer') {
    header("Location: login.php");
    exit;
}
$farmer_id = $_SESSION['user']['id'];
$farmer_name = $_SESSION['user']['name'] ?? 'Kisan';

/* ===== PHP LOGIC: ADD PRODUCT ===== */
$msg = [];
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='add_product'){
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $qty = (int)($_POST['qty'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);
    $discount = (int)($_POST['discount_percent'] ?? 0);
    
    if(!$name || !$price){
        $msg = ['type'=>'error','text'=> $lang['farmer_add_product_error'] ?? 'Name and Price required' ];
    } else {
        $imageName = null;
        if(!empty($_FILES['image']) && $_FILES['image']['error']===0){
            $allowed = ['image/jpeg','image/png','image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['image']['tmp_name']);
            finfo_close($finfo);
            if(in_array($mime,$allowed)){
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $imageName = 'prod_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
                $dest = __DIR__.'/images/'.$imageName;
                move_uploaded_file($_FILES['image']['tmp_name'],$dest);
            }
        }
        if(empty($msg)){
            $ins = $pdo->prepare('INSERT INTO products (farmer_id,category_id,name,description,price,qty,image,discount_percent) VALUES (?,?,?,?,?,?,?,?)');
            $ins->execute([$farmer_id,$category_id,$name,$description,$price,$qty,$imageName,$discount]);
            $msg = ['type'=>'success','text'=> $lang['farmer_add_product_success'] ?? 'Product Added Successfully' ];
        }
    }
}

// ===== DATA QUERIES =====
// 1. My Products
$stmt = $pdo->prepare('SELECT p.*, c.name as category FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.farmer_id=? ORDER BY p.id DESC');
$stmt->execute([$farmer_id]);
$myProducts = $stmt->fetchAll();

// 2. Categories
$cats = $pdo->query('SELECT * FROM categories')->fetchAll();

// 3. Stats
$statsStmt = $pdo->prepare("SELECT COALESCE(SUM(oi.qty * oi.price),0) AS total_income, COALESCE(SUM(oi.qty),0) AS total_sold, COUNT(DISTINCT o.id) AS total_orders FROM order_items oi INNER JOIN orders o ON oi.order_id = o.id INNER JOIN products p ON oi.product_id = p.id WHERE p.farmer_id = ?");
$statsStmt->execute([$farmer_id]);
$initial_stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// 4. Recent Orders
$ordersStmt = $pdo->prepare("SELECT o.id AS order_id, u.name AS customer_name, p.name AS product_name, oi.qty, oi.price, (oi.qty * oi.price) AS total_price, o.created_at FROM order_items oi INNER JOIN orders o ON oi.order_id = o.id INNER JOIN users u ON o.user_id = u.id INNER JOIN products p ON oi.product_id = p.id WHERE p.farmer_id = ? ORDER BY o.id DESC LIMIT 10");
$ordersStmt->execute([$farmer_id]);
$initial_recent_orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);

// 5. Live Activity
$initial_activity_stmt = $pdo->prepare("SELECT id, product_name, created_at FROM live_activity WHERE farmer_id = ? ORDER BY id DESC LIMIT 5");
$initial_activity_stmt->execute([$farmer_id]);
$initial_activities = $initial_activity_stmt->fetchAll(PDO::FETCH_ASSOC);

// 6. Today's Stats
$today_stmt = $pdo->prepare("SELECT COUNT(DISTINCT o.id) AS today_orders, COALESCE(SUM(oi.qty * oi.price), 0) AS today_income FROM orders o JOIN order_items oi ON o.id = oi.order_id JOIN products p ON oi.product_id = p.id WHERE p.farmer_id = ? AND DATE(o.created_at) = CURDATE()");
$today_stmt->execute([$farmer_id]);
$today_stats = $today_stmt->fetch(PDO::FETCH_ASSOC);

// 7. Low Stock
if (!defined('LOW_STOCK_THRESHOLD')) { define('LOW_STOCK_THRESHOLD', 10); }
$low_stock_stmt = $pdo->prepare("SELECT name, qty FROM products WHERE farmer_id = ? AND qty > 0 AND qty <= ? ORDER BY qty ASC LIMIT 5");
$low_stock_stmt->execute([$farmer_id, LOW_STOCK_THRESHOLD]);
$low_stock_items = $low_stock_stmt->fetchAll(PDO::FETCH_ASSOC);

// 8. Top Selling
$top_selling_stmt = $pdo->prepare("SELECT p.name, SUM(oi.qty) AS total_sold FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE p.farmer_id = ? GROUP BY p.id ORDER BY total_sold DESC LIMIT 5");
$top_selling_stmt->execute([$farmer_id]);
$top_selling_items = $top_selling_stmt->fetchAll(PDO::FETCH_ASSOC);

// 9. Sales Over Time
$sales_over_time_stmt = $pdo->prepare("SELECT DATE(o.created_at) as sale_date, SUM(oi.price * oi.qty) as daily_total FROM order_items oi INNER JOIN orders o ON oi.order_id = o.id INNER JOIN products p ON oi.product_id = p.id WHERE p.farmer_id = ? AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY sale_date ORDER BY sale_date ASC");
$sales_over_time_stmt->execute([$farmer_id]);
$sales_over_time_data = $sales_over_time_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang ?? 'en'; ?>">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
<title><?php echo $lang['farmer_dashboard_title'] ?? 'KisanX Dashboard'; ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<script type="module" src="https://ajax.googleapis.com/ajax/libs/model-viewer/3.5.0/model-viewer.min.js"></script>
<style>
/* --- CORE STYLES --- */
:root {
    --primary-color: #2C7865; --primary-dark: #1A4D2E; --accent-color: #F5A623; --danger-color: #D0021B;
    --bg-light: #F4F6F5; --card-bg-light: #FFFFFF; --text-light: #1A2E26; --text-muted-light: #5A6D67; --border-light: #DCE3E1;
    --bg-dark: #0f1412; --card-bg-dark: #151d1a; --text-dark: #E1E8E6; --text-muted-dark: #8F9D99; --border-dark: #25342e;
    --shadow-color: rgba(44, 120, 101, 0.06); --shadow-hover-color: rgba(44, 120, 101, 0.12);
    --border-radius: 16px; --transition-speed: 0.3s;
    --ticker-height: 40px;
}
* { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
body {
    --bg: var(--bg-light); --card-bg: var(--card-bg-light); --text: var(--text-light); --text-muted: var(--text-muted-light); --border: var(--border-light);
    font-family: 'Poppins', sans-serif; margin: 0; background-color: var(--bg); color: var(--text);
    display: block; 
    min-height: 100vh;
    transition: background-color var(--transition-speed), color var(--transition-speed);
    padding-top: var(--ticker-height);
    overflow-x: hidden;
}
body.dark { --bg: var(--bg-dark); --card-bg: var(--card-bg-dark); --text: var(--text-dark); --text-muted: var(--text-muted-dark); --border: var(--border-dark); }

/* --- ANIMATIONS --- */
@keyframes fadeInUp{from{opacity:0;transform:translateY(25px)}to{opacity:1;transform:translateY(0)}}
@keyframes number-update-flash{0%{transform:scale(1)}50%{transform:scale(1.15);color:var(--accent-color)}100%{transform:scale(1)}}
@keyframes pulse-glow { 0% { box-shadow: 0 0 0 0 rgba(44, 120, 101, 0.7); } 70% { box-shadow: 0 0 0 15px rgba(44, 120, 101, 0); } 100% { box-shadow: 0 0 0 0 rgba(44, 120, 101, 0); } }
@keyframes message-pop-in { from { opacity: 0; transform: translateY(10px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }
@keyframes ripple { to { transform: scale(4); opacity: 0; } }
@keyframes ticker { 0% { transform: translate3d(0, 0, 0); } 100% { transform: translate3d(-100%, 0, 0); } }

.stat-update { animation: number-update-flash .6s ease-in-out; }
.stagger-in { animation: fadeInUp 0.5s ease-out both; }

/* --- TICKER --- */
.news-ticker-container { 
    position: fixed; top: 0; left: 0; width: 100%; height: var(--ticker-height); 
    background: var(--card-bg); border-bottom: 1px solid var(--border); overflow: hidden; 
    white-space: nowrap; z-index: 2000; display: flex; align-items: center; 
}
.news-label { background: var(--accent-color); color: #000; padding: 0 15px; font-weight: 800; font-size: 13px; height: 100%; display: flex; align-items: center; z-index: 2; box-shadow: 4px 0 10px rgba(0,0,0,0.1); }
.news-ticker-wrapper { display: inline-block; padding-left: 100%; animation: ticker 40s linear infinite; }
.news-item { display: inline-block; padding: 0 20px; color: var(--text); font-size: 13px; font-weight: 500; }
@media(max-width:480px) { .news-label { padding: 0 8px; font-size: 11px; } .news-ticker-wrapper { animation-duration: 30s; } }

/* --- SIDEBAR --- */
.sidebar { 
    width: 260px; background: var(--card-bg); position: fixed; top: var(--ticker-height); bottom: 0; left: 0; 
    transition: transform var(--transition-speed) ease-in-out; z-index: 1100; border-right: 1px solid var(--border); 
    display: flex; flex-direction: column; overflow-y: auto;
}
.main { margin-left: 260px; padding: 25px; transition: margin-left var(--transition-speed) ease-in-out; }
.page-section { display: none; padding-bottom: 80px; }
.page-section.active { display: block; animation: fadeInUp .5s ease-out; }

.sidebar-header { text-align:center; padding: 25px 0; border-bottom: 1px solid var(--border); }
.sidebar-header h2 { margin:0; font-size:26px; font-family: 'Montserrat', sans-serif; font-weight:800; color:var(--primary-color); letter-spacing:1px; }
.sidebar-nav { flex-grow: 1; margin-top: 20px; padding-bottom: 20px; }
.sidebar-nav a { display: flex; align-items: center; gap: 15px; color: var(--text-muted); padding: 14px 25px; text-decoration: none; font-weight: 500; border-left: 4px solid transparent; transition: all var(--transition-speed); font-size: 15px; }
.sidebar-nav a:hover, .sidebar-nav a.active { background-color: color-mix(in srgb, var(--primary-color) 12%, transparent); color: var(--text); }
.sidebar-nav a.active { border-left-color: var(--accent-color); font-weight: 600; }

/* --- HEADER --- */
.header{ 
    display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; 
    background: color-mix(in srgb, var(--card-bg) 75%, transparent); 
    backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); 
    padding: 15px 20px; border-radius: var(--border-radius); 
    position: sticky; top: 15px; z-index: 1000; border: 1px solid var(--border); 
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
}
.header h1 { margin:0; font-size: 22px; font-weight: 700; color:var(--text); line-height: 1.2; }
.header-actions { display:flex; align-items:center; gap:10px; }
#menu-toggle { display:none; background:none; border:none; font-size:24px; padding: 5px; cursor:pointer; color:var(--text); }
.search-bar { position: relative; display: flex; align-items: center; gap: 8px; }
.search-bar input { padding:10px 14px; border-radius:12px; border:1px solid var(--border); width:280px; background-color: var(--bg); color: var(--text); font-size: 14px; outline: none; }
.export-btn, .toggle-btn { padding:10px 14px; border-radius:12px; border:1px solid var(--border); background-color:var(--card-bg); color:var(--text-muted); cursor:pointer; font-weight: 500; font-size: 14px; display: flex; align-items: center; gap: 6px; white-space: nowrap; }

/* --- CARDS & GRIDS --- */
.content-card { background-color: var(--card-bg); padding: 22px; border-radius: var(--border-radius); box-shadow: 0 8px 30px var(--shadow-color); margin-bottom: 22px; border: 1px solid var(--border); transition: transform 0.3s ease, box-shadow 0.3s ease; }
.content-card h2 { margin-top:0; margin-bottom: 18px; border-bottom: 1px solid var(--border); padding-bottom: 12px; font-weight: 600; color: var(--text); font-size: 18px; }

.stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:20px; margin-bottom:24px; }
.stat-card { background: linear-gradient(180deg, color-mix(in srgb, var(--card-bg) 95%, transparent), var(--card-bg)); padding:20px; border-radius:14px; box-shadow:0 4px 15px var(--shadow-color); display:flex; align-items:center; gap:15px; border: 1px solid var(--border); }
.stat-card::before { content:attr(data-icon); font-size:28px; display:grid; place-items:center; width:50px; height:50px; border-radius:50%; background-color:color-mix(in srgb, var(--primary-color) 10%, transparent); color:var(--primary-color); flex-shrink:0; }
.stat-card h3 { margin:0 0 2px; color:var(--text); font-size:24px; font-weight:700; }
.stat-card p { margin:0; color:var(--text-muted); font-size:13px; font-weight:500; }

.dashboard-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
.dashboard-grid .full-width { grid-column: 1 / -1; }
.chart-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

.info-list { list-style: none; padding: 0; margin: 0; }
.info-list li { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--border); font-size: 14px; }
.info-list li:last-child { border-bottom: none; }
.info-list .value { font-weight: 600; color: var(--primary-color); }
.info-list .low-stock .value { color: var(--danger-color); }
.activity-feed li { display: flex; gap: 10px; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--border); font-size: 14px; }

/* --- WIDGETS --- */
.weather-widget { display: flex; gap: 15px; align-items: center; justify-content: space-between; flex-wrap: wrap; }
.weather-item { flex: 1; min-width: 80px; text-align: center; padding: 10px; background: var(--bg); border-radius: 12px; }
.weather-item .icon { font-size: 28px; display: block; margin-bottom: 5px; }
.weather-item .val { font-size: 18px; font-weight: 700; color: var(--text); }
.weather-item .label { color: var(--text-muted); font-size: 12px; }

.soil-widget { display: flex; flex-direction: column; gap: 12px; }
.soil-bar-group { display: flex; align-items: center; gap: 10px; font-size: 13px; }
.soil-label { width: 90px; font-weight: 500; color: var(--text-muted); }
.soil-progress { flex: 1; height: 10px; background: color-mix(in srgb, var(--border) 60%, transparent); border-radius: 5px; overflow: hidden; }
.soil-fill { height: 100%; border-radius: 5px; }
.fill-moisture { background: #3498db; } .fill-ph { background: #9b59b6; } .fill-n { background: #2ecc71; } .fill-p { background: #f1c40f; } .fill-k { background: #e74c3c; }

/* --- FORMS & PRODUCTS --- */
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
.form-grid .full-width { grid-column:1 / -1; }
form input, form textarea, form select { width:100%; padding:14px; margin-bottom:5px; border:1px solid var(--border); border-radius:10px; background-color:var(--bg); color:var(--text); font-size:15px; font-family: inherit; }
form label { display: block; margin-bottom: 5px; font-weight: 500; font-size: 14px; color: var(--text-muted); }
.ripple-btn { position: relative; overflow: hidden; }
span.ripple { position: absolute; border-radius: 50%; transform: scale(0); animation: ripple 600ms linear; background-color: rgba(255, 255, 255, 0.4); }
form button { width: 100%; background:linear-gradient(45deg, var(--primary-color), var(--primary-dark)); color:#fff; padding:15px; border:none; border-radius:12px; font-weight:700; cursor:pointer; font-size:16px; margin-top: 10px; }

.product-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(250px, 1fr)); gap:20px; }
.product-card { background-color:var(--card-bg); border-radius:14px; box-shadow:0 6px 20px var(--shadow-color); display:flex; flex-direction:column; overflow:hidden; position: relative; border: 1px solid var(--border); }
.product-card img { width:100%; height:180px; object-fit:cover; display:block; }
.product-card-body { padding:15px; flex-grow:1; display:flex; flex-direction:column; }
.product-card h4 { margin: 0 0 5px; font-size: 16px; }
.product-card p { font-size: 13px; color: var(--text-muted); flex-grow: 1; margin: 0 0 10px; }
.product-card-meta { display: flex; justify-content: space-between; align-items: center; font-size: 14px; font-weight: 600; }
.product-card-meta .price { color: var(--primary-color); font-size: 16px; }
.discount-badge { position: absolute; top: 10px; right: 10px; background-color: var(--accent-color); color: #fff; padding: 5px 8px; font-size: 11px; font-weight: 800; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }

.table-wrapper { overflow-x:auto; -webkit-overflow-scrolling: touch; border-radius: 12px; border: 1px solid var(--border); }
.styled-table { width:100%; border-collapse:collapse; background-color:var(--card-bg); font-size: 13px; white-space: nowrap; }
.styled-table thead tr { background-color:color-mix(in srgb, var(--primary-dark) 90%, var(--card-bg)); color:var(--primary-color); }
.styled-table th, .styled-table td { padding:14px; text-align:left; border-bottom: 1px solid var(--border); }
.styled-table tbody tr:last-child { border-bottom: none; }

/* --- CHATBOT --- */
.chat-widget-btn { position: fixed; bottom: 25px; right: 25px; width: 60px; height: 60px; background: var(--primary-color); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 25px rgba(0,0,0,0.2); cursor: pointer; z-index: 1500; border: none; font-size: 30px; animation: pulse-glow 2s infinite; }
.chat-window { position: fixed; bottom: 100px; right: 25px; width: 360px; height: 500px; background: var(--card-bg); border: 1px solid var(--border); border-radius: 20px; overflow: hidden; display: none; flex-direction: column; z-index: 1500; box-shadow: 0 15px 50px rgba(0,0,0,0.25); animation: fadeInUp 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
.chat-header { background: var(--primary-color); padding: 15px; display: flex; justify-content: space-between; align-items: center; color: white; }
.chat-header h5 { margin: 0; font-size: 16px; }
.chat-body { flex: 1; padding: 15px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; background: var(--bg); }
.chat-message { max-width: 85%; padding: 10px 14px; border-radius: 14px; font-size: 14px; line-height: 1.4; word-wrap: break-word; animation: message-pop-in 0.3s ease-out forwards; }
.chat-bot { background: var(--card-bg); align-self: flex-start; border-bottom-left-radius: 2px; border: 1px solid var(--border); }
.chat-user { background: var(--primary-color); color: white; align-self: flex-end; border-bottom-right-radius: 2px; }
.chat-footer { padding: 10px; border-top: 1px solid var(--border); display: flex; gap: 8px; background: var(--card-bg); }
.chat-input { flex: 1; padding: 10px 15px; border-radius: 20px; border: 1px solid var(--border); background: var(--bg); color: var(--text); outline: none; margin: 0; }
.chat-send { width: 40px; height: 40px; border-radius: 50%; padding: 0; margin: 0; display: grid; place-items: center; }

/* --- RESPONSIVE MEDIA QUERIES --- */
@media(max-width: 992px) {
    .sidebar { transform: translateX(-100%); width: 260px; }
    .sidebar.open { transform: translateX(0); box-shadow: 10px 0 50px rgba(0,0,0,0.3); }
    .main { margin-left: 0; padding: 15px; }
    #menu-toggle { display: block; }
}

@media(max-width: 768px) {
    .header { flex-direction: column; align-items: stretch; gap: 15px; padding: 15px; }
    .header-actions { justify-content: space-between; flex-wrap: wrap; }
    .search-bar { width: 100%; order: 3; }
    .search-bar input { width: 100%; }
    .header h1 { font-size: 20px; }
    
    .dashboard-grid, .form-grid, .chart-grid { grid-template-columns: 1fr; }
    .chart-grid .full-width { grid-column: auto; }
    
    /* Optimize Chat for Mobile */
    .chat-window { width: 94%; right: 3%; bottom: 90px; height: 60vh; }
    
    /* Adjust Fonts */
    .stat-card { padding: 15px; }
    .stat-card h3 { font-size: 20px; }
    .weather-item { min-width: 45%; }
}

@media(max-width: 480px) {
    .header h1 { font-size: 18px; }
    .content-card { padding: 15px; }
    .product-grid { grid-template-columns: 1fr; }
    .toggle-btn span { display: none; } /* Hide text, keep icon */
}
</style>
</head>
<body class="dark"> 

<div class="news-ticker-container">
    <div class="news-label">📢 Agri-News</div>
    <div class="news-ticker-wrapper" id="newsTicker"></div>
</div>

<nav class="sidebar" id="sidebar">
    <div class="sidebar-header"><h2>kisanX</h2></div>
    <div class="sidebar-nav">
        <a href="#dashboard" class="nav-link active">📊 <?php echo $lang['farmer_sidebar_dashboard'] ?? 'Dashboard'; ?></a>
        <a href="#fieldConditions" class="nav-link">🌤 Field Status</a>
        <a href="#addProduct" class="nav-link">➕ <?php echo $lang['farmer_sidebar_add_product'] ?? 'Add Product'; ?></a>
        <a href="#myProducts" class="nav-link">📦 <?php echo $lang['farmer_sidebar_my_products'] ?? 'My Products'; ?></a>
        <a href="#salesAnalytics" class="nav-link">📈 <?php echo $lang['farmer_sidebar_analytics'] ?? 'Analytics'; ?></a>
        <a href="news.php" class="nav-link">📰 <?php echo $lang['news_menu'] ?? 'Agri News'; ?></a>
        <a href="logout.php">🚪 <?php echo $lang['farmer_sidebar_logout'] ?? 'Logout'; ?></a>
    </div>
</nav>

<main class="main">
    <div class="header">
        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <h1><span class="greeting" id="dynamic-greeting"></span> <?php echo $lang['farmer_header_title'] ?? 'Farmer Panel'; ?></h1>
            <button id="menu-toggle" class="ripple-btn">☰</button>
        </div>
        <div class="header-actions">
            <div class="search-bar">
                <input id="productSearch" placeholder="<?php echo $lang['farmer_header_search_placeholder'] ?? 'Search products...'; ?>" />
            </div>
            <div style="display: flex; gap: 8px;">
                <button id="exportCSV" class="export-btn ripple-btn">⬇ Export</button>
                <button id="toggleDark" class="toggle-btn ripple-btn">🌙</button>
            </div>
        </div>
    </div>

    <section id="dashboard" class="page-section active">
        <div class="stats-grid">
             <div class="stat-card stagger-in" data-icon="☀" style="animation-delay: 0ms;"><div class="stat-info"><h3 id="today_income">₹<?= number_format($today_stats['today_income'] ?? 0, 2) ?></h3><p><?php echo $lang['farmer_stats_today_revenue'] ?? "Today's Revenue"; ?></p></div></div>
             <div class="stat-card stagger-in" data-icon="🛍" style="animation-delay: 100ms;"><div class="stat-info"><h3 id="today_orders"><?= (int)($today_stats['today_orders'] ?? 0) ?></h3><p><?php echo $lang['farmer_stats_today_orders'] ?? "Today's Orders"; ?></p></div></div>
            <div class="stat-card stagger-in" data-icon="💰" style="animation-delay: 200ms;"><div class="stat-info"><h3 id="income">₹<?= number_format($initial_stats['total_income'] ?? 0, 2) ?></h3><p><?php echo $lang['farmer_stats_total_revenue'] ?? 'Total Revenue'; ?></p></div></div>
            <div class="stat-card stagger-in" data-icon="🧺" style="animation-delay: 300ms;"><div class="stat-info"><h3 id="sold"><?= (int)($initial_stats['total_sold'] ?? 0) ?></h3><p><?php echo $lang['farmer_stats_total_sold'] ?? 'Total Sold'; ?></p></div></div>
        </div>

        <div class="content-card stagger-in" style="animation-delay: 400ms;">
            <h2 style="text-align: center; border: none; margin-bottom: 12px;"><?php echo $lang['farmer_model_title'] ?? 'Your Equipment'; ?></h2>
            <model-viewer id="tractorModel" src="tractor.glb" alt="A 3D model of a tractor" shadow-intensity="1" auto-rotate auto-rotate-delay="0" rotation-per-second="10deg" camera-orbit="0deg 80deg 2.5m" field-of-view="30deg" environment-image="neutral" exposure="1.2" style="width: 100%; height: 300px; border-radius: 12px; background: var(--bg);"></model-viewer>
        </div>
        
        <div class="dashboard-grid">
            <div class="content-card stagger-in" style="animation-delay: 500ms;">
                <h2>⭐ <?php echo $lang['farmer_top_selling_title'] ?? 'Top Selling'; ?></h2>
                <ul class="info-list" id="topSellingList">
                    <?php if (empty($top_selling_items)): ?><li><?php echo $lang['farmer_top_selling_no_data'] ?? 'No data available'; ?></li><?php else: ?><?php foreach($top_selling_items as $item): ?><li><span><?= htmlspecialchars($item['name']) ?></span><span class="value"><?= $item['total_sold'] ?> <?php echo $lang['farmer_top_selling_sold'] ?? 'Sold'; ?></span></li><?php endforeach; ?><?php endif; ?>
                </ul>
            </div>
            <div class="content-card stagger-in" style="animation-delay: 600ms;">
                <h2>⚠ <?php echo $lang['farmer_low_stock_title'] ?? 'Low Stock'; ?></h2>
                <ul class="info-list" id="lowStockList">
                    <?php if (empty($low_stock_items)): ?><li><?php echo $lang['farmer_low_stock_no_data'] ?? 'No low stock items'; ?></li><?php else: ?><?php foreach($low_stock_items as $item): ?><li class="low-stock"><span><?= htmlspecialchars($item['name']) ?></span><span class="value"><?= $item['qty'] ?> <?php echo $lang['farmer_low_stock_left'] ?? 'left'; ?></span></li><?php endforeach; ?><?php endif; ?>
                </ul>
            </div>
            <div class="content-card full-width stagger-in" style="animation-delay: 700ms;">
                <h2>🛒 <?php echo $lang['farmer_live_activity_title'] ?? 'Live Activity'; ?></h2>
                <ul class="activity-feed" id="activity-feed">
                    <?php if (empty($initial_activities)): ?><li id="no-activity-msg"><?php echo $lang['farmer_live_activity_waiting'] ?? 'Waiting for activity...'; ?></li><?php else: ?><?php foreach($initial_activities as $activity): ?><li><span class="icon">🛍</span><div class="details"><?php echo $lang['farmer_live_activity_added'] ?? 'User added'; ?> <strong class="product-name"><?= htmlspecialchars($activity['product_name']) ?></strong> <?php echo $lang['farmer_live_activity_to_basket'] ?? 'to basket'; ?></div></li><?php endforeach; ?><?php endif; ?>
                </ul>
            </div>
            <div class="content-card full-width stagger-in" style="animation-delay: 800ms;">
                <h2>📄 <?php echo $lang['farmer_recent_orders_title'] ?? 'Recent Orders'; ?></h2>
                 <div class="table-wrapper">
                    <table class="styled-table" id="ordersTable">
                        <thead><tr><th><?php echo $lang['farmer_table_order_id'] ?? 'ID'; ?></th><th><?php echo $lang['farmer_table_customer'] ?? 'Customer'; ?></th><th><?php echo $lang['farmer_table_product'] ?? 'Product'; ?></th><th><?php echo $lang['farmer_table_qty'] ?? 'Qty'; ?></th><th><?php echo $lang['farmer_table_price'] ?? 'Price'; ?></th><th><?php echo $lang['farmer_table_total'] ?? 'Total'; ?></th><th><?php echo $lang['farmer_table_date'] ?? 'Date'; ?></th></tr></thead>
                        <tbody>
                        <?php if (empty($initial_recent_orders)): ?><tr id="no-orders-row"><td colspan="7"><?php echo $lang['farmer_recent_orders_no_data'] ?? 'No orders found'; ?></td></tr><?php else: ?><?php foreach($initial_recent_orders as $ord): ?><tr><td>#<?= $ord['order_id']?></td><td><?= htmlspecialchars($ord['customer_name'])?></td><td><?= htmlspecialchars($ord['product_name'])?></td><td><?= $ord['qty']?></td><td>₹<?= number_format($ord['price'], 2)?></td><td>₹<?= number_format($ord['total_price'], 2)?></td><td><?= date('d M', strtotime($ord['created_at']))?></td></tr><?php endforeach; ?><?php endif; ?>
                        </tbody>
                    </table>
                 </div>
            </div>
        </div>
    </section>

    <section id="fieldConditions" class="page-section">
        <div class="dashboard-grid">
            <div class="content-card">
                <h2>🌤 Live Weather</h2>
                <div class="weather-widget">
                    <div class="weather-item"><span class="icon">🌡️</span><div class="val">28°C</div><div class="label">Temp</div></div>
                    <div class="weather-item"><span class="icon">💧</span><div class="val">65%</div><div class="label">Humid</div></div>
                    <div class="weather-item"><span class="icon">🍃</span><div class="val">12k</div><div class="label">Wind</div></div>
                    <div class="weather-item"><span class="icon">🌦️</span><div class="val">Clear</div><div class="label">Sky</div></div>
                </div>
            </div>

            <div class="content-card">
                <h2>🌱 Soil Health</h2>
                <div class="soil-widget">
                    <div class="soil-bar-group"><div class="soil-label">Moisture</div><div class="soil-progress"><div class="soil-fill fill-moisture" style="width: 35%;"></div></div><div class="soil-val">35%</div></div>
                    <div class="soil-bar-group"><div class="soil-label">pH Level</div><div class="soil-progress"><div class="soil-fill fill-ph" style="width: 62%;"></div></div><div class="soil-val">6.2</div></div>
                    <div class="soil-bar-group"><div class="soil-label">Nitrogen</div><div class="soil-progress"><div class="soil-fill fill-n" style="width: 80%;"></div></div><div class="soil-val">High</div></div>
                    <div class="soil-bar-group"><div class="soil-label">Phosp.</div><div class="soil-progress"><div class="soil-fill fill-p" style="width: 50%;"></div></div><div class="soil-val">Med</div></div>
                    <div class="soil-bar-group"><div class="soil-label">Potas.</div><div class="soil-progress"><div class="soil-fill fill-k" style="width: 70%;"></div></div><div class="soil-val">Good</div></div>
                </div>
            </div>
        </div>
    </section>

    <section id="addProduct" class="page-section">
        <div class="content-card">
            <h2><?php echo $lang['farmer_add_product_title'] ?? 'Add New Product'; ?></h2>
            <?php if(!empty($msg)): ?><div style="padding:10px; border-radius:8px; margin-bottom:15px; color:white; background:<?= $msg['type']=='error' ? 'var(--danger-color)' : 'var(--primary-color)' ?>"><?= htmlspecialchars($msg['text']) ?></div><?php endif; ?>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_product">
                <div class="form-grid">
                    <div><label for="name"><?php echo $lang['farmer_add_product_name'] ?? 'Product Name'; ?></label><input type="text" id="name" name="name" required></div>
                    <div><label for="category_id"><?php echo $lang['farmer_add_product_category'] ?? 'Category'; ?></label><select id="category_id" name="category_id" required><option value=""><?php echo $lang['farmer_add_product_select_cat'] ?? 'Select Category'; ?></option><?php foreach($cats as $c): ?><option value="<?=$c['id']?>"><?=htmlspecialchars($c['name'])?></option><?php endforeach; ?></select></div>
                    <div><label for="price"><?php echo $lang['farmer_add_product_price'] ?? 'Price (₹)'; ?></label><input type="number" id="price" step="0.01" name="price" required></div>
                    <div><label for="qty"><?php echo $lang['farmer_add_product_qty'] ?? 'Quantity'; ?></label><input type="number" id="qty" name="qty" required></div>
                    <div><label for="discount"><?php echo $lang['farmer_add_product_discount'] ?? 'Discount (%)'; ?></label><input type="number" id="discount" name="discount_percent" min="0" max="100" value="0"></div>
                    <div><label for="image"><?php echo $lang['farmer_add_product_image'] ?? 'Product Image'; ?></label><input type="file" id="image" name="image" accept="image/*"></div>
                    <div class="full-width"><label for="description"><?php echo $lang['farmer_add_product_desc'] ?? 'Description'; ?></label><textarea id="description" name="description" rows="4"></textarea></div>
                    <div class="full-width"><img id="image-preview" src="#" alt="Preview" style="display:none; max-height:200px; border-radius:10px;" /></div>
                </div>
                <button type="submit" class="ripple-btn">➕ <?php echo $lang['farmer_add_product_button'] ?? 'Add Product'; ?></button>
            </form>
        </div>
    </section>

    <section id="myProducts" class="page-section">
        <div class="content-card">
            <h2><?php echo $lang['farmer_my_products_title'] ?? 'My Products'; ?></h2>
            <div class="product-grid" id="productGrid">
                <?php if (empty($myProducts)): ?><p style="text-align: center; color: var(--text-muted); grid-column: 1 / -1;"><?php echo $lang['farmer_my_products_no_data'] ?? 'No products added yet.'; ?></p><?php else: ?><?php foreach($myProducts as $p): ?>
                    <div class="product-card" data-name="<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>" data-id="<?= $p['id'] ?>">
                        <?php if ($p['discount_percent'] > 0): ?><div class="discount-badge"><?= $p['discount_percent'] ?><?php echo $lang['farmer_my_products_discount_off'] ?? '% OFF'; ?></div><?php endif; ?>
                        <img src="images/<?= $p['image'] ? htmlspecialchars($p['image']) : 'default.png' ?>" alt="<?= htmlspecialchars($p['name'])?>">
                        <div class="product-card-body">
                            <h4><?= htmlspecialchars($p['name'])?></h4><p><?= htmlspecialchars($p['description'])?></p>
                            <div class="product-card-meta"><span><?php echo $lang['farmer_my_products_stock'] ?? 'Stock:'; ?> <?= $p['qty']?></span><span class="price">₹<?= number_format($p['price'], 2)?></span></div>
                        </div>
                    </div>
                <?php endforeach; ?><?php endif; ?>
            </div>
        </div>
    </section>

    <section id="salesAnalytics" class="page-section">
        <div class="content-card">
            <h2><?php echo $lang['farmer_analytics_title'] ?? 'Analytics Overview'; ?></h2>
            <div id="chart-message-container"></div>
            <div id="chart-canvas-container" class="chart-grid">
                <div style="position: relative; height: 300px; width: 100%;"><canvas id="salesPie"></canvas></div>
                <div style="position: relative; height: 300px; width: 100%;"><canvas id="salesBar"></canvas></div>
                <div class="full-width" style="position: relative; height: 300px; width: 100%;"><canvas id="salesOverTime"></canvas></div>
            </div>
        </div>
    </section>
</main>

<button class="chat-widget-btn" onclick="toggleChat()">🤖</button>

<div class="chat-window" id="chatWindow">
    <div class="chat-header">
        <h5>KisanX AI Analyst</h5>
        <button class="chat-close" onclick="toggleChat()" style="background:none; border:none; color:white; font-size:24px; cursor:pointer;">×</button>
    </div>
    <div class="chat-body" id="chatBody"></div>
    <div class="chat-footer">
        <input type="text" id="chatInput" class="chat-input" placeholder="Ask: 'Onion rate?', 'Schemes?'" onkeypress="handleChatKey(event)">
        <button class="chat-send ripple-btn" onclick="sendChatMessage()">➤</button>
    </div>
</div>

<div id="notification" style="display:none; position:fixed; top:70px; right:20px; background:var(--primary-color); color:white; padding:12px 24px; border-radius:8px; z-index:2000; transition: opacity 0.3s;"></div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0/dist/chartjs-plugin-datalabels.min.js"></script>
<script>
// *** DATA & CONFIG ***
const FARMER_NAME = "<?= htmlspecialchars($farmer_name) ?>";
const LANG_JS = {
    greetingMorning: "<?php echo $lang['farmer_header_greeting_morning'] ?? 'Good Morning'; ?>",
    greetingAfternoon: "<?php echo $lang['farmer_header_greeting_afternoon'] ?? 'Good Afternoon'; ?>",
    greetingEvening: "<?php echo $lang['farmer_header_greeting_evening'] ?? 'Good Evening'; ?>",
    darkMode: "<?php echo $lang['farmer_header_dark_mode'] ?? 'Dark Mode'; ?>",
    lightMode: "<?php echo $lang['farmer_header_light_mode'] ?? 'Light Mode'; ?>",
    exportNotification: "<?php echo $lang['farmer_notification_export'] ?? 'CSV Exported Successfully'; ?>",
    analyticsNoData: "<?php echo $lang['farmer_analytics_no_data'] ?? 'Not enough data to display charts'; ?>",
    pieLabelSold: "<?php echo $lang['farmer_analytics_pie_label_1'] ?? 'Sold'; ?>",
    pieLabelStock: "<?php echo $lang['farmer_analytics_pie_label_2'] ?? 'In Stock'; ?>",
    barLabel: "<?php echo $lang['farmer_analytics_bar_label'] ?? 'Revenue'; ?>",
    lineLabel: "<?php echo $lang['farmer_analytics_line_label'] ?? 'Sales Trend'; ?>"
};

// Database snapshots for JS
const DASHBOARD_DATA = {
    todayIncome: <?= (float)($today_stats['today_income'] ?? 0) ?>,
    todayOrders: <?= (int)($today_stats['today_orders'] ?? 0) ?>,
    totalIncome: <?= (float)($initial_stats['total_income'] ?? 0) ?>,
    totalSold: <?= (int)($initial_stats['total_sold'] ?? 0) ?>,
    lowStock: <?= json_encode($low_stock_items) ?>,
    topSelling: <?= json_encode($top_selling_items) ?>,
    recentOrders: <?= json_encode($initial_recent_orders) ?>
};

const FIELD_DATA = {
    weather: { temp: 28, humidity: 65, wind: 12, condition: "Sunny", rain_forecast: false },
    soil: { moisture: 35, ph: 6.2, nitrogen: "High", phosphorus: "Medium", potassium: "Good" }
};

// --- SIMULATED LIVE NEWS DATABASE ---
const LATEST_NEWS = [
    "📢 **Output:** India hits 357 MT foodgrain!",
    "📢 **PM Kisan:** 22nd Installment in Feb.",
    "📢 **Wheat:** Sowing area up by 17%.",
    "📢 **Scheme:** 'Drone Didi' for spraying.",
    "📢 **Market:** Onion prices stabilizing."
];

// --- MARKET DATABASE (Mutable for Live Updates) ---
let MARKET_PRICES = {
    'tomato': { today: 31, yesterday: 33, month_avg: 25, unit: 'kg', trend: 'down' },
    'onion': { today: 30, yesterday: 28, month_avg: 25, unit: 'kg', trend: 'up' },
    'potato': { today: 22, yesterday: 20, month_avg: 18, unit: 'kg', trend: 'up' },
    'wheat': { today: 2850, yesterday: 2800, month_avg: 2700, unit: 'quintal', trend: 'up' },
    'rice': { today: 3500, yesterday: 3550, month_avg: 3400, unit: 'quintal', trend: 'down' },
    'cotton': { today: 6000, yesterday: 5900, month_avg: 5800, unit: 'quintal', trend: 'up' },
    'turmeric': { today: 8500, yesterday: 8400, month_avg: 8200, unit: 'quintal', trend: 'up' },
    'chili': { today: 60, yesterday: 55, month_avg: 50, unit: 'kg', trend: 'up' },
    'apple': { today: 120, yesterday: 115, month_avg: 110, unit: 'kg', trend: 'up' },
    'banana': { today: 40, yesterday: 40, month_avg: 35, unit: 'dozen', trend: 'stable' }
};

document.addEventListener('DOMContentLoaded', () => {
    
    // --- 1. NEWS TICKER INITIALIZATION ---
    const ticker = document.getElementById('newsTicker');
    if(ticker) {
        LATEST_NEWS.forEach(news => {
            const span = document.createElement('span');
            span.className = 'news-item';
            span.innerHTML = news.replace(/\*\*/g, '');
            ticker.appendChild(span);
        });
    }

    // --- 2. THE BRAIN UPDATE FUNCTION (Every 30 Minutes) ---
    function updateBrain() {
        console.log("🔄 Updating AI Brain with latest market data...");
        const keys = Object.keys(MARKET_PRICES);
        keys.forEach(key => {
            const fluctuation = Math.floor(Math.random() * 5) - 2; 
            MARKET_PRICES[key].yesterday = MARKET_PRICES[key].today;
            MARKET_PRICES[key].today += fluctuation;
            if (MARKET_PRICES[key].today > MARKET_PRICES[key].yesterday) MARKET_PRICES[key].trend = 'up';
            else if (MARKET_PRICES[key].today < MARKET_PRICES[key].yesterday) MARKET_PRICES[key].trend = 'down';
            else MARKET_PRICES[key].trend = 'stable';
        });
    }

    setTimeout(updateBrain, 2000); 
    setInterval(updateBrain, 1800000); 

    // --- 3. PERSONALIZED GREETING ---
    function getWelcomeMessage() {
        const hour = new Date().getHours();
        let greeting = "Namaste";
        if (hour < 12) greeting = "Good Morning";
        else if (hour < 18) greeting = "Good Afternoon";
        else greeting = "Good Evening";
        return `**${greeting}, ${FARMER_NAME}! 🌾**\n\nKisanX Go\n\n• **News:** Wheat sowing is up 17% this year.\n• **Alert:** ${DASHBOARD_DATA.lowStock.length > 0 ? "Check Low Stock (" + DASHBOARD_DATA.lowStock.length + " items)" : "Inventory healthy"}\n\nAsk me about **Govt Schemes**, **Weather**, or **Crop Prices**!`;
    }
    
    const chatBody = document.getElementById('chatBody');
    if(chatBody) {
        const div = document.createElement('div');
        div.className = 'chat-message chat-bot';
        div.innerHTML = formatMessage(getWelcomeMessage());
        chatBody.appendChild(div);
    }

    // --- RIPPLE EFFECT ---
    function createRipple(event) {
        const button = event.currentTarget;
        const circle = document.createElement("span");
        const diameter = Math.max(button.clientWidth, button.clientHeight);
        const radius = diameter / 2;
        circle.style.width = circle.style.height = `${diameter}px`;
        circle.style.left = `${event.clientX - button.getBoundingClientRect().left - radius}px`;
        circle.style.top = `${event.clientY - button.getBoundingClientRect().top - radius}px`;
        circle.classList.add("ripple");
        const ripple = button.getElementsByClassName("ripple")[0];
        if (ripple) { ripple.remove(); }
        button.appendChild(circle);
    }
    const buttons = document.getElementsByClassName("ripple-btn");
    for (const button of buttons) { button.addEventListener("click", createRipple); }

    // Navigation Logic
    const navLinks = document.querySelectorAll('.nav-link');
    const pageSections = document.querySelectorAll('.page-section');
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.getElementById('menu-toggle');
    const toggleBtn = document.getElementById('toggleDark');
    const productSearch = document.getElementById('productSearch');
    const exportCSV = document.getElementById('exportCSV');
    const notification = document.getElementById('notification');
    let chartsInitialized = false;

    function showPage(hash) {
        const targetHash = (hash && document.querySelector(hash)) ? hash : '#dashboard';
        navLinks.forEach(link => link.classList.toggle('active', link.hash === targetHash));
        pageSections.forEach(section => section.classList.toggle('active', '#' + section.id === targetHash));
        if (window.innerWidth <= 992) sidebar.classList.remove('open');
        if (targetHash === '#dashboard') { document.querySelectorAll('#dashboard .stagger-in').forEach((card, index) => { card.style.animationDelay = `${index * 100}ms`; }); }
        if (targetHash === '#salesAnalytics' && !chartsInitialized) { setTimeout(() => { initializeCharts(); chartsInitialized = true; }, 150); }
        if (targetHash === '#myProducts') { document.querySelectorAll('#myProducts .product-card').forEach((card, index) => { card.style.animationDelay = `${index * 80}ms`; card.classList.add('stagger-in'); }); }
    }
    
    navLinks.forEach(link => { 
        if (link.getAttribute('href').startsWith('#')) {
            link.addEventListener('click', e => { 
                e.preventDefault(); 
                const hash = link.hash; 
                if (window.location.hash !== hash) history.pushState(null, '', hash); 
                showPage(hash); 
            }); 
        }
    });
    
    window.addEventListener('popstate', () => showPage(window.location.hash));
    if (menuToggle) menuToggle.addEventListener('click', () => sidebar.classList.toggle('open'));
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', (e) => {
        if(window.innerWidth <= 992 && sidebar.classList.contains('open') && !sidebar.contains(e.target) && e.target !== menuToggle) {
            sidebar.classList.remove('open');
        }
    });

    function showNotification(text, timeout=3500){ if (!notification) return; notification.textContent = text; notification.style.display = 'block'; notification.style.opacity = '1'; setTimeout(()=>{ notification.style.opacity = '0'; notification.addEventListener('transitionend', ()=> notification.style.display = 'none', { once:true }); }, timeout); }
    
    if (toggleBtn) { const userTheme = localStorage.getItem('theme'); const isInitiallyDark = (userTheme === 'dark') || (userTheme === null); if (isInitiallyDark) { document.body.classList.add('dark'); toggleBtn.innerHTML = `☀`; } else { document.body.classList.remove('dark'); toggleBtn.innerHTML = `🌙`; } toggleBtn.addEventListener('click', () => { document.body.classList.toggle('dark'); const isDark = document.body.classList.contains('dark'); localStorage.setItem('theme', isDark ? 'dark' : 'light'); toggleBtn.innerHTML = isDark ? `☀` : `🌙`; if (chartsInitialized) initializeCharts(); }); }
    
    // Greeting
    const greetingEl = document.getElementById('dynamic-greeting'); if (greetingEl) { const hour = new Date().getHours(); let greeting = LANG_JS.greetingEvening; if (hour < 12) greeting = LANG_JS.greetingMorning; else if (hour < 18) greeting = LANG_JS.greetingAfternoon; greetingEl.textContent = greeting; }
    
    // Animate Stats
    function animateStat(id, from, to, decimals=0){ const el = document.getElementById(id); if(!el) return; const start = Date.now(); const dur = 1200; const diff = to - from; function frame(){ const pct = Math.min(1, (Date.now()-start)/dur); const cur = from + diff * (1 - Math.pow(1-pct, 3)); const rounded = decimals ? cur.toFixed(decimals) : Math.round(cur); el.textContent = (id.includes('income') ? '₹' : '') + rounded; if(pct < 1) requestAnimationFrame(frame); else { const final = decimals ? to.toFixed(decimals) : to; el.textContent = (id.includes('income') ? '₹' : '') + final; el.classList.add('stat-update'); setTimeout(()=>el.classList.remove('stat-update'), 600); } } frame(); }
    animateStat('income', 0, <?= (float)($initial_stats['total_income'] ?? 0) ?>, 2); animateStat('sold', 0, <?= (int)($initial_stats['total_sold'] ?? 0) ?>, 0); animateStat('today_income', 0, <?= (float)($today_stats['today_income'] ?? 0) ?>, 2); animateStat('today_orders', 0, <?= (int)($today_stats['today_orders'] ?? 0) ?>, 0);
    
    showPage(window.location.hash);
    
    const imageInput = document.getElementById('image'); if (imageInput) { imageInput.addEventListener('change', function() { if (this.files && this.files[0]) { const reader = new FileReader(); reader.onload = (e) => { const preview = document.getElementById('image-preview'); if (preview) { preview.src = e.target.result; preview.style.display = 'block'; } }; reader.readAsDataURL(this.files[0]); } }); }
    
    if (productSearch) { productSearch.addEventListener('input', e => { const term = e.target.value.trim().toLowerCase(); document.querySelectorAll('#productGrid .product-card').forEach(card => { const name = (card.dataset.name || '').toLowerCase(); card.style.display = name.includes(term) ? 'flex' : 'none'; }); }); }

    if (exportCSV) { exportCSV.addEventListener('click', ()=>{ const rows = [['ID','Name','Price','Qty','Image']]; document.querySelectorAll('#productGrid .product-card').forEach(card=>{ if (card.style.display === 'none') return; const id = card.dataset.id || ''; const name = card.dataset.name || ''; const price = card.querySelector('.price')?.textContent.replace('₹','').trim() ?? ''; const stockInfo = card.querySelector('.product-card-meta > span:first-child')?.textContent ?? ''; const qty = stockInfo.replace('Stock', '').trim(); const img = card.querySelector('img')?.src.split('/').pop() ?? ''; rows.push([id, name, price, qty, img]); }); const csv = rows.map(r => r.map(c => `"${String(c).replace(/"/g, '""')}"`).join(',')).join('\n'); const blob = new Blob([csv], { type: 'text/csv' }); const url = URL.createObjectURL(blob); const a = document.createElement('a'); a.href = url; a.download = 'my_products.csv'; document.body.appendChild(a); a.click(); a.remove(); URL.revokeObjectURL(url); showNotification(LANG_JS.exportNotification); }); }

    function initializeCharts() { const salesData = { sold: <?= (int)($initial_stats['total_sold'] ?? 0) ?>, unsold: <?= (int)array_sum(array_column($myProducts, 'qty')) ?>, products: <?= json_encode(array_map(fn($p) => $p['name'], $myProducts)) ?>, income: <?= json_encode(array_map(fn($p) => ($p['price'] * ($p['sold_qty'] ?? 0)), $myProducts)) ?>, overTime: <?= json_encode($sales_over_time_data) ?> }; if (salesData.products.length === 0 && salesData.sold === 0) { document.getElementById('chart-canvas-container').style.display = 'none'; document.getElementById('chart-message-container').innerHTML = `<p style="text-align: center; color: var(--text-muted); padding: 40px 0;">${LANG_JS.analyticsNoData}</p>`; return; } Chart.register(ChartDataLabels); const isDark = document.body.classList.contains('dark'); const textColor = isDark ? '#E1E8E6' : '#1A2E26'; const gridColor = isDark ? 'rgba(47, 69, 62, 0.5)' : 'rgba(220, 227, 225, 0.5)'; ['salesPie', 'salesBar', 'salesOverTime'].forEach(id => { const chart = Chart.getChart(id); if (chart) chart.destroy(); }); new Chart(document.getElementById('salesPie'), { type: 'doughnut', data: { labels: [LANG_JS.pieLabelSold, LANG_JS.pieLabelStock], datasets: [{ data: [salesData.sold, salesData.unsold], backgroundColor: ['#2C7865', '#F5A623'], borderColor: isDark ? '#151d1a' : '#FFFFFF', borderWidth: 5 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { color: textColor } }, datalabels: { color: '#fff', font: { weight: 'bold' }, formatter: (v) => v > 0 ? v : '' } } } }); new Chart(document.getElementById('salesBar'), { type: 'bar', data: { labels: salesData.products, datasets: [{ label: LANG_JS.barLabel, data: salesData.income, backgroundColor: '#2C7865', borderRadius: 8 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, datalabels: { display: false } }, scales: { x: { ticks: { color: textColor }, grid: { display: false } }, y: { ticks: { color: textColor }, grid: { color: gridColor } } } } }); const ctx = document.getElementById('salesOverTime').getContext('2d'); const gradient = ctx.createLinearGradient(0, 0, 0, 320); gradient.addColorStop(0, 'rgba(44, 120, 101, 0.5)'); gradient.addColorStop(1, 'rgba(44, 120, 101, 0)'); new Chart(ctx, { type: 'line', data: { labels: salesData.overTime.map(d => new Date(d.sale_date).toLocaleString('en-IN', { day: 'numeric', month: 'short'})), datasets: [{ label: LANG_JS.lineLabel, data: salesData.overTime.map(d => d.daily_total), borderColor: '#2C7865', backgroundColor: gradient, fill: true, tension: 0.35 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, datalabels: { display: false } }, scales: { x: { ticks: { color: textColor }, grid: { display: false } }, y: { ticks: { color: textColor }, grid: { color: gridColor } } } } }); }
});

// ============================================
// 🧠 SUPER-INTELLIGENT AI BOT LOGIC (GEMINI STYLE)
// ============================================

function toggleChat() {
    const win = document.getElementById('chatWindow');
    if (win.style.display === 'flex') { win.style.display = 'none'; }
    else { win.style.display = 'flex'; document.getElementById('chatInput').focus(); }
}
function handleChatKey(e) { if(e.key === 'Enter') sendChatMessage(); }

function sendChatMessage() {
    const input = document.getElementById('chatInput');
    const msg = input.value.trim();
    if(!msg) return;
    
    addMessage(msg, 'user');
    input.value = '';
    
    // Thinking Indicator
    const chatBody = document.getElementById('chatBody');
    const loading = document.createElement('div');
    loading.className = 'chat-message chat-bot';
    loading.innerHTML = '<i>Thinking...</i>';
    loading.id = 'ai-loading';
    chatBody.appendChild(loading);
    chatBody.scrollTop = chatBody.scrollHeight;

    setTimeout(() => {
        const loader = document.getElementById('ai-loading');
        if(loader) loader.remove();
        const response = generateSmartResponse(msg);
        addMessage(response, 'bot');
    }, 800);
}

function addMessage(text, sender) {
    const div = document.createElement('div');
    div.className = 'chat-message chat-' + sender;
    div.innerHTML = formatMessage(text);
    const body = document.getElementById('chatBody');
    body.appendChild(div);
    body.scrollTop = body.scrollHeight;
}

function formatMessage(text) {
    return text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
               .replace(/\n/g, '<br>');
}

// *** THE AI BRAIN (Handles Everything) ***
function generateSmartResponse(input) {
    let text = input.toLowerCase();
    
    // 1. CONVERSATIONAL LAYER (GEMINI-STYLE)
    if (text.match(/^(hi|hello|hey|namaste|ram ram|hola)/)) {
        return "Namaste! 🙏 I am your KisanX Farming Assistant. How can I help you grow today? 🌱";
    }
    if (text.includes('how are you') || text.includes('kaise ho')) {
        return "I am an AI, so I don't get tired! 🤖 But I am functioning at 100% capacity to help you make profits. How is your farm doing?";
    }
    if (text.includes('who are you') || text.includes('tum kaun ho')) {
        return "I am **KisanX AI**, a specialized intelligence designed to help Indian farmers with **market prices, weather forecasts, and selling strategies**. 🚜";
    }
    if (text.includes('thank') || text.includes('dhanyavad') || text.includes('shukriya')) {
        return "You are welcome! Happy Farming! 🌾 Let me know if you need anything else.";
    }

    // 2. INTENT: SPECIFIC CROP ANALYSIS (Price, Yesterday, Strategy, Suggest, Range)
    let foundCrop = null;
    const crops = Object.keys(MARKET_PRICES);
    for (let c of crops) {
        if (text.includes(c)) { foundCrop = c; break; }
    }

    if (foundCrop) {
        const data = MARKET_PRICES[foundCrop];
        const diff = data.today - data.yesterday;
        const trendIcon = diff > 0 ? "📈" : (diff < 0 ? "📉" : "➖");

        // A. Ask for Yesterday's Price
        if (text.includes('yesterday') || text.includes('kal') || text.includes('last price')) {
            return `**${foundCrop.toUpperCase()} Price History:**\n\n• **Yesterday:** ₹${data.yesterday}/${data.unit}\n• **Today:** ₹${data.today}/${data.unit}\n\nThe price has moved by ₹${Math.abs(diff)} (${diff > 0 ? 'Up' : 'Down'}) since yesterday.`;
        }

        // B. Ask for Selling Strategy for specific crop
        if (text.includes('strategy') || text.includes('bechu') || text.includes('sell')) {
            if (diff > 0) return `**Strategy for ${foundCrop.toUpperCase()} (Rising Market 📈):**\n\nDemand is high! The price rose by ₹${diff} today.\n👉 **Advice:** Hold your stock for 2-3 more days to get an even better rate.`;
            if (diff < 0) return `**Strategy for ${foundCrop.toUpperCase()} (Falling Market 📉):**\n\nAlert! Price dropped by ₹${Math.abs(diff)} today.\n👉 **Advice:** Sell immediately before rates drop further.`;
            return `**Strategy for ${foundCrop.toUpperCase()} (Stable ➖):**\n\nMarket is steady. You can sell 50% now and hold the rest.`;
        }

        // C. Price Suggestion / Range
        if (text.includes('suggest') || text.includes('range') || text.includes('sahi rate') || text.includes('what price')) {
            const minPrice = Math.floor(data.today * 0.95);
            const maxPrice = Math.ceil(data.today * 1.10);
            return `**Price Suggestion for ${foundCrop.toUpperCase()}:**\n\n• **Current Market:** ₹${data.today}/${data.unit}\n• **Safe Range:** ₹${minPrice} - ₹${maxPrice}\n\n💡 **My Advice:**\nSet price at **₹${Math.ceil(data.today * 1.02)}** to stay competitive but earn profit.`;
        }

        // D. Default Crop Report
        return `**${foundCrop.toUpperCase()} Update:**\n\n• Rate: ₹${data.today}/${data.unit}\n• Trend: ${trendIcon} (${diff > 0 ? 'Rising' : 'Falling'})\n\n**Advice:** ${diff >= 0 ? "Hold for 2 days." : "Sell immediately."}`;
    }

    // 3. INTENT: GENERAL SELLING STRATEGY (No Crop Mentioned)
    if (text.includes('strategy') || text.includes('plan') || text.includes('idea')) {
         return `**💡 General Selling Strategy:**\n\n1. **Vegetables:** Harvest early morning (4 AM - 6 AM) and reach mandi by 9 AM for best rates.\n2. **Grains:** If you have storage, hold for 2-3 months post-harvest.\n3. **Trends:** Check the "News" section to see which crops are in demand.\n\n*Ask me about a specific crop (e.g., "Wheat strategy") for better advice.*`;
    }

    // 4. INTENT: WEATHER (Context Aware: Kal vs Aaj)
    if (text.includes('mausam') || text.includes('weather') || text.includes('barish') || text.includes('rain')) {
        const w = FIELD_DATA.weather;
        
        // Check time context
        if (text.includes('kal') || text.includes('tomorrow')) {
             // Simulating a forecast different from current
             return `**🌤 Weather Forecast for Tomorrow:**\n\nExpect a sunny day with slightly higher temperatures (30°C). No rain predicted. ✅ \n\n*Great day for spraying fertilizers.*`;
        } else {
             let advice = w.rain_forecast ? "⚠️ Rain Alert! Do not spray pesticides." : "✅ Clear Skies. Safe for fieldwork.";
             return `**🌤 Current Weather (Today):**\n\n• Condition: **${w.condition}**\n• Temp: ${w.temp}°C\n• Humidity: ${w.humidity}%\n\n👉 **Advice:** ${advice}`;
        }
    }

    // 5. INTENT: BEST SELLING PRODUCT
    if (text.includes('best selling') || text.includes('top') || text.includes('jyada bikne wala')) {
        const topItem = DASHBOARD_DATA.topSelling[0];
        if (topItem) {
            return `**🏆 Top Performer:**\n\nYour **${topItem.name}** is #1 with ${topItem.total_sold} units sold!\n\n👉 **Strategy:** You can safely increase the price by 5% to maximize profit.`;
        } else {
            return "No sales data yet to determine a winner. Add more products!";
        }
    }

    // 6. INTENT: PM KISAN / NEWS
    if (text.includes('pm kisan') || text.includes('installment')) {
        return `**PM Kisan Update (Dec 2025):**\n\n✅ **21st Installment:** Released on Nov 19, 2025.\n📅 **Next (22nd):** Expected in **February 2026**.\n\n👉 **Action:** Check your status at **pmkisan.gov.in**.`;
    }

    // 7. FALLBACK
    return "I am not sure I understand. I can help with **Prices**, **Weather**, **Selling Strategy**, or **Govt Schemes**. \n\nTry asking: *'Tomato price yesterday'* or *'Kal mausam kaisa rahega?'*";
}
</script>
</body>
</html>