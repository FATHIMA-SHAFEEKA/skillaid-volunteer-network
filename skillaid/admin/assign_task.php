 <?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit(); }
if (!isset($_GET['id']))           { header("Location: dashboard.php"); exit(); }

$incident_id = intval($_GET['id']);

$stmt = mysqli_prepare($conn, "SELECT * FROM incident WHERE incident_id = ?");
mysqli_stmt_bind_param($stmt, "i", $incident_id);
mysqli_stmt_execute($stmt);
$incident = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$incident) { header("Location: dashboard.php"); exit(); }

$volunteers_needed  = intval($incident['volunteers_needed'] ?? 1);
$success_msg = $error_msg = "";

if (isset($_POST['assign'])) {
    $volunteer_id = intval($_POST['volunteer_id']);

    // Count currently ACCEPTED volunteers for this incident
    $acc_stmt = mysqli_prepare($conn, "
        SELECT COUNT(*) AS cnt FROM task_assignment
        WHERE incident_id = ? AND assignment_status = 'Accepted'
    ");
    mysqli_stmt_bind_param($acc_stmt, "i", $incident_id);
    mysqli_stmt_execute($acc_stmt);
    $accepted_count = mysqli_fetch_assoc(mysqli_stmt_get_result($acc_stmt))['cnt'];

    if ($accepted_count >= $volunteers_needed) {
        $error_msg = "All $volunteers_needed volunteer slot(s) for this incident are already filled.";
    } else {
        $dup = mysqli_prepare($conn, "SELECT assignment_id FROM task_assignment WHERE incident_id=? AND volunteer_id=?");
        mysqli_stmt_bind_param($dup, "ii", $incident_id, $volunteer_id);
        mysqli_stmt_execute($dup);
        if (mysqli_num_rows(mysqli_stmt_get_result($dup)) > 0) {
            $error_msg = "This volunteer is already assigned to this incident.";
        } else {
            $ins = mysqli_prepare($conn, "INSERT INTO task_assignment (volunteer_id, incident_id, assignment_status, assigned_date) VALUES (?,?,'Pending',NOW())");
            mysqli_stmt_bind_param($ins, "ii", $volunteer_id, $incident_id);
            if (mysqli_stmt_execute($ins)) {
                $upd = mysqli_prepare($conn, "UPDATE incident SET status='Assigned' WHERE incident_id=?");
                mysqli_stmt_bind_param($upd, "i", $incident_id);
                mysqli_stmt_execute($upd);
                $success_msg = "Volunteer assigned successfully!";
                $s2 = mysqli_prepare($conn, "SELECT * FROM incident WHERE incident_id=?");
                mysqli_stmt_bind_param($s2, "i", $incident_id);
                mysqli_stmt_execute($s2);
                $incident = mysqli_fetch_assoc(mysqli_stmt_get_result($s2));
            } else { $error_msg = "Assignment failed. Please try again."; }
        }
    }
}

// Current slot counts
$slot_stmt = mysqli_prepare($conn, "
    SELECT
        SUM(assignment_status = 'Accepted')  AS accepted,
        SUM(assignment_status = 'Pending')   AS pending,
        SUM(assignment_status = 'Completed') AS completed,
        COUNT(*)                             AS total_assigned
    FROM task_assignment WHERE incident_id = ?
");
mysqli_stmt_bind_param($slot_stmt, "i", $incident_id);
mysqli_stmt_execute($slot_stmt);
$slots = mysqli_fetch_assoc(mysqli_stmt_get_result($slot_stmt));
$accepted_count  = intval($slots['accepted']  ?? 0);
$slots_remaining = max(0, $volunteers_needed - $accepted_count);
$slots_full      = ($slots_remaining === 0);

// Skill matching
$skill_map = ['Medical'=>'Medical Assistance','Fire'=>'Fire Safety','Flood'=>'Search and Rescue',
              'Search'=>'Search and Rescue','Rescue'=>'Search and Rescue','Accident'=>'First Aid',
              'First Aid'=>'First Aid','Natural Disaster'=>'Search and Rescue','Landslide'=>'Search and Rescue'];
$required_skill = '';
foreach ($skill_map as $k => $s) {
    if (stripos($incident['incident_type'], $k) !== false) { $required_skill = $s; break; }
}

// Proximity zones
$proximity_zones = [
    'colombo'    => ['Colombo','Dehiwala','Nugegoda','Kotte','Maharagama','Moratuwa','Piliyandala','Battaramulla','Rajagiriya'],
    'gampaha'    => ['Gampaha','Biyagama','Kelaniya','Angoda','Wattala','Ja-Ela','Negombo','Kadawatha','Ragama','Peliyagoda','Minuwangoda','Mirigama'],
    'kalutara'   => ['Kalutara','Panadura','Horana','Matugama','Wadduwa','Beruwala','Aluthgama','Bandaragama'],
    'kandy'      => ['Kandy','Peradeniya','Katugastota','Gampola','Nawalapitiya','Matale','Dambulla','Kurunegala'],
    'galle'      => ['Galle','Hikkaduwa','Ambalangoda','Elpitiya'],
    'matara'     => ['Matara','Weligama','Akuressa','Deniyaya'],
    'hambantota' => ['Hambantota','Tangalle','Tissamaharama','Ambalantota'],
    'jaffna'     => ['Jaffna','Chavakachcheri','Kilinochchi','Mannar'],
    'ampara'     => ['Ampara','Kalmunai','Sammanthurai','Pottuvil'],
    'batticaloa' => ['Batticaloa','Kattankudy','Eravur'],
    'trincomalee'=> ['Trincomalee','Kinniya','Mutur'],
    'ratnapura'  => ['Ratnapura','Embilipitiya','Balangoda','Avissawella'],
    'kegalle'    => ['Kegalle','Mawanella','Warakapola','Ruwanwella'],
    'anuradhapura'=>['Anuradhapura','Kekirawa','Vavuniya'],
    'puttalam'   => ['Puttalam','Chilaw','Kuliyapitiya'],
];

function getZone($loc, $zones) {
    foreach ($zones as $zone => $areas) {
        foreach ($areas as $area) {
            if (stripos($loc, $area) !== false) return $zone;
        }
    }
    return null;
}

$incident_zone    = getZone($incident['location'], $proximity_zones);
$nearby_locations = $incident_zone ? $proximity_zones[$incident_zone] : [];
$proximity_matched = !empty($nearby_locations);

// Fetch volunteers — exclude already assigned to this incident
$not_assigned_sub = "SELECT ta.volunteer_id FROM task_assignment ta WHERE ta.incident_id = ?";

$nearby_volunteers   = [];
$fallback_volunteers = [];

if ($required_skill && $proximity_matched) {
    $like_conds  = array_map(fn($l) => "v.location LIKE ?", $nearby_locations);
    $loc_sql     = implode(' OR ', $like_conds);
    $like_params = array_map(fn($l) => "%$l%", $nearby_locations);

    $sql = "SELECT v.* FROM volunteer v WHERE v.skills=? AND ($loc_sql) AND v.volunteer_id NOT IN ($not_assigned_sub) ORDER BY v.availability='Anytime' DESC, v.full_name ASC";
    $p   = array_merge([$required_skill], $like_params, [$incident_id]);
    $t   = 's' . str_repeat('s', count($like_params)) . 'i';
    $st  = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($st, $t, ...$p);
    mysqli_stmt_execute($st);
    $nearby_volunteers = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);

    $sql2 = "SELECT v.* FROM volunteer v WHERE v.skills=? AND NOT ($loc_sql) AND v.volunteer_id NOT IN ($not_assigned_sub) ORDER BY v.availability='Anytime' DESC, v.full_name ASC";
    $st2  = mysqli_prepare($conn, $sql2);
    mysqli_stmt_bind_param($st2, $t, ...$p);
    mysqli_stmt_execute($st2);
    $fallback_volunteers = mysqli_fetch_all(mysqli_stmt_get_result($st2), MYSQLI_ASSOC);
} elseif ($required_skill) {
    $st = mysqli_prepare($conn, "SELECT v.* FROM volunteer v WHERE v.skills=? AND v.volunteer_id NOT IN ($not_assigned_sub) ORDER BY v.availability='Anytime' DESC, v.full_name ASC");
    mysqli_stmt_bind_param($st, "si", $required_skill, $incident_id);
    mysqli_stmt_execute($st);
    $nearby_volunteers = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);
} else {
    $st = mysqli_prepare($conn, "SELECT v.* FROM volunteer v WHERE v.volunteer_id NOT IN ($not_assigned_sub) ORDER BY v.availability='Anytime' DESC, v.full_name ASC");
    mysqli_stmt_bind_param($st, "i", $incident_id);
    mysqli_stmt_execute($st);
    $nearby_volunteers = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);
}

