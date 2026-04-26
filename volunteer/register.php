 <?php
include("../config/db.php");

$error   = "";
$success = "";

if (isset($_POST['register'])) {
    $full_name      = trim($_POST['full_name']      ?? "");
    $email          = trim($_POST['email']          ?? "");
    $contact_number = trim($_POST['contact_number'] ?? "");
    $availability   = $_POST['availability']        ?? "";
    $location       = trim($_POST['location']       ?? "");
    $transport      = $_POST['transport']           ?? "";
    $password       = trim($_POST['password']       ?? "");
    $skills         = (isset($_POST['skills']) && is_array($_POST['skills']))
                      ? implode(", ", $_POST['skills']) : "";

    if (!$full_name || !$email || !$contact_number || !$availability ||
        !$location  || !$transport || !$password || !$skills) {
        $error = "All fields are required. Please complete the form.";
    } else {
        // Check email exists
        $chk = mysqli_prepare($conn, "SELECT volunteer_id FROM volunteer WHERE email = ?");
        mysqli_stmt_bind_param($chk, "s", $email);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);

        if (mysqli_stmt_num_rows($chk) > 0) {
            $error = "This email is already registered. Please login instead.";
        } else {
            $ins = mysqli_prepare($conn, "
                INSERT INTO volunteer (full_name, email, contact_number, skills, availability, location, transport_mode, password)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            mysqli_stmt_bind_param($ins, "ssssssss",
                $full_name, $email, $contact_number, $skills,
                $availability, $location, $transport, $password
            );
            if (mysqli_stmt_execute($ins)) {
                $success = "You're registered! Redirecting to login...";
                header("refresh:2;url=login.php?registered=success");
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Volunteer Registration | SkillAid</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<style>
:root {
    --navy:  #0d2238;
    --blue:  #0d6efd;
    --red:   #dc3545;
    --gold:  #f0a500;
    --green: #22c55e;
    --bg:    #f0f4f8;
    --muted: #8fa3ba;
}
* { box-sizing: border-box; }
body { font-family: 'DM Sans', sans-serif; background: var(--bg); min-height: 100vh; }

/* NAVBAR */
.navbar { background: var(--navy); padding: 12px 0; }
.navbar-brand { font-family: 'Poppins', sans-serif; font-size: 1.5rem; font-weight: 700; color: #fff !important; display: flex; align-items: center; gap: 10px; text-decoration: none; }
.navbar-brand img { height: 42px; }
.nav-link { color: #e2e8f0 !important; font-weight: 500; font-size: 0.9rem; }
.nav-link:hover { color: #fff !important; }
.nav-divider { color: #64748b; margin: 0 8px; }

/* PAGE */
.page-wrap { padding: 100px 16px 60px; }

/* HERO STRIP */
.reg-hero {
    background: linear-gradient(135deg, var(--navy) 0%, #1a3a55 100%);
    border-radius: 20px;
    padding: 36px 44px;
    margin-bottom: 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 20px;
}
.reg-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: rgba(34,197,94,0.18);
    border: 1px solid rgba(34,197,94,0.3);
    color: #86efac;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 10px;
}
.reg-hero-badge .dot { width:7px; height:7px; border-radius:50%; background:#86efac; animation:blink 2s infinite; }
@keyframes blink { 0%,100%{opacity:1;} 50%{opacity:0.3;} }
.reg-hero h2 {
    font-family: 'Poppins', sans-serif;
    font-weight: 700; font-size: 1.7rem;
    color: #fff; margin: 0 0 6px;
    letter-spacing: -0.3px;
}
.reg-hero p { color: rgba(255,255,255,0.55); font-size: 0.92rem; margin: 0; }
.hero-steps { display: flex; gap: 20px; flex-wrap: wrap; }
.hero-step {
    display: flex; align-items: center; gap: 8px;
    background: rgba(255,255,255,0.07);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 10px; padding: 10px 16px;
    font-size: 0.82rem; color: rgba(255,255,255,0.65);
    white-space: nowrap;
}
.hero-step i { color: #60a5fa; }

/* FORM CARD */
.form-card {
    background: #fff;
    border-radius: 20px;
    padding: 36px 40px;
    box-shadow: 0 4px 24px rgba(13,34,56,0.09);
    border: 1px solid #e2e8f0;
    margin-bottom: 24px;
}

/* SECTION LABELS */
.form-section-label {
    font-family: 'Poppins', sans-serif;
    font-weight: 600;
    font-size: 0.92rem;
    color: var(--navy);
    display: flex;
    align-items: center;
    gap: 8px;
    padding-bottom: 12px;
    border-bottom: 1.5px solid #eef2f7;
    margin-bottom: 20px;
}
.section-num {
    width: 26px; height: 26px;
    border-radius: 50%;
    background: var(--navy);
    color: #fff;
    font-size: 0.72rem;
    font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}

/* FIELD */
.field-label { font-size: 0.8rem; font-weight: 600; color: #4a5568; margin-bottom: 6px; display: block; }
.req { color: var(--red); }
.input-wrap { position: relative; }
.input-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #a0aec0; font-size: 0.9rem; pointer-events: none; }

.field-input, .field-select {
    width: 100%;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 11px 14px 11px 40px;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.92rem;
    color: #1a2a3a;
    background: #f8fafc;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
}
.field-input:focus, .field-select:focus {
    border-color: var(--blue);
    background: #fff;
    box-shadow: 0 0 0 3px rgba(13,110,253,0.09);
}
.field-input::placeholder { color: #c0ccd8; }
.field-select { appearance: none; }

/* SKILL CARDS */
.skill-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
@media(max-width:576px) { .skill-grid { grid-template-columns: 1fr; } }

.skill-card {
    border: 2px solid #e8eef5;
    border-radius: 12px;
    padding: 14px 16px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 12px;
    position: relative;
    user-select: none;
}
.skill-card:hover { border-color: var(--blue); background: #f0f6ff; }
.skill-card input[type="checkbox"] { position: absolute; opacity: 0; width: 0; height: 0; }
.skill-card.checked { border-color: var(--blue); background: #eef4ff; }
.skill-card.checked .skill-icon { background: var(--blue); color: #fff; }
.skill-icon {
    width: 36px; height: 36px;
    border-radius: 10px;
    background: #e7f0ff;
    color: var(--blue);
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
    transition: all 0.2s;
}
.skill-name { font-weight: 600; font-size: 0.88rem; color: var(--navy); }
.skill-desc { font-size: 0.75rem; color: var(--muted); margin-top: 1px; }
.skill-check {
    margin-left: auto;
    width: 20px; height: 20px;
    border-radius: 50%;
    border: 2px solid #e2e8f0;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    transition: all 0.2s;
}
.skill-card.checked .skill-check {
    background: var(--blue);
    border-color: var(--blue);
    color: #fff;
    font-size: 0.7rem;
}

/* ALERTS */
.alert-error   { background:#fff5f5; border:1px solid #fed7d7; border-radius:12px; padding:12px 16px; color:#c53030; font-size:0.88rem; display:flex; align-items:center; gap:8px; margin-bottom:20px; }
.alert-success { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; padding:12px 16px; color:#166534; font-size:0.88rem; display:flex; align-items:center; gap:8px; margin-bottom:20px; }

/* SUBMIT */
.btn-register {
    width: 100%;
    background: var(--navy);
    color: #fff;
    border: none;
    border-radius: 12px;
    padding: 14px;
    font-family: 'DM Sans', sans-serif;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: background 0.2s, box-shadow 0.2s, transform 0.15s;
}
.btn-register:hover { background: #1a3a55; box-shadow: 0 4px 18px rgba(13,34,56,0.25); transform: translateY(-1px); }

.login-note { text-align: center; margin-top: 16px; font-size: 0.85rem; color: var(--muted); }
.login-note a { color: var(--blue); font-weight: 600; text-decoration: none; }
.login-note a:hover { text-decoration: underline; }

/* FOOTER */
footer { background: var(--navy); color: #cbd5e1; padding: 40px 0 20px; margin-top: 20px; }
footer a { color: #cbd5e1; text-decoration: none; font-size: 0.88rem; }
footer a:hover { color: #fff; }
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
            <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
            <span class="nav-divider">|</span>
            <li class="nav-item"><a class="nav-link" href="../index.php#contact">Contact</a></li>
        </ul>
    </div>
</div>
</nav>

<div class="page-wrap">
<div class="container" style="max-width:820px;">

    <!-- Hero Strip -->
    <div class="reg-hero">
        <div>
            <div class="reg-hero-badge"><span class="dot"></span> Join the Network</div>
            <h2>Become a Volunteer</h2>
            <p>Register your skills and help save lives across Sri Lanka.</p>
        </div>
        <div class="hero-steps">
            <div class="hero-step"><i class="bi bi-person-fill"></i> Fill your details</div>
            <div class="hero-step"><i class="bi bi-stars"></i> Pick your skills</div>
            <div class="hero-step"><i class="bi bi-check-circle-fill"></i> Start helping</div>
        </div>
    </div>

    <!-- Alerts -->
    <?php if ($error): ?>
        <div class="alert-error"><i class="bi bi-exclamation-circle-fill"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert-success"><i class="bi bi-check-circle-fill"></i><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form id="volunteerForm" method="POST" novalidate>

        <!-- SECTION 1: Personal Info -->
        <div class="form-card">
            <div class="form-section-label">
                <div class="section-num">1</div>
                Personal Information
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="field-label">Full Name <span class="req">*</span></label>
                    <div class="input-wrap">
                        <i class="bi bi-person input-icon"></i>
                        <input type="text" name="full_name" class="field-input" placeholder="Your full name" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="field-label">Email Address <span class="req">*</span></label>
                    <div class="input-wrap">
                        <i class="bi bi-envelope input-icon"></i>
                        <input type="email" name="email" class="field-input" placeholder="your@email.com" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="field-label">Contact Number <span class="req">*</span></label>
                    <div class="input-wrap">
                        <i class="bi bi-telephone input-icon"></i>
                        <input type="text" name="contact_number" class="field-input" placeholder="+94 77 000 0000" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="field-label">Location / District <span class="req">*</span></label>
                    <div class="input-wrap">
                        <i class="bi bi-geo-alt input-icon"></i>
                        <input type="text" name="location" class="field-input" placeholder="e.g. Colombo, Kandy, Gampaha" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="field-label">Availability <span class="req">*</span></label>
                    <div class="input-wrap">
                        <i class="bi bi-clock input-icon"></i>
                        <select name="availability" class="field-select" required>
                            <option value="">Select availability</option>
                            <option value="Anytime">Anytime</option>
                            <option value="Weekends">Weekends Only</option>
                            <option value="Specific Hours">Specific Hours</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="field-label">Transport Mode <span class="req">*</span></label>
                    <div class="input-wrap">
                        <i class="bi bi-car-front input-icon"></i>
                        <select name="transport" class="field-select" required>
                            <option value="">Select transport</option>
                            <option value="None">None — I will walk</option>
                            <option value="Bicycle">Bicycle</option>
                            <option value="Motorbike">Motorbike</option>
                            <option value="Car">Car</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="field-label">Password <span class="req">*</span></label>
                    <div class="input-wrap">
                        <i class="bi bi-lock input-icon"></i>
                        <input type="password" name="password" id="passwordInput" class="field-input" placeholder="Create a password" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="field-label">Confirm Password <span class="req">*</span></label>
                    <div class="input-wrap">
                        <i class="bi bi-lock-fill input-icon"></i>
                        <input type="password" id="confirmPassword" class="field-input" placeholder="Re-enter password">
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 2: Skills -->
        <div class="form-card">
            <div class="form-section-label">
                <div class="section-num">2</div>
                Your Skills <span style="font-size:0.78rem;color:var(--muted);font-weight:400;">— Select all that apply</span>
            </div>

            <div class="skill-grid">

                <label class="skill-card" onclick="toggleSkill(this)">
                    <input type="checkbox" name="skills[]" value="First Aid">
                    <div class="skill-icon"><i class="bi bi-heart-pulse-fill"></i></div>
                    <div>
                        <div class="skill-name">First Aid</div>
                        <div class="skill-desc">Basic emergency medical care</div>
                    </div>
                    <div class="skill-check"></div>
                </label>

                <label class="skill-card" onclick="toggleSkill(this)">
                    <input type="checkbox" name="skills[]" value="Search and Rescue">
                    <div class="skill-icon"><i class="bi bi-search-heart"></i></div>
                    <div>
                        <div class="skill-name">Search and Rescue</div>
                        <div class="skill-desc">Locating and rescuing victims</div>
                    </div>
                    <div class="skill-check"></div>
                </label>

                <label class="skill-card" onclick="toggleSkill(this)">
                    <input type="checkbox" name="skills[]" value="Medical Assistance">
                    <div class="skill-icon"><i class="bi bi-hospital-fill"></i></div>
                    <div>
                        <div class="skill-name">Medical Assistance</div>
                        <div class="skill-desc">Advanced medical support</div>
                    </div>
                    <div class="skill-check"></div>
                </label>

                <label class="skill-card" onclick="toggleSkill(this)">
                    <input type="checkbox" name="skills[]" value="Fire Safety">
                    <div class="skill-icon"><i class="bi bi-fire"></i></div>
                    <div>
                        <div class="skill-name">Fire Safety</div>
                        <div class="skill-desc">Fire response and prevention</div>
                    </div>
                    <div class="skill-check"></div>
                </label>

            </div>
        </div>

        <!-- SUBMIT -->
        <button type="submit" name="register" class="btn-register">
            <i class="bi bi-person-check-fill"></i>
            Register as Volunteer
        </button>

        <div class="login-note">
            Already registered?
            <a href="login.php">Sign in to your account</a>
        </div>

    </form>
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
            <a href="login.php" class="d-block mb-1">Volunteer Login</a>
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
<script>
// Toggle skill card checked state
function toggleSkill(label) {
    const checkbox = label.querySelector('input[type="checkbox"]');
    const checkIcon = label.querySelector('.skill-check');
    checkbox.checked = !checkbox.checked;
    if (checkbox.checked) {
        label.classList.add('checked');
        checkIcon.innerHTML = '<i class="bi bi-check"></i>';
    } else {
        label.classList.remove('checked');
        checkIcon.innerHTML = '';
    }
}

// Form validation
document.getElementById('volunteerForm').addEventListener('submit', function(e) {
    // Required fields
    const required = this.querySelectorAll('input[required], select[required]');
    for (let f of required) {
        if (!f.value.trim()) {
            alert('Please fill in all required fields.');
            f.focus();
            e.preventDefault();
            return;
        }
    }

    // At least one skill
    const skills = this.querySelectorAll('input[name="skills[]"]:checked');
    if (skills.length === 0) {
        alert('Please select at least one skill.');
        e.preventDefault();
        return;
    }

    // Password match
    const pw  = document.getElementById('passwordInput').value;
    const cpw = document.getElementById('confirmPassword').value;
    if (pw !== cpw) {
        alert('Passwords do not match. Please check and try again.');
        document.getElementById('confirmPassword').focus();
        e.preventDefault();
        return;
    }
});
</script>
</body>
</html>