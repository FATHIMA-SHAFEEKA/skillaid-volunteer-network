 <?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['volunteer_id'])) {
    header("Location: login.php");
    exit();
}

$volunteer_id = $_SESSION['volunteer_id'];

$vstmt = mysqli_prepare($conn, "SELECT * FROM volunteer WHERE volunteer_id = ?");
mysqli_stmt_bind_param($vstmt, "i", $volunteer_id);
mysqli_stmt_execute($vstmt);
$volunteer = mysqli_fetch_assoc(mysqli_stmt_get_result($vstmt));

$counts_stmt = mysqli_prepare($conn, "
    SELECT COUNT(*) AS total,
        SUM(assignment_status='Pending')   AS pending,
        SUM(assignment_status='Accepted')  AS accepted,
        SUM(assignment_status='Completed') AS completed
    FROM task_assignment WHERE volunteer_id = ?
");
mysqli_stmt_bind_param($counts_stmt, "i", $volunteer_id);
mysqli_stmt_execute($counts_stmt);
$counts = mysqli_fetch_assoc(mysqli_stmt_get_result($counts_stmt));

$query = mysqli_prepare($conn, "
    SELECT i.incident_id, i.incident_type, i.description,
           i.location, i.latitude, i.longitude, i.reported_time,
           ta.assignment_status, ta.assignment_id, ta.assigned_date
    FROM task_assignment ta
    JOIN incident i ON ta.incident_id = i.incident_id
    WHERE ta.volunteer_id = ?
    ORDER BY ta.assigned_date DESC
");
mysqli_stmt_bind_param($query, "i", $volunteer_id);
mysqli_stmt_execute($query);
$tasks = mysqli_fetch_all(mysqli_stmt_get_result($query), MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard | SkillAid Volunteer</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<style>
:root { --navy:#0d2238; --blue:#0d6efd; --red:#dc3545; --gold:#f0a500; --green:#22c55e; --bg:#f0f4f8; --muted:#8fa3ba; }
* { box-sizing:border-box; }
body { font-family:'Inter',sans-serif; background:var(--bg); min-height:100vh; }

.navbar { background:var(--navy); padding:12px 0; }
.navbar-brand { font-family:'Inter',sans-serif; font-size:1.5rem; font-weight:600; color:#fff !important; display:flex; align-items:center; gap:10px; text-decoration:none; }
.navbar-brand img { height:44px; }
.nav-link { color:#e2e8f0 !important; font-weight:500; }
.nav-link:hover { color:#fff !important; }
.nav-divider { color:#64748b; margin:0 10px; }

.welcome-header {
    background:linear-gradient(135deg, var(--navy) 0%, #1a3a55 100%);
    border-radius:20px; padding:32px 36px; color:#fff;
    margin-bottom:24px; display:flex; align-items:center;
    justify-content:space-between; flex-wrap:wrap; gap:20px;
}
.welcome-name { font-family:'Poppins',sans-serif; font-size:1.7rem; font-weight:700; margin:0; }
.welcome-sub  { color:#94c9f5; font-size:0.92rem; margin-top:4px; }

.stat-pill { background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.15); border-radius:14px; padding:14px 22px; text-align:center; min-width:90px; }
.stat-pill .num { font-family:'Poppins',sans-serif; font-size:1.8rem; font-weight:700; line-height:1; color:#fff; }
.stat-pill .lbl { font-size:0.72rem; color:#94c9f5; text-transform:uppercase; letter-spacing:0.5px; margin-top:3px; }

.section-head { font-family:'Poppins',sans-serif; font-weight:700; font-size:1.2rem; color:var(--navy); margin-bottom:20px; display:flex; align-items:center; gap:10px; }
.count-badge { background:var(--navy); color:#fff; font-size:0.75rem; padding:3px 10px; border-radius:12px; font-weight:600; }

.task-card { background:#fff; border-radius:18px; overflow:hidden; box-shadow:0 2px 16px rgba(13,34,56,0.07); border:1.5px solid #e8eef5; transition:box-shadow 0.2s, transform 0.2s; height:100%; display:flex; flex-direction:column; }
.task-card:hover { box-shadow:0 8px 28px rgba(13,34,56,0.13); transform:translateY(-3px); }

.card-strip { height:5px; width:100%; }
.strip-Pending   { background:var(--gold); }
.strip-Accepted  { background:var(--blue); }
.strip-Completed { background:var(--green); }
.strip-Declined  { background:var(--red); }

.card-body-wrap { padding:22px 22px 0; flex-grow:1; }

.task-type { font-family:'Poppins',sans-serif; font-size:1.05rem; font-weight:700; color:var(--navy); margin:0 0 6px; display:flex; align-items:center; gap:8px; }
.type-icon { width:32px; height:32px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:0.9rem; flex-shrink:0; }
.task-desc { font-size:0.85rem; color:#64748b; margin:6px 0 10px; line-height:1.5; }
.task-meta { font-size:0.8rem; color:var(--muted); display:flex; align-items:center; gap:5px; margin-bottom:4px; }

.status-pill { display:inline-flex; align-items:center; gap:5px; padding:5px 14px; border-radius:20px; font-size:0.78rem; font-weight:600; margin:10px 0; }
.status-Pending   { background:#fef9c3; color:#854d0e; border:1px solid #fde68a; }
.status-Accepted  { background:#dbeafe; color:#1e3a8a; border:1px solid #bfdbfe; }
.status-Completed { background:#dcfce7; color:#166534; border:1px solid #bbf7d0; }
.status-Declined  { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }

/* LEAFLET MAP */
.btn-view-map {
    width:100%; background:#f0f6ff; border:1.5px solid #c5d8f5;
    border-radius:10px; padding:9px 14px; font-size:0.82rem; font-weight:600;
    color:var(--blue); cursor:pointer; display:flex; align-items:center;
    justify-content:center; gap:6px; margin-top:12px; transition:background 0.2s;
}
.btn-view-map:hover { background:#dbeafe; }

.leaflet-map-wrap {
    display:none; height:180px; margin-top:10px;
    border-radius:10px; overflow:hidden;
    border:1px solid #e8eef5; position:relative;
}

.gmaps-link {
    display:none; align-items:center; gap:5px;
    font-size:0.78rem; font-weight:600; color:var(--blue);
    text-decoration:none; padding:5px 0 2px;
}
.gmaps-link.show { display:flex; }
.gmaps-link:hover { color:#0b5ed7; text-decoration:underline; }

.no-map-note { background:#f8fafc; border-radius:10px; padding:10px 14px; font-size:0.78rem; color:var(--muted); margin:10px 0 0; display:flex; align-items:center; gap:6px; border:1px solid #e8eef5; }

.card-actions { padding:14px 22px 20px; display:flex; gap:8px; flex-wrap:wrap; border-top:1px solid #eef2f7; margin-top:auto; }
.btn-act { flex:1; min-width:80px; padding:9px 10px; border-radius:10px; font-size:0.82rem; font-weight:600; border:none; cursor:pointer; text-align:center; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; gap:5px; transition:all 0.2s; }
.btn-accept   { background:var(--blue); color:#fff; }
.btn-accept:hover   { background:#0b5ed7; color:#fff; }
.btn-complete { background:var(--green); color:#fff; }
.btn-complete:hover { background:#16a34a; color:#fff; }
.btn-decline  { background:#fff; color:var(--red); border:1.5px solid #fecaca; }
.btn-decline:hover  { background:var(--red); color:#fff; }

.empty-state { background:#fff; border-radius:18px; padding:60px 24px; text-align:center; border:1.5px solid #e8eef5; color:var(--muted); }
.empty-state i { font-size:3rem; display:block; margin-bottom:14px; }

footer { background:var(--navy); color:#cbd5e1; padding:40px 0 24px; margin-top:60px; }
footer a { color:#cbd5e1; text-decoration:none; }
footer a:hover { color:#fff; }

@media(max-width:768px) {
    .welcome-name { font-size:1.3rem; }
    .stat-pill { min-width:72px; padding:10px 14px; }
    .stat-pill .num { font-size:1.4rem; }
}
</style>
</head>
<body>

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
            <li class="nav-item"><a class="nav-link" style="color:#94c9f5 !important;" href="#">Dashboard</a></li>
            <span class="nav-divider">|</span>
            <li class="nav-item"><a class="nav-link" href="profile.php">My Profile</a></li>
            <span class="nav-divider">|</span>
            <li class="nav-item"><a class="nav-link text-warning fw-semibold" href="logout.php">Logout</a></li>
        </ul>
    </div>
</div>
</nav>

<div class="container" style="max-width:1100px; padding-top:100px; padding-bottom:40px;">

    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] == 'success'): ?>
            <div class="alert alert-success rounded-4 mb-3">
                <i class="bi bi-check-circle-fill me-2"></i>
                Task status updated — <strong><?= htmlspecialchars($_GET['status'] ?? '') ?></strong>
            </div>
        <?php elseif ($_GET['msg'] == 'locked'): ?>
            <div class="alert alert-warning rounded-4 mb-3">
                <i class="bi bi-lock-fill me-2"></i>
                This task was already accepted by another volunteer.
            </div>
        <?php else: ?>
            <div class="alert alert-danger rounded-4 mb-3">
                <i class="bi bi-exclamation-circle-fill me-2"></i>
                Something went wrong. Please try again.
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="welcome-header">
        <div>
            <h2 class="welcome-name">Welcome back, <?= htmlspecialchars($volunteer['full_name']) ?> 👋</h2>
            <p class="welcome-sub">
                <i class="bi bi-stars me-1"></i><?= htmlspecialchars($volunteer['skills']) ?>
                &nbsp;·&nbsp;
                <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($volunteer['location']) ?>
                &nbsp;·&nbsp;
                <i class="bi bi-clock me-1"></i><?= htmlspecialchars($volunteer['availability']) ?>
            </p>
        </div>
        <div class="d-flex gap-3 flex-wrap">
            <div class="stat-pill">
                <div class="num"><?= $counts['total'] ?? 0 ?></div>
                <div class="lbl">Total</div>
            </div>
            <div class="stat-pill">
                <div class="num" style="color:#fde68a;"><?= $counts['pending'] ?? 0 ?></div>
                <div class="lbl">Pending</div>
            </div>
            <div class="stat-pill">
                <div class="num" style="color:#93c5fd;"><?= $counts['accepted'] ?? 0 ?></div>
                <div class="lbl">Active</div>
            </div>
            <div class="stat-pill">
                <div class="num" style="color:#86efac;"><?= $counts['completed'] ?? 0 ?></div>
                <div class="lbl">Done</div>
            </div>
        </div>
    </div>

    <div class="section-head">
        <i class="bi bi-fire" style="color:var(--red);"></i>
        Assigned Emergency Tasks
        <span class="count-badge"><?= count($tasks) ?> Tasks</span>
    </div>

    <?php if (empty($tasks)): ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <p class="fw-semibold mb-1">No tasks assigned yet.</p>
            <p class="small">When an admin assigns you to an incident, it will appear here.</p>
        </div>
    <?php else: ?>

    <?php
    $type_icons = [
        'Flood'             => ['bi-water',               '#dbeafe','#1e3a8a'],
        'Fire'              => ['bi-fire',                '#fee2e2','#991b1b'],
        'Accident'          => ['bi-car-front',           '#fef9c3','#854d0e'],
        'Medical'           => ['bi-heart-pulse-fill',    '#fce7f3','#9d174d'],
        'Landslide'         => ['bi-exclamation-triangle','#fef3c7','#92400e'],
        'Search and Rescue' => ['bi-search-heart',        '#e0f2fe','#0c4a6e'],
        'Other'             => ['bi-question-circle',     '#f3f4f6','#374151'],
    ];
    function getTypeIcon($type, $map) {
        foreach ($map as $k => $v) { if (stripos($type, $k) !== false) return $v; }
        return ['bi-exclamation-circle','#f3f4f6','#374151'];
    }
    $status_icons = ['Pending'=>'bi-hourglass-split','Accepted'=>'bi-check-circle','Completed'=>'bi-check-circle-fill','Declined'=>'bi-x-circle'];
    ?>

    <div class="row g-4">
    <?php foreach ($tasks as $t):
        $status       = $t['assignment_status'];
        $has_gps      = !empty($t['latitude']) && !empty($t['longitude']);
        $lat          = floatval($t['latitude']  ?? 0);
        $lng          = floatval($t['longitude'] ?? 0);
        $icon_set     = getTypeIcon($t['incident_type'], $type_icons);
        $assigned_fmt = $t['assigned_date'] ? date("M d, Y · H:i", strtotime($t['assigned_date'])) : 'N/A';
        $map_id       = 'map_' . $t['assignment_id'];
    ?>
        <div class="col-md-6 col-lg-4">
            <div class="task-card">
                <div class="card-strip strip-<?= $status ?>"></div>
                <div class="card-body-wrap">

                    <div class="task-type">
                        <div class="type-icon" style="background:<?= $icon_set[1] ?>;color:<?= $icon_set[2] ?>;">
                            <i class="bi <?= $icon_set[0] ?>"></i>
                        </div>
                        <?= htmlspecialchars($t['incident_type']) ?>
                    </div>

                    <p class="task-desc"><?= htmlspecialchars(mb_strimwidth($t['description'], 0, 100, '...')) ?></p>

                    <div class="task-meta">
                        <i class="bi bi-geo-alt-fill" style="color:var(--red);"></i>
                        <?= htmlspecialchars($t['location']) ?>
                    </div>
                    <div class="task-meta">
                        <i class="bi bi-calendar3"></i>
                        <?= $assigned_fmt ?>
                    </div>

                    <div class="status-pill status-<?= $status ?>">
                        <i class="bi <?= $status_icons[$status] ?? 'bi-circle' ?>"></i>
                        <?= $status ?>
                    </div>

                    <?php if ($has_gps): ?>
                        <!-- Toggle button — map only loads on click -->
                        <button class="btn-view-map"
                                onclick="toggleLeafletMap(this, '<?= $map_id ?>', <?= $lat ?>, <?= $lng ?>)"
                                data-open="0">
                            <i class="bi bi-map-fill"></i> View Location on Map
                        </button>

                        <!-- Leaflet map container — empty until clicked -->
                        <div class="leaflet-map-wrap" id="<?= $map_id ?>"></div>

                        <!-- Google Maps navigation link -->
                        <a href="https://www.google.com/maps?q=<?= $lat ?>,<?= $lng ?>"
                           target="_blank"
                           class="gmaps-link"
                           id="glink_<?= $t['assignment_id'] ?>">
                            <i class="bi bi-box-arrow-up-right"></i>
                            Open in Google Maps for navigation
                        </a>
                    <?php else: ?>
                        <div class="no-map-note">
                            <i class="bi bi-map"></i> No GPS — use text location above
                        </div>
                    <?php endif; ?>

                </div>

                <div class="card-actions">
                    <?php if ($status !== 'Completed' && $status !== 'Declined'): ?>
                        <a href="update_status.php?id=<?= $t['assignment_id'] ?>&status=Accepted"
                           class="btn-act btn-accept">
                            <i class="bi bi-check-lg"></i>Accept
                        </a>
                        <a href="update_status.php?id=<?= $t['assignment_id'] ?>&status=Completed"
                           class="btn-act btn-complete">
                            <i class="bi bi-check-all"></i>Complete
                        </a>
                        <a href="update_status.php?id=<?= $t['assignment_id'] ?>&status=Declined"
                           class="btn-act btn-decline"
                           onclick="return confirm('Decline this task?');">
                            <i class="bi bi-x"></i>Decline
                        </a>
                    <?php else: ?>
                        <div style="width:100%;text-align:center;font-size:0.82rem;color:var(--muted);padding:4px 0;">
                            <?php if ($status === 'Completed'): ?>
                                <i class="bi bi-check-circle-fill me-1" style="color:var(--green);"></i>Task completed
                            <?php else: ?>
                                <i class="bi bi-x-circle-fill me-1" style="color:var(--red);"></i>Task declined
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

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
            <p class="small mb-1"><a href="#">Dashboard</a></p>
            <p class="small mb-1"><a href="profile.php">My Profile</a></p>
        </div>
        <div class="col-md-4">
            <h6 class="fw-bold text-white">Contact</h6>
            <p class="small mb-1"><strong>Email:</strong> skillaid@gmail.com</p>
            <p class="small mb-1"><strong>Phone:</strong> +94 76 155 8321</p>
            <p class="small mb-1">123 Biyagama, Colombo, Sri Lanka</p>
        </div>
    </div>
    <hr style="border-color:rgba(203,213,225,0.25);" class="mt-3">
    <p class="text-center small mb-0">© 2026 <strong>SkillAid</strong>. All Rights Reserved.</p>
</div>
</footer>

<!-- Leaflet JS — loaded once at bottom -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Track initialised maps so we don't reinitialise
const initialisedMaps = {};

function toggleLeafletMap(btn, mapId, lat, lng) {
    const wrap     = document.getElementById(mapId);
    const gmapLink = document.getElementById('glink_' + mapId.replace('map_', ''));
    const isOpen   = btn.dataset.open === '1';

    if (!isOpen) {
        // Show container
        wrap.style.display = 'block';
        gmapLink.classList.add('show');
        btn.innerHTML  = '<i class="bi bi-x-circle"></i> Hide Map';
        btn.style.background   = '#fee2e2';
        btn.style.borderColor  = '#fecaca';
        btn.style.color        = '#991b1b';
        btn.dataset.open = '1';

        // Init Leaflet only once per map
        if (!initialisedMaps[mapId]) {
            const map = L.map(mapId, { zoomControl: true, scrollWheelZoom: false }).setView([lat, lng], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(map);
            // Red marker
            const redIcon = L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
            });
            L.marker([lat, lng], {icon: redIcon})
             .addTo(map)
             .bindPopup('<strong>Incident Location</strong><br>Lat: ' + lat + '<br>Lng: ' + lng)
             .openPopup();
            initialisedMaps[mapId] = map;
        }
        // Fix Leaflet size after display:block
        setTimeout(() => initialisedMaps[mapId].invalidateSize(), 100);

    } else {
        wrap.style.display = 'none';
        gmapLink.classList.remove('show');
        btn.innerHTML = '<i class="bi bi-map-fill"></i> View Location on Map';
        btn.style.background  = '#f0f6ff';
        btn.style.borderColor = '#c5d8f5';
        btn.style.color       = 'var(--blue)';
        btn.dataset.open = '0';
    }
}
</script>
</body>
</html>