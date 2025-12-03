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
  <title><?php echo $lang['terms_page_title']; ?></title>
  
  <script>
    (function() {
        try {
            const theme = localStorage.getItem('theme');
            if (theme === 'light') { document.documentElement.classList.add('light-mode'); }
        } catch (e) {}
    })();
  </script>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    :root {
        --kd-bg: #12181b; --kd-bg-surface: #1a2226; --kd-earthy-green: #68d391;
        --kd-text: #e6f1ff; --kd-muted: #a0aec0; --glass-bg: rgba(26, 34, 38, 0.6);
        --glass-border: rgba(160, 174, 192, 0.2);
        --kd-warm-gold: #f5b041;
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
    body {
        margin: 0; background: var(--kd-bg); color: var(--kd-text);
        font-family: 'Poppins', sans-serif; -webkit-font-smoothing: antialiased;
    }
    main.container {
        padding: 2rem 5%; max-width: 900px; margin: 0 auto; padding-top: 80px;
    }
    .card {
        background: var(--glass-bg); border: 1px solid var(--glass-border);
        border-radius: 16px; backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
        padding: 2.5rem; box-shadow: 0 8px 30px rgba(0,0,0,0.3);
    }
    h2 {
        font-family: 'Montserrat', sans-serif; font-size: clamp(1.8rem, 4vw, 2.5rem);
        font-weight: 700; text-align: center; margin: 0 0 1rem 0; color: var(--kd-text);
    }
    h2 span { color: var(--kd-earthy-green); }
    h3 {
        font-family: 'Montserrat', sans-serif; font-size: 1.5rem;
        margin: 2rem 0 1.5rem; color: var(--kd-earthy-green);
        border-bottom: 1px solid var(--glass-border); padding-bottom: 0.5rem;
    }
    p, li { color: var(--kd-muted); line-height: 1.8; font-size: 1.05rem; }
    ul { padding-left: 20px; }
    li { margin-bottom: 0.75rem; }
    p strong, li strong { color: var(--kd-text); font-weight: 600; }
    .updated { text-align: center; color: var(--kd-muted); margin-bottom: 2.5rem; font-style: italic; }
  </style>
</head>
<body>
  <?php include 'header.php'; ?>

  <main id="kd-page" class="container" data-page="terms">
    <div class="card">
      <h2><span><?php echo $lang['terms_title_span']; ?></span> <?php echo $lang['terms_title']; ?></h2>
      <p class="updated"><?php echo $lang['terms_last_updated']; ?></p>

      <h3><?php echo $lang['terms_agreement_title']; ?></h3>
      <p><?php echo $lang['terms_agreement_p1']; ?></p>

      <h3><?php echo $lang['terms_platform_title']; ?></h3>
      <p><?php echo $lang['terms_platform_p1']; ?></p>

      <h3><?php echo $lang['terms_accounts_title']; ?></h3>
      <p><?php echo $lang['terms_accounts_p1']; ?></p>
      
      <h3><?php echo $lang['terms_farmer_title']; ?></h3>
      <ul>
        <li><?php echo $lang['terms_farmer_li1']; ?></li>
        <li><?php echo $lang['terms_farmer_li2']; ?></li>
        <li><?php echo $lang['terms_farmer_li3']; ?></li>
        <li><?php echo $lang['terms_farmer_li4']; ?></li>
      </ul>

      <h3><?php echo $lang['terms_buyer_title']; ?></h3>
      <ul>
        <li><?php echo $lang['terms_buyer_li1']; ?></li>
        <li><?php echo $lang['terms_buyer_li2']; ?></li>
        <li><?php echo $lang['terms_buyer_li3']; ?></li>
      </ul>

      <h3><?php echo $lang['terms_liability_title']; ?></h3>
      <p><?php echo $lang['terms_liability_p1']; ?></p>
      
      <h3><?php echo $lang['terms_governing_title']; ?></h3>
      <p><?php echo $lang['terms_governing_p1']; ?></p>

    </div>
  </main>

  <?php include 'footer.php'; ?>
</body>
</html>