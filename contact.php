<?php
session_start();
// *** NEW: Include the language engine ***
include 'php/language_init.php';
?>
<!doctype html>
<html lang="<?php echo $current_lang; ?>"> <head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo $lang['contact_page_title']; ?></title> <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  
  <script>
    (function() {
        try {
            const theme = localStorage.getItem('theme');
            if (theme === 'light') { document.documentElement.classList.add('light-mode'); }
        } catch (e) {}
    })();
  </script>

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
        max-width: 1200px;
        margin: 0 auto;
        padding-top: 80px; /* Space for sticky header */
    }
    .card {
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        border-radius: 16px;
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        padding: 2.5rem;
        box-shadow: 0 8px 30px rgba(0,0,0,0.3);
        margin-bottom: 2.5rem;
    }

    /* ===== NEW: Scroll Animation Styles ===== */
    .scroll-trigger {
        transition: opacity 0.7s ease-out, transform 0.7s ease-out;
    }
    .card.scroll-trigger {
        opacity: 0;
        transform: translateY(50px);
    }
    .card.scroll-trigger.is-visible {
        opacity: 1;
        transform: translateY(0);
    }
    
    /* ===== Typography & Title Animation ===== */
    h2 {
        font-family: 'Montserrat', sans-serif;
        font-size: clamp(1.8rem, 4vw, 2.5rem);
        font-weight: 700;
        text-align: center;
        margin: 0 0 2rem 0;
        color: var(--kd-text);
    }
    h2.h-title {
        display: flex; flex-wrap: wrap; justify-content: center; gap: 0.25em;
    }
    .h-title .h-word {
        display: inline-block;
        opacity: 0;
        transform: translateY(-100px) rotateZ(5deg);
        transition: transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94), opacity 0.8s ease-out;
    }
    .h-title.is-visible .h-word { opacity: 1; transform: translateY(0); }
    .h-title.is-visible .h-word:nth-child(2) { transition-delay: 0.1s; }
    .h-title.is-visible .h-word:nth-child(3) { transition-delay: 0.2s; }

    h2 span, .h-word.special { color: var(--kd-earthy-green); }
    p { color: var(--kd-muted); line-height: 1.8; margin: 0 0 1.5rem; }

    /* ===== Two-Column Layout & Animation ===== */
    .contact-layout {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 2.5rem;
        align-items: flex-start;
    }
    /* Specific card animations for this layout */
    .contact-layout .card {
        opacity: 0;
        transition: opacity 0.7s ease-out, transform 0.7s ease-out;
    }
    .contact-layout .form-card { transform: translateX(-100px); }
    .contact-layout .info-card { transform: translateX(100px); }
    
    .contact-layout.is-visible .card {
        opacity: 1;
        transform: translateX(0);
    }
    .contact-layout.is-visible .info-card { transition-delay: 0.2s; }

    /* ===== Form Styling ===== */
    label { display: block; margin: 1rem 0 0.5rem; font-weight: 500; color: var(--kd-muted); }
    input, textarea {
        width: 100%; padding: 0.9rem 1rem; border-radius: 8px;
        border: 1px solid var(--glass-border); background: var(--kd-bg-surface);
        color: var(--kd-text); font-size: 1rem; font-family: 'Poppins', sans-serif;
        transition: all .3s ease;
    }
    input:focus, textarea:focus {
        outline: none; border-color: var(--kd-earthy-green);
        box-shadow: 0 0 0 3px rgba(104, 211, 145, 0.3);
    }
    textarea { resize: vertical; min-height: 120px; }
    button[type="submit"] {
        margin-top: 1.5rem; padding: 0.9rem 2rem; border-radius: 8px; font-weight: 600;
        text-decoration: none; transition: all 0.3s ease; border: none;
        background: var(--kd-earthy-green); color: var(--kd-bg); cursor: pointer;
    }
    button[type="submit"]:hover { background: #55b880; transform: translateY(-3px); }
    .success-message {
        display: none; margin-top: 1.5rem; padding: 1rem;
        border-radius: 8px; background: rgba(104, 211, 145, 0.15);
        border: 1px solid rgba(104, 211, 145, 0.3);
        color: var(--kd-earthy-green); font-weight: 500;
    }

    /* ===== Contact Info Styling ===== */
    .info-card h3 {
        font-family: 'Montserrat', sans-serif;
        margin-top: 0;
        margin-bottom: 1.5rem;
        color: var(--kd-text);
    }
    .info-item { display: flex; align-items: flex-start; gap: 1rem; margin-bottom: 1.5rem; }
    .info-icon { font-size: 1.2rem; color: var(--kd-earthy-green); margin-top: 0.2rem; }
    .info-item strong { display: block; font-weight: 600; color: var(--kd-text); margin-bottom: 0.2rem; }
    .info-item p { margin: 0; line-height: 1.6; font-size: 0.95rem; }
    .info-item a { color: var(--kd-muted); text-decoration: none; transition: color .3s ease; }
    .info-item a:hover { color: var(--kd-warm-gold); }

    /* ===== Map Section ===== */
    .map-card iframe {
        width: 100%;
        height: 400px;
        border-radius: 12px;
        border: 1px solid var(--glass-border);
        filter: invert(90%) grayscale(80%);
    }

    /* ===== FAQ Section ===== */
    details {
        background: var(--kd-bg-surface);
        border: 1px solid var(--glass-border);
        border-radius: 8px;
        margin-bottom: 1rem;
        transition: background-color 0.3s ease;
    }
    details[open] { background: rgba(104, 211, 145, 0.1); }
    summary {
        padding: 1.2rem;
        cursor: pointer;
        font-weight: 600;
        list-style: none;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    summary::-webkit-details-marker { display: none; }
    summary::after {
        content: '+';
        font-size: 1.5rem;
        color: var(--kd-earthy-green);
        transition: transform 0.3s ease;
    }
    details[open] summary::after { transform: rotate(45deg); }
    details p {
        padding: 0 1.2rem 1.2rem;
        margin: 0;
        border-top: 1px solid var(--glass-border);
        padding-top: 1.2rem;
        font-size: 0.95rem;
    }

    /* ===== Responsive ===== */
    @media (max-width: 992px) {
        .contact-layout { grid-template-columns: 1fr; }
    }
    @media (max-width: 768px) {
        main.container { padding-top: 60px; }
        .card { padding: 1.5rem; }
    }
  </style>
</head>
<body>
  <?php include 'header.php'; ?>

  <main id="kd-page" class="container" data-page="contact">
    
    <h2 class="h-title scroll-trigger">
        <span class="h-word"><?php echo $lang['contact_title_1']; ?></span>
        <span class="h-word"><?php echo $lang['contact_title_2']; ?></span>
        <span class="h-word special"><?php echo $lang['contact_title_3']; ?></span>
    </h2>
    <p style="text-align:center; max-width: 700px; margin: 0 auto 2.5rem;"><?php echo $lang['contact_subtitle']; ?></p>

    <div class="contact-layout scroll-trigger">
        <div class="card form-card">
            <h3><?php echo $lang['contact_form_title']; ?></h3>
            <form id="contactForm">
                <label for="name"><?php echo $lang['contact_form_name']; ?></label>
                <input id="name" name="name" required />
                <label for="email"><?php echo $lang['contact_form_email']; ?></label>
                <input id="email" name="email" type="email" required />
                <label for="msg"><?php echo $lang['contact_form_message']; ?></label>
                <textarea id="msg" name="msg" rows="5" required placeholder="<?php echo $lang['contact_form_placeholder']; ?>"></textarea>
                <button type="submit"><?php echo $lang['contact_form_button']; ?></button>
                <div class="success-message" id="successMsg"><?php echo $lang['contact_form_success']; ?></div>
            </form>
        </div>

        <div class="card info-card">
            <h3><?php echo $lang['contact_details_title']; ?></h3>
            <div class="info-item">
                <div class="info-icon">📍</div>
                <div>
                    <strong><?php echo $lang['contact_details_addr_title']; ?></strong>
                    <p><?php echo $lang['contact_details_addr_p']; ?></p>
                </div>
            </div>
            <div class="info-item">
                <div class="info-icon">🕒</div>
                <div>
                    <strong><?php echo $lang['contact_details_hours_title']; ?></strong>
                    <p><?php echo $lang['contact_details_hours_p']; ?></p>
                </div>
            </div>
            <div class="info-item">
                <div class="info-icon">📞</div>
                <div>
                    <strong><?php echo $lang['contact_details_inq_title']; ?></strong>
                    <p><a href="tel:+919876543210">+91 98765 43210</a></p>
                </div>
            </div>
             <div class="info-item">
                <div class="info-icon">📧</div>
                <div>
                    <strong><?php echo $lang['contact_details_part_title']; ?></strong>
                    <p><a href="mailto:partners@kisanX.com">partners@kisanX.com</a></p>
                </div>
            </div>
             <div class="info-item">
                <div class="info-icon">💼</div>
                <div>
                    <strong><?php echo $lang['contact_details_b2b_title']; ?></strong>
                    <p><a href="mailto:sales@kisanX.com">sales@kisanX.com</a></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card map-card scroll-trigger">
        <h3><?php echo $lang['contact_location_title']; ?></h3>
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3782.261272210287!2d73.91429997519302!3d18.56206198253926!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3bc2c147b8b3a3bf%3A0x6f7fdcc8e4d6c77e!2sPhoenix%20Marketcity%2C%20Viman%20Nagar%2C%20Pune%2C%20Maharashtra%20411014!5e0!3m2!1sen!2sin!4v1724227092358!5m2!1sen!2sin" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    </div>

    <div class="card faq-card scroll-trigger">
        <h2><?php echo $lang['contact_faq_title']; ?></h2>
        <details>
            <summary><?php echo $lang['contact_faq_1_q']; ?></summary>
            <p><?php echo $lang['contact_faq_1_a']; ?></p>
        </details>
        <details>
            <summary><?php echo $lang['contact_faq_2_q']; ?></summary>
            <p><?php echo $lang['contact_faq_2_a']; ?></p>
        </details>
        <details>
            <summary><?php echo $lang['contact_faq_3_q']; ?></summary>
            <p><?php echo $lang['contact_faq_3_a']; ?></p>
        </details>
         <details>
            <summary><?php echo $lang['contact_faq_4_q']; ?></summary>
            <p><?php echo $lang['contact_faq_4_a']; ?></p>
        </details>
    </div>

  </main>

  <?php include 'footer.php'; ?>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
        // Form submission logic
        document.getElementById('contactForm').addEventListener('submit', function(e){
            e.preventDefault();
            document.getElementById('successMsg').style.display = 'block';
            this.reset();
        });

        // Scroll animation logic
        const scrollObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                } else {
                    entry.target.classList.remove('is-visible');
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