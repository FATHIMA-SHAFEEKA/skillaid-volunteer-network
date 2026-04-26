 <?php include("config/db.php"); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SkillAid | Smart Emergency Volunteer Network</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<style>
/* ── ROOT ── */
:root {
    --navy:  #0a1628;
    --navy2: #0d2238;
    --blue:  #0d6efd;
    --red:   #e02030;
    --gold:  #f0a500;
    --white: #ffffff;
    --light: #f0f4f8;
    --muted: #94a3b8;
    --text:  #1e293b;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

html { scroll-behavior: smooth; }

body {
    font-family: 'DM Sans', sans-serif;
    background: var(--white);
    color: var(--text);
    overflow-x: hidden;
}

/* ── NAVBAR ── */
.navbar {
    background: rgba(10,22,40,0.95);
    backdrop-filter: blur(20px);
    padding: 14px 0;
    border-bottom: 1px solid rgba(255,255,255,0.06);
    transition: background 0.3s;
}
.navbar-brand {
    font-family: 'Poppins', sans-serif;
    font-size: 1.6rem;
    font-weight: 800;
    color: #fff !important;
    display: flex;
    align-items: center;
    gap: 10px;
    letter-spacing: -0.5px;
}
.navbar-brand img { height: 42px; }
.nav-link {
    color: rgba(255,255,255,0.75) !important;
    font-weight: 500;
    font-size: 0.9rem;
    letter-spacing: 0.2px;
    transition: color 0.2s;
    padding: 6px 4px !important;
}
.nav-link:hover { color: #fff !important; }
.nav-divider { color: rgba(255,255,255,0.15); margin: 0 8px; }
.btn-nav-admin {
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.15);
    color: #fff !important;
    border-radius: 8px;
    padding: 7px 18px !important;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.2s;
}
.btn-nav-admin:hover { background: rgba(255,255,255,0.16); }

/* ── HERO ── */
.hero {
    position: relative;
    min-height: 100vh;
    display: flex;
    align-items: center;
    overflow: hidden;
    background: var(--navy);
}

/* Layered background */
.hero-bg {
    position: absolute;
    inset: 0;
    background:
        url('assets/images/herobackground.png') center/cover no-repeat;
    opacity: 0.30;
}
.hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        to right,
        rgba(10,22,40,0.96) 0%,
        rgba(10,22,40,0.92) 60%,
        rgba(10,22,40,0.75) 100%
    );
}

/* Animated grid */
.hero-grid { display: none; }

/* Glow orbs */
.orb { display: none; }
.orb-1, .orb-2, .orb-3 { display: none; }

@keyframes orbFloat {
    0%,100% { transform: translate(0,0) scale(1); }
    33%      { transform: translate(30px,-20px) scale(1.05); }
    66%      { transform: translate(-20px,30px) scale(0.95); }
}

.hero-content {
    position: relative;
    z-index: 2;
    padding: 140px 0 100px; text-align: left;
}

/* Emergency badge */
.emergency-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(224,32,48,0.15);
    border: 1px solid rgba(224,32,48,0.35);
    color: #ff6b7a;
    padding: 8px 18px;
    border-radius: 50px;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    margin-bottom: 28px;
    animation: fadeSlideUp 0.6s 0.1s both;
}
.emergency-badge .dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: #ff6b7a;
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0%,100% { opacity: 1; transform: scale(1); }
    50%      { opacity: 0.5; transform: scale(1.3); }
}

