 <?php
session_start();
include("../config/db.php");

$error = "";

if (isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? "");
    $password = trim($_POST['password'] ?? "");

    $stmt = mysqli_prepare($conn, "SELECT * FROM admin WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $admin = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($admin && $password === $admin['password']) {
        $_SESSION['admin_id'] = $admin['admin_id'];
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Login | SkillAid</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<style>
body {
    font-family: 'Inter', sans-serif;
    background: #f0f4f8;
    min-height: 100vh;
}

/* NAVBAR */
.navbar { background: #0d2238; padding: 12px 0; }
.navbar-brand {
    font-family: 'Inter', sans-serif;
    font-size: 1.5rem; font-weight: 600;
    color: #fff !important;
    display: flex; align-items: center; gap: 10px;
    text-decoration: none;
}
.navbar-brand img { height: 42px; }
.nav-link { color: #e2e8f0 !important; font-weight: 500; font-size: 0.9rem; }
.nav-link:hover { color: #fff !important; }

/* LAYOUT */
.page-wrap {
    min-height: calc(100vh - 68px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 16px;
    margin-top: 68px;
}

/* CARD */
.login-card {
    background: #fff;
    border-radius: 20px;
    padding: 44px 40px;
    width: 100%;
    max-width: 440px;
    box-shadow: 0 4px 24px rgba(13,34,56,0.10);
    border: 1px solid #e2e8f0;
}

/* BADGE */
.admin-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #e8f0fb;
    color: #0d2238;
    border: 1px solid #c5d8f5;
    padding: 5px 14px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    margin-bottom: 20px;
}

/* TITLE */
.login-title {
    font-family: 'Poppins', sans-serif;
    font-weight: 700;
    font-size: 1.75rem;
    color: #0d2238;
    margin-bottom: 4px;
}
.login-sub { color: #8fa3ba; font-size: 0.88rem; margin-bottom: 8px; }
.title-divider {
    width: 40px; height: 3px;
    background: #0d6efd;
    border-radius: 3px;
    margin: 12px 0 28px;
}

/* FIELDS */
.field-label {
    font-size: 0.82rem; font-weight: 600;
    color: #4a5568; margin-bottom: 6px; display: block;
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
    padding: 12px 14px 12px 40px;
    font-family: 'Inter', sans-serif;
    font-size: 0.92rem;
    color: #1a2a3a;
    background: #f8fafc;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
}
.field-input:focus {
    border-color: #0d6efd;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(13,110,253,0.1);
}
.field-input::placeholder { color: #c0ccd8; }

/* ERROR */
.error-alert {
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

/* BUTTON */
.btn-login {
    width: 100%;
    background: #0d2238;
    color: #fff;
    border: none;
    border-radius: 12px;
    padding: 13px;
    font-family: 'Inter', sans-serif;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s, box-shadow 0.2s, transform 0.15s;
    margin-top: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.btn-login:hover {
    background: #1a3a55;
    box-shadow: 0 4px 16px rgba(13,34,56,0.25);
    transform: translateY(-1px);
}
.btn-login:active { transform: translateY(0); }

/* BACK LINK */
.back-link {
    text-align: center;
    margin-top: 20px;
    font-size: 0.82rem;
    color: #8fa3ba;
}
.back-link a { color: #0d6efd; text-decoration: none; font-weight: 500; }
.back-link a:hover { text-decoration: underline; }

/* SECURITY NOTE */
.security-note {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 24px;
    padding: 10px 14px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    color: #8fa3ba;
    font-size: 0.78rem;
}
.security-note i { color: #22c55e; flex-shrink: 0; }

/* FOOTER */
footer {
    background: #0d2238;
    color: #cbd5e1;
    padding: 28px 0;
    text-align: center;
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
<div class="container-fluid px-4 d-flex align-items-center justify-content-between">
    <a class="navbar-brand" href="../index.php">
        <img src="../assets/images/logo.png" alt="SkillAid"> SkillAid
    </a>
    <div class="d-flex align-items-center gap-3">
        <a class="nav-link" href="../index.php">Home</a>
        <span style="color:#64748b;">|</span>
        <a class="nav-link" href="../volunteer/login.php">Volunteer Login</a>
    </div>
</div>
</nav>

<!-- CONTENT -->
<div class="page-wrap">
    <div class="login-card">

        <div class="admin-badge">
            <i class="bi bi-shield-lock-fill"></i> Admin Panel
        </div>

        <h2 class="login-title">Welcome Back</h2>
        <p class="login-sub">Sign in to manage incidents and volunteers</p>
        <div class="title-divider"></div>

        <?php if ($error): ?>
            <div class="error-alert">
                <i class="bi bi-exclamation-circle-fill"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <label class="field-label">Username</label>
            <div class="input-wrap">
                <i class="bi bi-person input-icon"></i>
                <input type="text" name="username" class="field-input"
                       placeholder="Enter username" required autocomplete="username">
            </div>

            <label class="field-label">Password</label>
            <div class="input-wrap">
                <i class="bi bi-lock input-icon"></i>
                <input type="password" name="password" class="field-input"
                       placeholder="Enter password" required autocomplete="current-password">
            </div>

            <button type="submit" name="login" class="btn-login">
                <i class="bi bi-box-arrow-in-right"></i>
                Login to Dashboard
            </button>

        </form>

        <div class="back-link">
            <a href="../index.php">
                <i class="bi bi-arrow-left me-1"></i>Back to SkillAid Home
            </a>
        </div>

        <div class="security-note">
            <i class="bi bi-shield-check-fill"></i>
            Secure admin area — authorised personnel only.
        </div>

    </div>
</div>

<!-- FOOTER -->
<footer>
    <p class="small mb-0">
        © 2026 <strong>SkillAid</strong>. All Rights Reserved.
        &nbsp;|&nbsp; Emergency Hotline: 199
    </p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>