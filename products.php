<?php
// products.php
session_start();
// Assumes language_init.php and db.php exist.
include 'php/language_init.php';
include 'php/db.php';

// Check if user is logged in
$is_logged_in = !empty($_SESSION['user']) && $_SESSION['user']['role'] === 'customer';
$user_id = $is_logged_in ? $_SESSION['user']['id'] : null;

/* ===== HANDLE ALL POST ACTIONS ON THIS PAGE ===== */
if($_SERVER['REQUEST_METHOD'] === 'POST' && $is_logged_in){
    // 1. Wishlist Action
    if(isset($_POST['wishlist_action'])){
        $pid = (int)($_POST['product_id'] ?? 0);
        if($pid){
            if(!isset($_SESSION['wishlist'])) $_SESSION['wishlist'] = [];
            
            if($_POST['wishlist_action']==='add') {
                $_SESSION['wishlist'][$pid]=time();
                $_SESSION['popup_message'] = $lang['toast_wishlist_add'] ?? 'Added to Wishlist';
            }
            if($_POST['wishlist_action']==='remove') {
                unset($_SESSION['wishlist'][$pid]);
                $_SESSION['popup_message'] = $lang['toast_wishlist_remove'] ?? 'Removed from Wishlist';
            }
        }
    }
    
    // 2. Cart Action
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
                $_SESSION['popup_message'] = $lang['toast_cart_update'] ?? 'Cart Updated';
            } else {
                $insertStmt = $pdo->prepare("INSERT INTO cart_items (user_id, product_id, qty) VALUES (?, ?, ?)");
                $insertStmt->execute([$user_id, $product_id, $qty]);
                $_SESSION['popup_message'] = $lang['toast_cart_add'] ?? 'Added to Cart';
            }
        }
    }
    
    // Redirect to prevent form resubmission
    $redirect_url = 'products.php?' . http_build_query($_GET);
    header('Location: ' . $redirect_url);
    exit;
}

// Handle Popups
$popup_message = '';
if(isset($_SESSION['popup_message'])){
    $popup_message = $_SESSION['popup_message'];
    unset($_SESSION['popup_message']);
}

// Fetch Categories & Search Params
$category_name = isset($_GET['category']) ? trim($_GET['category']) : null;
$search = trim($_GET['q'] ?? '');

// Build Query
$sql = "SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id";
$params = [];
$where_clauses = [];

if ($category_name) {
    $where_clauses[] = "c.name = ?";
    $params[] = $category_name;
}