$aq = mysqli_prepare($conn, "SELECT v.full_name, v.skills, v.location, ta.assignment_status, ta.assigned_date FROM task_assignment ta JOIN volunteer v ON ta.volunteer_id=v.volunteer_id WHERE ta.incident_id=? ORDER BY ta.assigned_date DESC");
mysqli_stmt_bind_param($aq, "i", $incident_id);
mysqli_stmt_execute($aq);
$assigned_list = mysqli_fetch_all(mysqli_stmt_get_result($aq), MYSQLI_ASSOC);

$has_location = !empty($incident['latitude']) && !empty($incident['longitude']);
$lat = floatval($incident['latitude']  ?? 0);
$lng = floatval($incident['longitude'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Assign Volunteer | SkillAid Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<style>
:root { --navy:#0d2238; --blue:#0d6efd; --accent:#dc3545; --gold:#f0a500; --green:#22c55e; --bg:#f3f6fb; --muted:#8fa3ba; }
body { font-family:'DM Sans',sans-serif; background:var(--bg); }
.navbar { background:var(--navy); padding:12px 0; }
.navbar-brand { font-family:'Poppins',sans-serif; font-size:1.5rem; font-weight:700; color:#fff !important; display:flex; align-items:center; gap:10px; }
.navbar-brand img { height:42px; }
.nav-link { color:#e2e8f0 !important; font-weight:500; }
.nav-link:hover { color:#fff !important; }
.nav-divider { color:#64748b; margin:0 10px; }
.page-wrap { max-width:960px; margin:130px auto 60px; padding:0 16px; }

.incident-banner { background:linear-gradient(135deg,#0d2238 0%,#1a3a55 100%); border-radius:20px; padding:28px 32px; color:#fff; margin-bottom:28px; }
.inc-type { font-family:'Poppins',sans-serif; font-size:1.5rem; font-weight:700; margin:0 0 6px; }
.inc-meta { font-size:0.9rem; color:#94c9f5; }
.inc-badge { display:inline-block; padding:5px 16px; border-radius:20px; font-size:0.8rem; font-weight:600; }
.badge-Pending   { background:rgba(240,165,0,0.2);  color:#f0a500; border:1px solid rgba(240,165,0,0.4); }
.badge-Assigned  { background:rgba(13,110,253,0.2); color:#60a5fa; border:1px solid rgba(13,110,253,0.4); }
.badge-Completed { background:rgba(34,197,94,0.2);  color:#22c55e; border:1px solid rgba(34,197,94,0.4); }

/* SLOTS BAR */
.slots-bar {
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 14px;
    padding: 16px 20px;
    margin-top: 16px;
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}
.slot-item { text-align:center; }
.slot-num { font-family:'Poppins',sans-serif; font-size:1.8rem; font-weight:800; line-height:1; }
.slot-lbl { font-size:0.7rem; color:rgba(255,255,255,0.5); text-transform:uppercase; letter-spacing:0.5px; margin-top:2px; }
.slot-divider { width:1px; height:40px; background:rgba(255,255,255,0.15); }

.slots-progress-wrap { flex-grow:1; }
.slots-progress-label { display:flex; justify-content:space-between; font-size:0.8rem; color:rgba(255,255,255,0.6); margin-bottom:6px; }
.slots-progress-bg { height:10px; background:rgba(255,255,255,0.1); border-radius:10px; overflow:hidden; }
.slots-progress-fill { height:100%; border-radius:10px; transition:width 0.5s ease; }

.full-badge { background:rgba(34,197,94,0.2); border:1px solid rgba(34,197,94,0.4); color:#22c55e; padding:6px 16px; border-radius:20px; font-size:0.8rem; font-weight:700; display:inline-flex; align-items:center; gap:6px; }

#incident-map { width:100%; height:200px; margin-top:16px; border-radius:14px; overflow:hidden; border:2px solid rgba(255,255,255,0.2); }
.map-meta { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-top:10px; }
.map-coord { font-size:0.8rem; color:rgba(255,255,255,0.6); display:flex; align-items:center; gap:5px; }
.btn-gmaps { display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,0.12); border:1px solid rgba(255,255,255,0.2); color:#fff; padding:7px 14px; border-radius:10px; font-size:0.8rem; font-weight:600; text-decoration:none; transition:background 0.2s; }
.btn-gmaps:hover { background:rgba(255,255,255,0.22); color:#fff; }
.no-gps { margin-top:12px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); border-radius:10px; padding:9px 14px; font-size:0.8rem; color:rgba(255,255,255,0.45); display:flex; align-items:center; gap:6px; }

.section-card { background:#fff; border-radius:20px; padding:28px 32px; box-shadow:0 2px 16px rgba(13,34,56,0.07); border:1.5px solid #e8eef5; margin-bottom:24px; }
.section-title { font-family:'Poppins',sans-serif; font-weight:600; font-size:1.05rem; color:var(--navy); margin-bottom:20px; display:flex; align-items:center; gap:10px; }
.s-icon { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1rem; }
.i-blue  { background:#e7f0ff; color:var(--blue); }
.i-green { background:#e6f9f0; color:var(--green); }
.i-gold  { background:#fff8e1; color:var(--gold); }

.slots-full-notice { background:#f0fdf4; border:1.5px solid #bbf7d0; border-radius:12px; padding:16px 20px; color:#166534; font-size:0.9rem; display:flex; align-items:center; gap:10px; margin-bottom:16px; }

.match-banner { border-radius:12px; padding:12px 18px; margin-bottom:16px; font-size:0.88rem; display:flex; align-items:center; gap:8px; }
.banner-primary  { background:#e7f0ff; border:1.5px solid #c5d8f5; color:var(--navy); }
.banner-warning  { background:#fff8e1; border:1.5px solid #fde68a; color:#854d0e; }

.vol-group-label { font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.8px; padding:5px 12px; border-radius:8px; margin-bottom:10px; display:inline-flex; align-items:center; gap:6px; }
.label-nearby   { background:#dcfce7; color:#166534; }
.label-fallback { background:#fef9c3; color:#854d0e; }

.vol-option { border:2px solid #e8eef5; border-radius:14px; padding:14px 18px; margin-bottom:8px; cursor:pointer; transition:all 0.2s; display:flex; align-items:center; gap:14px; }
.vol-option:hover { border-color:var(--blue); background:#f0f6ff; }
.vol-option.selected { border-color:var(--blue); background:#eef4ff; }
.vol-option input[type="radio"] { width:18px; height:18px; accent-color:var(--blue); flex-shrink:0; }
.vol-name { font-weight:600; font-size:0.95rem; color:var(--navy); margin:0; }
.vol-meta { font-size:0.8rem; color:var(--muted); margin:3px 0 0; }
.avail-badge { font-size:0.72rem; padding:3px 10px; border-radius:10px; font-weight:600; white-space:nowrap; margin-left:auto; flex-shrink:0; }
.avail-Anytime  { background:#dcfce7; color:#166534; }
.avail-Weekends { background:#fef9c3; color:#854d0e; }
.dist-badge { font-size:0.7rem; padding:2px 8px; border-radius:8px; font-weight:600; background:#e7f0ff; color:#1e3a8a; margin-left:6px; }

.assigned-row { display:flex; align-items:center; gap:12px; padding:12px 0; border-bottom:1px solid #eef2f7; }
.assigned-row:last-child { border-bottom:none; }
.a-status { font-size:0.75rem; padding:3px 10px; border-radius:10px; font-weight:600; margin-left:auto; }
.s-Pending   { background:#fde68a; color:#92400e; }
.s-Accepted  { background:#bfdbfe; color:#1e3a8a; }
.s-Completed { background:#bbf7d0; color:#065f46; }
.s-Declined  { background:#fecaca; color:#7f1d1d; }

.btn-assign { background:var(--blue); color:#fff; border:none; border-radius:12px; padding:12px 36px; font-weight:600; font-size:1rem; cursor:pointer; transition:0.2s; }
.btn-assign:hover { background:#0b5ed7; box-shadow:0 4px 16px rgba(13,110,253,0.35); }
.btn-back { background:transparent; color:var(--navy); border:1.5px solid #c5d8f5; border-radius:12px; padding:12px 28px; font-weight:600; text-decoration:none; transition:0.2s; }
.btn-back:hover { background:var(--navy); color:#fff; }

footer { background:var(--navy); color:#cbd5e1; padding:40px 0 24px; margin-top:60px; }
footer a { color:#cbd5e1; text-decoration:none; }
footer a:hover { color:#fff; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
<div class="container-fluid px-4">
    <a class="navbar-brand" href="../index.php"><img src="../assets/images/logo.png" alt="SkillAid"> SkillAid Admin</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse justify-content-end" id="nav">
        <ul class="navbar-nav align-items-center">
            <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
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

    <!-- Incident Banner -->
    <div class="incident-banner">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <p class="inc-meta mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>Incident #<?= $incident_id ?></p>
                <h2 class="inc-type"><?= htmlspecialchars($incident['incident_type']) ?></h2>
                <p class="inc-meta mb-2"><?= htmlspecialchars($incident['description']) ?></p>
                <p class="inc-meta mb-0">
                    <i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($incident['location']) ?>
                    &nbsp;·&nbsp;<i class="bi bi-clock me-1"></i><?= $incident['reported_time'] ?? 'N/A' ?>
                </p>
            </div>
            <div class="d-flex flex-column align-items-end gap-2">
                <span class="inc-badge badge-<?= $incident['status'] ?>"><?= htmlspecialchars($incident['status']) ?></span>
                <?php if ($slots_full): ?>
                    <span class="full-badge"><i class="bi bi-check-circle-fill"></i> All Slots Filled</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Slots Progress Bar -->
        <div class="slots-bar">
            <div class="slot-item">
                <div class="slot-num" style="color:#fde68a;"><?= $volunteers_needed ?></div>
                <div class="slot-lbl">Needed</div>
            </div>
            <div class="slot-divider"></div>
            <div class="slot-item">
                <div class="slot-num" style="color:#86efac;"><?= $accepted_count ?></div>
                <div class="slot-lbl">Accepted</div>
            </div>
            <div class="slot-divider"></div>
            <div class="slot-item">
                <div class="slot-num" style="color:#93c5fd;"><?= $slots_remaining ?></div>
                <div class="slot-lbl">Remaining</div>
            </div>
            <div class="slots-progress-wrap">
                <?php $pct = $volunteers_needed > 0 ? round(($accepted_count / $volunteers_needed) * 100) : 0; ?>
                <div class="slots-progress-label">
                    <span>Volunteer Slots</span>
                    <span><?= $accepted_count ?> / <?= $volunteers_needed ?></span>
                </div>
                <div class="slots-progress-bg">
                    <div class="slots-progress-fill"
                         style="width:<?= $pct ?>%;
                                background:<?= $slots_full ? '#22c55e' : 'linear-gradient(90deg,#60a5fa,#0d6efd)' ?>;">
                    </div>
                </div>
            </div>
        </div>

        <?php if ($has_location): ?>
            <div id="incident-map"></div>
            <div class="map-meta">
                <span class="map-coord"><i class="bi bi-pin-map-fill"></i> GPS: <?= number_format($lat,6) ?>, <?= number_format($lng,6) ?></span>
                <a href="https://www.google.com/maps?q=<?= $lat ?>,<?= $lng ?>" target="_blank" class="btn-gmaps">
                    <i class="bi bi-map-fill"></i> Open in Google Maps
                </a>
            </div>
        <?php else: ?>
            <div class="no-gps"><i class="bi bi-info-circle"></i> No GPS coordinates — text location only.</div>
        <?php endif; ?>
    </div>

    <?php if ($success_msg): ?><div class="alert alert-success rounded-4 mb-4"><i class="bi bi-check-circle-fill me-2"></i><?= $success_msg ?></div><?php endif; ?>
    <?php if ($error_msg):   ?><div class="alert alert-danger rounded-4 mb-4"><i class="bi bi-exclamation-circle-fill me-2"></i><?= $error_msg ?></div><?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="section-card">
                <div class="section-title"><div class="s-icon i-blue"><i class="bi bi-stars"></i></div>Smart Volunteer Matching</div>

                <?php if ($slots_full): ?>
                    <div class="slots-full-notice">
                        <i class="bi bi-check-circle-fill" style="font-size:1.4rem;"></i>
                        <div>
                            <strong>All <?= $volunteers_needed ?> volunteer slot(s) are filled!</strong><br>
                            <span style="font-size:0.85rem;">This incident has enough accepted volunteers. You can still assign more if a volunteer declines.</span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($required_skill): ?>
                    <div class="match-banner banner-primary">
                        <i class="bi bi-funnel-fill text-primary"></i>
                        <div>
                            <strong>Skill:</strong> <?= htmlspecialchars($required_skill) ?>
                            <?php if ($incident_zone): ?>&nbsp;·&nbsp;<strong>Zone:</strong> <?= ucfirst($incident_zone) ?><?php endif; ?>
                            &nbsp;·&nbsp;<strong><?= $slots_remaining ?> slot(s) remaining</strong>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="match-banner banner-warning">
                        <i class="bi bi-info-circle-fill"></i>
                        No exact skill match — showing nearby volunteers. <?= $slots_remaining ?> slot(s) remaining.
                    </div>
                <?php endif; ?>

                <?php if (empty($nearby_volunteers) && empty($fallback_volunteers)): ?>
                    <div class="text-center py-4" style="color:var(--muted);">
                        <i class="bi bi-person-x" style="font-size:2.5rem;"></i>
                        <p class="mt-2 mb-0">No matching volunteers available.</p>
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <?php if (!empty($nearby_volunteers)): ?>
                            <div class="vol-group-label label-nearby">
                                <i class="bi bi-geo-alt-fill"></i>
                                Nearby & Skill Matched (<?= count($nearby_volunteers) ?>)
                            </div>
                            <?php foreach ($nearby_volunteers as $v): ?>
                                <label class="vol-option" for="vol_<?= $v['volunteer_id'] ?>">
                                    <input type="radio" name="volunteer_id" id="vol_<?= $v['volunteer_id'] ?>" value="<?= $v['volunteer_id'] ?>" required>
                                    <div class="flex-grow-1">
                                        <p class="vol-name"><?= htmlspecialchars($v['full_name']) ?> <span class="dist-badge"><?= htmlspecialchars($v['location']) ?></span></p>
                                        <p class="vol-meta"><i class="bi bi-stars me-1"></i><?= htmlspecialchars($v['skills']) ?> &nbsp;·&nbsp;<i class="bi bi-car-front me-1"></i><?= htmlspecialchars($v['transport_mode']) ?></p>
                                    </div>
                                    <span class="avail-badge avail-<?= htmlspecialchars($v['availability']) ?>"><?= htmlspecialchars($v['availability']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if (!empty($fallback_volunteers)): ?>
                            <div class="vol-group-label label-fallback" style="margin-top:16px;">
                                <i class="bi bi-arrow-right-circle"></i>
                                Other Areas (<?= count($fallback_volunteers) ?>)
                            </div>
                            <?php foreach ($fallback_volunteers as $v): ?>
                                <label class="vol-option" for="vol_<?= $v['volunteer_id'] ?>" style="opacity:0.85;border-style:dashed;">
                                    <input type="radio" name="volunteer_id" id="vol_<?= $v['volunteer_id'] ?>" value="<?= $v['volunteer_id'] ?>">
                                    <div class="flex-grow-1">
                                        <p class="vol-name"><?= htmlspecialchars($v['full_name']) ?> <span class="dist-badge" style="background:#fef9c3;color:#854d0e;"><?= htmlspecialchars($v['location']) ?></span></p>
                                        <p class="vol-meta"><i class="bi bi-stars me-1"></i><?= htmlspecialchars($v['skills']) ?> &nbsp;·&nbsp;<i class="bi bi-car-front me-1"></i><?= htmlspecialchars($v['transport_mode']) ?></p>
                                    </div>
                                    <span class="avail-badge avail-<?= htmlspecialchars($v['availability']) ?>"><?= htmlspecialchars($v['availability']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <div class="d-flex gap-3 mt-4">
                            <button type="submit" name="assign" class="btn-assign"><i class="bi bi-person-check-fill me-2"></i>Assign Volunteer</button>
                            <a href="dashboard.php" class="btn-back">Cancel</a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="section-card">
                <div class="section-title"><div class="s-icon i-gold"><i class="bi bi-person-lines-fill"></i></div>Assignment History</div>
                <?php if (empty($assigned_list)): ?>
                    <div class="text-center py-3" style="color:var(--muted);"><i class="bi bi-inbox" style="font-size:2rem;"></i><p class="mt-2 mb-0 small">No volunteers assigned yet.</p></div>
                <?php else: ?>
                    <?php foreach ($assigned_list as $a): ?>
                        <div class="assigned-row">
                            <div>
                                <div style="font-weight:600;font-size:0.9rem;color:var(--navy);"><?= htmlspecialchars($a['full_name']) ?></div>
                                <div style="font-size:0.78rem;color:var(--muted);"><?= htmlspecialchars($a['skills']) ?> · <?= htmlspecialchars($a['location']) ?><?php if ($a['assigned_date']): ?> · <?= date("M d, Y", strtotime($a['assigned_date'])) ?><?php endif; ?></div>
                            </div>
                            <span class="a-status s-<?= $a['assignment_status'] ?>"><?= htmlspecialchars($a['assignment_status']) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="section-card">
                <div class="section-title"><div class="s-icon i-green"><i class="bi bi-info-circle-fill"></i></div>Incident Summary</div>
                <div style="font-size:0.88rem;">
                    <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid #eef2f7;"><span style="color:var(--muted);">Type</span><span style="font-weight:600;"><?= htmlspecialchars($incident['incident_type']) ?></span></div>
                    <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid #eef2f7;"><span style="color:var(--muted);">Location</span><span style="font-weight:600;"><?= htmlspecialchars($incident['location']) ?></span></div>
                    <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid #eef2f7;"><span style="color:var(--muted);">Volunteers Needed</span><span style="font-weight:700;color:var(--blue);"><?= $volunteers_needed ?></span></div>
                    <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid #eef2f7;"><span style="color:var(--muted);">Slots Remaining</span><span style="font-weight:700;color:<?= $slots_full ? '#22c55e' : 'var(--accent)' ?>;"><?= $slots_remaining ?> <?= $slots_full ? '✓ Full' : '' ?></span></div>
                    <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid #eef2f7;"><span style="color:var(--muted);">Status</span><span style="font-weight:600;"><?= htmlspecialchars($incident['status']) ?></span></div>
                    <div class="d-flex justify-content-between py-2"><span style="color:var(--muted);">Skill Needed</span><span style="font-weight:600;"><?= $required_skill ?: 'Any' ?></span></div>
                </div>
                <?php if ($has_location): ?>
                    <a href="https://www.google.com/maps?q=<?= $lat ?>,<?= $lng ?>" target="_blank" class="btn btn-sm btn-outline-primary w-100 mt-3" style="border-radius:10px;font-weight:600;">
                        <i class="bi bi-map me-1"></i>Open in Google Maps
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<footer>
<div class="container">
    <div class="row gy-3">
        <div class="col-md-4"><h5 class="fw-bold text-white">SkillAid Admin</h5><p class="small">Emergency Volunteer Coordination</p></div>
        <div class="col-md-4"><h6 class="fw-bold text-white">Admin Panel</h6><p class="small mb-1"><a href="dashboard.php">Dashboard</a></p><p class="small mb-1"><a href="manage_volunteers.php">Volunteers</a></p><p class="small mb-1"><a href="reports.php">Reports</a></p></div>
        <div class="col-md-4"><h6 class="fw-bold text-white">Contact</h6><p class="small mb-1">skillaid@gmail.com</p><p class="small mb-1">Hotline: 199</p></div>
    </div>
    <hr style="border-color:rgba(203,213,225,0.25);" class="mt-3">
    <p class="text-center small mb-0">© 2026 <strong>SkillAid</strong>. All Rights Reserved.</p>
</div>
</footer>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
<?php if ($has_location): ?>
document.addEventListener('DOMContentLoaded', function() {
    const map = L.map('incident-map', { scrollWheelZoom:false }).setView([<?= $lat ?>, <?= $lng ?>], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution:'© OpenStreetMap', maxZoom:19 }).addTo(map);
    const redIcon = L.icon({
        iconUrl:'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
        shadowUrl:'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize:[25,41], iconAnchor:[12,41], popupAnchor:[1,-34], shadowSize:[41,41]
    });
    L.marker([<?= $lat ?>, <?= $lng ?>], {icon:redIcon}).addTo(map)
     .bindPopup('<strong><?= htmlspecialchars(addslashes($incident['incident_type'])) ?></strong><br><?= htmlspecialchars(addslashes($incident['location'])) ?>').openPopup();
});
<?php endif; ?>
document.querySelectorAll('.vol-option input[type="radio"]').forEach(r => {
    r.addEventListener('change', () => {
        document.querySelectorAll('.vol-option').forEach(c => c.classList.remove('selected'));
        r.closest('.vol-option').classList.add('selected');
    });
});
</script>
</body>
</html>