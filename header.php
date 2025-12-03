<?php
// header.php
// Ensure session is started and language is initialized
if (session_status() === PHP_SESSION_NONE) {
    // We assume language_init.php might handle it, but safety first
}
include_once 'php/language_init.php';

// --- DYNAMIC HOME LINK LOGIC ---
$home_link = 'index.php'; // Default for guests
if (isset($_SESSION['user'])) {
    if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'farmer') {
        $home_link = 'farmer_dashboard.php';
    } else {
        $home_link = 'user_dashboard.php';
    }
}
?>
<header id="main-header">
    <div class="header-inner">
        <a href="<?php echo $home_link; ?>" class="logo">
            <img src="logo.png" alt="KisanX Logo">
            <span>KisanX</span>
        </a>

        <button class="menu-toggle" id="menu-open-btn" aria-label="Open Menu">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
        </button>

        <div class="nav-wrapper" id="nav-wrapper">
            
            <div class="mobile-nav-header">
                <span class="mobile-nav-title">Menu</span>
                <button class="menu-close" id="menu-close-btn" aria-label="Close Menu">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>

            <nav class="main-nav">
                <a href="<?php echo $home_link; ?>" class="nav-link"><?php echo $lang['home']; ?></a>
                <a href="news.php" class="nav-link"><?php echo isset($lang['news_menu']) ? $lang['news_menu'] : 'News'; ?></a>
                <a href="about.php" class="nav-link"><?php echo $lang['about_us']; ?></a>
                <a href="contact.php" class="nav-link"><?php echo $lang['contact_us']; ?></a>
            </nav>

            <div class="nav-divider"></div>

            <div class="controls-group">
                
                <button class="icon-btn theme-toggle" id="theme-toggle-btn" title="Switch Theme">
                    <svg class="sun" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                    <svg class="moon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                    <span class="mobile-only-text">Switch Theme</span>
                </button>

                <div class="dropdown-container">
                    <button class="icon-btn lang-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                        <span class="lang-text"><?php echo strtoupper($current_lang); ?></span>
                    </button>
                    <div class="dropdown-menu">
                        <?php foreach ($available_langs as $code => $name): ?>
                            <a href="?lang=<?php echo $code; ?>" class="<?php echo ($current_lang == $code) ? 'active' : ''; ?>">
                                <?php echo $name; ?> (<?php echo strtoupper($code); ?>)
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="auth-group">
                    <?php if (empty($_SESSION['user'])): ?>
                        <a href="login.php" class="btn btn-ghost"><?php echo $lang['login']; ?></a>
                        <a href="register.php" class="btn btn-primary"><?php echo $lang['register']; ?></a>
                    <?php else: ?>
                        <div class="user-profile">
                            <div class="avatar">
                                <?php echo strtoupper(substr($_SESSION['user']['name'], 0, 1)); ?>
                            </div>
                            <span class="user-name">
                                <?php echo htmlspecialchars($_SESSION['user']['name']); ?>
                            </span>
                        </div>
                        <a href="logout.php" class="btn btn-primary btn-sm"><?php echo $lang['logout']; ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="mobile-overlay" id="mobile-overlay"></div>
    </div>
</header>

