 <?php
session_start();
include("../config/db.php");

// Redirect if not logged in
if (!isset($_SESSION['volunteer_id'])) {
    header("Location: login.php");
    exit();
}

$volunteer_id = $_SESSION['volunteer_id'];

// ── Fetch real volunteer data ──────────────────────────────────────────
$stmt = mysqli_prepare($conn, "SELECT * FROM volunteer WHERE volunteer_id = ?");
mysqli_stmt_bind_param($stmt, "i", $volunteer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$volunteer = mysqli_fetch_assoc($result);

if (!$volunteer) {
    header("Location: login.php");
    exit();
}

// ── Fetch task stats from task_assignment ─────────────────────────────
$stmt2 = mysqli_prepare($conn, "
    SELECT 
        COUNT(*) AS total,
        SUM(assignment_status = 'Completed') AS completed,
        SUM(assignment_status = 'Accepted')  AS accepted,
        SUM(assignment_status = 'Declined')  AS declined,
        SUM(assignment_status = 'Pending')   AS pending
    FROM task_assignment 
    WHERE volunteer_id = ?
");
mysqli_stmt_bind_param($stmt2, "i", $volunteer_id);
mysqli_stmt_execute($stmt2);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));

// ── Fetch task history (recent assignments with incident details) ──────
 $stmt3 = mysqli_prepare($conn, "
    SELECT 
        i.incident_type,
        i.location,
        ta.assignment_status,
        ta.assigned_date
    FROM task_assignment ta
    JOIN incident i ON ta.incident_id = i.incident_id
    WHERE ta.volunteer_id = ?
    ORDER BY ta.assigned_date DESC
    LIMIT 6
");
mysqli_stmt_bind_param($stmt3, "i", $volunteer_id);
mysqli_stmt_execute($stmt3);
$history_result = mysqli_stmt_get_result($stmt3);
$history = [];
while ($row = mysqli_fetch_assoc($history_result)) {
    $history[] = $row;
}

// ── Alert messages after profile update ───────────────────────────────
$msg = "";
if (isset($_GET['updated']) && $_GET['updated'] == 1) $msg = "success";
if (isset($_GET['updated']) && $_GET['updated'] == 0) $msg = "error";

// ── Avatar initials ───────────────────────────────────────────────────
$parts    = explode(' ', $volunteer['full_name']);
$initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));

// ── Joined date formatted ─────────────────────────────────────────────
$joined = date("F Y", strtotime($volunteer['created_at']));