.hero-title {
    font-family: 'Poppins', sans-serif;
    font-weight: 800;
    font-size: clamp(3rem, 6vw, 5.5rem);
    color: #fff;
    line-height: 0.95;
    letter-spacing: -0.5px;
    margin-bottom: 20px;
    animation: fadeSlideUp 0.6s 0.2s both;
}
.hero-title .accent { color: #4da3ff; }
.hero-title .block  { display: block; }

.hero-tagline {
    font-size: clamp(1.1rem, 2.5vw, 1.6rem);
    color: rgba(255,255,255,0.6);
    font-weight: 400;
    margin-bottom: 32px;
    max-width: 580px;
    line-height: 1.5;
    animation: fadeSlideUp 0.6s 0.3s both;
}
.hero-tagline strong { color: rgba(255,255,255,0.9); font-weight: 600; }

.hero-desc {
    font-size: 1rem;
    color: rgba(255,255,255,0.5);
    max-width: 520px;
    line-height: 1.7;
    margin-bottom: 44px;
    animation: fadeSlideUp 0.6s 0.4s both;
}

/* CTA Buttons */
.hero-btns { display: flex; gap: 16px; flex-wrap: wrap; animation: fadeSlideUp 0.6s 0.5s both; }

.btn-report-now {
    background: var(--red);
    color: #fff;
    border: none;
    border-radius: 14px;
    padding: 16px 36px;
    font-size: 1rem;
    font-weight: 700;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 0 0 rgba(224,32,48,0.5);
    animation: glowRed 2.5s ease-in-out infinite;
    transition: transform 0.2s;
}
.btn-report-now:hover { color: #fff; transform: translateY(-2px); }
@keyframes glowRed {
    0%,100% { box-shadow: 0 4px 20px rgba(224,32,48,0.4); }
    50%      { box-shadow: 0 4px 36px rgba(224,32,48,0.8); }
}

.btn-join-vol {
    background: transparent;
    color: rgba(255,255,255,0.85);
    border: 1.5px solid rgba(255,255,255,0.25);
    border-radius: 14px;
    padding: 16px 36px;
    font-size: 1rem;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    transition: all 0.2s;
}
.btn-join-vol:hover {
    background: rgba(255,255,255,0.08);
    border-color: rgba(255,255,255,0.5);
    color: #fff;
    transform: translateY(-2px);
}

/* Hero stats */
.hero-stats {
    display: flex;
    gap: 40px;
    margin-top: 60px;
    padding-top: 40px;
    border-top: 1px solid rgba(255,255,255,0.08);
    flex-wrap: wrap;
    animation: fadeSlideUp 0.6s 0.6s both;
}
.stat-item .stat-num {
    font-family: 'Poppins', sans-serif;
    font-size: 2rem;
    font-weight: 800;
    color: #fff;
    line-height: 1;
}
.stat-item .stat-num span { color: var(--gold); }
.stat-item .stat-lbl {
    font-size: 0.8rem;
    color: rgba(255,255,255,0.45);
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-top: 4px;
}

/* Hero scroll indicator */
.scroll-indicator {
    position: absolute;
    bottom: 32px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 2;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    color: rgba(255,255,255,0.3);
    font-size: 0.72rem;
    letter-spacing: 1px;
    text-transform: uppercase;
    animation: bounce 2s ease-in-out infinite;
}
.scroll-indicator i { font-size: 1.2rem; }
@keyframes bounce {
    0%,100% { transform: translateX(-50%) translateY(0); }
    50%      { transform: translateX(-50%) translateY(8px); }
}

/* ── ANIMATIONS ── */
@keyframes fadeSlideUp {
    from { opacity:0; transform:translateY(24px); }
    to   { opacity:1; transform:translateY(0); }
}
.reveal { opacity:0; transform:translateY(30px); transition:opacity 0.7s ease, transform 0.7s ease; }
.reveal.visible { opacity:1; transform:translateY(0); }

/* ── ABOUT SECTION ── */
.about-section {
    padding: 120px 0;
    background: #fff;
}
.section-eyebrow {
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--blue);
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.section-eyebrow::before {
    content: '';
    display: inline-block;
    width: 24px; height: 2px;
    background: var(--blue);
    border-radius: 2px;
}
.section-title-lg {
    font-family: 'Poppins', sans-serif;
    font-weight: 800;
    font-size: clamp(2rem, 4vw, 3rem);
    color: var(--navy);
    line-height: 1.1;
    letter-spacing: -0.3px;
    margin-bottom: 20px;
}
.about-text {
    font-size: 1.05rem;
    color: #475569;
    line-height: 1.8;
    text-align: justify;
}
.about-img-wrap {
    position: relative;
}
.about-img-wrap img {
    border-radius: 20px;
    width: 100%;
    object-fit: cover;
    box-shadow: 0 24px 60px rgba(10,22,40,0.15);
}
.about-badge {
    position: absolute;
    bottom: -24px;
    left: -24px;
    background: var(--navy2);
    color: #fff;
    border-radius: 16px;
    padding: 18px 24px;
    box-shadow: 0 12px 32px rgba(10,22,40,0.3);
}
.about-badge .big-num {
    font-family: 'Poppins', sans-serif;
    font-size: 2.2rem;
    font-weight: 800;
    color: var(--gold);
    line-height: 1;
}
.about-badge .badge-lbl { font-size: 0.78rem; color: rgba(255,255,255,0.65); margin-top: 3px; }

/* ── WHY SECTION ── */
.why-section {
    padding: 120px 0;
    background: var(--light);
    position: relative;
    overflow: hidden;
}
.why-section::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, #c5d8f5, transparent);
}

.feature-card {
    background: #fff;
    border-radius: 20px;
    padding: 36px 32px;
    height: 100%;
    border: 1.5px solid #e8eef5;
    transition: all 0.3s;
    position: relative;
    overflow: hidden;
}
.feature-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--blue), var(--navy));
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.3s;
}
.feature-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 50px rgba(10,22,40,0.12);
    border-color: #c5d8f5;
}
.feature-card:hover::before { transform: scaleX(1); }

