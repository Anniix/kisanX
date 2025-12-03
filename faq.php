<?php
session_start();
// *** NEW: Include the language engine ***
include 'php/language_init.php';
?>
<!doctype html>
<html lang="<?php echo $current_lang; ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo $lang['faq_page_title']; ?></title>
  
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
    /* ===== Root variables to match the site theme ===== */
    :root {
        --kd-bg: #12181b;
        --kd-bg-surface: #1a2226;
        --kd-earthy-green: #68d391;
        --kd-warm-gold: #f5b041;
        --kd-text: #e6f1ff;
        --kd-muted: #a0aec0;
        --glass-bg: rgba(26, 34, 38, 0.6);
        --glass-border: rgba(160, 174, 192, 0.2);
    }

    /* ===== PREMIUM LIGHT MODE OVERRIDES ===== */
    html.light-mode {
        --kd-bg: #F3F4F6;           
        --kd-bg-surface: #FFFFFF;   
        --kd-earthy-green: #059669; 
        --kd-warm-gold: #D97706;    
        --kd-text: #111827;         
        --kd-muted: #6B7280;        
        --glass-bg: rgba(255, 255, 255, 0.85);
        --glass-border: rgba(0, 0, 0, 0.06); 
    }

    *, *::before, *::after { box-sizing: border-box; }

    /* ===== Global Styles ===== */
    body {
        margin: 0;
        background: var(--kd-bg);
        color: var(--kd-text);
        font-family: 'Poppins', sans-serif;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        overflow-x: hidden;
    }
    main.container {
        padding: 2rem 5%;
        max-width: 900px; /* Centered, focused layout */
        margin: 0 auto;
        padding-top: 80px;
    }

    /* ===== Scroll Animation Styles ===== */
    .scroll-trigger {
        opacity: 0;
        transform: translateY(40px);
        transition: opacity 0.7s ease-out, transform 0.7s ease-out;
    }
    .scroll-trigger.is-visible {
        opacity: 1;
        transform: translateY(0);
    }

    /* ===== Typography ===== */
    h2 {
        font-family: 'Montserrat', sans-serif;
        font-size: clamp(1.8rem, 4vw, 2.5rem);
        font-weight: 700;
        text-align: center;
        margin: 0 0 1rem 0;
        color: var(--kd-text);
    }
    h2 span {
        color: var(--kd-earthy-green);
    }
    h3 {
        font-family: 'Montserrat', sans-serif;
        font-size: 1.5rem;
        margin: 2.5rem 0 1.5rem;
        color: var(--kd-warm-gold);
        border-bottom: 2px solid var(--glass-border);
        padding-bottom: 0.5rem;
    }
    p {
        color: var(--kd-muted);
        line-height: 1.8;
        margin: 0 0 1.5rem;
        font-size: 1.05rem;
    }
    
    /* ===== FAQ Header ===== */
    .faq-header {
        text-align: center;
        margin-bottom: 3rem;
        border-bottom: 1px solid var(--glass-border);
        padding-bottom: 2rem;
    }
    .faq-header p {
        font-size: 1.1rem;
        max-width: 700px;
        margin: 0 auto;
    }

    /* ===== FAQ List (Accordion) ===== */
    details {
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        border-radius: 12px;
        margin-bottom: 1rem;
        transition: background-color 0.3s ease;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }
    details[open] { 
        background: rgba(104, 211, 145, 0.1); 
        border-color: rgba(104, 211, 145, 0.3);
    }
    summary {
        padding: 1.2rem 1.5rem;
        cursor: pointer;
        font-weight: 600;
        font-size: 1.1rem;
        list-style: none;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: var(--kd-text);
    }
    summary::-webkit-details-marker { display: none; }
    summary::after {
        content: '+';
        font-size: 1.8rem;
        color: var(--kd-earthy-green);
        transition: transform 0.3s ease;
    }
    details[open] summary {
        color: var(--kd-earthy-green);
    }
    details[open] summary::after { 
        transform: rotate(45deg); 
    }
    details p {
        padding: 0 1.5rem 1.5rem;
        margin: 0;
        border-top: 1px solid var(--glass-border);
        padding-top: 1.5rem;
        font-size: 1rem;
        line-height: 1.7;
    }

    /* ===== CTA Section ===== */
    .cta-section { 
        text-align: center; 
        margin-top: 4rem;
        padding-top: 2.5rem;
        border-top: 1px solid var(--glass-border);
    }
    .btn {
        display: inline-block; padding: 0.8rem 2rem; border-radius: 8px; font-weight: 600;
        text-decoration: none; transition: all 0.3s ease; border: 2px solid var(--kd-warm-gold);
        background: var(--kd-warm-gold); color: var(--kd-bg);
    }
    .btn:hover {
        background: transparent;
        color: var(--kd-warm-gold);
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.3);
    }

    /* ===== Responsive ===== */
    @media (max-width: 768px) {
        main.container { padding-top: 60px; }
        p { font-size: 1rem; }
        summary { font-size: 1rem; padding: 1rem; }
        details p { padding: 0 1rem 1rem; padding-top: 1rem; font-size: 0.95rem; }
    }
  </style>
