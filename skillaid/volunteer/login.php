 <?php
session_start();
include("../config/db.php");

$error = "";

if (isset($_POST['login'])) {
    $email    = trim($_POST['email']    ?? "");
    $password = trim($_POST['password'] ?? "");

    if ($email === "" || $password === "") {
        $error = "Please enter both email and password.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT * FROM volunteer WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result    = mysqli_stmt_get_result($stmt);
        $volunteer = mysqli_fetch_assoc($result);

        if ($volunteer) {
            if ($password === $volunteer['password']) {
                $_SESSION['volunteer_id']   = $volunteer['volunteer_id'];
                $_SESSION['volunteer_name'] = $volunteer['full_name'];
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Incorrect password. Please try again.";
            }
        } else {
            $error = "No account found with that email address.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Volunteer Login | SkillAid</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<style>
:root {
    --navy:  #0d2238;
    --blue:  #0d6efd;
    --red:   #dc3545;
    --bg:    #f0f4f8;
    --muted: #8fa3ba;
}

* { box-sizing: border-box; }

body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* NAVBAR */
.navbar { background: var(--navy); padding: 12px 0; }
.navbar-brand {
    font-family: 'Poppins', sans-serif;
    font-size: 1.5rem; font-weight: 700;
    color: #fff !important;
    display: flex; align-items: center; gap: 10px;
    text-decoration: none;
}
.navbar-brand img { height: 42px; }
.nav-link { color: #e2e8f0 !important; font-weight: 500; font-size: 0.9rem; }
.nav-link:hover { color: #fff !important; }
.nav-divider { color: #64748b; margin: 0 8px; }

/* PAGE LAYOUT */
.page-wrap {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 100px 16px 60px;
}

/* SPLIT CARD */
.login-container {
    width: 100%;
    max-width: 900px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 8px 40px rgba(13,34,56,0.14);
}

/* LEFT PANEL */
.left-panel {
    background: linear-gradient(155deg, var(--navy) 0%, #1a3a55 100%);
    padding: 52px 44px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
}
.left-panel::after {
    content: '';
    position: absolute;
    bottom: -60px; right: -60px;
    width: 220px; height: 220px;
    border-radius: 50%;
    background: rgba(13,110,253,0.12);
    border: 1px solid rgba(13,110,253,0.15);
}
.left-panel::before {
    content: '';
    position: absolute;
    top: -40px; left: -40px;
    width: 160px; height: 160px;
    border-radius: 50%;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.06);
}

.left-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: rgba(220,53,69,0.18);
    border: 1px solid rgba(220,53,69,0.3);
    color: #ff8090;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 24px;
    width: fit-content;
}
.left-badge .dot {
    width: 7px; height: 7px;
    border-radius: 50%;
    background: #ff8090;
    animation: blink 2s infinite;
}
@keyframes blink { 0%,100%{opacity:1;} 50%{opacity:0.3;} }

.left-title {
    font-family: 'Poppins', sans-serif;
    font-weight: 700;
    font-size: 1.9rem;
    color: #fff;
    line-height: 1.2;
    letter-spacing: -0.3px;
    margin-bottom: 14px;
}
.left-title span { color: #60a5fa; }

.left-desc {
    font-size: 0.9rem;
    color: rgba(255,255,255,0.55);
    line-height: 1.7;
    margin-bottom: 32px;
}

/* Feature list */
.feature-list { position: relative; z-index: 1; }
.feature-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 0;
    border-bottom: 1px solid rgba(255,255,255,0.07);
    font-size: 0.85rem;
    color: rgba(255,255,255,0.65);
}
.feature-item:last-child { border-bottom: none; }
.feature-item i {
    width: 30px; height: 30px;
    border-radius: 8px;
    background: rgba(255,255,255,0.08);
    display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem;
    color: #60a5fa;
    flex-shrink: 0;
}

/* RIGHT PANEL */
.right-panel {
    background: #fff;
    padding: 52px 44px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.login-eyebrow {
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: var(--blue);
    margin-bottom: 8px;
}
.login-title {
    font-family: 'Poppins', sans-serif;
    font-weight: 700;
    font-size: 1.75rem;
    color: var(--navy);
    margin-bottom: 4px;
    letter-spacing: -0.3px;
}
.login-sub {
    font-size: 0.88rem;
    color: var(--muted);
    margin-bottom: 8px;
}
.title-line {
    width: 36px; height: 3px;
    background: var(--blue);
    border-radius: 3px;
    margin-bottom: 28px;
}

/* SUCCESS ALERT */
.alert-registered {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 10px;
    padding: 10px 14px;
    color: #166534;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
}

/* ERROR ALERT */
.alert-error {
    background: #fff5f5;
    border: 1px solid #fed7d7;
    border-radius: 10px;
    padding: 10px 14px;
    color: #c53030;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 18px;
}

/* FIELDS */
.field-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #4a5568;
    margin-bottom: 6px;
    display: block;
}
.input-wrap { position: relative; margin-bottom: 18px; }
.input-icon {
    position: absolute; left: 14px; top: 50%;
    transform: translateY(-50%);
    color: #a0aec0; font-size: 0.95rem; pointer-events: none;
}
.field-input {
    width: 100%;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 12px 14px 12px 42px;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.92rem;
    color: #1a2a3a;
    background: #f8fafc;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
}
.field-input:focus {
    border-color: var(--blue);
    background: #fff;
    box-shadow: 0 0 0 3px rgba(13,110,253,0.1);
}
.field-input::placeholder { color: #c0ccd8; }

/* LOGIN BUTTON */
.btn-login {
    width: 100%;
    background: var(--navy);
    color: #fff;
    border: none;
    border-radius: 12px;
    padding: 13px;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: background 0.2s, box-shadow 0.2s, transform 0.15s;
    margin-top: 4px;
}
.btn-login:hover {
    background: #1a3a55;
    box-shadow: 0 4px 18px rgba(13,34,56,0.25);
    transform: translateY(-1px);
}

.divider {
    display: flex; align-items: center; gap: 10px;
    margin: 16px 0;
    font-size: 0.78rem; color: #c0ccd8;
}
.divider::before, .divider::after {
    content: ''; flex: 1;
    height: 1px; background: #e8eef5;
}

/* REGISTER LINK */
.register-link {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: #f0f6ff;
    border: 1.5px solid #c5d8f5;
    border-radius: 12px;
    padding: 12px;
    text-decoration: none;
    color: var(--blue);
    font-size: 0.88rem;
    font-weight: 600;
    transition: all 0.2s;
}
.register-link:hover {
    background: var(--blue);
    color: #fff;
    border-color: var(--blue);
}

.back-link {
    text-align: center;
    margin-top: 20px;
    font-size: 0.8rem;
    color: var(--muted);
}
.back-link a { color: var(--muted); text-decoration: none; }
.back-link a:hover { color: var(--navy); }

/* FOOTER */
footer {
    background: var(--navy);
    color: #cbd5e1;
    padding: 36px 0 20px;
}
footer a { color: #cbd5e1; text-decoration: none; font-size: 0.88rem; }
footer a:hover { color: #fff; }

/* RESPONSIVE */
@media(max-width: 768px) {
    .login-container { grid-template-columns: 1fr; }
    .left-panel { display: none; }
    .right-panel { padding: 36px 28px; }
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
<div class="container-fluid px-4">
    <a class="navbar-brand" href="../index.php">
        <img src="../assets/images/logo.png" alt="SkillAid"> SkillAid
    </a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navMenu">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navMenu">
        <ul class="navbar-nav align-items-center">
            <li class="nav-item"><a class="nav-link" href="../index.php">Home</a></li>
            <span class="nav-divider">|</span>
            <li class="nav-item"><a class="nav-link" href="../index.php#about">About</a></li>
            <span class="nav-divider">|</span>
            <li class="nav-item"><a class="nav-link" href="../index.php#why">Why SkillAid</a></li>
            <span class="nav-divider">|</span>
            <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
            <span class="nav-divider">|</span>
            <li class="nav-item"><a class="nav-link" href="../index.php#contact">Contact</a></li>
        </ul>
    </div>
</div>
</nav>

<!-- MAIN -->
<div class="page-wrap">
<div class="login-container">

    <!-- LEFT PANEL -->
    <div class="left-panel">
        <div>
            <div class="left-badge">
                <span class="dot"></span> Volunteer Portal
            </div>
            <h2 class="left-title">
                Welcome Back,<br>
                <span>Hero</span> 👋
            </h2>
            <p class="left-desc">
                Your community needs you. Log in to view your assigned
                tasks, check emergency locations, and make a difference today.
            </p>
        </div>

        <div class="feature-list">
            <div class="feature-item">
                <i class="bi bi-clipboard-check-fill"></i>
                View your assigned emergency tasks
            </div>
            <div class="feature-item">
                <i class="bi bi-geo-alt-fill"></i>
                See incident locations on the map
            </div>
            <div class="feature-item">
                <i class="bi bi-check-circle-fill"></i>
                Accept, complete or update task status
            </div>
            <div class="feature-item">
                <i class="bi bi-person-fill"></i>
                Manage your profile and availability
            </div>
        </div>
    </div>

    <!-- RIGHT PANEL -->
    <div class="right-panel">

        <div class="login-eyebrow">Volunteer Login</div>
        <h2 class="login-title">Sign In</h2>
        <p class="login-sub">Enter your credentials to access your dashboard</p>
        <div class="title-line"></div>

        <?php if (isset($_GET['registered'])): ?>
            <div class="alert-registered">
                <i class="bi bi-check-circle-fill"></i>
                Registration successful! Please log in.
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert-error">
                <i class="bi bi-exclamation-circle-fill"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <label class="field-label">Email Address</label>
            <div class="input-wrap">
                <i class="bi bi-envelope input-icon"></i>
                <input type="email" name="email" class="field-input"
                       placeholder="your@email.com" required autocomplete="email">
            </div>

            <label class="field-label">Password</label>
            <div class="input-wrap">
                <i class="bi bi-lock input-icon"></i>
                <input type="password" name="password" class="field-input"
                       placeholder="Enter your password" required autocomplete="current-password">
            </div>

            <button type="submit" name="login" class="btn-login">
                <i class="bi bi-box-arrow-in-right"></i>
                Login to Dashboard
            </button>
        </form>

        <div class="divider">or</div>

        <a href="register.php" class="register-link">
            <i class="bi bi-person-plus-fill"></i>
            Not registered yet? Become a Volunteer
        </a>

        <div class="back-link">
            <a href="../index.php">
                <i class="bi bi-arrow-left me-1"></i>Back to SkillAid Home
            </a>
        </div>

    </div>
</div>
</div>

<!-- FOOTER -->
<footer>
<div class="container">
    <div class="row gy-3">
        <div class="col-md-4">
            <h6 class="fw-bold text-white mb-2">SkillAid</h6>
            <p class="small mb-1">Smart Emergency Volunteer Network for Sri Lanka</p>
            <p class="small mb-0"><strong>Emergency Hotline:</strong> 199</p>
        </div>
        <div class="col-md-4">
            <h6 class="fw-bold text-white mb-2">Quick Links</h6>
            <a href="../index.php" class="d-block mb-1">Home</a>
            <a href="../index.php#about" class="d-block mb-1">About Us</a>
            <a href="register.php" class="d-block mb-1">Register as Volunteer</a>
            <a href="../reporter/report_incident.php" class="d-block">Report Emergency</a>
        </div>
        <div class="col-md-4">
            <h6 class="fw-bold text-white mb-2">Contact</h6>
            <p class="small mb-1"><strong>Email:</strong> <a href="mailto:skillaid@gmail.com">skillaid@gmail.com</a></p>
            <p class="small mb-1"><strong>Phone:</strong> +94 76 155 8321</p>
            <p class="small mb-0">123 Biyagama, Colombo, Sri Lanka</p>
        </div>
    </div>
    <hr style="border-color:rgba(203,213,225,0.2);" class="mt-3">
    <p class="text-center small mb-0" style="color:#475569;">© 2026 <strong style="color:#94a3b8;">SkillAid</strong>. All Rights Reserved.</p>
</div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>