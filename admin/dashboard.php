 <?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// ── Stats Queries ──────────────────────────────────────────────────────
$total_incidents  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM incident"))['c'];
$pending_inc      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM incident WHERE status='Pending'"))['c'];
$assigned_inc     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM incident WHERE status='Assigned'"))['c'];
$completed_inc    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM incident WHERE status='Completed'"))['c'];
$total_volunteers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM volunteer"))['c'];
$total_assignments= mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM task_assignment"))['c'];
$accepted_tasks   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM task_assignment WHERE assignment_status='Accepted'"))['c'];
$completed_tasks  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM task_assignment WHERE assignment_status='Completed'"))['c'];

// ── Recent Notifications (latest status changes) ───────────────────────
$notifications = mysqli_query($conn, "
    SELECT 
        v.full_name,
        i.incident_type,
        i.location,
        ta.assignment_status,
        ta.assigned_date
    FROM task_assignment ta
    JOIN volunteer v  ON ta.volunteer_id  = v.volunteer_id
    JOIN incident  i  ON ta.incident_id   = i.incident_id
    ORDER BY ta.assigned_date DESC
    LIMIT 5
");

// ── All Incidents ──────────────────────────────────────────────────────
$incidents = mysqli_query($conn, "SELECT * FROM incident ORDER BY reported_time DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard | SkillAid</title>
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

/* NAVBAR */
.navbar { background: var(--navy); padding: 12px 0; }
.navbar-brand { font-family: 'Poppins', sans-serif; font-size: 1.5rem; font-weight: 600; color: #fff !important; display: flex; align-items: center; gap: 10px; }
.navbar-brand img { height: 42px; }
.nav-link { color: #e2e8f0 !important; font-weight: 500; }
.nav-link:hover { color: #fff !important; }
.nav-divider { color: #64748b; margin: 0 10px; }

/* PAGE */
.page-wrap { max-width: 1200px; margin: 120px auto 60px; padding: 0 16px; }

/* PAGE HEADER */
.page-header {
    background: linear-gradient(135deg, var(--navy) 0%, #1a3a55 100%);
    border-radius: 20px;
    padding: 30px 36px;
    color: #fff;
    margin-bottom: 28px;
}
.page-header h2 { font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 1.8rem; margin: 0; }
.page-header p  { color: #94c9f5; margin: 6px 0 0; font-size: 0.95rem; }

/* STAT CARDS */
.stat-card {
    background: #fff;
    border-radius: 18px;
    padding: 24px 20px;
    box-shadow: 0 2px 16px rgba(13,34,56,0.07);
    border: 1.5px solid #e8eef5;
    transition: box-shadow 0.2s, transform 0.2s;
    height: 100%;
}
.stat-card:hover { box-shadow: 0 6px 24px rgba(13,34,56,0.13); transform: translateY(-3px); }
.stat-icon {
    width: 50px; height: 50px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem;
    margin-bottom: 14px;
}
.stat-num {
    font-family: 'Poppins', sans-serif;
    font-size: 2.4rem;
    font-weight: 700;
    line-height: 1;
    color: var(--navy);
}
.stat-label { font-size: 0.82rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }

/* SECTION CARD */
.section-card {
    background: #fff;
    border-radius: 20px;
    padding: 28px 30px;
    box-shadow: 0 2px 16px rgba(13,34,56,0.07);
    border: 1.5px solid #e8eef5;
    margin-bottom: 24px;
}
.section-title {
    font-family: 'Poppins', sans-serif;
    font-weight: 600;
    font-size: 1.05rem;
    color: var(--navy);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.s-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1rem; }
.i-blue  { background: #e7f0ff; color: var(--blue);   }
.i-red   { background: #fdecea; color: var(--accent);  }
.i-gold  { background: #fff8e1; color: var(--gold);   }
.i-green { background: #e6f9f0; color: var(--green);  }
.i-navy  { background: #e8f0fb; color: var(--navy);   }

/* INCIDENT CARDS */
.incident-row {
    background: #f9fbff;
    border: 1px solid #e5eaf3;
    border-radius: 14px;
    padding: 18px 20px;
    margin-bottom: 12px;
    transition: 0.2s;
}
.incident-row:hover { background: #fff; box-shadow: 0 4px 16px rgba(13,34,56,0.07); }
.inc-type { font-weight: 600; color: var(--navy); font-size: 0.97rem; margin: 0; }
.inc-desc { font-size: 0.85rem; color: var(--muted); margin: 3px 0 0; }

/* STATUS BADGES */
.status-badge {
    font-size: 0.75rem;
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 600;
    white-space: nowrap;
}
.s-Pending   { background: #fde68a; color: #92400e; }
.s-Assigned  { background: #bfdbfe; color: #1e3a8a; }
.s-Completed { background: #bbf7d0; color: #065f46; }

/* NOTIFICATION ITEMS */
.notif-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #eef2f7;
    font-size: 0.88rem;
}
.notif-item:last-child { border-bottom: none; }
.notif-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; margin-top: 5px; }
.notif-text { flex-grow: 1; color: var(--navy); }
.notif-time { font-size: 0.75rem; color: var(--muted); white-space: nowrap; }

/* ASSIGN BTN */
.btn-assign {
    background: var(--blue);
    color: #fff;
    padding: 7px 18px;
    border-radius: 10px;
    font-size: 0.82rem;
    font-weight: 600;
    text-decoration: none;
    transition: background 0.2s;
    white-space: nowrap;
}
.btn-assign:hover { background: #0b5ed7; color: #fff; }
.btn-view {
    background: transparent;
    color: var(--navy);
    border: 1.5px solid #c5d8f5;
    padding: 7px 16px;
    border-radius: 10px;
    font-size: 0.82rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
    white-space: nowrap;
}
.btn-view:hover { background: var(--navy); color: #fff; }

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
            <li class="nav-item"><a class="nav-link" style="color:#94c9f5 !important;" href="dashboard.php">Dashboard</a></li>
            <span class="nav-divider">|</span>
            <li class="nav-item"><a class="nav-link" href="manage_volunteers.php">Volunteers</a></li>
            <span class="nav-divider">|</span>
            <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
            <span class="nav-divider">|</span>
            <li class="nav-item"><a href="logout.php" class="btn btn-danger btn-sm px-3">Logout</a></li>
        </ul>
    </div>
</div>
</nav>

<div class="page-wrap">

    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</h2>
                <p>Manage emergency incidents and coordinate volunteer response</p>
            </div>
            <div class="d-flex gap-2">
                <a href="reports.php" class="btn btn-outline-light btn-sm px-4">
                    <i class="bi bi-bar-chart-fill me-1"></i>View Reports
                </a>
                <a href="manage_volunteers.php" class="btn btn-outline-light btn-sm px-4">
                    <i class="bi bi-people-fill me-1"></i>Volunteers
                </a>
            </div>
        </div>
    </div>

    <!-- Stat Cards -->
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
                <div class="stat-icon" style="background:#fff8e1;color:var(--gold);">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="stat-num"><?= $pending_inc ?></div>
                <div class="stat-label">Pending</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#e6f9f0;color:var(--green);">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="stat-num"><?= $completed_inc ?></div>
                <div class="stat-label">Completed</div>
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
    </div>

    <div class="row g-4">

        <!-- LEFT: Incidents List -->
        <div class="col-lg-8">
            <div class="section-card">
                <div class="section-title">
                    <div class="s-icon i-red"><i class="bi bi-fire"></i></div>
                    All Incidents
                    <span class="ms-auto badge bg-secondary" style="border-radius:10px;font-size:0.75rem;">
                        <?= $total_incidents ?> Total
                    </span>
                </div>

                <?php
                mysqli_data_seek($incidents, 0);
                while ($row = mysqli_fetch_assoc($incidents)):
                ?>
                <div class="incident-row">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                        <div class="flex-grow-1">
                            <p class="inc-type">
                                #<?= $row['incident_id'] ?> — <?= htmlspecialchars($row['incident_type']) ?>
                            </p>
                            <p class="inc-desc"><?= htmlspecialchars($row['description']) ?></p>
                            <p class="inc-desc mt-1">
                                <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($row['location']) ?>
                                <?php if ($row['reported_time']): ?>
                                    &nbsp;·&nbsp;<i class="bi bi-clock me-1"></i><?= $row['reported_time'] ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="d-flex flex-column align-items-end gap-2">
                            <span class="status-badge s-<?= $row['status'] ?>">
                                <?= htmlspecialchars($row['status']) ?>
                            </span>
                            <a href="assign_task.php?id=<?= $row['incident_id'] ?>" class="btn-assign">
                                <i class="bi bi-person-plus-fill me-1"></i>Assign
                            </a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- RIGHT: Chart + Notifications -->
        <div class="col-lg-4">

            <!-- Incident Status Chart -->
            <div class="section-card">
                <div class="section-title">
                    <div class="s-icon i-blue"><i class="bi bi-pie-chart-fill"></i></div>
                    Incident Status
                </div>
                <canvas id="statusChart" height="200"></canvas>
            </div>

            <!-- Recent Notifications -->
            <div class="section-card">
                <div class="section-title">
                    <div class="s-icon i-gold"><i class="bi bi-bell-fill"></i></div>
                    Recent Activity
                </div>

                <?php
                $notif_colors = [
                    'Accepted'  => '#0d6efd',
                    'Completed' => '#22c55e',
                    'Declined'  => '#dc3545',
                    'Pending'   => '#f0a500',
                ];
                $notif_msgs = [
                    'Accepted'  => 'accepted the task',
                    'Completed' => 'completed the task',
                    'Declined'  => 'declined the task',
                    'Pending'   => 'was assigned a task',
                ];
                while ($n = mysqli_fetch_assoc($notifications)):
                    $color = $notif_colors[$n['assignment_status']] ?? '#8fa3ba';
                    $msg   = $notif_msgs[$n['assignment_status']]   ?? 'updated task';
                    $time  = $n['assigned_date'] ? date("M d, H:i", strtotime($n['assigned_date'])) : '';
                ?>
                <div class="notif-item">
                    <div class="notif-dot" style="background:<?= $color ?>;box-shadow:0 0 5px <?= $color ?>88;"></div>
                    <div class="notif-text">
                        <strong><?= htmlspecialchars($n['full_name']) ?></strong> <?= $msg ?>
                        for <strong><?= htmlspecialchars($n['incident_type']) ?></strong>
                        in <?= htmlspecialchars($n['location']) ?>
                    </div>
                    <?php if ($time): ?>
                        <div class="notif-time"><?= $time ?></div>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
            </div>

            <!-- Quick Stats -->
            <div class="section-card">
                <div class="section-title">
                    <div class="s-icon i-green"><i class="bi bi-bar-chart-fill"></i></div>
                    Task Summary
                </div>
                <div style="font-size:0.88rem;">
                    <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid #eef2f7;">
                        <span style="color:var(--muted);">Total Assignments</span>
                        <span style="font-weight:700;color:var(--navy);"><?= $total_assignments ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid #eef2f7;">
                        <span style="color:var(--muted);">Accepted</span>
                        <span style="font-weight:700;color:#0d6efd;"><?= $accepted_tasks ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid #eef2f7;">
                        <span style="color:var(--muted);">Completed</span>
                        <span style="font-weight:700;color:#22c55e;"><?= $completed_tasks ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span style="color:var(--muted);">Active Volunteers</span>
                        <span style="font-weight:700;color:var(--navy);"><?= $total_volunteers ?></span>
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
            <p class="small"><strong>Hotline:</strong> 199</p>
        </div>
        <div class="col-md-4">
            <h6 class="fw-bold text-white">Admin Panel</h6>
            <p class="small mb-1"><a href="dashboard.php">Dashboard</a></p>
            <p class="small mb-1"><a href="manage_volunteers.php">Manage Volunteers</a></p>
            <p class="small mb-1"><a href="reports.php">Reports</a></p>
        </div>
        <div class="col-md-4">
            <h6 class="fw-bold text-white">Contact</h6>
            <p class="small mb-1">Email: skillaid@gmail.com</p>
            <p class="small mb-1">Phone: +94 76 155 8321</p>
        </div>
    </div>
    <hr style="border-color:rgba(203,213,225,0.25);" class="mt-3">
    <p class="text-center small mb-0">© 2026 <strong>SkillAid</strong>. All Rights Reserved.</p>
</div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Incident Status Pie Chart
const ctx = document.getElementById('statusChart').getContext('2d');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Pending', 'Assigned', 'Completed'],
        datasets: [{
            data: [<?= $pending_inc ?>, <?= $assigned_inc ?>, <?= $completed_inc ?>],
            backgroundColor: ['#fde68a', '#bfdbfe', '#bbf7d0'],
            borderColor:     ['#f0a500', '#0d6efd', '#22c55e'],
            borderWidth: 2,
            hoverOffset: 6
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 12 }, padding: 16 } }
        },
        cutout: '65%'
    }
});
</script>
</body>
</html>