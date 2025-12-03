<?php
session_start();
include 'php/language_init.php'; 
include 'php/db.php';

// Fetch data logic
$topStmt = $pdo->query("SELECT p.*, COALESCE(SUM(oi.qty),0) AS sold FROM products p LEFT JOIN order_items oi ON p.id=oi.product_id GROUP BY p.id ORDER BY sold DESC LIMIT 4");
$top = $topStmt->fetchAll();

$allStmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
$allProducts = $allStmt->fetchAll();
?>
<!doctype html>
<html lang="<?php echo $current_lang; ?>"> <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>kisanX — <?php echo $lang['site_tagline']; ?></title>
    
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
        /* =========================================
           1. CORE VARIABLES & THEME
           ========================================= */
        :root {
            --kd-bg: #12181b;
            --kd-bg-surface: #1a2226;
            --kd-green: #68d391;
            --kd-gold: #f5b041;
            --kd-text: #e6f1ff;
            --kd-muted: #a0aec0;
            --kd-danger: #e53e3e;
            --glass-bg: rgba(26, 34, 38, 0.6);
            --glass-border: rgba(160, 174, 192, 0.2);
            --card-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            --card-hover-shadow: 0 10px 25px rgba(0, 0, 0, 0.4), 0 0 15px rgba(104, 211, 145, 0.5);
            --info-blue: #3182ce;
        }
        
        /* LIGHT MODE */
        html.light-mode {
            --kd-bg: #F3F4F6;
            --kd-bg-surface: #FFFFFF;
            --kd-green: #059669;
            --kd-gold: #D97706;
            --kd-text: #111827;
            --kd-muted: #6B7280;
            --kd-danger: #DC2626;
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(0, 0, 0, 0.06);
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            --card-hover-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        *, *::before, *::after { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0; font-family: 'Poppins', sans-serif;
            background-color: var(--kd-bg); color: var(--kd-text);
            overflow-x: hidden; -webkit-tap-highlight-color: transparent;
        }

        /* PRELOADER */
        .preloader {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: var(--kd-bg); z-index: 9999;
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            transition: opacity 0.8s ease-in-out, filter 0.8s ease-in-out, visibility 0.8s;
        }
        .preloader.hidden { opacity: 0; filter: blur(20px); visibility: hidden; pointer-events: none; }
        .preloader-logo-container {
            width: 120px; height: 120px; border-radius: 50%;
            display: flex; justify-content: center; align-items: center;
            margin-bottom: 1.5rem; animation: pulse 2s infinite ease-in-out;
        }
        .preloader-logo-container img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
        .preloader-title { font-family: 'Montserrat', sans-serif; font-size: 1.5rem; color: var(--kd-muted); letter-spacing: 2px; opacity: 0; animation: fadeInText 1.5s 0.2s forwards; }
        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(104, 211, 145, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(104, 211, 145, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(104, 211, 145, 0); }
        }
        @keyframes fadeInText { to { opacity: 1; } }

        main.container { padding: 1rem 5%; max-width: 1400px; margin: 0 auto; position: relative; z-index: 10; width: 100%; }

        /* HERO SECTION */
        .hero {
            display: flex; align-items: center; justify-content: center; text-align: center;
            min-height: 90vh; /* Fallback */
            min-height: 90dvh; 
            position: relative; 
            padding: 6rem 1rem 4rem; 
            overflow: hidden;
        }
        /* Mobile adjustment for hero padding */
        @media (max-width: 768px) {
            .hero { padding: 5rem 1rem 3rem; min-height: 85dvh; }
        }

        .hero-background-gradient {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(ellipse at 50% 100%, rgba(104, 211, 145, 0.1), transparent 60%);
            z-index: 1;
        }
        .hero-field-silhouette {
            position: absolute; bottom: 0; left: 0; width: 100%; height: 40%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%2368d391" fill-opacity="1" d="M0,224L48,208C96,192,192,160,288,165.3C384,171,480,213,576,240C672,267,768,277,864,256C960,235,1056,181,1152,154.7C1248,128,1344,128,1392,128L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom center;
            background-size: cover; z-index: 2; opacity: 0.8;
            mask-image: linear-gradient(to top, black 50%, transparent 100%);
            -webkit-mask-image: linear-gradient(to top, black 50%, transparent 100%);
        }
        html.light-mode .hero-field-silhouette {
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23059669" fill-opacity="1" d="M0,224L48,208C96,192,192,160,288,165.3C384,171,480,213,576,240C672,267,768,277,864,256C960,235,1056,181,1152,154.7C1248,128,1344,128,1392,128L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
        }

        .hero-content { z-index: 5; position: relative; width: 100%; max-width: 800px; }
        
        /* RESTORED ORIGINAL ANIMATIONS */
        .hero-title {
            font-family: 'Montserrat', sans-serif; 
            font-size: clamp(2.5rem, 6vw, 4.5rem); /* Responsive Font Size */
            font-weight: 800; letter-spacing: -1px;
            color: #fff; text-shadow: 0 2px 20px rgba(0,0,0,0.5);
            margin: 0; display: flex; flex-wrap: wrap; justify-content: center; gap: 0.25em;
        }
        html.light-mode .hero-title { color: var(--kd-text); text-shadow: none; }

        .hero-title .h-word { 
            display: inline-block; opacity: 0; 
            transform: translateY(-100px) rotateZ(5deg);
            transition: transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94), opacity 0.8s ease-out; 
        }
        .hero-title .h-word.special { color: var(--kd-green); }
        
        .hero.is-visible .h-word:nth-child(1) { transition-delay: 0.2s; }
        .hero.is-visible .h-word:nth-child(2) { transition-delay: 0.4s; }
        .hero.is-visible .h-word:nth-child(3) { transition-delay: 0.6s; }
        .hero.is-visible .h-word { opacity: 1; transform: translateY(0) rotateZ(0); }

        .hero-content p {
            font-size: clamp(1rem, 3vw, 1.25rem); color: var(--kd-muted);
            margin: 1.5rem auto 2.5rem; max-width: 650px; line-height: 1.7;
        }
        
        .btn {
            display: inline-block; padding: 0.9rem 2.5rem; font-family: 'Poppins', sans-serif;
            font-weight: 600; font-size: 1rem; text-decoration: none;
            color: var(--kd-text); background: transparent;
            border: 2px solid var(--kd-gold); border-radius: 50px; transition: all 0.3s ease;
        }
        .btn:hover {
            background: var(--kd-gold); color: #fff; transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(217, 119, 6, 0.2);
        }

        /* CATEGORIES */
        .categories { margin-top: 4rem; overflow-x: hidden; }
        .section-title {
            font-family: 'Montserrat', sans-serif; font-size: clamp(1.8rem, 5vw, 2.5rem);
            font-weight: 700; text-align: center; margin: 4rem 0 2.5rem;
            color: var(--kd-text); letter-spacing: -0.5px;
        }
        .section-title span { color: var(--kd-green); }

        .category-grid { 
            display: flex; justify-content: center; gap: 2rem; flex-wrap: wrap; 
        }
        
        .category-item { 
            display: flex; flex-direction: column; align-items: center; 
            text-decoration: none; color: var(--kd-text); 
            opacity: 0; transition: opacity 0.7s ease-out, transform 0.7s ease-out; 
            width: 120px;
        }
        
        /* Original Slide Animation */
        .categories .category-item:nth-child(1), .categories .category-item:nth-child(2) { transform: translateX(-100px); }
        .categories .category-item:nth-child(3), .categories .category-item:nth-child(4) { transform: translateX(100px); }
        .categories.is-visible .category-item { opacity: 1; transform: translateX(0); }
        .categories.is-visible .category-item:nth-child(1), .categories.is-visible .category-item:nth-child(4) { transition-delay: 0.2s; }
        .categories.is-visible .category-item:nth-child(2), .categories.is-visible .category-item:nth-child(3) { transition-delay: 0.4s; }

        .category-circle {
            width: 100px; height: 100px; border-radius: 50%;
            background: var(--kd-bg-surface); display: flex; justify-content: center; align-items: center;
            margin-bottom: 0.8rem; overflow: hidden; border: 2px solid var(--glass-border);
            box-shadow: var(--card-shadow); transition: border-color 0.3s ease, box-shadow 0.3s ease, transform 0.3s;
        }
        .category-item:hover .category-circle { border-color: var(--kd-green); box-shadow: var(--card-hover-shadow); transform: scale(1.05); }
        .category-circle img { width: 100%; height: 100%; object-fit: cover; }
        .category-label { font-weight: 600; font-size: 0.9rem; }

        /* GRID SYSTEM (Responsive) */
        .grid { 
            display: grid; 
            /* Mobile Default: 2 columns */
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); 
            gap: 1.5rem; 
        }
        /* Desktop: Larger Cards */
        @media(min-width: 768px) {
             .grid { 
                 grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); 
                 gap: 2rem; 
             }
        }

        /* CARD STYLES */
        .card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
            overflow: hidden;
            box-shadow: var(--card-shadow);
            transition: transform 0.4s ease, box-shadow 0.4s ease;
            display: flex; flex-direction: column;
            height: 100%;
            opacity: 0; transform: translateY(50px); /* Initial Animation State */
        }
        .card:hover {
            transform: translateY(-8px) !important;
            border-color: var(--kd-green);
            box-shadow: var(--card-hover-shadow);
        }
        html.light-mode .card:hover { border-color: transparent; }
        
        .top-sales.is-visible .card, .products-list.is-visible .card, 
        .premium-services.is-visible .card, .testimonials.is-visible .card {
            opacity: 1; transform: translateY(0);
            transition: opacity 0.6s ease-out, transform 0.6s ease-out;
        }

        .card .img-container { 
            position: relative; width: 100%; padding-top: 75%; 
            overflow: hidden; border-bottom: 1px solid var(--glass-border); 
        }
        .card img { 
            position: absolute; top: 0; left: 0; width: 100%; height: 100%; 
            object-fit: cover; transition: transform .6s ease; 
        }
        .card:hover img { transform: scale(1.08); }
        
        .card-content { padding: 1.25rem; flex-grow: 1; display: flex; flex-direction: column; }
        
        /* Mobile adjustment for card content padding */
        @media(max-width: 480px) {
            .card-content { padding: 1rem; }
        }

        .card h4 { margin: 0 0 .5rem; font-size: 1.1rem; color: var(--kd-text); font-weight: 600; line-height: 1.3; }
        .card p { 
            margin: 0 0 1rem; color: var(--kd-muted); font-size: .9rem; line-height: 1.5; 
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }
        
        .price { margin-bottom: 0.8rem; font-weight: 700; font-size: 1.15rem; color: var(--kd-gold); margin-top: auto; }
        .price .old { text-decoration: line-through; color: var(--kd-muted); margin-right: 8px; font-size: 0.9rem; }
        .badge { background: #e53e3e; color: #fff; padding: 2px 6px; border-radius: 4px; font-size: .75rem; margin-left: 5px; vertical-align: middle; }
        
        .card-buttons { display: flex; gap: 10px; margin-top: 10px; }
        
        .add-to-cart {
            display: block; width: 100%; padding: .8rem; flex: 1;
            border: 1px solid var(--kd-green); border-radius: 8px; cursor: pointer;
            font-weight: 600; font-family: 'Poppins', sans-serif;
            color: var(--kd-green); background: transparent; transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        .add-to-cart:hover {
            background: var(--kd-green); color: #fff;
            box-shadow: 0 5px 15px rgba(5, 150, 105, 0.2);
        }
        
        .info-btn {
            background: transparent; color: var(--info-blue); border: 1px solid var(--info-blue);
            font-size: 1.2rem; line-height: 1; padding: 0.8rem; border-radius: 8px;
            cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center;
        }
        .info-btn:hover { background: var(--info-blue); color: #fff; transform: translateY(-2px); }

        /* OTHER SECTIONS */
        .service-card .icon { font-size: 2.5rem; color: var(--kd-green); margin-bottom: 1rem; }
        .service-card { text-align: center; }
        
        .testimonial-quote { font-style: italic; color: var(--kd-muted); border-left: 3px solid var(--kd-gold); padding-left: 1rem; margin-bottom: 1rem; }
        .testimonial-author { text-align: right; font-weight: 600; color: var(--kd-text); }
        .testimonial-author span { display: block; font-size: 0.85rem; font-weight: 400; color: var(--kd-gold); }
        
        .cta-section {
            margin: 4rem 0; padding: 3rem 1.5rem; text-align: center;
            background: var(--kd-bg-surface); border: 1px solid var(--glass-border);
            border-radius: 20px; box-shadow: var(--card-shadow);
        }

        /* MODAL */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(18, 24, 27, 0.85); backdrop-filter: blur(5px); -webkit-backdrop-filter: blur(5px); align-items: center; justify-content: center; padding: 1rem; }
        
        .modal-content { 
            margin: auto; padding: 2.5rem; width: 100%; max-width: 450px; text-align: center; position: relative; 
            background: var(--kd-bg-surface); border: 1px solid var(--glass-border); border-radius: 16px; 
            box-shadow: 0 10px 50px rgba(0,0,0,0.3); animation: slideIn 0.3s ease forwards; 
        }
        /* Mobile Modal Adjustments */
        @media(max-width: 480px) {
            .modal-content { padding: 1.5rem; width: 95%; max-width: none; }
            .close-button { right: 15px; top: 10px; }
        }

        .modal-buttons { display: flex; gap: 1rem; justify-content: center; margin-top: 1.5rem; }
        .modal-btn { padding: 0.8rem 1.8rem; border-radius: 8px; text-decoration: none; font-weight: 600; color: #fff; min-width: 120px; }
        .login-btn { background-color: var(--kd-green); }
        .register-btn { background-color: var(--kd-gold); }
        .close-button { position: absolute; top: 15px; right: 20px; color: var(--kd-muted); font-size: 28px; font-weight: bold; cursor: pointer; }

        /* TOAST */
        .kd-toast { position: fixed; right: 20px; bottom: 20px; padding: 1rem 1.5rem; border-radius: 12px; background: var(--kd-bg-surface); color: var(--kd-green); border: 1px solid var(--kd-green); box-shadow: 0 5px 20px rgba(0,0,0,0.3); transform: translateY(20px); opacity: 0; pointer-events: none; z-index: 3000; }
        .kd-toast.show { animation: toast-in .35s ease forwards; }
        @keyframes toast-in{ to{ transform:none; opacity:1 } }
        @keyframes slideIn { from { transform: translateY(50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        /* COMPARISON TABLE */
        .table-wrapper { overflow-x: auto; background: var(--glass-bg); border-radius: 12px; padding: 0.5rem; margin-top: 10px; -webkit-overflow-scrolling: touch; }
        .comparison-table { width: 100%; margin: 0; text-align: left; border-collapse: collapse; min-width: 300px; /* Ensure table doesn't squish too much */ }
        .comparison-table th { padding: 0.8rem; border-bottom: 1px solid var(--glass-border); color: var(--kd-text); font-size: 0.9rem; }
        .comparison-table td { padding: 0.8rem; border-bottom: 1px solid var(--glass-border); color: var(--kd-muted); font-size: 0.95rem; }
        .comparison-table .price-high { color: var(--kd-danger); font-weight: 600; }
        .comparison-table .price-low { color: var(--kd-green); font-weight: 800; font-size: 1.1rem; }
        .confirm-btn { background: var(--kd-green); color: #fff; padding: 0.8rem 1.5rem; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; margin-top: 1rem; width: 100%; }

    </style>
</head>
<body>

    <div class="preloader">
        <div class="preloader-logo-container">
            <img src="logo.png" alt="KisanX Logo">
        </div>
        <h1 class="preloader-title"><?php echo $lang['preloader_title']; ?></h1>
    </div>

    <?php include 'header.php'; ?>

    <main id="kd-page" class="container" data-page="home">
        
        <section class="hero scroll-trigger">
            <div class="hero-background-gradient"></div>
            <div class="hero-field-silhouette"></div>
            <div class="hero-content">
                <h1 class="hero-title">
                    <span class="h-word"><?php echo $lang['hero_title_harvesting']; ?></span>
                    <span class="h-word"><?php echo $lang['hero_title_the']; ?></span>
                    <span class="h-word special"><?php echo $lang['hero_title_future']; ?></span>
                </h1>
                <center><p><?php echo $lang['hero_subtitle']; ?></p></center>
                <a href="#products" class="btn"><?php echo $lang['hero_cta_button']; ?></a>
            </div>
        </section>
        
        <section class="categories scroll-trigger">
            <h2 class="section-title"><span><?php echo $lang['category_title']; ?></span></h2>
            <div class="category-grid">
                <a href="products.php?category=vegetables" class="category-item"><div class="category-circle"><img src="vegetables.jpg" alt="Vegetables"></div><div class="category-label"><?php echo $lang['category_vegetables']; ?></div></a>
                <a href="products.php?category=fruits" class="category-item"><div class="category-circle"><img src="fruits.jpg" alt="Fruits"></div><div class="category-label"><?php echo $lang['category_fruits']; ?></div></a>
                <a href="products.php?category=grains" class="category-item"><div class="category-circle"><img src="grains.jpg" alt="Grains"></div><div class="category-label"><?php echo $lang['category_grains']; ?></div></a>
                <a href="products.php?category=spices" class="category-item"><div class="category-circle"><img src="spices.jpg" alt="Spices"></div><div class="category-label"><?php echo $lang['category_spices']; ?></div></a>
            </div>
        </section>

        <section class="top-sales scroll-trigger">
            <h2 class="section-title"><span><?php echo $lang['topsales_title']; ?></span></h2>
            <div class="grid">
                <?php foreach($top as $index => $p): 
                    $finalPrice = $p['price'] * (1 - $p['discount_percent'] / 100);
                ?>
                    <div class="card" style="transition-delay: <?php echo $index * 0.1; ?>s;">
                        <div class="img-container"><img src="images/<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" loading="lazy"></div>
                        <div class="card-content">
                            <h4><?php echo htmlspecialchars($p['name']); ?></h4>
                            <p><?php echo htmlspecialchars($p['description']); ?></p>
                            <?php if(!empty($p['discount_percent']) && $p['discount_percent']>0): ?>
                                <p class="price"><span class="old">₹<?php echo number_format($p['price'],2); ?></span> ₹<?php echo number_format($finalPrice,2); ?> <span class="badge">-<?php echo (int)$p['discount_percent']; ?>%</span></p>
                            <?php else: ?>
                                <p class="price">₹<?php echo number_format($p['price'],2); ?></p>
                            <?php endif; ?>
                            
                            <div class="card-buttons">
                                <button class="add-to-cart" data-id="<?php echo (int)$p['id']; ?>"><?php echo $lang['card_add_to_cart']; ?></button>
                                <button type="button" class="info-btn compare-trigger" 
                                    data-name="<?php echo htmlspecialchars($p['name']); ?>" 
                                    data-price="<?php echo number_format($finalPrice, 2, '.', ''); ?>">ℹ️</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="products" class="products-list scroll-trigger">
            <h2 class="section-title"><span><?php echo $lang['allproducts_title']; ?></span></h2>
            <div id="productsGrid" class="grid">
                <?php foreach($allProducts as $index => $p): 
                    $finalPrice = $p['price'] * (1 - $p['discount_percent'] / 100);
                ?>
                    <div class="card" style="transition-delay: <?php echo ($index % 4) * 0.05; ?>s;">
                        <div class="img-container"><img src="images/<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" loading="lazy"></div>
                        <div class="card-content">
                            <h4><?php echo htmlspecialchars($p['name']); ?></h4>
                            <p><?php echo htmlspecialchars($p['description']); ?></p>
                            <?php if(!empty($p['discount_percent']) && $p['discount_percent']>0): ?>
                                <p class="price"><span class="old">₹<?php echo number_format($p['price'],2); ?></span> ₹<?php echo number_format($finalPrice,2); ?> <span class="badge">-<?php echo (int)$p['discount_percent']; ?>%</span></p>
                            <?php else: ?>
                                <p class="price">₹<?php echo number_format($p['price'],2); ?></p>
                            <?php endif; ?>
                            
                            <div class="card-buttons">
                                <button class="add-to-cart" data-id="<?php echo (int)$p['id']; ?>"><?php echo $lang['card_add_to_cart']; ?></button>
                                <button type="button" class="info-btn compare-trigger" 
                                    data-name="<?php echo htmlspecialchars($p['name']); ?>" 
                                    data-price="<?php echo number_format($finalPrice, 2, '.', ''); ?>">ℹ️</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="premium-services scroll-trigger">
            <h2 class="section-title"><span><?php echo $lang['services_title']; ?></span></h2>
            <div class="grid">
                <div class="card"><div class="card-content service-card"><div class="icon">📈</div><h4><?php echo $lang['service_1_title']; ?></h4><p><?php echo $lang['service_1_desc']; ?></p></div></div>
                <div class="card"><div class="card-content service-card"><div class="icon">🚚</div><h4><?php echo $lang['service_2_title']; ?></h4><p><?php echo $lang['service_2_desc']; ?></p></div></div>
                <div class="card"><div class="card-content service-card"><div class="icon">💳</div><h4><?php echo $lang['service_3_title']; ?></h4><p><?php echo $lang['service_3_desc']; ?></p></div></div>
            </div>
        </section>

        <section class="testimonials scroll-trigger">
            <h2 class="section-title"><span><?php echo $lang['community_title']; ?></span></h2>
            <div class="grid">
                 <div class="card"><div class="card-content"><p class="testimonial-quote"><?php echo $lang['community_1_quote']; ?></p><p class="testimonial-author"><?php echo $lang['community_1_author']; ?> <span><?php echo $lang['community_1_role']; ?></span></p></div></div>
                <div class="card"><div class="card-content"><p class="testimonial-quote"><?php echo $lang['community_2_quote']; ?></p><p class="testimonial-author"><?php echo $lang['community_2_author']; ?> <span><?php echo $lang['community_2_role']; ?></span></p></div></div>
                 <div class="card"><div class="card-content"><p class="testimonial-quote"><?php echo $lang['community_3_quote']; ?></p><p class="testimonial-author"><?php echo $lang['community_3_author']; ?> <span><?php echo $lang['community_3_role']; ?></span></p></div></div>
            </div>
        </section>
        
        <section class="cta-section">
            <h2 class="section-title"><span><?php echo $lang['cta_title']; ?></span></h2>
            <p><?php echo $lang['cta_subtitle']; ?></p>
            <a href="register.php" class="btn"><?php echo $lang['cta_button']; ?></a>
        </section>
    </main>

    <div id="kd-toast" class="kd-toast"><?php echo $lang['toast_added_to_cart']; ?></div>

    <div id="authModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2><?php echo $lang['modal_title']; ?></h2>
            <p><?php echo $lang['modal_subtitle']; ?></p>
            <div class="modal-buttons">
                <a href="login.php" class="modal-btn login-btn"><?php echo $lang['modal_login_button']; ?></a>
                <a href="register.php" class="modal-btn register-btn"><?php echo $lang['modal_register_button']; ?></a>
            </div>
        </div>
    </div>
    
    <div id="comparisonModal" class="modal">
        <div class="modal-content">
            <h4>Price Comparison 🔍</h4>
            <p>Market rates updated today!</p>
            <h5 id="compProductName" style="margin: 10px 0; color: var(--kd-text);"></h5>
            <div class="table-wrapper">
                <table class="comparison-table">
                    <thead>
                        <tr>
                            <th>Platform</th>
                            <th>Price (est.)</th>
                        </tr>
                    </thead>
                    <tbody id="comparisonTableBody">
                    </tbody>
                </table>
            </div>
            <button class="confirm-btn" onclick="closeComparisonModal()">Got it!</button>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
    
    <script src="js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            
            const preloader = document.querySelector('.preloader');
            function initScrollAnimations() {
                const scrollElements = document.querySelectorAll('.scroll-trigger');
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) { entry.target.classList.add('is-visible'); } 
                        else { entry.target.classList.remove('is-visible'); }
                    });
                }, { threshold: 0.1 });
                scrollElements.forEach(el => observer.observe(el));
            }

            if (preloader) {
                setTimeout(() => { preloader.classList.add('hidden'); initScrollAnimations(); }, 2000);
            } else { initScrollAnimations(); }

            const isLoggedIn = <?php echo isset($_SESSION['user']) ? 'true' : 'false'; ?>;
            const authModal = document.getElementById('authModal');
            const closeModalBtn = authModal ? authModal.querySelector('.close-button') : null;
            const addToCartButtons = document.querySelectorAll('.add-to-cart');
            
            addToCartButtons.forEach(button => {
                button.addEventListener('click', (event) => {
                    if (!isLoggedIn) { event.preventDefault(); if(authModal) authModal.style.display = 'flex'; }
                });
            });
            function closeModal() { if(authModal) authModal.style.display = 'none'; }
            if(closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
            window.addEventListener('click', (event) => { if (event.target == authModal) { closeModal(); } });
            
            // --- EVENT DELEGATION FOR COMPARISON BUTTONS ---
            document.body.addEventListener('click', function(e) {
                const btn = e.target.closest('.compare-trigger');
                if(btn) {
                    e.preventDefault();
                    const name = btn.dataset.name.toLowerCase();
                    const displayPrice = parseFloat(btn.dataset.price);
                    openComparisonModal(btn.dataset.name, displayPrice);
                }
            });
        });

        /* --- COMPARISON LOGIC --- */
        const comparisonModal = document.getElementById('comparisonModal');
        
        const marketKnowledge = {
            'onion': { blinkit: 55, kisanKonnect: 42, local: 38 },
            'potato': { blinkit: 40, kisanKonnect: 35, local: 32 },
            'tomato': { blinkit: 60, kisanKonnect: 55, local: 40 },
            'chilli': { blinkit: 15, kisanKonnect: 12, local: 10 }, 
            'garlic': { blinkit: 250, kisanKonnect: 200, local: 180 },
            'apple': { blinkit: 220, kisanKonnect: 180, local: 160 }
        };

        function openComparisonModal(name, kisanXPrice) {
            if(!comparisonModal) return;
            
            document.getElementById('compProductName').textContent = name;
            const tbody = document.getElementById('comparisonTableBody');
            tbody.innerHTML = '';
            const lowerName = name.toLowerCase();

            let blinkitPrice, kisanKonnectPrice, localPrice;

            let found = false;
            for (const [key, rates] of Object.entries(marketKnowledge)) {
                if (lowerName.includes(key)) {
                    blinkitPrice = (kisanXPrice * 1.45).toFixed(2); 
                    kisanKonnectPrice = (kisanXPrice * 1.25).toFixed(2); 
                    localPrice = (kisanXPrice * 1.15).toFixed(2); 
                    found = true;
                    break;
                }
            }

            if (!found) {
                blinkitPrice = (kisanXPrice * 1.40).toFixed(2);
                kisanKonnectPrice = (kisanXPrice * 1.20).toFixed(2);
                localPrice = (kisanXPrice * 1.10).toFixed(2);
            }

            const rows = [
                { name: "⚡ Blinkit", price: blinkitPrice, class: "price-high" },
                { name: "🥦 KisanKonnect", price: kisanKonnectPrice, class: "price-high" },
                { name: "🏪 Local", price: localPrice, class: "price-high" }
            ];

            rows.forEach(r => {
                tbody.innerHTML += `<tr>
                    <td class="store-name">${r.name}</td>
                    <td class="${r.class}">₹${r.price}</td>
                </tr>`;
            });

            const kisanRow = `<tr style="background: rgba(104, 211, 145, 0.1);">
                <td class="store-name" style="color: var(--kd-green);">✅ KisanX</td>
                <td class="price-low">₹${kisanXPrice.toFixed(2)}</td>
            </tr>`;
            tbody.innerHTML += kisanRow;

            comparisonModal.style.display = 'flex';
        }

        function closeComparisonModal() {
            if(comparisonModal) comparisonModal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == comparisonModal) {
                closeComparisonModal();
            }
        }
    </script>
</body>
</html>