.feature-icon {
    width: 56px; height: 56px;
    border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 20px;
}
.icon-blue   { background: #e7f0ff; color: var(--blue); }
.icon-red    { background: #fdecea; color: var(--red); }
.icon-gold   { background: #fff8e1; color: var(--gold); }
.icon-green  { background: #e6f9f0; color: #22c55e; }
.icon-navy   { background: #e8f0fb; color: var(--navy); }
.icon-purple { background: #f3e8ff; color: #7c3aed; }

.feature-card h4 {
    font-family: 'Poppins', sans-serif;
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--navy);
    margin-bottom: 10px;
}
.feature-card p {
    font-size: 0.92rem;
    color: #64748b;
    line-height: 1.7;
    margin: 0;
}

/* ── HOW IT WORKS ── */
.how-section {
    padding: 120px 0;
    background: var(--navy);
    position: relative;
    overflow: hidden;
}
.how-section::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(13,110,253,0.05) 1px, transparent 1px),
        linear-gradient(90deg, rgba(13,110,253,0.05) 1px, transparent 1px);
    background-size: 50px 50px;
}
.step-card {
    position: relative;
    z-index: 1;
    padding: 32px;
    border-radius: 20px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    height: 100%;
    transition: all 0.3s;
}
.step-card:hover {
    background: rgba(255,255,255,0.08);
    border-color: rgba(13,110,253,0.4);
    transform: translateY(-5px);
}
.step-num {
    font-family: 'Poppins', sans-serif;
    font-size: 3.5rem;
    font-weight: 800;
    color: rgba(255,255,255,0.06);
    line-height: 1;
    margin-bottom: 16px;
}
.step-icon {
    width: 48px; height: 48px;
    border-radius: 14px;
    background: rgba(13,110,253,0.2);
    border: 1px solid rgba(13,110,253,0.3);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem;
    color: #60a5fa;
    margin-bottom: 16px;
}
.step-card h5 {
    font-family: 'Poppins', sans-serif;
    font-size: 1.1rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 10px;
}
.step-card p { font-size: 0.88rem; color: rgba(255,255,255,0.5); line-height: 1.7; margin: 0; }

/* ── CTA SECTION ── */
.cta-section {
    padding: 100px 0;
    background: linear-gradient(135deg, #0a1628 0%, #0d2238 60%, #102d48 100%);
    position: relative;
    overflow: hidden;
}
.cta-section::before { display: none; }
.cta-title {
    font-family: 'Poppins', sans-serif;
    font-size: clamp(2rem, 5vw, 3.5rem);
    font-weight: 800;
    color: #fff;
    line-height: 1.1;
    letter-spacing: -1px;
    margin-bottom: 16px;
}
.cta-desc { font-size: 1.05rem; color: rgba(255,255,255,0.55); max-width: 500px; line-height: 1.7; }
.btn-cta-primary {
    background: var(--blue);
    color: #fff;
    border: none;
    border-radius: 14px;
    padding: 16px 40px;
    font-size: 1rem;
    font-weight: 700;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 4px 24px rgba(13,110,253,0.45);
    transition: all 0.2s;
    animation: glowBlue 2.5s ease-in-out infinite;
}
.btn-cta-primary:hover { background: #0b5ed7; color: #fff; transform: translateY(-2px); }
@keyframes glowBlue {
    0%,100% { box-shadow: 0 4px 20px rgba(13,110,253,0.4); }
    50%      { box-shadow: 0 4px 36px rgba(13,110,253,0.75); }
}

/* ── FOOTER ── */
footer {
    background: #070f1a;
    color: #64748b;
    padding: 70px 0 32px;
}
.footer-brand {
    font-family: 'Poppins', sans-serif;
    font-size: 1.6rem;
    font-weight: 800;
    color: #fff;
    letter-spacing: -0.5px;
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
}
.footer-brand img { height: 36px; }
.footer-desc { font-size: 0.88rem; line-height: 1.7; color: #475569; max-width: 260px; }
.footer-hotline {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(224,32,48,0.12);
    border: 1px solid rgba(224,32,48,0.25);
    color: #ff6b7a;
    padding: 8px 16px;
    border-radius: 10px;
    font-size: 0.82rem;
    font-weight: 600;
    margin-top: 16px;
}
.footer-heading {
    font-family: 'Poppins', sans-serif;
    font-size: 0.8rem;
    font-weight: 700;
    color: rgba(255,255,255,0.4);
    text-transform: uppercase;
    letter-spacing: 1.5px;
    margin-bottom: 16px;
}
footer a { color: #475569; text-decoration: none; font-size: 0.88rem; transition: color 0.2s; display: block; margin-bottom: 8px; }
footer a:hover { color: #94a3b8; }
.footer-divider { border-color: rgba(255,255,255,0.06); margin: 40px 0 24px; }
.footer-copy { font-size: 0.8rem; color: #334155; }

/* ── RESPONSIVE ── */
@media(max-width:768px) {
    .hero-title { letter-spacing: -1px; }
    .hero-stats { gap: 24px; }
    .about-badge { display: none; }
    .hero-btns { flex-direction: column; }
    .btn-report-now, .btn-join-vol { text-align: center; justify-content: center; }
}
</style>
</head>
<body>

<!-- ── NAVBAR ── -->
<nav class="navbar navbar-expand-lg fixed-top">
<div class="container-fluid px-4 px-lg-5">
    <a class="navbar-brand" href="#">
        <img src="assets/images/logo.png" alt="SkillAid">
        SkillAid
    </a>
    <button class="navbar-toggler border-0" data-bs-toggle="collapse" data-bs-target="#navMenu">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navMenu">
        <ul class="navbar-nav align-items-center gap-1">
            <li class="nav-item"><a class="nav-link" href="#">Home</a></li>
            <span class="nav-divider">|</span>
            <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
            <span class="nav-divider">|</span>
            <li class="nav-item"><a class="nav-link" href="#why">Why SkillAid</a></li>
            <span class="nav-divider">|</span>
            <li class="nav-item"><a class="nav-link" href="#how">How It Works</a></li>
            <span class="nav-divider">|</span>
            <li class="nav-item"><a class="nav-link" href="volunteer/register.php">Volunteer</a></li>
            <span class="nav-divider">|</span>
            <li class="nav-item"><a class="nav-link" href="reporter/report_incident.php">Report Emergency</a></li>
            <span class="nav-divider">|</span>
            <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
            <span class="nav-divider">|</span>
            <li class="nav-item ms-2"><a class="btn-nav-admin nav-link" href="admin/login.php"><i class="bi bi-shield-lock me-1"></i>Admin</a></li>
        </ul>
    </div>
</div>
</nav>

<!-- ── HERO ── -->
<section class="hero" id="home">
    <div class="hero-bg"></div>
    <div class="hero-overlay"></div>
    <div class="hero-grid"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <div class="container hero-content">
        <div class="row align-items-center">
            <div class="col-lg-8 col-xl-7">

                <div class="emergency-badge">
                    <span class="dot"></span>
                    Sri Lanka's Volunteer Response Network
                </div>

                <h1 class="hero-title">
                    SkillAid
                </h1>
                <h2 style="font-family:'Poppins',sans-serif; font-weight:600; font-size:clamp(1.3rem,3vw,2rem); color:rgba(255,255,255,0.80); margin-bottom:20px; letter-spacing:0;">
                    Smart Emergency Volunteer Network
                </h2>

                <p class="hero-tagline">
                    When disaster strikes, <strong>skilled volunteers</strong> are
                    the first line of hope — we make sure they reach you in time.
                </p>

                <p class="hero-desc">
                    SkillAid brings together compassionate, trained volunteers and people
                    in need during emergencies across Sri Lanka. Report an incident,
                    or join our volunteer community and help save lives today.
                </p>

                <div class="hero-btns">
                    <a href="reporter/report_incident.php" class="btn-report-now">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        Report an Incident
                    </a>
                    <a href="volunteer/register.php" class="btn-join-vol">
                        <i class="bi bi-person-plus-fill"></i>
                        Join as Volunteer
                    </a>
                </div>

                <div class="hero-stats">
                    <div class="stat-item">
                        <div class="stat-num">24<span>/7</span></div>
                        <div class="stat-lbl">Emergency Response</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-num">50<span>+</span></div>
                        <div class="stat-lbl">Skilled Volunteers</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-num">9<span>+</span></div>
                        <div class="stat-lbl">Districts Covered</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-num">4</div>
                        <div class="stat-lbl">Skill Categories</div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="scroll-indicator">
        <span>Scroll</span>
        <i class="bi bi-chevron-down"></i>
    </div>
</section>

<!-- ── ABOUT ── -->
<section class="about-section" id="about">
<div class="container">
    <div class="row align-items-center g-5">

        <div class="col-lg-6 reveal">
            <div class="section-eyebrow">Who We Are</div>
            <h2 class="section-title-lg">Volunteers at the Heart<br>of Every Emergency</h2>
            <p class="about-text mb-4">
                In an emergency, every minute matters. SkillAid was born from a simple truth — Sri Lanka has thousands of willing, skilled volunteers, but no organised way to reach them when it counts most. We change that.
            </p>
            <p class="about-text">
                Whether you are a trained first aider, a search and rescue expert, a medical professional, or a firefighter — SkillAid connects your skills directly to emergencies in your area. Communities report incidents, volunteers respond, and lives are saved. Simple, fast, human.
            </p>
            <div class="d-flex gap-3 mt-4 flex-wrap">
                <a href="volunteer/register.php"
                   style="background:var(--navy);color:#fff;border-radius:12px;padding:12px 28px;font-weight:600;font-size:0.95rem;text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:0.2s;"
                   onmouseover="this.style.background='#1a3a55'" onmouseout="this.style.background='var(--navy)'">
                    <i class="bi bi-people-fill"></i> Register as Volunteer
                </a>
                <a href="reporter/report_incident.php"
                   style="background:transparent;color:var(--red);border:1.5px solid var(--red);border-radius:12px;padding:12px 28px;font-weight:600;font-size:0.95rem;text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:0.2s;"
                   onmouseover="this.style.background='var(--red)';this.style.color='#fff'" onmouseout="this.style.background='transparent';this.style.color='var(--red)'">
                    <i class="bi bi-megaphone-fill"></i> Report Incident
                </a>
            </div>
        </div>

        <div class="col-lg-6 reveal" style="transition-delay:0.15s;">
            <div class="about-img-wrap">
                <img src="assets/images/about.jpg" alt="Emergency Response" style="min-height:380px;">
                <div class="about-badge">
                    <div class="big-num">Sri Lanka</div>
                    <div class="badge-lbl">Community-Driven Response</div>
                </div>
            </div>
        </div>

    </div>
</div>
</section>

<!-- ── WHY SKILLAID ── -->
<section class="why-section" id="why">
<div class="container">
    <div class="text-center mb-5 reveal">
        <div class="section-eyebrow justify-content-center">Built Around Volunteers</div>
        <h2 class="section-title-lg" style="color:var(--navy);">Why Volunteers &amp; Communities<br>Trust SkillAid</h2>
    </div>

    <div class="row g-4">
        <div class="col-md-6 col-lg-4 reveal">
            <div class="feature-card">
                <div class="feature-icon icon-blue"><i class="bi bi-stars"></i></div>
                <h4>Verified Skill Matching</h4>
                <p>Volunteers are deployed based on their verified expertise — First Aid, Search & Rescue, Medical Assistance, Fire Safety — ensuring the right person reaches the right emergency.</p>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 reveal" style="transition-delay:0.1s;">
            <div class="feature-card">
                <div class="feature-icon icon-red"><i class="bi bi-geo-alt-fill"></i></div>
                <h4>Closest Volunteer First</h4>
                <p>A volunteer from your district responds faster than one from across the island. SkillAid always shows the nearest available volunteers first, so help arrives sooner.</p>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 reveal" style="transition-delay:0.2s;">
            <div class="feature-card">
                <div class="feature-icon icon-gold"><i class="bi bi-lightning-fill"></i></div>
                <h4>Report in Seconds</h4>
                <p>Anyone can report an emergency — just fill in the details and share your GPS location with one tap. Coordinators receive it instantly and can dispatch volunteers immediately.</p>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 reveal" style="transition-delay:0.3s;">
            <div class="feature-card">
                <div class="feature-icon icon-green"><i class="bi bi-shield-check-fill"></i></div>
                <h4>No Confusion, No Overlap</h4>
                <p>Once a volunteer accepts a task, it is locked for them. No two volunteers are sent to the same emergency by mistake — coordination stays clean and clear.</p>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 reveal" style="transition-delay:0.4s;">
            <div class="feature-card">
                <div class="feature-icon icon-navy"><i class="bi bi-bar-chart-fill"></i></div>
                <h4>Every Response Tracked</h4>
                <p>Coordinators can see which incidents are active, which volunteers responded, and how quickly tasks were completed — so the system keeps improving over time.</p>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 reveal" style="transition-delay:0.5s;">
            <div class="feature-card">
                <div class="feature-icon icon-purple"><i class="bi bi-map-fill"></i></div>
                <h4>Know Exactly Where to Go</h4>
                <p>When someone reports an emergency, their GPS location is saved and shared with the assigned volunteer — so there is no guessing, no wrong turns, no lost time.</p>
            </div>
        </div>
    </div>
</div>
</section>

<!-- ── HOW IT WORKS ── -->
<section class="how-section" id="how">
<div class="container" style="position:relative;z-index:1;">
    <div class="text-center mb-5 reveal">
        <div class="section-eyebrow justify-content-center" style="color:rgba(255,255,255,0.4);">
            <span style="background:rgba(255,255,255,0.08);padding:2px 10px;border-radius:20px;">The Process</span>
        </div>
        <h2 class="section-title-lg" style="color:#fff;">How SkillAid Works</h2>
        <p style="color:rgba(255,255,255,0.45);font-size:1rem;max-width:500px;margin:0 auto;">
            Four simple steps. Real lives saved.
        </p>
    </div>

    <div class="row g-4">
        <div class="col-md-6 col-lg-3 reveal">
            <div class="step-card">
                <div class="step-num">01</div>
                <div class="step-icon"><i class="bi bi-megaphone-fill"></i></div>
                <h5>Someone Calls for Help</h5>
                <p>A citizen, family member or authority reports the emergency — what happened, where it is, and their GPS location.</p>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 reveal" style="transition-delay:0.1s;">
            <div class="step-card">
                <div class="step-num">02</div>
                <div class="step-icon"><i class="bi bi-person-check-fill"></i></div>
                <h5>Right Volunteer Found</h5>
                <p>The coordinator sees the incident and the system highlights volunteers with the right skill from the nearest area automatically.</p>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 reveal" style="transition-delay:0.2s;">
            <div class="step-card">
                <div class="step-num">03</div>
                <div class="step-icon"><i class="bi bi-check-circle-fill"></i></div>
                <h5>Volunteer Responds</h5>
                <p>The volunteer sees the task on their dashboard, checks the map for the exact location, and accepts with one click.</p>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 reveal" style="transition-delay:0.3s;">
            <div class="step-card">
                <div class="step-num">04</div>
                <div class="step-icon"><i class="bi bi-flag-fill"></i></div>
                <h5>Help Arrives</h5>
                <p>The volunteer reaches the scene and marks the task done. The incident is resolved, logged, and the community is safe.</p>
            </div>
        </div>
    </div>
</div>
</section>

<!-- ── CTA ── -->
<section class="cta-section">
<div class="container" style="position:relative;z-index:1;">
    <div class="row align-items-center g-5">
        <div class="col-lg-7 reveal">
            <div class="section-eyebrow" style="color:rgba(255,255,255,0.3);">
                <span style="background:rgba(255,255,255,0.06);padding:2px 10px;border-radius:20px;">Join Today</span>
            </div>
            <h2 class="cta-title">Your Skills Can<br>Save a Life</h2>
            <p class="cta-desc">
                Somewhere in Sri Lanka right now, someone needs help. A trained volunteer like you could be the difference. Register today — it only takes a few minutes.
            </p>
        </div>
        <div class="col-lg-5 reveal" style="transition-delay:0.15s;">
            <div style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:24px;padding:36px;">
                <h5 style="font-family:'Poppins',sans-serif;font-weight:700;color:#fff;margin-bottom:8px;">Join Our Volunteer Community</h5>
                <p style="font-size:0.88rem;color:rgba(255,255,255,0.45);margin-bottom:24px;">Share your skills and availability. Be ready when your community needs you.</p>
                <a href="volunteer/register.php" class="btn-cta-primary d-block text-center">
                    <i class="bi bi-person-plus-fill"></i>
                    Register as Volunteer
                </a>
                <div style="text-align:center;margin:16px 0;font-size:0.8rem;color:rgba(255,255,255,0.2);">— or —</div>
                <a href="reporter/report_incident.php"
                   style="display:block;text-align:center;background:rgba(224,32,48,0.15);border:1px solid rgba(224,32,48,0.3);color:#ff6b7a;border-radius:14px;padding:14px;font-weight:600;font-size:0.95rem;text-decoration:none;transition:0.2s;"
                   onmouseover="this.style.background='rgba(224,32,48,0.25)'" onmouseout="this.style.background='rgba(224,32,48,0.15)'">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Report an Emergency
                </a>
                <div style="margin-top:20px;padding-top:20px;border-top:1px solid rgba(255,255,255,0.08);display:flex;align-items:center;gap:8px;">
                    <i class="bi bi-telephone-fill" style="color:#ff6b7a;"></i>
                    <span style="font-size:0.82rem;color:rgba(255,255,255,0.4);">Emergency Hotline:</span>
                    <span style="font-size:0.9rem;font-weight:700;color:#fff;">199</span>
                </div>
            </div>
        </div>
    </div>
</div>
</section>

<!-- ── FOOTER ── -->
<footer id="contact">
<div class="container">
    <div class="row gy-5">
        <div class="col-lg-4">
            <div class="footer-brand">
                <img src="assets/images/logo.png" alt="SkillAid">
                SkillAid
            </div>
            <p class="footer-desc">A community of caring, skilled volunteers across Sri Lanka — united by one purpose: helping people in their hardest moments.</p>
            <div class="footer-hotline">
                <i class="bi bi-telephone-fill"></i>
                Emergency Hotline: <strong>199</strong>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="footer-heading">Navigation</div>
            <a href="#">Home</a>
            <a href="#about">About Us</a>
            <a href="#why">Why SkillAid</a>
            <a href="#how">How It Works</a>
            <a href="#contact">Contact</a>
        </div>
        <div class="col-6 col-lg-2">
            <div class="footer-heading">Platform</div>
            <a href="volunteer/register.php">Register as Volunteer</a>
            <a href="volunteer/login.php">Volunteer Login</a>
            <a href="reporter/report_incident.php">Report Emergency</a>
            <a href="admin/login.php">Admin Panel</a>
        </div>
        <div class="col-lg-4">
            <div class="footer-heading">Contact Us</div>
            <div style="display:flex;flex-direction:column;gap:10px;">
                <div style="display:flex;align-items:center;gap:10px;font-size:0.88rem;">
                    <i class="bi bi-envelope-fill" style="color:#334155;width:16px;"></i>
                    <a href="mailto:skillaid@gmail.com">skillaid@gmail.com</a>
                </div>
                <div style="display:flex;align-items:center;gap:10px;font-size:0.88rem;">
                    <i class="bi bi-telephone-fill" style="color:#334155;width:16px;"></i>
                    <span style="color:#475569;">+94 76 155 8321</span>
                </div>
                <div style="display:flex;align-items:flex-start;gap:10px;font-size:0.88rem;">
                    <i class="bi bi-geo-alt-fill" style="color:#334155;width:16px;margin-top:2px;"></i>
                    <span style="color:#475569;">123 Biyagama, Colombo, Sri Lanka</span>
                </div>
                <div style="display:flex;align-items:center;gap:10px;font-size:0.88rem;">
                    <i class="bi bi-mortarboard-fill" style="color:#334155;width:16px;"></i>
                    <span style="color:#334155;">Final Year Software Engineering Project</span>
                </div>
            </div>
        </div>
    </div>

    <hr class="footer-divider">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <p class="footer-copy mb-0">© 2026 <strong style="color:#475569;">SkillAid</strong>. All Rights Reserved.</p>
        <p class="footer-copy mb-0">Made with ❤️ for the people of Sri Lanka</p>
    </div>
</div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Scroll reveal
const observer = new IntersectionObserver((entries) => {
    entries.forEach(e => {
        if (e.isIntersecting) {
            e.target.classList.add('visible');
        }
    });
}, { threshold: 0.12 });

document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

// Navbar scroll effect
window.addEventListener('scroll', () => {
    const nav = document.querySelector('.navbar');
    if (window.scrollY > 50) {
        nav.style.background = 'rgba(10,22,40,0.98)';
    } else {
        nav.style.background = 'rgba(10,22,40,0.95)';
    }
});
</script>
</body>
</html>