// ── Completion rate ───────────────────────────────────────────────────
$total     = $stats['total'] ?? 0;
$completed = $stats['completed'] ?? 0;
$rate      = $total > 0 ? round(($completed / $total) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SkillAid | My Profile</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Montserrat:wght@700;800;900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<style>
:root {
    --navy:      #0d2238;
    --navy-soft: #1a3a55;
    --accent:    #dc3545;
    --blue:      #0d6efd;
    --gold:      #f0a500;
    --text-light:#cbd5e1;
    --text-muted:#8fa3ba;
    --bg-page:   #eef2f7;
}
* { box-sizing: border-box; }
body { font-family: 'Inter', sans-serif; background: var(--bg-page); color: #1a2a3a; min-height: 100vh; }

/* NAVBAR */
.navbar { background: var(--navy); padding: 12px 0; }
.navbar-brand { font-family: 'Inter', sans-serif; font-size: 1.6rem; font-weight: 500; color: #fff !important; display: flex; align-items: center; gap: 10px; }
.navbar-brand img { height: 48px; }
.nav-link { color: #e2e8f0 !important; font-weight: 500; }
.nav-link:hover { color: #fff !important; }
.nav-divider { color: #64748b; margin: 0 10px; }

/* BANNER */
.profile-banner {
    background: linear-gradient(135deg, rgba(13,34,56,0.92) 0%, rgba(13,110,253,0.18) 100%),
                url('../assets/images/herobackground.png');
    background-size: cover;
    background-position: center top;
    padding: 100px 0 0;
}
.profile-banner::after {
    content: '';
    display: block;
    height: 60px;
    background: var(--bg-page);
    clip-path: ellipse(55% 100% at 50% 100%);
    margin-top: 30px;
}

/* AVATAR */
.avatar-wrap { position: relative; display: inline-block; }
.avatar-circle {
    width: 130px; height: 130px;
    border-radius: 50%;
    border: 4px solid #fff;
    box-shadow: 0 8px 32px rgba(0,0,0,0.35);
    background: var(--navy-soft);
    display: flex; align-items: center; justify-content: center;
    font-family: 'Montserrat', sans-serif;
    font-size: 2.8rem; font-weight: 900; color: #fff;
}
.status-dot {
    position: absolute; bottom: 8px; right: 8px;
    width: 20px; height: 20px;
    border-radius: 50%; background: #22c55e;
    border: 3px solid #fff;
    box-shadow: 0 0 8px rgba(34,197,94,0.7);
}
.profile-name { font-family: 'Montserrat', sans-serif; font-weight: 800; font-size: 2.2rem; color: #fff; margin: 0; }
.profile-sub { color: #94c9f5; font-size: 1rem; margin-top: 4px; }
.badge-status {
    display: inline-block;
    background: rgba(34,197,94,0.18); color: #22c55e;
    border: 1.5px solid rgba(34,197,94,0.45);
    padding: 4px 14px; border-radius: 20px;
    font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;
}

/* STAT PILLS */
.stat-pill {
    background: rgba(255,255,255,0.10);
    border: 1px solid rgba(255,255,255,0.18);
    backdrop-filter: blur(8px);
    border-radius: 14px; padding: 14px 24px;
    text-align: center; color: #fff; min-width: 110px;
}
.stat-pill .stat-num { font-family: 'Montserrat', sans-serif; font-size: 1.9rem; font-weight: 900; line-height: 1; }
.stat-pill .stat-label { font-size: 0.75rem; color: #94c9f5; margin-top: 4px; text-transform: uppercase; letter-spacing: 0.6px; }

/* CARDS */
.profile-section {
    background: #fff; border-radius: 20px; padding: 30px 32px;
    box-shadow: 0 2px 16px rgba(13,34,56,0.07);
    margin-bottom: 24px; border: 1.5px solid #e8eef5;
    transition: box-shadow 0.2s;
}
.profile-section:hover { box-shadow: 0 6px 28px rgba(13,34,56,0.13); }
.section-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
.icon-navy  { background: #e8f0fb; color: var(--navy); }
.icon-red   { background: #fdecea; color: var(--accent); }
.icon-blue  { background: #e7f0ff; color: var(--blue); }
.icon-gold  { background: #fff8e1; color: var(--gold); }
.icon-green { background: #e6f9f0; color: #22c55e; }
.section-head { font-family: 'Poppins', sans-serif; font-weight: 600; font-size: 1.05rem; color: var(--navy); }

/* INFO */
.info-label { font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500; margin-bottom: 2px; }
.info-value { font-size: 0.97rem; font-weight: 500; color: #1a2a3a; }

/* SKILL TAG */
.skill-tag {
    display: inline-block; background: #e8f0fb; color: var(--navy);
    border: 1.5px solid #c5d8f5; padding: 6px 16px; border-radius: 20px;
    font-size: 0.85rem; font-weight: 500; margin: 4px 4px 4px 0; transition: all 0.2s;
}
.skill-tag:hover { background: var(--navy); color: #fff; border-color: var(--navy); }

/* BUTTONS */
.btn-edit-profile {
    background: var(--navy); color: #fff; border-radius: 12px;
    padding: 10px 28px; font-weight: 600; font-size: 0.95rem;
    border: none; text-decoration: none; cursor: pointer; transition: background 0.2s, box-shadow 0.2s;
}
.btn-edit-profile:hover { background: #1a3a55; box-shadow: 0 4px 18px rgba(13,34,56,0.25); color: #fff; }
.btn-danger-outline {
    background: transparent; color: var(--accent); border: 1.5px solid var(--accent);
    border-radius: 12px; padding: 10px 22px; font-weight: 600; font-size: 0.95rem;
    text-decoration: none; transition: all 0.2s;
}
.btn-danger-outline:hover { background: var(--accent); color: #fff; }

/* HISTORY */
.deploy-item { display: flex; align-items: center; gap: 16px; padding: 14px 0; border-bottom: 1px solid #eef2f7; }
.deploy-item:last-child { border-bottom: none; }
.deploy-dot { width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0; }
.deploy-title { font-size: 0.93rem; font-weight: 600; color: #1a2a3a; margin: 0; }
.deploy-meta  { font-size: 0.78rem; color: var(--text-muted); margin: 2px 0 0; }
.deploy-badge { font-size: 0.75rem; padding: 3px 10px; border-radius: 10px; font-weight: 600; white-space: nowrap; }

/* EDIT FORM TOGGLE */
.edit-form-wrap { display: none; background: #f8faff; border: 1.5px solid #dde8f8; border-radius: 16px; padding: 24px; margin-top: 20px; }
.edit-form-wrap.show { display: block; }

/* FOOTER */
footer { background: var(--navy); color: var(--text-light); padding: 40px 0 24px; margin-top: 60px; }
footer a { color: var(--text-light); text-decoration: none; }
footer a:hover { color: #fff; }

@media(max-width: 768px) {
    .profile-name { font-size: 1.5rem; }
    .stat-pill { min-width: 80px; padding: 10px 14px; }
    .stat-pill .stat-num { font-size: 1.4rem; }
    .profile-section { padding: 22px 18px; }
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
            <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
            <span class="nav-divider">|</span>
            <li class="nav-item"><a class="nav-link" style="color:#94c9f5 !important;" href="profile.php">My Profile</a></li>
            <span class="nav-divider">|</span>
            <li class="nav-item"><a class="nav-link" href="../reporter/report_incident.php">Report Emergency</a></li>
            <span class="nav-divider">|</span>
            <li class="nav-item"><a class="nav-link text-warning fw-semibold" href="logout.php">Logout</a></li>
        </ul>
    </div>
</div>
</nav>

<!-- PROFILE BANNER -->
<section class="profile-banner">
<div class="container">
    <div class="d-flex flex-column flex-md-row align-items-center align-items-md-end gap-4 pb-3">

        <div class="avatar-wrap">
            <div class="avatar-circle"><?= $initials ?></div>
            <div class="status-dot"></div>
        </div>

        <div class="text-white text-center text-md-start pb-md-2">
            <h1 class="profile-name mb-1"><?= htmlspecialchars($volunteer['full_name']) ?></h1>
            <div class="profile-sub mb-2">
                <i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($volunteer['location']) ?>, Sri Lanka
                &nbsp;·&nbsp;
                <i class="bi bi-calendar3 me-1"></i>Joined <?= $joined ?>
            </div>
            <span class="badge-status">
                <i class="bi bi-circle-fill me-1" style="font-size:0.55rem;vertical-align:middle;"></i>Active Volunteer
            </span>
        </div>

        <div class="ms-md-auto d-flex gap-3 flex-wrap justify-content-center pb-md-2">
            <div class="stat-pill">
                <div class="stat-num"><?= $stats['total'] ?? 0 ?></div>
                <div class="stat-label">Total Tasks</div>
            </div>
            <div class="stat-pill">
                <div class="stat-num"><?= $stats['completed'] ?? 0 ?></div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-pill">
                <div class="stat-num"><?= $stats['accepted'] ?? 0 ?></div>
                <div class="stat-label">Active</div>
            </div>
        </div>
    </div>
</div>
</section>

<!-- MAIN CONTENT -->
<div class="container" style="margin-top:10px; padding-bottom:40px;">

    <?php if ($msg === 'success'): ?>
        <div class="alert alert-success rounded-4 mb-4">
            <i class="bi bi-check-circle-fill me-2"></i>Profile updated successfully! Changes are saved to the database.
        </div>
    <?php elseif ($msg === 'error'): ?>
        <div class="alert alert-danger rounded-4 mb-4">
            <i class="bi bi-exclamation-circle-fill me-2"></i>Something went wrong. Please try again.
        </div>
    <?php endif; ?>

<div class="row g-4">

    <!-- LEFT COLUMN -->
    <div class="col-lg-5">

        <!-- Personal Information (live from DB) -->
        <div class="profile-section">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="section-icon icon-navy"><i class="bi bi-person-fill"></i></div>
                <span class="section-head">Personal Information</span>
            </div>
            <div class="row gy-3">
                <div class="col-12">
                    <div class="info-label">Full Name</div>
                    <div class="info-value"><i class="bi bi-person me-2 text-muted"></i><?= htmlspecialchars($volunteer['full_name']) ?></div>
                </div>
                <div class="col-sm-6">
                    <div class="info-label">Email</div>
                    <div class="info-value" style="word-break:break-word;"><i class="bi bi-envelope me-2 text-muted"></i><?= htmlspecialchars($volunteer['email']) ?></div>
                </div>
                <div class="col-sm-6">
                    <div class="info-label">Contact Number</div>
                    <div class="info-value"><i class="bi bi-telephone me-2 text-muted"></i><?= htmlspecialchars($volunteer['contact_number']) ?></div>
                </div>
                <div class="col-sm-6">
                    <div class="info-label">Location / District</div>
                    <div class="info-value"><i class="bi bi-geo-alt me-2 text-muted"></i><?= htmlspecialchars($volunteer['location']) ?></div>
                </div>
                <div class="col-sm-6">
                    <div class="info-label">Transport Mode</div>
                    <div class="info-value"><i class="bi bi-car-front me-2 text-muted"></i><?= htmlspecialchars($volunteer['transport_mode']) ?></div>
                </div>
                <div class="col-sm-6">
                    <div class="info-label">Availability</div>
                    <div class="info-value"><i class="bi bi-clock me-2 text-muted"></i><?= htmlspecialchars($volunteer['availability']) ?></div>
                </div>
                <div class="col-sm-6">
                    <div class="info-label">Member Since</div>
                    <div class="info-value"><i class="bi bi-calendar3 me-2 text-muted"></i><?= $joined ?></div>
                </div>
            </div>
        </div>

        <!-- Skills (live from DB) -->
        <div class="profile-section">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="section-icon icon-blue"><i class="bi bi-stars"></i></div>
                <span class="section-head">Skills & Expertise</span>
            </div>
            <span class="skill-tag">
                <i class="bi bi-check-circle-fill me-1" style="font-size:0.7rem;color:#0d6efd;"></i>
                <?= htmlspecialchars($volunteer['skills']) ?>
            </span>
        </div>

        <!-- Quick Actions + Inline Edit Form -->
        <div class="profile-section">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="section-icon icon-gold"><i class="bi bi-lightning-fill"></i></div>
                <span class="section-head">Quick Actions</span>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn-edit-profile" onclick="toggleEditForm()">
                    <i class="bi bi-pencil-fill me-2"></i>Edit Profile
                </button>
                <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"
                   style="border-radius:12px; padding:10px 18px; font-weight:600; text-decoration:none;">
                    <i class="bi bi-speedometer2 me-1"></i>Dashboard
                </a>
                <a href="logout.php" class="btn-danger-outline ms-auto">
                    <i class="bi bi-box-arrow-right me-1"></i>Logout
                </a>
            </div>

            <!-- Inline Edit Form — saves to DB via update_profile.php -->
            <div class="edit-form-wrap" id="editForm">
                <h6 class="fw-bold mb-3" style="color:var(--navy);">
                    <i class="bi bi-pencil-square me-2"></i>Update Your Details
                </h6>
                <form method="POST" action="update_profile.php">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Full Name</label>
                            <input type="text" name="full_name" class="form-control"
                                   value="<?= htmlspecialchars($volunteer['full_name']) ?>" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control"
                                   value="<?= htmlspecialchars($volunteer['contact_number']) ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Location / District</label>
                            <input type="text" name="location" class="form-control"
                                   value="<?= htmlspecialchars($volunteer['location']) ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Transport Mode</label>
                            <select name="transport_mode" class="form-select">
                                <?php foreach (['Car','Bicycle','Motorbike','None'] as $t): ?>
                                    <option value="<?= $t ?>" <?= $volunteer['transport_mode'] == $t ? 'selected' : '' ?>><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Skills</label>
                            <select name="skills" class="form-select">
                                <?php foreach (['First Aid','Search and Rescue','Medical Assistance','Fire Safety'] as $s): ?>
                                    <option value="<?= $s ?>" <?= $volunteer['skills'] == $s ? 'selected' : '' ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Availability</label>
                            <select name="availability" class="form-select">
                                <?php foreach (['Anytime','Weekends','Specific Hours'] as $a): ?>
                                    <option value="<?= $a ?>" <?= $volunteer['availability'] == $a ? 'selected' : '' ?>><?= $a ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 d-flex gap-2 mt-1">
                            <button type="submit" class="btn btn-primary" style="border-radius:10px; font-weight:600;">
                                <i class="bi bi-save me-1"></i>Save Changes
                            </button>
                            <button type="button" class="btn btn-outline-secondary" style="border-radius:10px;" onclick="toggleEditForm()">
                                Cancel
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN -->
    <div class="col-lg-7">

        <!-- Task History (real data from task_assignment + incident) -->
        <div class="profile-section">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="section-icon icon-red"><i class="bi bi-fire"></i></div>
                <span class="section-head">Task History</span>
                <span class="ms-auto badge bg-danger" style="border-radius:10px;"><?= $stats['total'] ?? 0 ?> Total</span>
            </div>

            <?php if (empty($history)): ?>
                <div class="text-center py-4" style="color:var(--text-muted);">
                    <i class="bi bi-inbox" style="font-size:2.5rem;"></i>
                    <p class="mt-2 mb-0">No tasks assigned yet.</p>
                </div>
            <?php else:
                $status_colors = [
                    'Completed' => '#22c55e',
                    'Accepted'  => '#0d6efd',
                    'Declined'  => '#dc3545',
                    'Pending'   => '#f0a500',
                ];
                foreach ($history as $h):
                    $color    = $status_colors[$h['assignment_status']] ?? '#8fa3ba';
                    $date_fmt = $h['assigned_date'] ? date("M d, Y", strtotime($h['assigned_date'])) : 'N/A';
            ?>
                <div class="deploy-item">
                    <div class="deploy-dot" style="background:<?= $color ?>;box-shadow:0 0 6px <?= $color ?>88;"></div>
                    <div class="flex-grow-1">
                        <p class="deploy-title"><?= htmlspecialchars($h['incident_type']) ?></p>
                        <p class="deploy-meta">
                            <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($h['location']) ?>
                            &nbsp;·&nbsp;
                            <i class="bi bi-calendar3 me-1"></i><?= $date_fmt ?>
                        </p>
                    </div>
                    <span class="deploy-badge"
                          style="background:<?= $color ?>22;color:<?= $color ?>;border:1px solid <?= $color ?>55;">
                        <?= htmlspecialchars($h['assignment_status']) ?>
                    </span>
                </div>
            <?php endforeach; endif; ?>
        </div>

        <!-- Task Overview Stats (real numbers) -->
        <div class="profile-section">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="section-icon icon-green"><i class="bi bi-bar-chart-fill"></i></div>
                <span class="section-head">Task Overview</span>
            </div>

            <div class="row g-3 text-center mb-4">
                <div class="col-3">
                    <div style="font-family:'Montserrat',sans-serif;font-size:2.2rem;font-weight:900;color:var(--navy);"><?= $stats['total'] ?? 0 ?></div>
                    <div style="font-size:0.78rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;">Total</div>
                </div>
                <div class="col-3">
                    <div style="font-family:'Montserrat',sans-serif;font-size:2.2rem;font-weight:900;color:#22c55e;"><?= $stats['completed'] ?? 0 ?></div>
                    <div style="font-size:0.78rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;">Done</div>
                </div>
                <div class="col-3">
                    <div style="font-family:'Montserrat',sans-serif;font-size:2.2rem;font-weight:900;color:#0d6efd;"><?= $stats['accepted'] ?? 0 ?></div>
                    <div style="font-size:0.78rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;">Active</div>
                </div>
                <div class="col-3">
                    <div style="font-family:'Montserrat',sans-serif;font-size:2.2rem;font-weight:900;color:#dc3545;"><?= $stats['declined'] ?? 0 ?></div>
                    <div style="font-size:0.78rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;">Declined</div>
                </div>
            </div>

            <!-- Completion rate bar -->
            <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <span style="font-size:0.88rem;font-weight:500;">Completion Rate</span>
                    <span style="font-size:0.82rem;color:var(--text-muted);"><?= $rate ?>%</span>
                </div>
                <div style="height:8px;background:#e8eef5;border-radius:10px;overflow:hidden;">
                    <div id="completionBar"
                         style="width:0%;height:100%;background:linear-gradient(90deg,#22c55e,#0d6efd);
                                border-radius:10px;transition:width 1.2s ease;">
                    </div>
                </div>
            </div>

            <!-- Volunteer summary -->
            <div class="mt-4 pt-3" style="border-top:1px solid #eef2f7;">
                <div class="row g-2">
                    <div class="col-6">
                        <div class="info-label">Skill</div>
                        <div class="info-value"><?= htmlspecialchars($volunteer['skills']) ?></div>
                    </div>
                    <div class="col-6">
                        <div class="info-label">Availability</div>
                        <div class="info-value"><?= htmlspecialchars($volunteer['availability']) ?></div>
                    </div>
                    <div class="col-6 mt-2">
                        <div class="info-label">Transport</div>
                        <div class="info-value"><?= htmlspecialchars($volunteer['transport_mode']) ?></div>
                    </div>
                    <div class="col-6 mt-2">
                        <div class="info-label">Location</div>
                        <div class="info-value"><?= htmlspecialchars($volunteer['location']) ?></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
</div>

<!-- FOOTER -->
<footer>
<div class="container">
    <div class="row gy-3">
        <div class="col-md-4">
            <h5 class="fw-bold text-white">SkillAid</h5>
            <p class="small">Smart Emergency Volunteer Network for Sri Lanka</p>
            <p class="small"><strong>Emergency Hotline:</strong> 199</p>
        </div>
        <div class="col-md-4">
            <h6 class="fw-bold text-white">Quick Links</h6>
            <p class="small mb-1"><a href="../index.php">Home</a></p>
            <p class="small mb-1"><a href="dashboard.php">Dashboard</a></p>
            <p class="small mb-1"><a href="../reporter/report_incident.php">Report Emergency</a></p>
        </div>
        <div class="col-md-4">
            <h6 class="fw-bold text-white">Contact</h6>
            <p class="small mb-1"><strong>Email:</strong> <a href="mailto:skillaid@gmail.com">skillaid@gmail.com</a></p>
            <p class="small mb-1"><strong>Phone:</strong> +94 76 155 8321</p>
            <p class="small mb-1"><strong>Address:</strong> 123 Biyagama, Colombo, Sri Lanka</p>
        </div>
    </div>
    <hr style="border-color:rgba(203,213,225,0.25);" class="mt-3">
    <p class="text-center small mb-0">© 2026 <strong>SkillAid</strong>. All Rights Reserved.</p>
</div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        document.getElementById('completionBar').style.width = '<?= $rate ?>%';
    }, 400);
});
function toggleEditForm() {
    const form = document.getElementById('editForm');
    form.classList.toggle('show');
    if (form.classList.contains('show')) {
        form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}
</script>
</body>
</html>
