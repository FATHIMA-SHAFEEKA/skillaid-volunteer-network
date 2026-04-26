<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// ── Core Stats ────────────────────────────────────────────────────────
$total_incidents  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM incident"))['c'];
$pending_inc      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM incident WHERE status='Pending'"))['c'];
$assigned_inc     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM incident WHERE status='Assigned'"))['c'];
$completed_inc    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM incident WHERE status='Completed'"))['c'];
$total_volunteers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM volunteer"))['c'];
$total_assignments= mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM task_assignment"))['c'];
$accepted_tasks   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM task_assignment WHERE assignment_status='Accepted'"))['c'];
$completed_tasks  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM task_assignment WHERE assignment_status='Completed'"))['c'];
$declined_tasks   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM task_assignment WHERE assignment_status='Declined'"))['c'];
$pending_tasks    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM task_assignment WHERE assignment_status='Pending'"))['c'];

// ── Incidents by Type ────────────────────────────────────────────────
$by_type_res = mysqli_query($conn, "
    SELECT incident_type, COUNT(*) AS cnt 
    FROM incident 
    GROUP BY incident_type 
    ORDER BY cnt DESC
");
$type_labels = []; $type_counts = [];
while ($r = mysqli_fetch_assoc($by_type_res)) {
    $type_labels[] = $r['incident_type'];
    $type_counts[] = $r['cnt'];
}

// ── Volunteer Activity (most active volunteers) ───────────────────────
$vol_activity = mysqli_query($conn, "
    SELECT v.full_name, v.skills, v.location,
           COUNT(ta.assignment_id)                             AS total_tasks,
           SUM(ta.assignment_status = 'Completed')            AS completed,
           SUM(ta.assignment_status = 'Accepted')             AS accepted,
           SUM(ta.assignment_status = 'Declined')             AS declined
    FROM volunteer v
    LEFT JOIN task_assignment ta ON v.volunteer_id = ta.volunteer_id
    GROUP BY v.volunteer_id
    ORDER BY total_tasks DESC
    LIMIT 10
");

// ── Skills Distribution ───────────────────────────────────────────────
$skills_res = mysqli_query($conn, "
    SELECT skills, COUNT(*) AS cnt 
    FROM volunteer 
    GROUP BY skills 
    ORDER BY cnt DESC
");
$skill_labels = []; $skill_counts = [];
while ($r = mysqli_fetch_assoc($skills_res)) {
    $skill_labels[] = $r['skills'];
    $skill_counts[] = $r['cnt'];
}

// ── Completion rate ───────────────────────────────────────────────────
$completion_rate = $total_assignments > 0 
    ? round(($completed_tasks / $total_assignments) * 100) 
    : 0;
$response_rate = $total_assignments > 0
    ? round((($accepted_tasks + $completed_tasks) / $total_assignments) * 100)
    : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reports | SkillAid Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
:root {
    --navy:  #0d2238;
    --blue:  #0d6efd;
    --accent:#dc3545;
    --gold:  #f0a500;
    --green: #22c55e;
    --bg:    #f3f6fb;
    --muted: #8fa3ba;
}
body { font-family: 'Inter', sans-serif; background: var(--bg); }

.navbar { background: var(--navy); padding: 12px 0; }
.navbar-brand { font-family: 'Poppins', sans-serif; font-size: 1.5rem; font-weight: 600; color: #fff !important; display: flex; align-items: center; gap: 10px; }
.navbar-brand img { height: 42px; }
.nav-link { color: #e2e8f0 !important; font-weight: 500; }
.nav-link:hover { color: #fff !important; }
.nav-divider { color: #64748b; margin: 0 10px; }

.page-wrap { max-width: 1200px; margin: 120px auto 60px; padding: 0 16px; }

.page-header {
    background: linear-gradient(135deg, var(--navy) 0%, #1a3a55 100%);
    border-radius: 20px;
    padding: 28px 36px;
    color: #fff;
    margin-bottom: 28px;
}
.page-header h2 { font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 1.8rem; margin: 0; }
.page-header p  { color: #94c9f5; margin: 6px 0 0; }

/* STAT CARDS */
.stat-card {
    background: #fff;
    border-radius: 18px;
    padding: 24px 20px;
    box-shadow: 0 2px 16px rgba(13,34,56,0.07);
    border: 1.5px solid #e8eef5;
    transition: 0.2s;
    height: 100%;
}
.stat-card:hover { box-shadow: 0 6px 24px rgba(13,34,56,0.13); transform: translateY(-3px); }
.stat-icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; margin-bottom: 14px; }
.stat-num  { font-family: 'Poppins', sans-serif; font-size: 2.4rem; font-weight: 700; line-height: 1; color: var(--navy); }
.stat-label{ font-size: 0.82rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }

/* SECTION CARD */
.section-card { background: #fff; border-radius: 20px; padding: 28px 30px; box-shadow: 0 2px 16px rgba(13,34,56,0.07); border: 1.5px solid #e8eef5; margin-bottom: 24px; }
.section-title { font-family: 'Poppins', sans-serif; font-weight: 600; font-size: 1.05rem; color: var(--navy); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
.s-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1rem; }
.i-blue  { background: #e7f0ff; color: var(--blue); }
.i-red   { background: #fdecea; color: var(--accent); }
.i-gold  { background: #fff8e1; color: var(--gold); }
.i-green { background: #e6f9f0; color: var(--green); }
.i-navy  { background: #e8f0fb; color: var(--navy); }

/* PROGRESS BARS */
.prog-wrap { margin-bottom: 16px; }
.prog-label { display: flex; justify-content: between; margin-bottom: 5px; font-size: 0.88rem; }
.prog-bar-bg { height: 10px; background: #e8eef5; border-radius: 10px; overflow: hidden; }
.prog-bar-fill { height: 100%; border-radius: 10px; transition: width 1.2s ease; }

/* VOLUNTEER TABLE */
.vol-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.vol-table th { font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--muted); font-weight: 600; padding: 10px 14px; border-bottom: 2px solid #eef2f7; }
.vol-table td { padding: 12px 14px; border-bottom: 1px solid #eef2f7; font-size: 0.88rem; vertical-align: middle; }
.vol-table tr:last-child td { border-bottom: none; }
.vol-table tr:hover td { background: #f8faff; }
.task-count { font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 1.1rem; }

/* SKILL TAG */
.skill-pill { display: inline-block; background: #e8f0fb; color: var(--navy); border: 1px solid #c5d8f5; padding: 3px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 500; }

/* FOOTER */
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
            <li class="nav-item"><a class="nav-link" href="manage_volunteers.php">Volunteers</a></li>
            <span class="nav-divider">|</span>
            <li class="nav-item"><a class="nav-link" style="color:#94c9f5 !important;" href="reports.php">Reports</a></li>
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
                <h2><i class="bi bi-bar-chart-fill me-2"></i>Reports & Analytics</h2>
                <p>System-wide performance overview for SkillAid emergency coordination</p>
            </div>
            <a href="dashboard.php" class="btn btn-outline-light btn-sm px-4">
                <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Top Stats -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#e7f0ff;color:var(--blue);">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div class="stat-num"><?= $total_incidents ?></div>
                <div class="stat-label">Total Incidents</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#e6f9f0;color:var(--green);">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="stat-num"><?= $completed_inc ?></div>
                <div class="stat-label">Resolved</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fdecea;color:var(--accent);">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="stat-num"><?= $total_volunteers ?></div>
                <div class="stat-label">Volunteers</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff8e1;color:var(--gold);">
                    <i class="bi bi-clipboard-check-fill"></i>
                </div>
                <div class="stat-num"><?= $total_assignments ?></div>
                <div class="stat-label">Assignments</div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        <!-- Charts Column -->
        <div class="col-lg-8">

            <!-- Incidents by Type Bar Chart -->
            <div class="section-card">
                <div class="section-title">
                    <div class="s-icon i-blue"><i class="bi bi-bar-chart-fill"></i></div>
                    Incidents by Type
                </div>
                <canvas id="typeChart" height="160"></canvas>
            </div>

            <!-- Volunteer Activity Table -->
            <div class="section-card">
                <div class="section-title">
                    <div class="s-icon i-navy"><i class="bi bi-people-fill"></i></div>
                    Volunteer Activity
                    <span class="ms-auto badge bg-secondary" style="border-radius:10px;font-size:0.75rem;">Top 10</span>
                </div>
                <div style="overflow-x:auto;">
                <table class="vol-table">
                    <thead>
                        <tr>
                            <th>Volunteer</th>
                            <th>Skill</th>
                            <th>Location</th>
                            <th style="text-align:center;">Total</th>
                            <th style="text-align:center;">Done</th>
                            <th style="text-align:center;">Active</th>
                            <th style="text-align:center;">Declined</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($v = mysqli_fetch_assoc($vol_activity)): ?>
                        <tr>
                            <td style="font-weight:600;color:var(--navy);"><?= htmlspecialchars($v['full_name']) ?></td>
                            <td><span class="skill-pill"><?= htmlspecialchars($v['skills']) ?></span></td>
                            <td style="color:var(--muted);"><?= htmlspecialchars($v['location']) ?></td>
                            <td style="text-align:center;">
                                <span class="task-count"><?= $v['total_tasks'] ?></span>
                            </td>
                            <td style="text-align:center;color:#22c55e;font-weight:700;"><?= $v['completed'] ?></td>
                            <td style="text-align:center;color:#0d6efd;font-weight:700;"><?= $v['accepted'] ?></td>
                            <td style="text-align:center;color:#dc3545;font-weight:700;"><?= $v['declined'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                </div>
            </div>

        </div>

        <!-- Right Column -->
        <div class="col-lg-4">

            <!-- Task Status Doughnut -->
            <div class="section-card">
                <div class="section-title">
                    <div class="s-icon i-gold"><i class="bi bi-pie-chart-fill"></i></div>
                    Task Status
                </div>
                <canvas id="taskChart" height="200"></canvas>
            </div>

            <!-- Skills Distribution -->
            <div class="section-card">
                <div class="section-title">
                    <div class="s-icon i-green"><i class="bi bi-stars"></i></div>
                    Volunteer Skills
                </div>
                <canvas id="skillChart" height="200"></canvas>
            </div>

            <!-- Performance Rates -->
            <div class="section-card">
                <div class="section-title">
                    <div class="s-icon i-blue"><i class="bi bi-speedometer2"></i></div>
                    Performance Rates
                </div>

                <div class="prog-wrap">
                    <div class="d-flex justify-content-between mb-1">
                        <span style="font-size:0.88rem;font-weight:500;">Task Completion Rate</span>
                        <span style="font-size:0.82rem;color:var(--muted);"><?= $completion_rate ?>%</span>
                    </div>
                    <div class="prog-bar-bg">
                        <div class="prog-bar-fill" id="compBar"
                             style="width:0%;background:linear-gradient(90deg,#22c55e,#0d6efd);"></div>
                    </div>
                </div>

                <div class="prog-wrap">
                    <div class="d-flex justify-content-between mb-1">
                        <span style="font-size:0.88rem;font-weight:500;">Volunteer Response Rate</span>
                        <span style="font-size:0.82rem;color:var(--muted);"><?= $response_rate ?>%</span>
                    </div>
                    <div class="prog-bar-bg">
                        <div class="prog-bar-fill" id="respBar"
                             style="width:0%;background:linear-gradient(90deg,#0d6efd,#0d2238);"></div>
                    </div>
                </div>

                <div class="prog-wrap">
                    <div class="d-flex justify-content-between mb-1">
                        <span style="font-size:0.88rem;font-weight:500;">Incident Resolution Rate</span>
                        <span style="font-size:0.82rem;color:var(--muted);">
                            <?= $total_incidents > 0 ? round(($completed_inc / $total_incidents) * 100) : 0 ?>%
                        </span>
                    </div>
                    <div class="prog-bar-bg">
                        <div class="prog-bar-fill" id="resBar"
                             style="width:0%;background:linear-gradient(90deg,#f0a500,#dc3545);"></div>
                    </div>
                </div>

                <!-- Summary numbers -->
                <div class="mt-4 pt-3" style="border-top:1px solid #eef2f7;">
                    <div class="row g-2 text-center">
                        <div class="col-6">
                            <div style="font-family:'Poppins',sans-serif;font-size:1.8rem;font-weight:700;color:#22c55e;"><?= $completed_tasks ?></div>
                            <div style="font-size:0.75rem;color:var(--muted);text-transform:uppercase;">Completed Tasks</div>
                        </div>
                        <div class="col-6">
                            <div style="font-family:'Poppins',sans-serif;font-size:1.8rem;font-weight:700;color:#dc3545;"><?= $declined_tasks ?></div>
                            <div style="font-size:0.75rem;color:var(--muted);text-transform:uppercase;">Declined Tasks</div>
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
<script>
// Incidents by Type Bar Chart
new Chart(document.getElementById('typeChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($type_labels) ?>,
        datasets: [{
            label: 'Incidents',
            data: <?= json_encode($type_counts) ?>,
            backgroundColor: ['#bfdbfe','#bbf7d0','#fde68a','#fecaca','#ddd6fe','#fed7aa'],
            borderColor:     ['#0d6efd','#22c55e','#f0a500','#dc3545','#8b5cf6','#f97316'],
            borderWidth: 2, borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#eef2f7' } },
            x: { grid: { display: false } }
        }
    }
});

// Task Status Doughnut
new Chart(document.getElementById('taskChart'), {
    type: 'doughnut',
    data: {
        labels: ['Pending','Accepted','Completed','Declined'],
        datasets: [{
            data: [<?= $pending_tasks ?>, <?= $accepted_tasks ?>, <?= $completed_tasks ?>, <?= $declined_tasks ?>],
            backgroundColor: ['#fde68a','#bfdbfe','#bbf7d0','#fecaca'],
            borderColor:     ['#f0a500','#0d6efd','#22c55e','#dc3545'],
            borderWidth: 2, hoverOffset: 6
        }]
    },
    options: { responsive: true, cutout: '60%', plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 12 } } } }
});

// Skills Distribution
new Chart(document.getElementById('skillChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($skill_labels) ?>,
        datasets: [{
            data: <?= json_encode($skill_counts) ?>,
            backgroundColor: ['#bfdbfe','#bbf7d0','#fde68a','#fecaca','#ddd6fe'],
            borderColor:     ['#0d6efd','#22c55e','#f0a500','#dc3545','#8b5cf6'],
            borderWidth: 2, hoverOffset: 6
        }]
    },
    options: { responsive: true, cutout: '55%', plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 10 } } } }
});

// Animate progress bars
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        document.getElementById('compBar').style.width = '<?= $completion_rate ?>%';
        document.getElementById('respBar').style.width = '<?= $response_rate ?>%';
        document.getElementById('resBar').style.width  = '<?= $total_incidents > 0 ? round(($completed_inc / $total_incidents) * 100) : 0 ?>%';
    }, 400);
});
</script>
</body>
</html>