<?php
session_start();
// Include your necessary files (assuming they exist)
include 'php/language_init.php';
include 'php/db.php';

// Check if user is logged in
if(empty($_SESSION['user'])){ header('Location: login.php'); exit; }
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// Fetch order details
try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $_SESSION['user']['id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $order = false;
}

if(!$order) {
    echo "<div style='color:var(--kd-text); background:var(--kd-bg); text-align:center; padding:50px;'>Order not found or access denied.</div>";
    exit;
}

// Mock coordinates: Farmer/Farm (Start) and Customer (End)
$farm_lat = 19.1000;
$farm_lon = 72.8500;
$customer_lat = 19.0750;
$customer_lon = 72.8800;

$current_status = $order['status'] ?? 'pending';

function get_step_class($step_status, $current_status) {
    $statuses = ['ordered', 'processing', 'shipped', 'delivered'];
    $step_index = array_search($step_status, $statuses);
    $current_index = array_search($current_status, $statuses);

    if ($current_index !== false && $step_index !== false && $current_index >= $step_index) {
        return 'active';
    }
    return '';
}
?>
<!doctype html>
<html lang="<?php echo $current_lang ?? 'en'; ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Track Order #<?php echo $order_id; ?></title>

    <script>
    (function() {
        try {
            const storedTheme = localStorage.getItem('theme');
            if (storedTheme === 'light') {
                document.documentElement.classList.add('light-mode');
            } else {
                localStorage.setItem('theme', 'dark');
                document.documentElement.classList.remove('light-mode');
            }
        } catch (e) {}
    })();
    </script>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

    <style>
        /* --- CORE VARIABLES --- */
        :root {
            --kd-bg: #0C0F14;
            --kd-bg-surface: #1E252C;
            --kd-earthy-green: #4CAF50;
            --kd-warm-gold: #FFC107;
            --kd-text: #E0E7EB;
            --kd-muted: #9AA6AE;
            --glass-bg: rgba(26, 34, 40, 0.75);
            --glass-border: rgba(255, 255, 255, 0.12);
            --card-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.6);
            --map-blue: #4285F4;
        }

        /* --- GLOBAL RESET & TYPOGRAPHY --- */
        * { box-sizing: border-box; }
        body { 
            margin: 0; 
            font-family: 'Poppins', sans-serif; 
            background: var(--kd-bg); 
            color: var(--kd-text); 
            transition: background 0.5s, color 0.5s;
            overflow-x: hidden; 
        }

        /* --- CONTAINER --- */
        .container { 
            width: 100%;
            max-width: 900px; 
            margin: 0 auto; 
            padding: 4rem 1.5rem; /* Adjustable padding */
            animation: fadeIn 0.8s ease; 
        }

        /* --- TRACKING CARD (GLASSMORPHISM) --- */
        .track-card {
            background: var(--glass-bg); 
            border: 1px solid var(--glass-border);
            border-radius: 24px; 
            padding: 3rem; 
            box-shadow: var(--card-shadow);
            text-align: center; 
            backdrop-filter: blur(25px); 
            -webkit-backdrop-filter: blur(25px);
            width: 100%;
        }

        /* --- HEADERS & STATUS --- */
        h1 { 
            font-family: 'Montserrat', sans-serif; 
            color: var(--kd-text); 
            margin-bottom: 0.5rem; 
            font-size: 2rem; 
            line-height: 1.2;
        }
        
        #current-status-text {
            display: inline-block;
            margin-bottom: 2rem;
        }

        /* --- ETA STATS GRID --- */
        .eta-stats { 
            display: flex; 
            justify-content: space-around; 
            margin-bottom: 3rem; 
            gap: 15px;
            flex-wrap: wrap; /* Allows wrapping on mobile */
        }
        .stat-item { 
            text-align: center; 
            flex: 1; /* Distribute space evenly */
            min-width: 100px; /* Prevent crushing on tiny screens */
        }
        .stat-value { 
            font-family: 'Montserrat', sans-serif; 
            color: var(--kd-warm-gold); 
            font-weight: 800; 
            font-size: 1.5rem; 
            line-height: 1; 
            display: block;
            margin-bottom: 5px;
        }
        .stat-label { 
            color: var(--kd-muted); 
            font-size: 0.8rem; 
            font-weight: 500; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* --- PROGRESS BAR --- */
        .progress-bar-container { 
            width: 100%; 
            max-width: 500px;
            margin: 20px auto; 
            height: 8px; 
            background: var(--kd-bg-surface); 
            border-radius: 10px; 
            overflow: hidden; 
        }
        .progress-bar { 
            height: 100%; 
            width: 0; 
            background: var(--kd-earthy-green); 
            transition: width 0.5s ease; 
            border-radius: 10px; 
        }

        /* --- TIMELINE (RESPONSIVE) --- */
        .timeline { 
            position: relative; 
            margin: 3rem 0; 
            padding: 0; 
            list-style: none; 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-start;
        }
        /* The connecting line */
        .timeline::before {
            content: ''; 
            position: absolute; 
            top: 18px; 
            left: 5%; 
            width: 90%; /* Prevent line from sticking out */
            height: 4px;
            background: var(--kd-bg-surface); 
            z-index: 0; 
            border-radius: 4px;
        }

        .step { 
            position: relative; 
            z-index: 1; 
            text-align: center; 
            flex: 1; 
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .step-dot {
            width: 42px; 
            height: 42px; 
            background: var(--kd-bg-surface); 
            border: 4px solid var(--kd-muted);
            border-radius: 50%; 
            margin-bottom: 0.8rem; 
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex; 
            align-items: center; 
            justify-content: center; 
            color: var(--kd-text);
            font-size: 1.2rem;
        }

        .step.active .step-dot {
            background: var(--kd-earthy-green); 
            border-color: var(--kd-earthy-green);
            box-shadow: 0 0 0 6px rgba(76, 175, 80, 0.2); 
            transform: scale(1.1);
        }

        .step-text { 
            font-size: 0.9rem; 
            color: var(--kd-muted); 
            font-weight: 500; 
            transition: 0.3s; 
            line-height: 1.3;
        }
        .step.active .step-text { 
            color: var(--kd-text); 
            font-weight: 700; 
            transform: translateY(5px); 
        }

        /* --- BUTTONS --- */
        .back-btn { 
            margin-top: 2rem; 
            display: inline-block; 
            color: var(--kd-muted); 
            text-decoration: none; 
            font-weight: 600; 
            transition: 0.3s; 
            padding: 10px 20px;
            border-radius: 8px;
        }
        .back-btn:hover { 
            color: var(--kd-text); 
            background: rgba(255,255,255,0.05);
            transform: translateX(-5px); 
        }

        /* --- MAP CONTAINER --- */
        #map {
            width: 100%; 
            height: 450px; 
            margin-top: 30px; 
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            z-index: 10;
        }

        /* --- RIDER CARD (OVER MAP) --- */
        .rider-card {
            position: absolute; 
            right: 18px; 
            top: 18px; 
            z-index: 1100;
            background: rgba(12, 15, 20, 0.85); /* Slightly darker for contrast */
            backdrop-filter: blur(10px);
            color: var(--kd-text);
            border-radius: 16px; 
            padding: 12px 16px; 
            display: flex; 
            gap: 12px;
            align-items: center; 
            min-width: 240px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }
        .rider-avatar {
            width: 50px; 
            height: 50px; 
            border-radius: 50%;
            background: linear-gradient(135deg, #ffffff15, #ffffff05);
            display: flex; 
            align-items: center; 
            justify-content: center;
            font-size: 24px; 
            border: 1px solid rgba(255,255,255,0.1);
            flex-shrink: 0;
        }
        .rider-meta { text-align: left; font-size: 13px; }
        .rider-meta .name { font-weight: 700; font-family: 'Montserrat', sans-serif; font-size: 0.95rem; }
        .rider-meta .sub { color: var(--kd-muted); margin-top: 2px; font-size: 12px; }

        /* --- ETA BOX ON MAP --- */
        .eta-box {
            background-color: var(--map-blue); 
            color: white; 
            padding: 6px 14px;
            border-radius: 50px; 
            font-family: 'Montserrat', sans-serif;
            font-weight: 800; 
            font-size: 1rem; 
            text-align: center;
            box-shadow: 0 4px 15px rgba(66, 133, 244, 0.4);
            white-space: nowrap;
        }

        /* --- ICONS & ANIMATION --- */
        .rider-icon { background-color: transparent; border: none; transition: transform 0.3s linear; z-index: 1000; }
        .rider-svg-container { width: 50px; height: 50px; display: block; transform-origin: center center; filter: drop-shadow(0px 5px 5px rgba(0,0,0,0.5)); }
        @keyframes fadeIn { from { opacity: 0; transform: scale(0.98); } to { opacity: 1; transform: scale(1); } }
        .arriving-pulse { box-shadow: 0 0 0 0 var(--kd-warm-gold); animation: pulse-ring 1.5s infinite; }
        @keyframes pulse-ring { 0% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7); transform: scale(0.7); opacity: 0.7; } 100% { box-shadow: 0 0 0 30px rgba(255, 193, 7, 0); transform: scale(1.2); opacity: 0; } }

        /* --- DELIVERY OVERLAY --- */
        .delivery-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.85); z-index: 9999;
            display: none; flex-direction: column;
            align-items: center; justify-content: center;
            animation: fadeInOverlay 0.5s ease-out;
            backdrop-filter: blur(8px);
            padding: 20px;
        }
        @keyframes fadeInOverlay { from { opacity: 0; } to { opacity: 1; } }

        .delivery-content {
            background: var(--kd-bg-surface);
            padding: 40px; border-radius: 30px;
            text-align: center; border: 1px solid var(--kd-earthy-green);
            box-shadow: 0 0 50px rgba(76, 175, 80, 0.3);
            transform: scale(0.8);
            animation: popUpContent 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
            width: 100%; max-width: 400px;
        }
        @keyframes popUpContent { to { transform: scale(1); } }
        .big-check { font-size: 80px; margin-bottom: 20px; display: block; animation: rotateCheck 0.8s ease-in-out; }
        @keyframes rotateCheck { 0% { transform: scale(0) rotate(-45deg); } 70% { transform: scale(1.2) rotate(10deg); } 100% { transform: scale(1) rotate(0deg); } }
        .delivery-content h2 { color: var(--kd-text); margin: 10px 0; font-family: 'Montserrat', sans-serif; font-size: 1.8rem; }
        .delivery-content p { color: var(--kd-muted); font-size: 1rem; margin-bottom: 20px;}
        .close-overlay-btn {
            background: var(--kd-earthy-green); color: white; border: none; padding: 14px 35px;
            font-size: 1rem; border-radius: 50px; font-weight: 700; cursor: pointer;
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.4); transition: 0.3s; width: 100%;
        }
        .close-overlay-btn:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(76, 175, 80, 0.6); }

        /* =========================================
           RESPONSIVE MEDIA QUERIES
           ========================================= */

        /* Tablet (768px - 1024px) */
        @media (max-width: 1024px) {
            .container { max-width: 95%; padding: 2rem 1rem; }
            .track-card { padding: 2rem; }
        }

        /* Mobile (< 768px) */
        @media (max-width: 767px) {
            .container { 
                padding: 1rem; 
                margin-top: 10px;
            }
            .track-card { 
                padding: 1.5rem 1rem; 
                border-radius: 20px;
            }
            
            h1 { font-size: 1.5rem; }
            .stat-value { font-size: 1.2rem; }
            
            /* Compact Timeline for Mobile */
            .timeline { margin: 2rem 0; }
            .timeline::before { top: 14px; height: 3px; }
            .step-dot { width: 32px; height: 32px; border-width: 3px; font-size: 1rem; }
            .step-text { font-size: 0.75rem; }
            
            /* Adjust Map Height */
            #map { height: 350px; margin-top: 20px; }

            /* Reposition Rider Card on Mobile */
            .rider-card {
                top: 10px; left: 10px; right: 10px; /* Full width top */
                width: auto;
                min-width: unset;
                padding: 10px 14px;
                justify-content: flex-start;
            }
            
            /* Overlay adjustments */
            .delivery-content { padding: 25px; }
            .big-check { font-size: 60px; }
            .delivery-content h2 { font-size: 1.5rem; }
        }

        /* Small Mobile (< 400px) */
        @media (max-width: 400px) {
            .step-text { display: none; } /* Hide text, show only dots if screen extremely small */
            .step.active .step-text { display: block; font-size: 0.7rem; position: absolute; top: 45px; width: 100px; left: 50%; transform: translateX(-50%); }
            .timeline { margin-bottom: 3.5rem; } /* Space for absolute text */
            
            /* Stack stats 1 per row */
            .eta-stats { flex-direction: column; gap: 10px; }
            .stat-item { display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.03); padding: 10px 15px; border-radius: 10px;}
            .stat-value { margin-bottom: 0; font-size: 1.1rem; order: 2; }
            .stat-label { order: 1; }
        }

    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="container">
        <div class="track-card">
            <h1>Tracking Order #<?php echo $order_id; ?></h1>
            <span class="stat-label" id="current-status-text">Status: <strong style="color:var(--kd-text)"><?php echo ucwords($current_status); ?></strong></span>

            <div class="eta-stats">
                <div class="stat-item">
                    <span class="stat-value" id="eta-value">-- min</span>
                    <span class="stat-label">ETA</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value" id="distance-value">-- km</span>
                    <span class="stat-label">Distance Left</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value" id="speed-value">-- km/h</span>
                    <span class="stat-label">Avg Speed</span>
                </div>
            </div>

            <div class="progress-bar-container">
                <div class="progress-bar" id="progressBar"></div>
            </div>

            <ul class="timeline">
                <li class="step <?php echo get_step_class('ordered', $current_status); ?>">
                    <div class="step-dot">📦</div>
                    <div class="step-text">Ordered</div>
                </li>
                <li class="step <?php echo get_step_class('processing', $current_status); ?>">
                    <div class="step-dot">🌾</div>
                    <div class="step-text">Farming</div>
                </li>
                <li class="step <?php echo get_step_class('shipped', $current_status); ?>">
                    <div class="step-dot">🛵</div>
                    <div class="step-text">Shipped</div>
                </li>
                <li class="step <?php echo get_step_class('delivered', $current_status); ?>">
                    <div class="step-dot">🏠</div>
                    <div class="step-text">Delivered</div>
                </li>
            </ul>

            <div id="map" style="position:relative;">
                <div class="rider-card" id="riderCard" style="display:block;">
                    <div class="rider-avatar" id="riderAvatar">🧑‍🌾</div>
                    <div class="rider-meta">
                        <div class="name" id="riderName">Rajesh Kumar</div>
                        <div class="sub" id="riderPhone">9876543210</div>
                        <div class="sub" id="riderBike">Hero Splendor+</div>
                        <div class="sub" id="riderRating">⭐ 4.9</div>
                    </div>
                </div>
                
                <div id="map-eta-box" class="eta-box" style="position:absolute; top:80px; left:50%; transform:translateX(-50%); display:none; z-index:1000;">
                    <span id="map-eta-value">-- min</span>
                </div>
            </div>

            <a href="user_dashboard.php" class="back-btn">← Back to Orders</a>
        </div>
    </main>

    <div id="deliveryOverlay" class="delivery-overlay">
        <div class="delivery-content">
            <span class="big-check">✅</span>
            <h2>Order Delivered!</h2>
            <p>Your fresh produce has arrived safely.</p>
            <button class="close-overlay-btn" onclick="closeDeliveryOverlay()">Awesome!</button>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        // --- Hardcoded rider details ---
        const riderInfo = {
            name: "Rajesh Kumar",
            phone: "9876543210",
            bike: "Hero Splendor+",
            rating: 4.9,
            avatarEmoji: "🧑‍🌾"
        };

        document.addEventListener('DOMContentLoaded', () => {
            const nameEl = document.getElementById('riderName');
            const phoneEl = document.getElementById('riderPhone');
            const bikeEl = document.getElementById('riderBike');
            const ratingEl = document.getElementById('riderRating');
            const avatarEl = document.getElementById('riderAvatar');
            if (nameEl) nameEl.textContent = riderInfo.name;
            if (phoneEl) phoneEl.textContent = riderInfo.phone;
            if (bikeEl) bikeEl.textContent = riderInfo.bike;
            if (ratingEl) ratingEl.textContent = `⭐ ${riderInfo.rating}`;
            if (avatarEl) avatarEl.textContent = riderInfo.avatarEmoji;
        });

        const R_LAT = <?php echo $farm_lat; ?>;
        const R_LON = <?php echo $farm_lon; ?>;
        const C_LAT = <?php echo $customer_lat; ?>;
        const C_LON = <?php echo $customer_lon; ?>;
        const ORDER_ID = <?php echo $order_id; ?>;

        const map = L.map('map', { zoomControl: false }).setView([R_LAT, R_LON], 14);
        // Add Zoom control to top right to not conflict with Rider Card on mobile
        L.control.zoom({ position: 'bottomright' }).addTo(map);

        const tileUrl = 'https://tiles.stadiamaps.com/tiles/alidade_smooth_dark/{z}/{x}/{y}{r}.png';
        L.tileLayer(tileUrl, {
            maxZoom: 20,
            attribution: '&copy; <a href="https://stadiamaps.com/">Stadia Maps</a>',
            id: 'stamen-toner-dark'
        }).addTo(map);

        // --- NEW SVG ICON (Points North) ---
        const svgIcon = `
        <div class="rider-svg-container">
            <svg width="50" height="50" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M25 0L45 40L25 32L5 40L25 0Z" fill="#4CAF50"/>
                <path d="M25 10C27 10 28 11 28 12V18H22V12C22 11 23 10 25 10ZM30 25C30 27.76 27.76 30 25 30C22.24 30 20 27.76 20 25C20 22.24 22.24 20 25 20C27.76 20 30 22.24 30 25Z" fill="white"/>
            </svg>
        </div>`;

        const riderIcon = L.divIcon({
            className: 'rider-icon',
            html: svgIcon,
            iconSize: [50, 50],
            iconAnchor: [25, 25] 
        });

        L.marker([R_LAT, R_LON], {
            icon: L.divIcon({ html: '🚜', className: 'farm-icon', iconSize: [30, 30] })
        }).addTo(map).bindPopup("Farm Location");

        const customerMarker = L.marker([C_LAT, C_LON], {
            icon: L.divIcon({ html: '🏠', className: 'customer-icon', iconSize: [30, 30] })
        }).addTo(map).bindPopup("Your Location");

        const riderMarker = L.marker([R_LAT, R_LON], { icon: riderIcon }).addTo(map);

        let routePolyline = null;
        let lastLat = R_LAT;
        let lastLon = R_LON;
        let trackingStarted = false;

        let animState = {
            prevPos: { lat: R_LAT, lon: R_LON },
            nextPos: { lat: R_LAT, lon: R_LON },
            prevTs: performance.now(),
            nextTs: performance.now() + 2000,
            animating: false
        };

        let expectedUpdateInterval = 2000;
        let deliveryAnimationPlayed = false;

        function calculateBearing(lat1, lon1, lat2, lon2) {
            const l1 = lat1 * (Math.PI / 180);
            const l2 = lat2 * (Math.PI / 180);
            const dLon = (lon2 - lon1) * (Math.PI / 180);
            const y = Math.sin(dLon) * Math.cos(l2);
            const x = Math.cos(l1) * Math.sin(l2) - Math.sin(l1) * Math.cos(l2) * Math.cos(dLon);
            let brng = Math.atan2(y, x);
            brng = brng * (180 / Math.PI);
            return (brng + 360) % 360;
        }

        function updateTimeline(status) {
            const steps = document.querySelectorAll('.step');
            const statusMap = { 'ordered': 0, 'processing': 1, 'shipped': 2, 'delivered': 3 };
            const currentStepIndex = statusMap[status] !== undefined ? statusMap[status] : -1;
            steps.forEach((step, index) => {
                if (index <= currentStepIndex) step.classList.add('active');
                else step.classList.remove('active');
            });
        }

        function lerp(a, b, t) { return a + (b - a) * t; }
        function interpolatePos(p1, p2, t) {
            return { lat: lerp(p1.lat, p2.lat, t), lon: lerp(p1.lon, p2.lon, t) };
        }

        function setRiderRotation(bearing) {
            const iconElement = riderMarker.getElement();
            if (iconElement) {
                const svgContainer = iconElement.querySelector('.rider-svg-container');
                if (svgContainer) {
                    svgContainer.style.transform = `rotate(${bearing}deg)`;
                } else {
                    iconElement.style.transform = `translate(-50%, -50%) rotate(${bearing}deg)`;
                }
            }
        }

        function animate() {
            if (!animState.animating) return;
            const now = performance.now();
            const denom = (animState.nextTs - animState.prevTs) || 1;
            let t = (now - animState.prevTs) / denom;
            if (t < 0) t = 0;
            if (t > 1) t = 1;

            const cur = interpolatePos(animState.prevPos, animState.nextPos, t);
            riderMarker.setLatLng([cur.lat, cur.lon]);

            const sampleAheadT = Math.min(t + 0.05, 1.0);
            const sampleAhead = interpolatePos(animState.prevPos, animState.nextPos, sampleAheadT);
            
            if (cur.lat !== sampleAhead.lat || cur.lon !== sampleAhead.lon) {
                const bearing = calculateBearing(cur.lat, cur.lon, sampleAhead.lat, sampleAhead.lon);
                setRiderRotation(bearing);
            }

            if (t < 1) {
                requestAnimationFrame(animate);
            } else {
                animState.animating = false;
            }
        }

        function triggerDeliveryCelebration() {
            const overlay = document.getElementById('deliveryOverlay');
            overlay.style.display = 'flex'; 

            var count = 200;
            var defaults = { origin: { y: 0.7 } };

            function fire(particleRatio, opts) {
                confetti(Object.assign({}, defaults, opts, {
                    particleCount: Math.floor(count * particleRatio)
                }));
            }

            fire(0.25, { spread: 26, startVelocity: 55, });
            fire(0.2, { spread: 60, });
            fire(0.35, { spread: 100, decay: 0.91, scalar: 0.8 });
            fire(0.1, { spread: 120, startVelocity: 25, decay: 0.92, scalar: 1.2 });
            fire(0.1, { spread: 120, startVelocity: 45, });
        }

        function closeDeliveryOverlay() {
            document.getElementById('deliveryOverlay').style.display = 'none';
        }

        function updateMapAndUI(data) {
            const latitude = data.latitude !== undefined ? Number(data.latitude) : null;
            const longitude = data.longitude !== undefined ? Number(data.longitude) : null;
            const status = data.status;
            const eta = data.eta;
            const distance = data.distance;
            const total_distance = data.total_distance || 0;
            const speed = data.speed;
            const route_coords = data.route_coords || [];

            const now = performance.now();
            if (data.update_interval) expectedUpdateInterval = Number(data.update_interval);

            if (status === 'delivered' && !deliveryAnimationPlayed) {
                triggerDeliveryCelebration();
                deliveryAnimationPlayed = true; 
            }

            if (status === 'shipped' && latitude !== null && longitude !== null) {
                const isAnimating = animState.animating;
                let startPos;
                const currentNow = performance.now();
                if (isAnimating) {
                    const denom = (animState.nextTs - animState.prevTs) || 1;
                    let tt = (currentNow - animState.prevTs) / denom;
                    tt = Math.max(0, Math.min(1, tt));
                    startPos = interpolatePos(animState.prevPos, animState.nextPos, tt);
                } else {
                    startPos = { lat: lastLat, lon: lastLon };
                }

                const newPos = { lat: latitude, lon: longitude };
                const duration = Math.max(500, expectedUpdateInterval); 
                animState.prevPos = startPos;
                animState.nextPos = newPos;
                animState.prevTs = currentNow;
                animState.nextTs = currentNow + duration;
                animState.animating = true;

                lastLat = latitude;
                lastLon = longitude;

                requestAnimationFrame(animate);
            }

            const etaValue = eta !== undefined && eta !== null ? Math.ceil(eta) : null;
            document.getElementById('eta-value').textContent = etaValue !== null ? `${etaValue} min` : '-- min';
            document.getElementById('map-eta-value').textContent = etaValue !== null ? `${etaValue} min` : '';
            document.getElementById('distance-value').textContent = distance !== undefined && distance !== null ? `${Number(distance).toFixed(2)} km` : '-- km';
            document.getElementById('speed-value').textContent = speed !== undefined && speed !== null ? `${Number(speed).toFixed(1)} km/h` : '-- km/h';

            if (status) {
                 const statusText = status.charAt(0).toUpperCase() + status.slice(1);
                 document.getElementById('current-status-text').innerHTML = `Status: <strong style="color:var(--kd-text)">${statusText}</strong>`;
                 updateTimeline(status);
            }

            const mapEtaBox = document.getElementById('map-eta-box');
            if (status === 'shipped') {
                mapEtaBox.style.display = 'block';
            } else {
                mapEtaBox.style.display = 'none';
            }

            if (total_distance > 0 && distance !== undefined && distance !== null) {
                const distanceCovered = total_distance - distance;
                const progressPercentage = (distanceCovered / total_distance) * 100;
                document.getElementById('progressBar').style.width = `${Math.min(progressPercentage, 100)}%`;
            }

            if (status === 'shipped' && Array.isArray(route_coords) && route_coords.length > 0) {
                const latLons = route_coords.map(c => [c.lat, c.lon]);

                if (routePolyline) map.removeLayer(routePolyline);
                routePolyline = L.polyline(latLons, {
                    color: 'var(--map-blue)', weight: 8, opacity: 0.9, lineJoin: 'round'
                }).addTo(map);

                if (!trackingStarted) {
                    const bounds = L.latLngBounds(latLons);
                    map.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 });
                    trackingStarted = true;
                } else if (status === 'shipped') {
                    let centerPos = [lastLat, lastLon];
                    if (animState.animating) {
                        const denom = (animState.nextTs - animState.prevTs) || 1;
                        let tt = (performance.now() - animState.prevTs) / denom;
                        tt = Math.max(0, Math.min(1, tt));
                        const cur = interpolatePos(animState.prevPos, animState.nextPos, tt);
                        centerPos = [cur.lat, cur.lon];
                    }
                    map.setView(centerPos, map.getZoom());
                }
            }

            if (status === 'shipped' && distance !== undefined && distance !== null && Number(distance) < 0.5) {
                const cmEl = customerMarker.getElement();
                if (cmEl) cmEl.classList.add('arriving-pulse');
                document.getElementById('eta-value').textContent = `1 min`;
                document.getElementById('map-eta-value').textContent = `1 min`;
            } else {
                const cmEl = customerMarker.getElement();
                if (cmEl) cmEl.classList.remove('arriving-pulse');
            }
        }

        function fetchLocationData() {
            fetch(`php/get_location.php?order_id=${ORDER_ID}`)
                .then(res => res.json())
                .then(data => updateMapAndUI(data))
                .catch(e => console.error("Error fetching location data:", e));
        }

        fetchLocationData();
        const updateInterval = 2000;
        setInterval(fetchLocationData, updateInterval);

    </script>
</body>
</html>