<style>
    /* =========================================
       1. CORE VARIABLES & THEME
       ========================================= */
    :root {
        --h-bg: #12181b;
        --h-surface: #1a2226;
        --h-green: #68d391;
        --h-gold: #f5b041;
        --h-text: #e6f1ff;
        --h-muted: #a0aec0;
        --h-border: rgba(160, 174, 192, 0.2);
        --h-glass: rgba(18, 24, 27, 0.9);
        --h-shadow: 0 4px 20px rgba(0,0,0,0.2);
    }
    
    html.light-mode {
        --h-bg: #F3F4F6;
        --h-surface: #FFFFFF;
        --h-green: #059669;
        --h-gold: #D97706;
        --h-text: #111827;
        --h-muted: #6B7280;
        --h-border: rgba(0, 0, 0, 0.08);
        --h-glass: rgba(255, 255, 255, 0.95);
        --h-shadow: 0 4px 15px rgba(0,0,0,0.06);
    }

    /* =========================================
       2. HEADER LAYOUT
       ========================================= */
    #main-header {
        position: sticky;
        top: 0;
        z-index: 1000;
        background: var(--h-glass);
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        border-bottom: 1px solid var(--h-border);
        box-shadow: var(--h-shadow);
        width: 100%;
    }

    .header-inner {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 5%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 70px;
    }

    /* =========================================
       3. LOGO
       ========================================= */
    .logo {
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        z-index: 1002;
    }
    .logo img {
        height: 38px;
        width: 38px;
        border-radius: 50%;
        border: 2px solid var(--h-green);
    }
    .logo span {
        font-family: 'Montserrat', sans-serif;
        font-size: 1.4rem;
        font-weight: 700;
        color: var(--h-text);
        letter-spacing: -0.5px;
    }

    /* =========================================
       4. NAVIGATION (DESKTOP)
       ========================================= */
    .nav-wrapper {
        display: flex;
        align-items: center;
        gap: 2.5rem;
        flex-grow: 1;
        justify-content: flex-end;
    }
    
    .mobile-nav-header, .nav-divider, .mobile-only-text { display: none; }

    .main-nav {
        display: flex;
        gap: 2rem;
    }

    .nav-link {
        font-family: 'Poppins', sans-serif;
        font-size: 0.95rem;
        font-weight: 500;
        color: var(--h-muted);
        text-decoration: none;
        position: relative;
        transition: color 0.3s;
        padding: 5px 0;
    }
    .nav-link:hover { color: var(--h-text); }
    .nav-link::after {
        content: ''; position: absolute; left: 0; bottom: 0;
        width: 0; height: 2px; background: var(--h-green);
        transition: width 0.3s ease;
    }
    .nav-link:hover::after { width: 100%; }

    /* =========================================
       5. CONTROLS
       ========================================= */
    .controls-group { display: flex; align-items: center; gap: 1rem; }

    .icon-btn {
        background: transparent;
        border: 1px solid var(--h-border);
        color: var(--h-muted);
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        flex-shrink: 0;
    }
    .icon-btn:hover {
        background: var(--h-surface);
        color: var(--h-green);
        border-color: var(--h-green);
    }

    /* Theme Icons */
    .theme-toggle { position: relative; overflow: hidden; }
    .theme-toggle svg { position: absolute; transition: transform 0.4s, opacity 0.3s; }
    .theme-toggle .sun { opacity: 1; transform: rotate(0); }
    .theme-toggle .moon { opacity: 0; transform: rotate(90deg); }
    html.light-mode .theme-toggle .sun { opacity: 0; transform: rotate(-90deg); }
    html.light-mode .theme-toggle .moon { opacity: 1; transform: rotate(0); stroke: var(--h-text); }

    /* Language Dropdown */
    .dropdown-container { position: relative; }
    .lang-btn { width: auto; padding: 0 12px; gap: 8px; font-family: 'Poppins', sans-serif; font-size: 0.9rem; font-weight: 600; }
    .dropdown-menu {
        position: absolute; top: calc(100% + 12px); right: 0;
        background: var(--h-surface);
        border: 1px solid var(--h-border);
        border-radius: 12px;
        padding: 0.5rem;
        min-width: 160px;
        opacity: 0; visibility: hidden; transform: translateY(10px);
        transition: all 0.2s ease;
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }
    .dropdown-container:hover .dropdown-menu { opacity: 1; visibility: visible; transform: translateY(0); }
    .dropdown-menu a {
        display: block; padding: 0.7rem 1rem;
        color: var(--h-muted); text-decoration: none;
        border-radius: 8px; font-size: 0.9rem; transition: 0.2s; margin-bottom: 2px;
    }
    .dropdown-menu a:hover { background: var(--h-bg); color: var(--h-text); }
    .dropdown-menu a.active { color: var(--h-green); font-weight: 600; background: rgba(104, 211, 145, 0.1); }

    /* Auth Buttons */
    .auth-group { display: flex; gap: 10px; align-items: center; }
    .btn {
        padding: 0.55rem 1.4rem;
        border-radius: 8px;
        font-family: 'Poppins', sans-serif;
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        transition: all 0.3s;
        cursor: pointer;
        border: 1px solid transparent;
        white-space: nowrap;
        display: inline-block;
    }
    .btn-ghost { color: var(--h-gold); border-color: var(--h-gold); background: transparent; }
    .btn-ghost:hover { background: var(--h-gold); color: #fff; }
    .btn-primary { background: var(--h-green); color: #fff; border-color: var(--h-green); }
    .btn-primary:hover { background: transparent; color: var(--h-green); }
    .btn-sm { padding: 0.4rem 0.8rem; font-size: 0.85rem; }

    /* User Profile */
    .user-profile { display: flex; align-items: center; gap: 8px; margin-right: 5px; }
    .avatar {
        width: 34px; height: 34px;
        background: var(--h-green); color: #fff;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 0.9rem;
    }
    .user-name { color: var(--h-text); font-weight: 500; font-size: 0.9rem; max-width: 100px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    /* =========================================
       6. MOBILE SPECIFIC (DRAWER MENU)
       ========================================= */
    .menu-toggle {
        display: none;
        background: transparent; border: none;
        color: var(--h-text);
        cursor: pointer; 
        padding: 5px;
    }

    .mobile-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100vh;
        background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);
        opacity: 0; visibility: hidden; transition: 0.3s; z-index: 1003;
    }

    @media (max-width: 992px) {
        .menu-toggle { display: flex; align-items: center; justify-content: center; }
        
        .nav-wrapper {
            position: fixed; top: 0; right: -320px; /* Hidden off-screen */
            width: 85%; max-width: 320px; height: 100vh;
            background: var(--h-surface);
            flex-direction: column;
            justify-content: flex-start;
            padding: 0;
            gap: 0;
            transition: right 0.4s cubic-bezier(0.16, 1, 0.3, 1); /* Smooth iOS easing */
            box-shadow: -5px 0 30px rgba(0,0,0,0.3);
            z-index: 1004;
            overflow-y: auto;
        }

        .nav-wrapper.active { right: 0; }
        .mobile-overlay.active { opacity: 1; visibility: visible; }
        
        /* Mobile Header inside menu */
        .mobile-nav-header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid var(--h-border);
            margin-bottom: 1rem;
        }
        .mobile-nav-title { font-size: 1.2rem; font-weight: 700; color: var(--h-text); font-family: 'Montserrat', sans-serif; }
        .menu-close { background: transparent; border: none; color: var(--h-muted); cursor: pointer; }
        .menu-close:hover { color: var(--h-text); }

        /* Links on Mobile */
        .main-nav { flex-direction: column; width: 100%; gap: 0; padding: 0 1.5rem; }
        .nav-link { 
            font-size: 1.1rem; padding: 1rem 0; 
            border-bottom: 1px solid var(--h-border); 
            display: block; width: 100%;
        }
        .nav-link:last-child { border-bottom: none; }
        .nav-link::after { display: none; } 

        .nav-divider { 
            display: block; height: 1px; background: var(--h-border); width: 100%; margin: 1.5rem 0; 
        }

        /* Controls on Mobile */
        .controls-group { 
            flex-direction: column; width: 100%; gap: 1rem; align-items: flex-start; padding: 0 1.5rem 2rem;
        }

        /* Full width buttons */
        .dropdown-container, .auth-group { width: 100%; }
        .lang-btn { width: 100%; justify-content: space-between; border-radius: 8px; }
        
        .dropdown-menu { 
            position: static; width: 100%; opacity: 1; visibility: visible; transform: none; 
            box-shadow: none; border: 1px solid var(--h-border); padding: 0.5rem; display: none; margin-top: 10px; 
        }
        .dropdown-container:hover .dropdown-menu { display: block; }

        /* Theme Toggle Mobile */
        .theme-toggle { 
            width: 100%; justify-content: flex-start; border: none; padding-left: 0; height: auto; 
            background: transparent !important;
        }
        .theme-toggle svg { position: relative; margin-right: 12px; }
        .mobile-only-text { display: inline-block; color: var(--h-text); font-weight: 500; font-family: 'Poppins', sans-serif; margin-left: 30px; }

        .auth-group { flex-direction: column; align-items: stretch; margin-top: 0.5rem; }
        .btn { text-align: center; width: 100%; padding: 0.8rem; }
        .user-profile { margin-bottom: 1rem; justify-content: center; }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const openBtn = document.getElementById('menu-open-btn');
        const closeBtn = document.getElementById('menu-close-btn');
        const navWrapper = document.getElementById('nav-wrapper');
        const overlay = document.getElementById('mobile-overlay');

        function openMenu() {
            navWrapper.classList.add('active');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        }

        function closeMenu() {
            navWrapper.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = ''; // Restore scrolling
        }

        if (openBtn) openBtn.addEventListener('click', openMenu);
        if (closeBtn) closeBtn.addEventListener('click', closeMenu);
        if (overlay) overlay.addEventListener('click', closeMenu);

        // --- Theme Toggle Logic ---
        const themeBtn = document.getElementById('theme-toggle-btn');
        if (themeBtn) {
            themeBtn.addEventListener('click', () => {
                document.documentElement.classList.toggle('light-mode');
                const newTheme = document.documentElement.classList.contains('light-mode') ? 'light' : 'dark';
                try { localStorage.setItem('theme', newTheme); } catch (e) {}
            });
        }
    });
</script>