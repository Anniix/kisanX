<?php
session_start();
// *** NEW: Include the language engine ***
include 'php/language_init.php';
?>
<!doctype html>
<html lang="<?php echo $current_lang; ?>"> <head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo $lang['about_page_title']; ?></title> <link rel="preconnect" href="https://fonts.googleapis.com">
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
        overflow-x: hidden; /* Prevent horizontal scrollbars from animations */
    }
    main.container {
        padding: 2rem 5%;
        max-width: 1100px;
        margin: 0 auto;
        padding-top: 80px; /* Space for sticky header */
    }

    /* ===== Themed Content Card ===== */
    .about-card {
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        border-radius: 16px;
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        padding: 2.5rem;
        box-shadow: 0 8px 30px rgba(0,0,0,0.3);
        margin-bottom: 2.5rem;
    }

    /* ===== Scroll Animation Styles ===== */
    .scroll-trigger {
        opacity: 0;
        transform: translateY(50px);
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
        margin: 0 0 2rem 0;
        color: var(--kd-text);
    }
    h2 span, h3 span {
        color: var(--kd-earthy-green);
    }
    h3 {
        font-family: 'Montserrat', sans-serif;
        font-size: 1.5rem;
        margin-bottom: 1rem;
        color: var(--kd-text);
    }
    p {
        color: var(--kd-muted);
        line-height: 1.8;
        margin: 0 0 1.5rem;
        font-size: 1.05rem;
    }
    p strong {
        color: var(--kd-warm-gold);
        font-weight: 600;
    }

    /* ===== Mission/Vision Section ===== */
    .split-section {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2.5rem;
    }
    .split-item {
        border-left: 3px solid var(--kd-earthy-green);
        padding-left: 1.5rem;
    }

    /* ===== Values Section ===== */
    .values-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
        text-align: center;
    }
    .value-item h4 {
        font-family: 'Montserrat', sans-serif;
        font-size: 1.2rem;
        margin: 1rem 0 0.5rem;
        color: var(--kd-text);
    }
    .value-item p { font-size: 0.95rem; line-height: 1.6; }
    .value-icon {
        font-size: 2.5rem;
        color: var(--kd-earthy-green);
    }

    /* ===== Impact Section ===== */
    .impact-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        text-align: center;
    }
    .impact-item h2 {
        font-size: 3rem;
        color: var(--kd-warm-gold);
        margin-bottom: 0.5rem;
    }
    .impact-item p {
        font-size: 1rem;
        margin-bottom: 0;
        color: var(--kd-text);
    }

    /* ===== CTA Section with Animated Buttons ===== */
    .cta-section { text-align: center; }
    .cta-buttons { display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap; margin-top: 1.5rem; }
    .btn {
        display: inline-block; padding: 0.8rem 2rem; border-radius: 8px; font-weight: 600;
        text-decoration: none; transition: all 0.3s ease; border: 2px solid var(--kd-earthy-green);
        background: var(--kd-earthy-green); color: var(--kd-bg);
    }
    .btn.secondary { background: transparent; color: var(--kd-earthy-green); }
    .btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        filter: brightness(1.1); /* Brighten effect for primary button */
    }
    .btn.secondary:hover {
        background: var(--kd-earthy-green); /* Fill effect for secondary button */
        color: var(--kd-bg);
    }

    /* ===== Team Carousel (Unchanged) ===== */
    .carousel { position: relative; width: 100%; max-width: 400px; height: 350px; margin: 0 auto; perspective: 1200px; display: flex; align-items: center; justify-content: center; }
    .carousel__container { width: 100%; height: 100%; position: absolute; transform-style: preserve-3d; animation: spin 20s infinite linear; }
    .carousel:hover .carousel__container { animation-play-state: paused; }
    .carousel__item { position: absolute; top: 40px; left: 50%; margin-left: -88px; width: 176px; transform-style: preserve-3d; text-align: center; color: var(--kd-text); }
    .carousel__item img { width: 160px; height: 160px; border-radius: 50%; border: 4px solid var(--kd-earthy-green); box-shadow: 0 5px 20px rgba(104, 211, 145, 0.2); cursor: pointer; transition: transform 0.3s ease; }
    .carousel__item:hover img { transform: scale(1.05); }
    .member-name { margin-top: 1rem; font-weight: 600; font-size: 1.1rem; color: var(--kd-text); }
    .member-role { font-size: .9rem; color: var(--kd-muted); }
    @keyframes spin { from { transform: rotateY(0deg); } to { transform: rotateY(360deg); } }

    /* ===== Responsive adjustments ===== */
    @media (max-width: 768px) {
        main.container { padding-top: 60px; }
        .about-card { padding: 1.5rem; }
        p { font-size: 1rem; }
        .split-section { grid-template-columns: 1fr; gap: 2rem; }
        .carousel { transform: scale(0.8); height: 300px; margin-top: -2rem; }
        /* NEW: Button responsiveness for mobile */
        .cta-buttons {
            flex-direction: column;
            align-items: center;
        }
        .cta-buttons .btn {
            width: 100%;
            max-width: 350px;
        }
    }
  </style>