</head>
<body>
  <?php include 'header.php'; ?>

  <main id="kd-page" class="container" data-page="faq">
    
    <section class="faq-header scroll-trigger">
      <h2><span><?php echo $lang['faq_title_span']; ?></span> <?php echo $lang['faq_title']; ?></h2>
      <p><?php echo $lang['faq_subtitle']; ?></p>
    </section>

    <section class="faq-list scroll-trigger">
      
      <div class="faq-category">
        <h3><?php echo $lang['faq_category_buyers']; ?></h3>
        <details>
            <summary><?php echo $lang['faq_q_quality']; ?></summary>
            <p><?php echo $lang['faq_a_quality']; ?></p>
        </details>
        <details>
            <summary><?php echo $lang['faq_q_b2b']; ?></summary>
            <p><?php echo $lang['faq_a_b2b']; ?></p>
        </details>
        <details>
            <summary><?php echo $lang['faq_q_delivery']; ?></summary>
            <p><?php echo $lang['faq_a_delivery']; ?></p>
        </details>
      </div>
      
      <div class="faq-category">
        <h3><?php echo $lang['faq_category_farmers']; ?></h3>
        <details>
            <summary><?php echo $lang['faq_q_farmer_reg']; ?></summary>
            <p><?php echo $lang['faq_a_farmer_reg']; ?></p>
        </details>
        <details>
            <summary><?php echo $lang['faq_q_farmer_benefits']; ?></summary>
            <p><?php echo $lang['faq_a_farmer_benefits']; ?></p>
        </details>
         <details>
            <summary><?php echo $lang['faq_q_farmer_list']; ?></summary>
            <p><?php echo $lang['faq_a_farmer_list']; ?></p>
        </details>
      </div>

      <div class="faq-category">
        <h3><?php echo $lang['faq_category_general']; ?></h3>
         <details>
            <summary><?php echo $lang['faq_q_payment']; ?></summary>
            <p><?php echo $lang['faq_a_payment']; ?></p>
        </details>
        <details>
            <summary><?php echo $lang['faq_q_returns']; ?></summary>
            <p><?php echo $lang['faq_a_returns']; ?></p>
        </details>
      </div>

    </section>

    <section class="cta-section scroll-trigger">
      <h2><span><?php echo $lang['faq_cta_title_span']; ?></span> <?php echo $lang['faq_cta_title']; ?></h2>
      <p><?php echo $lang['faq_cta_subtitle']; ?></p>
      <a href="contact.php" class="btn"><?php echo $lang['faq_cta_button']; ?></a>
    </section>

  </main>

  <?php include 'footer.php'; ?>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
        const scrollObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.scroll-trigger').forEach(el => {
            scrollObserver.observe(el);
        });
    });
  </script>
</body>
</html>