if($search){
    $where_clauses[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[]="%$search%";
    $params[]="%$search%";
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}
$sql .= " ORDER BY p.name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Fetch All Products (for "All Products" section)
$all_products_stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.name ASC");
$all_products = $all_products_stmt->fetchAll();

$cats = $pdo->query('SELECT * FROM categories')->fetchAll();
$wishlist_ids = array_keys($_SESSION['wishlist'] ?? []);
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang ?? 'en'; ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title><?php echo $lang['products_page_title'] ?? 'Our Products'; ?></title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />

<style>
/* --- CORE VARIABLES (Matching Dashboard Style) --- */
:root {
    --primary-color: #2C7865; --primary-dark: #1A4D2E; --accent-color: #F5A623; --danger-color: #D0021B;
    --bg-light: #F4F6F5; --card-bg-light: #FFFFFF; --text-light: #1A2E26; --text-muted-light: #5A6D67; --border-light: #DCE3E1;
    --bg-dark: #0f1412; --card-bg-dark: #151d1a; --text-dark: #E1E8E6; --text-muted-dark: #8F9D99; --border-dark: #25342e;
    --shadow-color: rgba(44, 120, 101, 0.08);
    --sidebar-width: 260px;
    --transition: 0.3s ease;
}

/* --- RESET & BASIC --- */
* { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
body {
    --bg: var(--bg-light); --card-bg: var(--card-bg-light); --text: var(--text-light); --text-muted: var(--text-muted-light); --border: var(--border-light);
    font-family: 'Poppins', sans-serif; margin: 0; background-color: var(--bg); color: var(--text);
    overflow-x: hidden; transition: background-color var(--transition), color var(--transition);
}
body.dark { --bg: var(--bg-dark); --card-bg: var(--card-bg-dark); --text: var(--text-dark); --text-muted: var(--text-muted-dark); --border: var(--border-dark); }

/* --- SIDEBAR --- */
.sidebar {
    position: fixed; top: 0; left: 0; width: var(--sidebar-width); height: 100vh;
    background: var(--card-bg); border-right: 1px solid var(--border);
    z-index: 1000; padding-top: 80px; transition: transform var(--transition);
    display: flex; flex-direction: column;
}
.sidebar a {
    padding: 15px 25px; color: var(--text-muted); text-decoration: none; font-weight: 500;
    display: flex; align-items: center; gap: 15px; border-left: 4px solid transparent; transition: var(--transition);
}
.sidebar a:hover, .sidebar a.active {
    background: color-mix(in srgb, var(--primary-color) 10%, transparent);
    color: var(--text); border-left-color: var(--primary-color);
}

/* --- MAIN LAYOUT --- */
.main-content {
    margin-left: var(--sidebar-width); padding: 30px; min-height: 100vh; transition: margin var(--transition);
}
.page-overlay {
    display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.5); z-index: 900; backdrop-filter: blur(4px);
}

/* --- HEADER & SEARCH --- */
.header-bar {
    display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;
}
.section-title {
    font-family: 'Montserrat', sans-serif; font-size: 28px; font-weight: 800;
    color: var(--text); margin: 0;
}
.section-title span { color: var(--primary-color); }

.search-container {
    background: var(--card-bg); padding: 20px; border-radius: 16px; border: 1px solid var(--border);
    display: flex; gap: 15px; box-shadow: 0 4px 20px var(--shadow-color); margin-bottom: 30px; flex-wrap: wrap;
}
.search-input {
    flex: 1; padding: 12px 15px; border-radius: 10px; border: 1px solid var(--border);
    background: var(--bg); color: var(--text); font-size: 15px; outline: none; min-width: 200px;
}
.category-select {
    padding: 12px 15px; border-radius: 10px; border: 1px solid var(--border);
    background: var(--bg); color: var(--text); font-size: 15px; cursor: pointer; outline: none;
}
.search-btn {
    background: var(--primary-color); color: white; border: none; padding: 12px 25px;
    border-radius: 10px; font-weight: 600; cursor: pointer; transition: transform 0.2s;
}
.search-btn:active { transform: scale(0.95); }

/* --- PRODUCT GRID --- */
.product-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 25px;
}
.card {
    background: var(--card-bg); border-radius: 16px; overflow: hidden;
    border: 1px solid var(--border); box-shadow: 0 5px 15px var(--shadow-color);
    transition: transform 0.3s, box-shadow 0.3s; display: flex; flex-direction: column; position: relative;
}
.card:hover { transform: translateY(-7px); box-shadow: 0 12px 30px rgba(0,0,0,0.15); border-color: var(--primary-color); }

.img-wrapper { position: relative; padding-top: 75%; overflow: hidden; }
.img-wrapper img {
    position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;
    transition: transform 0.5s;
}
.card:hover .img-wrapper img { transform: scale(1.1); }