</head>
<body>
  <?php include 'header.php'; ?>

  <main id="kd-page" class="container" data-page="about">
    
    <section class="about-card scroll-trigger">
      <h2><?php echo $lang['about_title']; ?> <span><?php echo $lang['about_title_span']; ?></span></h2>
      <p><?php echo $lang['about_intro_p1']; ?></p>
      <p><?php echo $lang['about_intro_p2']; ?></p>
    </section>

    <section class="about-card scroll-trigger">
        <div class="split-section">
            <div class="split-item">
                <h3><?php echo $lang['about_mission_title']; ?></h3>
                <p><?php echo $lang['about_mission_p']; ?></p>
            </div>
            <div class="split-item">
                <h3><?php echo $lang['about_vision_title']; ?></h3>
                <p><?php echo $lang['about_vision_p']; ?></p>
            </div>
        </div>
    </section>

    <section class="about-card scroll-trigger">
        <h2><?php echo $lang['about_values_title']; ?> <span><?php echo $lang['about_values_title_span']; ?></span></h2>
        <div class="values-grid">
            <div class="value-item">
                <div class="value-icon">🤝</div>
                <h4><?php echo $lang['about_value_1_title']; ?></h4>
                <p><?php echo $lang['about_value_1_p']; ?></p>
            </div>
            <div class="value-item">
                <div class="value-icon">🔍</div>
                <h4><?php echo $lang['about_value_2_title']; ?></h4>
                <p><?php echo $lang['about_value_2_p']; ?></p>
            </div>
            <div class="value-item">
                <div class="value-icon">💻</div>
                <h4><?php echo $lang['about_value_3_title']; ?></h4>
                <p><?php echo $lang['about_value_3_p']; ?></p>
            </div>
        </div>
    </section>
    
    <section id="impact-section" class="about-card scroll-trigger">
        <h2><?php echo $lang['about_impact_title']; ?> <span><?php echo $lang['about_impact_title_span']; ?></span></h2>
        <div class="impact-grid">
            <div class="impact-item">
                <h2 class="counter-number" data-goal="1000">0+</h2>
                <p><?php echo $lang['about_impact_1_p']; ?></p>
            </div>
            <div class="impact-item">
                <h2 class="counter-number" data-goal="30">0%</h2>
                <p><?php echo $lang['about_impact_2_p']; ?></p>
            </div>
            <div class="impact-item">
                <h2 class="counter-number" data-goal="5000">0+</h2>
                <p><?php echo $lang['about_impact_3_p']; ?></p>
            </div>
        </div>
    </section>

    <section class="about-card team-section scroll-trigger">
      <h2><?php echo $lang['about_team_title']; ?> <span><?php echo $lang['about_team_title_span']; ?></span></h2>
      <div class="carousel">
        <div class="carousel__container">
          <div class="carousel__item" style="transform: rotateY(0deg) translateZ(220px);">
            <a href="#"><img src="aniket.jpeg" alt="Aniket Dubey"></a>
            <div class="member-name">Aniket Dubey</div>
            <div class="member-role"><?php echo $lang['about_team_1_role']; ?></div>
          </div>
          <div class="carousel__item" style="transform: rotateY(90deg) translateZ(220px);">
            <a href="#"><img src="saniya.jpeg" alt="Saniya Farooqui"></a>
            <div class="member-name">Saniya Farooqui</div>
            <div class="member-role"><?php echo $lang['about_team_2_role']; ?></div>
          </div>
          <div class="carousel__item" style="transform: rotateY(180deg) translateZ(220px);">
            <a href="#"><img src="arjun.jpeg" alt="Arjun Tiwari"></a>
            <div class="member-name">Arjun Tiwari</div>
            <div class="member-role"><?php echo $lang['about_team_3_role']; ?></div>
          </div>
          <div class="carousel__item" style="transform: rotateY(270deg) translateZ(220px);">
            <a href="#"><img src="sneha.jpeg" alt="Sneha Pandey"></a>
            <div class="member-name">Sneha Pandey</div>
            <div class="member-role"><?php echo $lang['about_team_4_role']; ?></div>
          </div>
        </div>
      </div>
    </section>

    <section class="about-card cta-section scroll-trigger">
      <h2><?php echo $lang['about_join_title']; ?> <span><?php echo $lang['about_join_title_span']; ?></span></h2>
      <p><?php echo $lang['about_join_p']; ?></p>
      <div class="cta-buttons">
          <a href="register.php" class="btn"><?php echo $lang['about_join_btn_1']; ?></a>
          <a href="register.php" class="btn secondary"><?php echo $lang['about_join_btn_2']; ?></a>
      </div>
    </section>

  </main>

  <?php include 'footer.php'; ?>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
        // --- REPEATABLE SCROLL ANIMATION ---
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

        // --- ONE-TIME NUMBER COUNTER ANIMATION ---
        const runCounter = () => {
            const counters = document.querySelectorAll('.counter-number');
            counters.forEach(counter => {
                const goal = parseInt(counter.dataset.goal, 10);
                const suffix = counter.innerText.replace('0', ''); // Gets '+' or '%'
                let startTimestamp = null;
                const duration = 2000; // Animation duration in milliseconds

                const step = (timestamp) => {
                    if (!startTimestamp) startTimestamp = timestamp;
                    const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                    const currentValue = Math.floor(progress * goal);
                    counter.innerText = currentValue.toLocaleString() + suffix;
                    if (progress < 1) {
                        window.requestAnimationFrame(step);
                    }
                };
                window.requestAnimationFrame(step);
            });
        };
        
        const impactSection = document.getElementById('impact-section');
        const counterObserver = new IntersectionObserver((entries, observer) => {
            const [entry] = entries;
            if (entry.isIntersecting) {
                runCounter();
                observer.unobserve(impactSection); // Ensures the counter runs only once
            }
        }, { threshold: 0.5 });

        if(impactSection) {
            counterObserver.observe(impactSection);
        }
    });
  </script>
</body>
</html>