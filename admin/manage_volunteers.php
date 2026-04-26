<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Filter by skill
$filter_skill = $_GET['skill'] ?? '';
$filter_avail = $_GET['availability'] ?? '';

$where = "WHERE 1=1";
$params = [];
$types  = "";

if ($filter_skill) {
    $where   .= " AND v.skills = ?";
    $params[] = $filter_skill;
    $types   .= "s";
}
if ($filter_avail) {
    $where   .= " AND v.availability = ?";
    $params[] = $filter_avail;
    $types   .= "s";
}

$sql = "
    SELECT v.*,
           COUNT(ta.assignment_id)                  AS total_tasks,
           SUM(ta.assignment_status='Completed')    AS completed,
           SUM(ta.assignment_status='Accepted')     AS accepted
    FROM volunteer v
    LEFT JOIN task_assignment ta ON v.volunteer_id = ta.volunteer_id
    $where
    GROUP BY v.volunteer_id
    ORDER BY total_tasks DESC
";

if ($params) {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $volunteers = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
} else {
    $volunteers = mysqli_fetch_all(mysqli_query($conn, $sql), MYSQLI_ASSOC);
}

// All unique skills for filter dropdown
$all_skills = mysqli_fetch_all(mysqli_query($conn, "SELECT DISTINCT skills FROM volunteer ORDER BY skills"), MYSQLI_ASSOC);
$total_vols = count($volunteers);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Volunteers | SkillAid Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<style>
:root { --navy:#0d2238; --blue:#0d6efd; --accent:#dc3545; --gold:#f0a500; --green:#22c55e; --bg:#f3f6fb; --muted:#8fa3ba; }
body { font-family: 'Inter', sans-serif; background: var(--bg); }

.navbar { background: var(--navy); padding: 12px 0; }
.navbar-brand { font-family: 'Poppins', sans-serif; font-size: 1.5rem; font-weight: 600; color: #fff !important; display: flex; align-items: center; gap: 10px; }
.navbar-brand img { height: 42px; }
.nav-link { color: #e2e8f0 !important; font-weight: 500; }
.nav-link:hover { color: #fff !important; }
.nav-divider { color: #64748b; margin: 0 10px; }

.page-wrap { max-width: 1200px; margin: 120px auto 60px; padding: 0 16px; }

.page-header { background: linear-gradient(135deg, var(--navy) 0%, #1a3a55 100%); border-radius: 20px; padding: 28px 36px; color: #fff; margin-bottom: 28px; }
.page-header h2 { font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 1.8rem; margin: 0; }
.page-header p  { color: #94c9f5; margin: 6px 0 0; }

/* FILTER BAR */
.filter-bar { background: #fff; border-radius: 16px; padding: 20px 24px; box-shadow: 0 2px 12px rgba(13,34,56,0.06); border: 1.5px solid #e8eef5; margin-bottom: 24px; }

/* VOLUNTEER CARDS */
.vol-card {
    background: #fff;
    border-radius: 18px;
    padding: 22px 24px;
    box-shadow: 0 2px 12px rgba(13,34,56,0.06);
    border: 1.5px solid #e8eef5;
    transition: 0.2s;
    height: 100%;
}
.vol-card:hover { box-shadow: 0 6px 24px rgba(13,34,56,0.12); transform: translateY(-3px); }
.vol-initials {
    width: 52px; height: 52px;
    border-radius: 50%;
    background: var(--navy);
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-family: 'Poppins', sans-serif;
    font-size: 1.2rem; font-weight: 700;
    flex-shrink: 0;
}
.vol-name  { font-weight: 700; font-size: 1rem; color: var(--navy); margin: 0; }
.vol-email { font-size: 0.8rem; color: var(--muted); margin: 2px 0 0; }

.info-row { font-size: 0.82rem; color: #4a5568; margin: 4px 0; }
.info-row i { color: var(--muted); margin-right: 5px; width: 14px; }

.skill-tag { display: inline-block; background: #e8f0fb; color: var(--navy); border: 1px solid #c5d8f5; padding: 3px 12px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; }

.avail-badge { font-size: 0.72rem; padding: 3px 10px; border-radius: 10px; font-weight: 600; }
.avail-Anytime         { background: #dcfce7; color: #166534; }
.avail-Weekends        { background: #fef9c3; color: #854d0e; }
.avail-Specific-Hours  { background: #fee2e2; color: #991b1b; }

.task-stat { text-align: center; }
.task-stat .num { font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 1.3rem; line-height: 1; }
.task-stat .lbl { font-size: 0.7rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.3px; margin-top: 2px; }

/* EMPTY STATE */
.empty-state { text-align: center; padding: 60px 20px; color: var(--muted); }
.empty-state i { font-size: 3rem; margin-bottom: 12px; display: block; }

footer { background: var(--navy); color: #cbd5e1; padding: 40px 0 24px; margin-top: 60px; }
footer a { color: #cbd5e1; text-decoration: none; }
footer a:hover { color: #fff; }
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
<div class="container-fluid px-4">
    <a class="navbar-brand" href="../index.php">
        <img src="../assets/images/logo.png" alt="SkillAid"> SkillAid Admin
    </a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navMenu">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navMenu">
        <ul class="navbar-nav align-items-center">
            <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
            <span class="nav-divider">|</span>
            <li class="nav-item"><a class="nav-link" style="color:#94c9f5 !important;" href="manage_volunteers.php">Volunteers</a></li>
            <span class="nav-divider">|</span>
            <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
            <span class="nav-divider">|</span>
            <li class="nav-item"><a href="logout.php" class="btn btn-danger btn-sm px-3">Logout</a></li>
        </ul>
    </div>
</div>
</nav>

<div class="page-wrap">

    <!-- Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2><i class="bi bi-people-fill me-2"></i>Manage Volunteers</h2>
                <p>View, filter and monitor all registered volunteers — <?= $total_vols ?> found</p>
            </div>
            <a href="dashboard.php" class="btn btn-outline-light btn-sm px-4">
                <i class="bi bi-arrow-left me-1"></i>Dashboard
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Filter by Skill</label>
                <select name="skill" class="form-select form-select-sm" style="border-radius:10px;">
                    <option value="">All Skills</option>
                    <?php foreach ($all_skills as $s): ?>
                        <option value="<?= htmlspecialchars($s['skills']) ?>"
                            <?= $filter_skill == $s['skills'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['skills']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Filter by Availability</label>
                <select name="availability" class="form-select form-select-sm" style="border-radius:10px;">
                    <option value="">All Availability</option>
                    <?php foreach (['Anytime','Weekends','Specific Hours'] as $a): ?>
                        <option value="<?= $a ?>" <?= $filter_avail == $a ? 'selected' : '' ?>><?= $a ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-4" style="border-radius:10px;">
                    <i class="bi bi-funnel-fill me-1"></i>Filter
                </button>
                <a href="manage_volunteers.php" class="btn btn-outline-secondary btn-sm px-3" style="border-radius:10px;">
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Volunteer Cards Grid -->
    <?php if (empty($volunteers)): ?>
        <div class="empty-state">
            <i class="bi bi-person-x"></i>
            <p class="fw-semibold">No volunteers found matching your filter.</p>
            <a href="manage_volunteers.php" class="btn btn-outline-primary btn-sm" style="border-radius:10px;">Clear Filters</a>
        </div>
    <?php else: ?>
        <div class="row g-3">
        <?php foreach ($volunteers as $v):
            $parts    = explode(' ', $v['full_name']);
            $initials = strtoupper(substr($parts[0],0,1) . (isset($parts[1]) ? substr($parts[1],0,1) : ''));
            $avail_class = str_replace(' ', '-', $v['availability']);
        ?>
            <div class="col-md-6 col-lg-4">
                <div class="vol-card">

                    <!-- Top: Avatar + Name -->
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="vol-initials"><?= $initials ?></div>
                        <div>
                            <p class="vol-name"><?= htmlspecialchars($v['full_name']) ?></p>
                            <p class="vol-email"><?= htmlspecialchars($v['email']) ?></p>
                        </div>
                    </div>

                    <!-- Info Rows -->
                    <div class="mb-3">
                        <div class="info-row"><i class="bi bi-telephone"></i><?= htmlspecialchars($v['contact_number']) ?></div>
                        <div class="info-row"><i class="bi bi-geo-alt"></i><?= htmlspecialchars($v['location']) ?></div>
                        <div class="info-row"><i class="bi bi-car-front"></i><?= htmlspecialchars($v['transport_mode']) ?></div>
                    </div>

                    <!-- Skill + Availability -->
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="skill-tag"><?= htmlspecialchars($v['skills']) ?></span>
                        <span class="avail-badge avail-<?= $avail_class ?>">
                            <?= htmlspecialchars($v['availability']) ?>
                        </span>
                    </div>

                    <!-- Task Stats -->
                    <div style="border-top:1px solid #eef2f7; padding-top:14px;">
                        <div class="row g-0 text-center">
                            <div class="col-4 task-stat">
                                <div class="num" style="color:var(--navy);"><?= $v['total_tasks'] ?></div>
                                <div class="lbl">Total</div>
                            </div>
                            <div class="col-4 task-stat" style="border-left:1px solid #eef2f7;border-right:1px solid #eef2f7;">
                                <div class="num" style="color:#22c55e;"><?= $v['completed'] ?></div>
                                <div class="lbl">Done</div>
                            </div>
                            <div class="col-4 task-stat">
                                <div class="num" style="color:#0d6efd;"><?= $v['accepted'] ?></div>
                                <div class="lbl">Active</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<!-- FOOTER -->
<footer>
<div class="container">
    <div class="row gy-3">
        <div class="col-md-4">
            <h5 class="fw-bold text-white">SkillAid Admin</h5>
            <p class="small">Emergency Volunteer Coordination System</p>
        </div>
        <div class="col-md-4">
            <h6 class="fw-bold text-white">Admin Panel</h6>
            <p class="small mb-1"><a href="dashboard.php">Dashboard</a></p>
            <p class="small mb-1"><a href="manage_volunteers.php">Volunteers</a></p>
            <p class="small mb-1"><a href="reports.php">Reports</a></p>
        </div>
        <div class="col-md-4">
            <h6 class="fw-bold text-white">Contact</h6>
            <p class="small mb-1">Email: skillaid@gmail.com</p>
            <p class="small mb-1">Hotline: 199</p>
        </div>
    </div>
    <hr style="border-color:rgba(203,213,225,0.25);" class="mt-3">
    <p class="text-center small mb-0">© 2026 <strong>SkillAid</strong>. All Rights Reserved.</p>
</div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>