.card-body { padding: 18px; flex: 1; display: flex; flex-direction: column; justify-content: space-between; }
.card-title { font-size: 16px; font-weight: 700; margin: 0 0 8px; color: var(--text); }
.card-desc { font-size: 13px; color: var(--text-muted); margin: 0 0 15px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

.price-row { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; }
.current-price { font-size: 18px; font-weight: 700; color: var(--primary-color); }
.old-price { font-size: 13px; text-decoration: line-through; color: var(--text-muted); }
.discount-tag { background: var(--accent-color); color: #000; font-size: 11px; font-weight: 800; padding: 3px 8px; border-radius: 6px; }

/* --- ACTIONS & BUTTONS --- */
.action-row { display: flex; flex-direction: column; gap: 8px; margin-top: auto; }

.qty-control { display: flex; border: 1px solid var(--border); border-radius: 8px; overflow: hidden; margin-bottom: 5px; }
.qty-btn { background: var(--bg); border: none; width: 35px; color: var(--text); font-size: 18px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.qty-input { flex: 1; text-align: center; border: none; border-left: 1px solid var(--border); border-right: 1px solid var(--border); background: var(--card-bg); color: var(--text); font-weight: 600; width: 40px; }

.btn-cart {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: white; border: none; padding: 12px; border-radius: 10px; font-weight: 600;
    cursor: pointer; width: 100%; transition: opacity 0.2s;
}
.btn-cart:hover { opacity: 0.9; }

.btn-wishlist {
    background: transparent; border: 1px solid var(--border); color: var(--text-muted);
    padding: 10px; border-radius: 10px; cursor: pointer; font-size: 13px; font-weight: 500;
    width: 100%; transition: all 0.2s;
}
.btn-wishlist:hover { border-color: var(--accent-color); color: var(--accent-color); }
.btn-wishlist.active { background: #ffebeb; border-color: var(--danger-color); color: var(--danger-color); }

/* --- HAMBURGER MENU --- */
.hamburger {
    display: none; position: fixed; top: 20px; left: 20px; z-index: 1100;
    background: var(--card-bg); border: 1px solid var(--border); border-radius: 8px;
    padding: 8px; cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
.hamburger span { display: block; width: 25px; height: 3px; background: var(--text); margin: 5px 0; border-radius: 3px; }

/* --- MODAL --- */
.modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 2000; align-items: center; justify-content: center; backdrop-filter: blur(5px); }
.modal-content { background: var(--card-bg); padding: 30px; border-radius: 20px; width: 90%; max-width: 400px; text-align: center; position: relative; border: 1px solid var(--border); }
.modal-close { position: absolute; top: 15px; right: 20px; font-size: 24px; cursor: pointer; color: var(--text-muted); }
.modal-btn { display: block; padding: 12px; border-radius: 10px; margin-top: 10px; text-decoration: none; font-weight: 600; color: white; }
.btn-login { background: var(--primary-color); }
.btn-register { background: var(--accent-color); color: #000; }

/* --- TOAST --- */
.toast {
    position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%) translateY(50px);
    background: var(--text); color: var(--bg); padding: 12px 24px; border-radius: 30px;
    font-weight: 500; opacity: 0; transition: all 0.3s; z-index: 3000; pointer-events: none;
    box-shadow: 0 5px 20px rgba(0,0,0,0.3);
}
.toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }

/* --- RESPONSIVE --- */
@media (max-width: 992px) {
    .sidebar { transform: translateX(-100%); width: 280px; box-shadow: 5px 0 20px rgba(0,0,0,0.2); }
    .sidebar.open { transform: translateX(0); }
    .main-content { margin-left: 0; padding: 20px; padding-top: 80px; }
    .hamburger { display: block; }
    .page-overlay.show { display: block; }
}
@media (max-width: 600px) {
    .product-grid { grid-template-columns: 1fr; }
    .search-container { flex-direction: column; }
    .search-btn, .search-input, .category-select { width: 100%; }
    .section-title { font-size: 22px; }
}
</style>
</head>
<body class="dark"> <div class="page-overlay" id="overlay"></div>

    <div class="hamburger" id="hamburger">
        <span></span><span></span><span></span>
    </div>

    <nav class="sidebar" id="sidebar">
        <div style="padding: 25px; font-size: 24px; font-weight: 800; color: var(--primary-color); text-align: center;">kisanX</div>
        <a href="user_dashboard.php">🏠 <?php echo $lang['sidebar_dashboard'] ?? 'Dashboard'; ?></a>
        <a href="wishlist.php">💖 <?php echo $lang['sidebar_wishlist'] ?? 'Wishlist'; ?></a>
        <a href="cart.php">🛒 <?php echo $lang['sidebar_cart'] ?? 'My Cart'; ?></a>
        <a href="orders.php">📦 <?php echo $lang['sidebar_orders'] ?? 'My Orders'; ?></a>
    </nav>

    <main class="main-content">
        
        <div class="header-bar">
            <h3 class="section-title"><span><?php echo $lang['products_search_title_span'] ?? 'Explore'; ?></span> <?php echo $lang['products_search_title'] ?? 'Marketplace'; ?></h3>
            <button id="themeToggle" style="background:none; border:1px solid var(--border); padding:8px 12px; border-radius:8px; color:var(--text); cursor:pointer;">🌙</button>
        </div>

        <form method="get" class="search-container" data-aos="fade-up">
            <input type="text" name="q" class="search-input" placeholder="<?php echo $lang['products_search_placeholder'] ?? 'Search seeds, tools, crops...'; ?>" value="<?php echo htmlspecialchars($search); ?>">
            
            <select name="category" class="category-select">
                <option value=""><?php echo $lang['products_all_categories'] ?? 'All Categories'; ?></option>
                <?php foreach($cats as $c): ?>
                    <option value="<?php echo htmlspecialchars($c['name']); ?>" <?php if($category_name == $c['name']) echo 'selected'; ?>><?php echo htmlspecialchars($c['name']); ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="search-btn"><?php echo $lang['products_search_button'] ?? 'Search'; ?></button>
        </form>
        
        <?php if ($category_name || $search): ?>
            <h4 style="margin: 30px 0 20px; color: var(--text-muted);"><?php echo $lang['products_results_title'] ?? 'Results for'; ?> "<?= htmlspecialchars($category_name ?? $search) ?>"</h4>
            <div class="product-grid">
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $p): $inWishlist = in_array($p['id'], $wishlist_ids); ?>
                        <div class="card" data-aos="fade-up">
                            <div class="img-wrapper">
                                <img src="images/<?php echo htmlspecialchars($p['image'] ?? 'default.png'); ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                            </div>
                            <div class="card-body">
                                <div>
                                    <h4 class="card-title"><?= htmlspecialchars($p['name']) ?></h4>
                                    <p class="card-desc"><?= htmlspecialchars($p['description']) ?></p>
                                    <div class="price-row">
                                        <?php if(!empty($p['discount_percent']) && $p['discount_percent']>0): ?>
                                            <span class="current-price">₹<?= number_format($p['price']*(1-$p['discount_percent']/100),2) ?></span>
                                            <span class="old-price">₹<?= number_format($p['price'],2) ?></span>
                                            <span class="discount-tag">-<?= (int)$p['discount_percent'] ?>%</span>
                                        <?php else: ?>
                                            <span class="current-price">₹<?= number_format($p['price'],2) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="action-row">
                                    <form method="post" action="products.php?<?= http_build_query($_GET) ?>" class="cart-form">
                                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="cart_action" value="add">
                                        <div class="qty-control">
                                            <button type="button" class="qty-btn minus">-</button>
                                            <input type="number" name="qty" class="qty-input" value="1" min="1" readonly>
                                            <button type="button" class="qty-btn plus">+</button>
                                        </div>
                                        <button type="submit" class="btn-cart">🛒 <?php echo $lang['card_add_to_cart'] ?? 'Add to Cart'; ?></button>
                                    </form>
                                    <form method="post" action="products.php?<?= http_build_query($_GET) ?>">
                                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="wishlist_action" value="<?= $inWishlist ? 'remove' : 'add' ?>">
                                        <button type="submit" class="btn-wishlist <?= $inWishlist ? 'active' : '' ?>">
                                            <?= $inWishlist ? '❤️ In Wishlist' : '🤍 Add to Wishlist' ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="grid-column: 1/-1; text-align: center; padding: 40px; color: var(--text-muted);">No products found matching your search.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <h4 style="margin: 50px 0 20px; font-size: 20px; font-weight: 700; color: var(--text);"><?php echo $lang['products_all_title'] ?? 'All Products'; ?></h4>
        <div class="product-grid">
            <?php foreach ($all_products as $p): $inWishlist = in_array($p['id'], $wishlist_ids); ?>
                 <div class="card" data-aos="fade-up">
                    <div class="img-wrapper">
                        <img src="images/<?php echo htmlspecialchars($p['image'] ?? 'default.png'); ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                    </div>
                    <div class="card-body">
                        <div>
                            <h4 class="card-title"><?= htmlspecialchars($p['name']) ?></h4>
                            <p class="card-desc"><?= htmlspecialchars($p['description']) ?></p>
                            <div class="price-row">
                                <?php if(!empty($p['discount_percent']) && $p['discount_percent']>0): ?>
                                    <span class="current-price">₹<?= number_format($p['price']*(1-$p['discount_percent']/100),2) ?></span>
                                    <span class="old-price">₹<?= number_format($p['price'],2) ?></span>
                                    <span class="discount-tag">-<?= (int)$p['discount_percent'] ?>%</span>
                                <?php else: ?>
                                    <span class="current-price">₹<?= number_format($p['price'],2) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="action-row">
                            <form method="post" action="products.php?<?= http_build_query($_GET) ?>" class="cart-form">
                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="cart_action" value="add">
                                <div class="qty-control">
                                    <button type="button" class="qty-btn minus">-</button>
                                    <input type="number" name="qty" class="qty-input" value="1" min="1" readonly>
                                    <button type="button" class="qty-btn plus">+</button>
                                </div>
                                <button type="submit" class="btn-cart">🛒 <?php echo $lang['card_add_to_cart'] ?? 'Add to Cart'; ?></button>
                            </form>
                            <form method="post" action="products.php?<?= http_build_query($_GET) ?>">
                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="wishlist_action" value="<?= $inWishlist ? 'remove' : 'add' ?>">
                                <button type="submit" class="btn-wishlist <?= $inWishlist ? 'active' : '' ?>">
                                    <?= $inWishlist ? '❤️ In Wishlist' : '🤍 Add to Wishlist' ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <div id="authModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="document.getElementById('authModal').style.display='none'">&times;</span>
            <h2><?php echo $lang['modal_title'] ?? 'Please Login'; ?></h2>
            <p style="color: var(--text-muted); margin-bottom: 20px;">You need to be logged in to add items to cart.</p>
            <a href="login.php" class="modal-btn btn-login"><?php echo $lang['login'] ?? 'Login'; ?></a>
            <a href="register.php" class="modal-btn btn-register"><?php echo $lang['register'] ?? 'Register'; ?></a>
        </div>
    </div>

    <div id="toast" class="toast"></div>

    <?php include 'footer.php'; ?>
    
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            AOS.init({ duration: 800, once: true });
            
            // --- VARIABLES ---
            const hamburger = document.getElementById('hamburger');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            const themeBtn = document.getElementById('themeToggle');
            const authModal = document.getElementById('authModal');
            const toast = document.getElementById('toast');
            
            // --- SIDEBAR TOGGLE ---
            function toggleMenu() {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('show');
            }
            if(hamburger) hamburger.addEventListener('click', toggleMenu);
            if(overlay) overlay.addEventListener('click', toggleMenu);

            // --- THEME TOGGLE ---
            if(themeBtn) {
                // Check local storage
                if(localStorage.getItem('theme') === 'light') {
                    document.body.classList.remove('dark');
                    themeBtn.textContent = '☀';
                }
                themeBtn.addEventListener('click', () => {
                    document.body.classList.toggle('dark');
                    const isDark = document.body.classList.contains('dark');
                    localStorage.setItem('theme', isDark ? 'dark' : 'light');
                    themeBtn.textContent = isDark ? '🌙' : '☀';
                });
            }

            // --- QUANTITY SELECTOR ---
            document.querySelectorAll('.qty-control').forEach(ctrl => {
                const input = ctrl.querySelector('input');
                ctrl.querySelector('.minus').addEventListener('click', () => {
                    const val = parseInt(input.value);
                    if(val > 1) input.value = val - 1;
                });
                ctrl.querySelector('.plus').addEventListener('click', () => {
                    const val = parseInt(input.value);
                    input.value = val + 1;
                });
            });

            // --- AUTH CHECK & MODAL ---
            const isLoggedIn = <?php echo json_encode($is_logged_in); ?>;
            document.querySelectorAll('.cart-form, .btn-wishlist').forEach(el => {
                el.addEventListener('submit', (e) => checkAuth(e));
                // If it's just a button not in a form (unlikely but safe to add)
                if(el.tagName === 'BUTTON') el.addEventListener('click', (e) => checkAuth(e));
            });

            function checkAuth(e) {
                if(!isLoggedIn) {
                    e.preventDefault();
                    authModal.style.display = 'flex';
                }
            }

            // --- TOAST NOTIFICATION ---
            const serverMsg = "<?php echo $popup_message; ?>";
            if(serverMsg) {
                toast.textContent = serverMsg;
                toast.classList.add('show');
                setTimeout(() => toast.classList.remove('show'), 3000);
            }
        });
    </script>
</body>
</html>