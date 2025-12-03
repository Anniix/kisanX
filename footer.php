<?php
// footer.php

// The $lang array is already available from the parent page (e.g., index.php)
// We also assume session_start() has been called.
if (session_status() === PHP_SESSION_NONE) {
    // This is a safety check in case the footer is ever loaded directly
    session_start();
    include 'php/language_init.php';
}

// Determine the correct URL for the "Home" link based on login status
$home_link = 'index.php'; // Default for guests
if (!empty($_SESSION['user'])) {
    // If a user is logged in, change the link to their dashboard
    $home_link = 'user_dashboard.php';
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<footer class="kd-footer">
    <div class="footer-container">
        <div class="footer-col brand">
            <h2>kisanX</h2>
            <p><?php echo $lang['footer_brand_subtitle']; ?></p>
        </div>

        <div class="footer-col">
            <h4><?php echo $lang['footer_quick_links']; ?></h4>
            <ul>
                <li><a href="<?php echo $home_link; ?>"><?php echo $lang['home']; ?></a></li>
                <li><a href="about.php"><?php echo $lang['about_us']; ?></a></li>
                <li><a href="contact.php"><?php echo $lang['contact_us']; ?></a></li>
                <li><a href="products.php"><?php echo $lang['footer_products']; ?></a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4><?php echo $lang['footer_support']; ?></h4>
            <ul>
                <li><a href="faq.php"><?php echo $lang['footer_faqs']; ?></a></li>
                <li><a href="policy.php"><?php echo $lang['footer_policy']; ?></a></li>
                <li><a href="terms.php"><?php echo $lang['footer_terms']; ?></a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4><?php echo $lang['footer_follow']; ?></h4>
            <div class="social">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-linkedin-in"></i></a>
            </div>
        </div>
    </div>

    <div class="footer-bottom">
        <p>© <?php echo date("Y"); ?> kisanX. <?php echo $lang['footer_copyright']; ?></p>
    </div>
</footer>

<style>
    /* ===== Root variables to match the rest of the site ===== */
    :root {
        --kd-bg-surface: #1a2226;
        --kd-earthy-green: #68d391;
        --kd-warm-gold: #f5b041;
        --kd-text: #e6f1ff;
        --kd-muted: #a0aec0;
        --glass-border: rgba(160, 174, 192, 0.2);
    }

    /* ===== Main Footer Styling ===== */
    .kd-footer {
        background-color: var(--kd-bg-surface);
        color: var(--kd-text);
        padding: 4rem 5% 2rem;
        margin-top: 4rem;
        border-top: 1px solid var(--glass-border);
    }
    .kd-footer .footer-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 2.5rem;
        max-width: 1400px; /* Matching container width */
        margin: 0 auto;
    }
    
    /* ===== Brand Column ===== */
     .kd-footer .footer-col.brand h2 {
        font-family: 'Montserrat', sans-serif;
        color: var(--kd-text);
        margin-bottom: 1rem;
        font-size: 1.8rem;
        font-weight: 700;
    }
    .kd-footer .footer-col.brand p {
        color: var(--kd-muted);
        font-size: 0.95rem;
        line-height: 1.6;
        max-width: 250px;
    }

    /* ===== Link Columns Styling ===== */
    .kd-footer .footer-col h4 {
        font-family: 'Montserrat', sans-serif;
        font-size: 1.1rem;
        margin-bottom: 1.5rem; /* More space for the underline */
        font-weight: 600;
        color: var(--kd-text);
        position: relative;
    }
    .kd-footer .footer-col h4::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: -8px;
        width: 35px;
        height: 2px;
        background: var(--kd-earthy-green);
        border-radius: 4px;
    }
    .kd-footer .footer-col ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .kd-footer .footer-col ul li {
        margin-bottom: 0.75rem;
    }
    .kd-footer .footer-col ul li a {
        text-decoration: none;
        color: var(--kd-muted);
        transition: color .3s ease, transform .3s ease;
        display: inline-block;
        font-family: 'Poppins', sans-serif;
    }
    .kd-footer .footer-col ul li a:hover {
        color: var(--kd-earthy-green);
        transform: translateX(5px);
    }
   
    /* ===== Social Icons ===== */
    .kd-footer .social a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-right: 0.75rem;
        background: transparent;
        border: 1px solid var(--glass-border);
        width: 40px;
        height: 40px;
        border-radius: 50%;
        font-size: 1rem;
        color: var(--kd-muted);
        transition: all .3s ease;
    }
    .kd-footer .social a:hover {
        border-color: var(--kd-warm-gold);
        background: var(--kd-warm-gold);
        color: var(--kd-bg-surface);
        transform: translateY(-4px);
    }
    
    /* ===== Footer Bottom Bar ===== */
    .kd-footer .footer-bottom {
        text-align: center;
        margin-top: 3rem;
        padding-top: 2rem;
        border-top: 1px solid var(--glass-border);
        font-size: 0.9rem;
        color: var(--kd-muted);
    }
</style>