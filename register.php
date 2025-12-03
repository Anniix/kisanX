<?php
session_start();
include 'php/language_init.php';
include 'php/db.php';

// Handle Register Logic
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    // Get role from form, default to 'customer' if tampered
    $role = isset($_POST['role']) && $_POST['role'] === 'farmer' ? 'farmer' : 'customer';
    
    if ($name && $email && $password) {
        // Check if email exists
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        
        if ($check->rowCount() > 0) {
            $error = $lang['register_error_exists'] ?? "Email already registered.";
        } else {
            // Hash password
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user with SELECTED ROLE
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$name, $email, $hashed, $role])) {
                $_SESSION['popup_message'] = $lang['register_success'] ?? "Registration successful! Please login.";
                header("Location: login.php");
                exit;
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    } else {
        $error = $lang['register_error_empty'] ?? "Please fill all fields.";
    }
}
?>
<!doctype html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Register — KisanX</title>
    
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
        :root {
            /* DARK MODE (Default) */
            --kd-bg: #0C0F14;
            --kd-bg-surface: #1E252C;
            --kd-earthy-green: #4CAF50;
            --kd-warm-gold: #FFC107;
            --kd-text: #E0E7EB;
            --kd-muted: #9AA6AE;
            --kd-danger: #EF5350;
            --glass-bg: rgba(26, 34, 40, 0.75);
            --glass-border: rgba(255, 255, 255, 0.12);
            --card-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.6);
            --input-bg: rgba(0, 0, 0, 0.4);
            /* Gradient specific to Register page */
            --gradient-1: #FFC107; 
            --gradient-2: #4CAF50;
        }
        
        /* LIGHT MODE */
        html.light-mode {
            --kd-bg: #F5F7FA;
            --kd-bg-surface: #FFFFFF;
            --kd-earthy-green: #2E7D32;
            --kd-warm-gold: #FF8F00;
            --kd-text: #263238;
            --kd-muted: #607D8B;
            --kd-danger: #D32F2F;
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(0, 0, 0, 0.08);
            --card-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1), 0 10px 20px -10px rgba(0,0,0,0.05);
            --input-bg: rgba(240, 242, 245, 0.8);
            --gradient-1: #FF8F00;
            --gradient-2: #2E7D32;
        }

        *, *::before, *::after { box-sizing: border-box; }
        
        body {
            margin: 0; font-family: 'Poppins', sans-serif;
            background-color: var(--kd-bg); color: var(--kd-text);
            min-height: 100vh; display: flex; flex-direction: column;
            transition: background-color 0.6s ease, color 0.6s ease;
            overflow-x: hidden;
        }

        /* === ANIMATED PARTICLE BACKGROUND === */
        .auth-container {
            flex: 1; display: flex; justify-content: center; align-items: center;
            padding: 2rem; position: relative; z-index: 1;
            perspective: 1000px;
        }

        .particles-background {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            pointer-events: none; overflow: hidden;
        }

        .particle {
            position: absolute; background: radial-gradient(circle, rgba(255,255,255,0.8) 0%, transparent 60%);
            border-radius: 50%; animation: moveParticles 15s infinite linear alternate;
            opacity: 0; will-change: transform, opacity;
        }
        html.light-mode .particle {
            background: radial-gradient(circle, rgba(0,0,0,0.08) 0%, transparent 60%);
        }

        /* Particle Positions */
        .particle:nth-child(1) { width: 50px; height: 50px; top: 20%; left: 10%; animation-delay: 0s; }
        .particle:nth-child(2) { width: 30px; height: 30px; top: 70%; left: 80%; animation-delay: 3s; animation-duration: 18s; }
        .particle:nth-child(3) { width: 40px; height: 40px; top: 40%; left: 40%; animation-delay: 6s; animation-duration: 12s; }
        .particle:nth-child(4) { width: 60px; height: 60px; top: 80%; left: 20%; animation-delay: 9s; animation-duration: 20s; }
        .particle:nth-child(5) { width: 25px; height: 25px; top: 10%; left: 90%; animation-delay: 1s; animation-duration: 10s; }

        @keyframes moveParticles {
            0% { transform: translate(0, 0) scale(0.5); opacity: 0; }
            50% { opacity: 0.3; }
            100% { transform: translate(-50px, 50px) scale(1.2); opacity: 0; }
        }

        /* Background Glow Blobs */
        .background-glow {
            position: absolute; border-radius: 50%; filter: blur(100px); z-index: 0; opacity: 0.3;
            animation: pulseGlow 8s infinite alternate ease-in-out;
        }
        .glow-1 { top: -20%; right: -20%; width: 600px; height: 600px; background: var(--gradient-1); }
        .glow-2 { bottom: -20%; left: -20%; width: 500px; height: 500px; background: var(--gradient-2); animation-delay: 2s; }

        @keyframes pulseGlow {
            0% { transform: scale(0.95); opacity: 0.25; }
            100% { transform: scale(1.05); opacity: 0.4; }
        }

        /* === PREMIUM GLASS CARD === */
        .auth-card {
            background: var(--glass-bg);
            backdrop-filter: blur(30px) saturate(180%); 
            -webkit-backdrop-filter: blur(30px) saturate(180%);
            border: 1px solid var(--glass-border);
            padding: 3rem 2.5rem;
            border-radius: 28px;
            width: 100%; max-width: 480px;
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
            animation: cardEntrance 1s cubic-bezier(0.075, 0.82, 0.165, 1) forwards;
            transform: translateY(50px) scale(0.95); opacity: 0;
        }

        .auth-card::before {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 5px;
            background: linear-gradient(90deg, var(--gradient-1), var(--gradient-2));
            border-radius: 28px 28px 0 0;
        }

        @keyframes cardEntrance {
            to { transform: translateY(0) scale(1); opacity: 1; }
        }

        .auth-header { text-align: center; margin-bottom: 2rem; }
        .auth-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 2.3rem; font-weight: 800;
            background: linear-gradient(135deg, var(--kd-text) 0%, var(--kd-muted) 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            margin: 0; letter-spacing: -1px;
        }
        
        .auth-subtitle {
            color: var(--kd-muted); margin-top: 0.6rem; font-size: 0.95rem;
        }

        /* === INPUT STYLES === */
        .form-group { margin-bottom: 1.2rem; position: relative; }
        .form-label {
            display: block; margin-bottom: 0.5rem;
            font-weight: 600; font-size: 0.85rem;
            color: var(--kd-text); letter-spacing: 0.5px; text-transform: uppercase;
        }

        .input-wrapper { position: relative; }
        
        .input-icon {
            position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
            color: var(--kd-muted); transition: color 0.3s ease;
            pointer-events: none;
        }

        .form-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem; /* Space for icon */
            border-radius: 12px;
            border: 1px solid var(--glass-border);
            background: var(--input-bg);
            color: var(--kd-text);
            font-family: 'Poppins', sans-serif; font-size: 1rem;
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--kd-warm-gold);
            background: var(--kd-bg-surface);
            box-shadow: 0 0 0 4px rgba(255, 193, 7, 0.2); /* Gold Glow */
            transform: translateY(-2px);
        }
        html.light-mode .form-input:focus {
            box-shadow: 0 0 0 4px rgba(255, 143, 0, 0.15);
        }
        .form-input:focus + .input-icon { color: var(--kd-warm-gold); }

        /* === ROLE SELECTION STYLES (NEW) === */
        .role-group {
            display: flex; gap: 1rem; margin-bottom: 1rem;
        }
        .role-option { flex: 1; cursor: pointer; position: relative; }
        .role-option input { display: none; } /* Hide default radio */
        
        .role-card {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            padding: 1rem; border-radius: 14px;
            border: 1px solid var(--glass-border);
            background: var(--input-bg);
            transition: all 0.3s ease;
            height: 100px;
        }
        
        .role-icon { font-size: 1.8rem; margin-bottom: 0.5rem; transition: transform 0.3s ease; }
        .role-text { font-weight: 600; color: var(--kd-muted); font-size: 0.9rem; }

        /* Active State - Customer/Buyer */
        .role-option input[value="customer"]:checked + .role-card {
            border-color: var(--kd-warm-gold);
            background: rgba(255, 193, 7, 0.1);
            box-shadow: 0 0 0 2px rgba(255, 193, 7, 0.2);
        }
        .role-option input[value="customer"]:checked + .role-card .role-text { color: var(--kd-warm-gold); }
        
        /* Active State - Farmer */
        .role-option input[value="farmer"]:checked + .role-card {
            border-color: var(--kd-earthy-green);
            background: rgba(76, 175, 80, 0.1);
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
        }
        .role-option input[value="farmer"]:checked + .role-card .role-text { color: var(--kd-earthy-green); }
        
        .role-option:hover .role-card { background: var(--kd-bg-surface); transform: translateY(-2px); }
        .role-option input:checked + .role-card .role-icon { transform: scale(1.2); }


        /* === BUTTON === */
        .auth-btn {
            width: 100%; padding: 1.1rem;
            border-radius: 14px; border: none;
            background: linear-gradient(135deg, var(--gradient-1) 0%, #FFA000 100%);
            color: #12181b; /* Dark text on gold button */
            font-weight: 700; font-size: 1.05rem;
            cursor: pointer; position: relative; overflow: hidden;
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            font-family: 'Montserrat', sans-serif;
            margin-top: 1rem; letter-spacing: 0.5px;
            box-shadow: 0 10px 20px -8px rgba(255, 193, 7, 0.4);
        }
        
        html.light-mode .auth-btn {
             color: #fff; 
             background: linear-gradient(135deg, var(--gradient-1) 0%, #FF6F00 100%);
             box-shadow: 0 10px 20px -8px rgba(255, 143, 0, 0.3);
        }

        .auth-btn:hover {
            transform: translateY(-3px) scale(1.01);
            box-shadow: 0 15px 30px -10px rgba(255, 193, 7, 0.6);
        }

        /* === FOOTER === */
        .auth-footer {
            text-align: center; margin-top: 2rem;
            color: var(--kd-muted); font-size: 0.9rem;
            border-top: 1px solid var(--glass-border);
            padding-top: 1.5rem;
        }
        .auth-footer a {
            color: var(--kd-earthy-green); text-decoration: none;
            font-weight: 600; transition: color 0.3s;
        }
        .auth-footer a:hover { color: var(--kd-warm-gold); text-decoration: underline; }

        .error-msg {
            background: rgba(239, 83, 80, 0.15);
            color: var(--kd-danger);
            padding: 1rem; border-radius: 10px;
            margin-bottom: 1.5rem; text-align: center;
            border-left: 5px solid var(--kd-danger);
            font-size: 0.95rem; font-weight: 500;
            animation: shake 0.6s ease-in-out;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-8px); }
            40%, 80% { transform: translateX(8px); }
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="auth-container">
        <div class="particles-background">
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
        </div>
        <div class="background-glow glow-1"></div>
        <div class="background-glow glow-2"></div>

        <div class="auth-card">
            <div class="auth-header">
                <h1 class="auth-title"><?php echo $lang['register'] ?? 'Create Account'; ?></h1>
                <p class="auth-subtitle"><?php echo $lang['register_subtitle'] ?? 'Join the KisaanX community today'; ?></p>
            </div>

            <?php if ($error): ?>
                <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label"><?php echo $lang['form_name'] ?? 'Full Name'; ?></label>
                    <div class="input-wrapper">
                        <input type="text" name="name" class="form-input" placeholder="John Doe" required>
                        <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label"><?php echo $lang['form_email'] ?? 'Email Address'; ?></label>
                    <div class="input-wrapper">
                        <input type="email" name="email" class="form-input" placeholder="name@example.com" required>
                        <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label"><?php echo $lang['form_password'] ?? 'Password'; ?></label>
                    <div class="input-wrapper">
                        <input type="password" name="password" class="form-input" placeholder="••••••••" required>
                        <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label"><?php echo $lang['register_role_label'] ?? 'I am a...'; ?></label>
                    <div class="role-group">
                        <label class="role-option">
                            <input type="radio" name="role" value="customer" checked>
                            <div class="role-card">
                                <span class="role-icon">🛒</span>
                                <span class="role-text"><?php echo $lang['register_role_buyer'] ?? 'Buyer'; ?></span>
                            </div>
                        </label>
                        <label class="role-option">
                            <input type="radio" name="role" value="farmer">
                            <div class="role-card">
                                <span class="role-icon">🚜</span>
                                <span class="role-text"><?php echo $lang['register_role_farmer'] ?? 'Farmer'; ?></span>
                            </div>
                        </label>
                    </div>
                </div>

                <button type="submit" class="auth-btn"><?php echo $lang['register_button'] ?? 'Register'; ?></button>
            </form>

            <div class="auth-footer">
                <?php echo $lang['has_account'] ?? "Already have an account?"; ?> 
                <a href="login.php"><?php echo $lang['login_link'] ?? 'Login here'; ?></a>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

</body>
</html>