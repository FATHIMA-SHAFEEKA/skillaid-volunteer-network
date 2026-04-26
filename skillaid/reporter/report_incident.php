 <?php
include("../config/db.php");

$success = false;
$error   = "";

if (isset($_POST['submit'])) {
    $name              = trim($_POST['name']              ?? '');
    $contact           = trim($_POST['contact']           ?? '');
    $incident_type     = trim($_POST['incident_type']     ?? '');
    $description       = trim($_POST['description']       ?? '');
    $location          = trim($_POST['location']          ?? '');
    $volunteers_needed = intval($_POST['volunteers_needed'] ?? 1);
    $latitude          = trim($_POST['latitude']          ?? '');
    $longitude         = trim($_POST['longitude']         ?? '');

    if ($volunteers_needed < 1) $volunteers_needed = 1;

    if ($name && $contact && $incident_type && $description && $location) {
        $stmt1 = mysqli_prepare($conn, "INSERT INTO reporter (name, contact_info) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt1, "ss", $name, $contact);
        mysqli_stmt_execute($stmt1);
        $reporter_id = mysqli_insert_id($conn);

        $lat = $latitude  !== '' ? floatval($latitude)  : null;
        $lng = $longitude !== '' ? floatval($longitude) : null;

        $stmt2 = mysqli_prepare($conn, "
    INSERT INTO incident (incident_type, description, location, latitude, longitude, volunteers_needed, reporter_id)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
mysqli_stmt_bind_param($stmt2, "sssddii",
    $incident_type, $description, $location, $lat, $lng, $volunteers_needed, $reporter_id);

        if (mysqli_stmt_execute($stmt2)) {
            $success = true;
        } else {
            $error = "Something went wrong. Please try again.";
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Report Incident | SkillAid</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<style>
:root { --navy:#0d2238; --blue:#0d6efd; --red:#dc3545; --bg:#f0f4f8; --muted:#8fa3ba; }
* { box-sizing:border-box; }
body { font-family:'DM Sans',sans-serif; background:var(--bg); min-height:100vh; }

.navbar { background:var(--navy); padding:12px 0; }
.navbar-brand { font-family:'Poppins',sans-serif; font-size:1.5rem; font-weight:700; color:#fff !important; display:flex; align-items:center; gap:10px; text-decoration:none; }
.navbar-brand img { height:42px; }
.nav-link { color:#e2e8f0 !important; font-weight:500; font-size:0.9rem; }
.nav-link:hover { color:#fff !important; }
.nav-divider { color:#64748b; margin:0 8px; }

.page-wrap { padding:100px 16px 60px; }

.report-hero {
    background:linear-gradient(135deg,#7f0000 0%,#c0392b 50%,#e02030 100%);
    border-radius:20px; padding:32px 40px; margin-bottom:28px;
    display:flex; align-items:center; justify-content:space-between;
    flex-wrap:wrap; gap:20px; position:relative; overflow:hidden;
}
.report-hero::after { content:''; position:absolute; right:-40px; top:-40px; width:180px; height:180px; border-radius:50%; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); }
.report-hero::before { content:''; position:absolute; left:-30px; bottom:-30px; width:120px; height:120px; border-radius:50%; background:rgba(255,255,255,0.04); }
.hero-badge-red { display:inline-flex; align-items:center; gap:7px; background:rgba(255,255,255,0.15); border:1px solid rgba(255,255,255,0.25); color:#fff; padding:6px 14px; border-radius:20px; font-size:0.72rem; font-weight:700; letter-spacing:1px; text-transform:uppercase; margin-bottom:10px; width:fit-content; }
.hero-badge-red .dot { width:7px; height:7px; border-radius:50%; background:#fff; animation:blink 1.5s infinite; }
@keyframes blink { 0%,100%{opacity:1;} 50%{opacity:0.3;} }
.report-hero h2 { font-family:'Poppins',sans-serif; font-weight:700; font-size:1.6rem; color:#fff; margin:0 0 6px; }
.report-hero p { color:rgba(255,255,255,0.75); font-size:0.9rem; margin:0; }
.hotline-box { background:rgba(255,255,255,0.12); border:1px solid rgba(255,255,255,0.2); border-radius:14px; padding:18px 24px; text-align:center; color:#fff; position:relative; z-index:1; min-width:160px; }
.hotline-box .hl-label { font-size:0.72rem; text-transform:uppercase; letter-spacing:1px; color:rgba(255,255,255,0.65); margin-bottom:4px; }
.hotline-box .hl-num { font-family:'Poppins',sans-serif; font-size:2.4rem; font-weight:800; line-height:1; }
.hotline-box .hl-sub { font-size:0.72rem; color:rgba(255,255,255,0.55); margin-top:3px; }

.form-card { background:#fff; border-radius:20px; padding:36px 40px; box-shadow:0 4px 24px rgba(13,34,56,0.09); border:1px solid #e2e8f0; }

.section-label { font-family:'Poppins',sans-serif; font-weight:600; font-size:0.92rem; color:var(--navy); display:flex; align-items:center; gap:8px; padding-bottom:12px; border-bottom:1.5px solid #eef2f7; margin-bottom:20px; }
.section-num { width:26px; height:26px; border-radius:50%; background:var(--navy); color:#fff; font-size:0.72rem; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }

/* INCIDENT CARDS */
.incident-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(175px,1fr)); gap:10px; margin-bottom:6px; }
.incident-card { border:2px solid #e8eef5; border-radius:12px; padding:14px 16px; cursor:pointer; transition:all 0.2s; display:flex; align-items:center; gap:10px; user-select:none; position:relative; }
.incident-card:hover { border-color:var(--red); background:#fff5f5; }
.incident-card.selected { border-color:var(--red); background:#fff5f5; }
.incident-card input[type="radio"] { position:absolute; opacity:0; width:0; height:0; }
.inc-emoji { font-size:1.4rem; flex-shrink:0; }
.inc-label { font-weight:600; font-size:0.85rem; color:var(--navy); }
.inc-check { width:18px; height:18px; border-radius:50%; border:2px solid #e2e8f0; margin-left:auto; flex-shrink:0; display:flex; align-items:center; justify-content:center; transition:all 0.2s; font-size:0.65rem; }
.incident-card.selected .inc-check { background:var(--red); border-color:var(--red); color:#fff; }

/* FIELDS */
.field-label { font-size:0.8rem; font-weight:600; color:#4a5568; margin-bottom:6px; display:block; }
.req { color:var(--red); }
.input-wrap { position:relative; }
.input-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#a0aec0; font-size:0.9rem; pointer-events:none; }
.input-icon-top { top:16px; transform:none; }
.field-input, .field-textarea { width:100%; border:1.5px solid #e2e8f0; border-radius:12px; padding:12px 14px 12px 42px; font-family:'DM Sans',sans-serif; font-size:0.92rem; color:#1a2a3a; background:#f8fafc; outline:none; transition:border-color 0.2s,box-shadow 0.2s,background 0.2s; }
.field-textarea { padding:12px 14px 12px 42px; resize:vertical; min-height:110px; }
.field-input:focus,.field-textarea:focus { border-color:var(--blue); background:#fff; box-shadow:0 0 0 3px rgba(13,110,253,0.09); }
.field-input::placeholder,.field-textarea::placeholder { color:#c0ccd8; }

/* vol counter removed */

/* GPS */
.location-box { background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:14px; padding:20px; margin-top:4px; }
.location-box-title { font-size:0.85rem; font-weight:600; color:var(--navy); margin-bottom:14px; display:flex; align-items:center; gap:8px; }
.btn-gps { background:var(--navy); color:#fff; border:none; border-radius:10px; padding:10px 20px; font-size:0.88rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:8px; transition:background 0.2s,transform 0.15s; white-space:nowrap; }
.btn-gps:hover { background:#1a3a55; transform:translateY(-1px); }
.btn-gps.loading { background:#64748b; cursor:not-allowed; }
.gps-status { font-size:0.8rem; padding:6px 12px; border-radius:8px; display:none; align-items:center; gap:6px; }
.gps-status.show { display:flex; }
.gps-status.ok    { background:#dcfce7; color:#166534; }
.gps-status.error { background:#fee2e2; color:#991b1b; }
.gps-status.info  { background:#e7f0ff; color:#1e3a8a; }
.coord-display { display:none; background:#e7f0ff; border:1px solid #c5d8f5; border-radius:10px; padding:10px 14px; font-size:0.82rem; color:var(--navy); margin-top:12px; align-items:center; gap:8px; }
.coord-display.show { display:flex; }
#gps-map-preview { display:none; height:200px; margin-top:14px; border-radius:12px; overflow:hidden; border:1.5px solid #c5d8f5; }
.gmaps-nav-link { display:none; align-items:center; gap:6px; font-size:0.8rem; font-weight:600; color:var(--blue); text-decoration:none; margin-top:8px; }
.gmaps-nav-link.show { display:flex; }

/* ALERTS */
.alert-error-custom { background:#fff5f5; border:1px solid #fed7d7; border-radius:12px; padding:12px 16px; color:#c53030; font-size:0.88rem; display:flex; align-items:center; gap:8px; margin-bottom:20px; }
.alert-success-custom { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:16px; padding:40px 24px; text-align:center; }

/* BUTTONS */
.btn-report { background:var(--red); color:#fff; border:none; border-radius:14px; padding:14px 44px; font-family:'DM Sans',sans-serif; font-size:1rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:8px; box-shadow:0 4px 16px rgba(220,53,69,0.3); transition:background 0.2s,box-shadow 0.2s,transform 0.15s; }
.btn-report:hover { background:#bb2d3b; transform:translateY(-1px); }
.btn-cancel { border-radius:14px; padding:14px 32px; font-weight:500; text-decoration:none; color:#64748b; border:1.5px solid #e2e8f0; background:#fff; font-size:1rem; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; }
.btn-cancel:hover { background:#f1f5f9; color:var(--navy); }

footer { background:var(--navy); color:#cbd5e1; padding:40px 0 20px; margin-top:60px; }
footer a { color:#cbd5e1; text-decoration:none; font-size:0.88rem; }
footer a:hover { color:#fff; }
@keyframes spin { from{transform:rotate(0deg);} to{transform:rotate(360deg);} }
.spin { display:inline-block; animation:spin 0.8s linear infinite; }
@media(max-width:768px) { .form-card{padding:24px 18px;} .report-hero{padding:24px 20px;} .incident-grid{grid-template-columns:repeat(2,1fr);} }
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
            <li class="nav-item"><a class="nav-link" style="color:#fca5a5 !important;font-weight:600;" href="#">Report Emergency</a></li>
            <span class="nav-divider">|</span>
            <li class="nav-item"><a class="nav-link" href="../volunteer/register.php">Become a Volunteer</a></li>
            <span class="nav-divider">|</span>
            <li class="nav-item"><a class="nav-link" href="../index.php#contact">Contact</a></li>
        </ul>
    </div>
</div>
</nav>

<div class="page-wrap">
<div class="container" style="max-width:860px;">

    <div class="report-hero">
        <div style="position:relative;z-index:1;">
            <div class="hero-badge-red"><span class="dot"></span> Emergency Reporting</div>
            <h2>Report an Emergency</h2>
            <p>Fill in the details below. Our coordinators will<br>dispatch the nearest volunteers immediately.</p>
        </div>
        <div class="hotline-box">
            <div class="hl-label">Emergency Hotline</div>
            <div class="hl-num">199</div>
            <div class="hl-sub">Life-threatening situation?<br>Call immediately</div>
        </div>
    </div>

    <?php if ($success): ?>
    <div class="form-card">
        <div class="alert-success-custom">
            <i class="bi bi-check-circle-fill" style="font-size:3rem;color:#22c55e;display:block;margin-bottom:16px;"></i>
            <h4 class="fw-bold mb-2" style="font-family:'Poppins',sans-serif;color:#166534;">Incident Reported Successfully!</h4>
            <p class="mb-4" style="font-size:0.95rem;color:#166534;max-width:400px;margin:0 auto 20px;">
                Your report has been received. Our coordinators are reviewing it and will dispatch volunteers shortly.
            </p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="../index.php" class="btn btn-success px-4" style="border-radius:10px;font-weight:600;">
                    <i class="bi bi-house me-1"></i> Back to Home
                </a>
                <a href="report_incident.php" class="btn btn-outline-success px-4" style="border-radius:10px;font-weight:600;">
                    <i class="bi bi-plus-circle me-1"></i> Report Another
                </a>
            </div>
        </div>
    </div>

    <?php else: ?>
    <div class="form-card">

        <?php if ($error): ?>
            <div class="alert-error-custom">
                <i class="bi bi-exclamation-circle-fill"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="reportForm">
            <input type="hidden" name="latitude"            id="latitude"             value="">
            <input type="hidden" name="longitude"           id="longitude"            value="">
            <input type="hidden" name="incident_type"       id="incident_type_hidden" value="">
            <!-- volunteers_needed is now a direct number input below -->

            <!-- SECTION 1 -->
            <div class="section-label"><div class="section-num">1</div>Your Details</div>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="field-label">Full Name <span class="req">*</span></label>
                    <div class="input-wrap">
                        <i class="bi bi-person input-icon"></i>
                        <input type="text" name="name" id="name" class="field-input" placeholder="Your full name" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="field-label">Contact Number / Email <span class="req">*</span></label>
                    <div class="input-wrap">
                        <i class="bi bi-telephone input-icon"></i>
                        <input type="text" name="contact" id="contact" class="field-input" placeholder="+94 77 000 0000">
                    </div>
                </div>
            </div>

            <!-- SECTION 2 -->
            <div class="section-label"><div class="section-num">2</div>Type of Emergency <span style="font-size:0.78rem;color:var(--muted);font-weight:400;">— Select one</span></div>
            <div class="incident-grid mb-4">
                <?php
                $types = [
                    ['Flood','🌊','Flood'],
                    ['Fire','🔥','Fire'],
                    ['Accident','🚗','Accident'],
                    ['Medical Emergency','🏥','Medical'],
                    ['Landslide','⛰️','Landslide'],
                    ['Search and Rescue','🔍','Search & Rescue'],
                    ['Other','📋','Other'],
                ];
                foreach ($types as $t):
                ?>
                <label class="incident-card" onclick="selectIncident(this,'<?= $t[0] ?>')">
                    <input type="radio" name="incident_type_radio" value="<?= $t[0] ?>">
                    <span class="inc-emoji"><?= $t[1] ?></span>
                    <div class="inc-label"><?= $t[2] ?></div>
                    <div class="inc-check"></div>
                </label>
                <?php endforeach; ?>
            </div>

            <!-- SECTION 3 -->
            <div class="section-label"><div class="section-num">3</div>What Happened?</div>
            <div class="mb-4">
                <label class="field-label">Description <span class="req">*</span></label>
                <div class="input-wrap">
                    <i class="bi bi-card-text input-icon input-icon-top"></i>
                    <textarea name="description" id="description" class="field-textarea"
                              placeholder="Describe the situation — how many people are affected, how urgent it is..." required></textarea>
                </div>
            </div>

            <!-- SECTION 4 — VOLUNTEERS NEEDED -->
            <div class="section-label"><div class="section-num">4</div>How Many Volunteers Do You Need?</div>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="field-label">Number of Volunteers Required <span class="req">*</span></label>
                    <div class="input-wrap">
                        <i class="bi bi-people-fill input-icon"></i>
                        <input type="number" name="volunteers_needed" id="volunteers_needed"
                               class="field-input" placeholder="e.g. 3" min="1" value="1" required>
                    </div>
                    <p style="font-size:0.78rem;color:var(--muted);margin-top:6px;">
                        <i class="bi bi-info-circle me-1"></i>
                        Type how many volunteers are needed at the scene.
                    </p>
                </div>
            </div>

            <!-- SECTION 5 -->
            <div class="section-label"><div class="section-num">5</div>Location</div>
            <div class="mb-3">
                <label class="field-label">City / Area <span class="req">*</span></label>
                <div class="input-wrap">
                    <i class="bi bi-geo-alt input-icon"></i>
                    <input type="text" name="location" id="location" class="field-input"
                           placeholder="e.g. Biyagama, Colombo 03, Kandy town..." required>
                </div>
            </div>

            <div class="location-box">
                <div class="location-box-title">
                    <i class="bi bi-crosshair2" style="color:var(--blue);"></i>
                    Share Your GPS Location
                    <span style="color:var(--muted);font-weight:400;font-size:0.78rem;">(optional — helps volunteers find you faster)</span>
                </div>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <button type="button" class="btn-gps" id="gpsBtn" onclick="getLocation()">
                        <i class="bi bi-crosshair2"></i> Use My Location
                    </button>
                    <div class="gps-status" id="gpsStatus"></div>
                </div>
                <div class="coord-display" id="coordDisplay">
                    <i class="bi bi-pin-map-fill" style="color:var(--blue);"></i>
                    <span id="coordText"></span>
                </div>
                <div id="gps-map-preview"></div>
                <a id="gmapsNavLink" href="#" target="_blank" class="gmaps-nav-link">
                    <i class="bi bi-map-fill"></i> Open in Google Maps
                </a>
            </div>

            <div class="d-flex justify-content-center gap-3 flex-wrap mt-4">
                <button type="submit" name="submit" class="btn-report">
                    <i class="bi bi-send-fill"></i> Submit Report
                </button>
                <a href="../index.php" class="btn-cancel">
                    <i class="bi bi-arrow-left"></i> Cancel
                </a>
            </div>
        </form>
    </div>
    <?php endif; ?>

</div>
</div>

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
            <a href="#" class="d-block mb-1">Report Emergency</a>
            <a href="../volunteer/register.php" class="d-block mb-1">Become a Volunteer</a>
            <a href="../volunteer/login.php" class="d-block">Volunteer Login</a>
        </div>
        <div class="col-md-4">
            <h6 class="fw-bold text-white mb-2">Contact</h6>
            <p class="small mb-1"><strong>Email:</strong> <a href="mailto:skillaid@gmail.com">skillaid@gmail.com</a></p>
            <p class="small mb-1"><strong>Phone:</strong> +94 76 155 8321</p>
            <p class="small mb-0">Final Year Software Engineering Project</p>
        </div>
    </div>
    <hr style="border-color:rgba(203,213,225,0.2);" class="mt-3">
    <p class="text-center small mb-0" style="color:#475569;">© 2026 <strong style="color:#94a3b8;">SkillAid</strong>. All Rights Reserved.</p>
</div>
</footer>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
let gpsMap = null;
function selectIncident(label, value) {
    document.querySelectorAll('.incident-card').forEach(c => {
        c.classList.remove('selected');
        c.querySelector('.inc-check').innerHTML = '';
    });
    label.classList.add('selected');
    label.querySelector('.inc-check').innerHTML = '<i class="bi bi-check"></i>';
    label.querySelector('input[type="radio"]').checked = true;
    document.getElementById('incident_type_hidden').value = value;
}

function getLocation() {
    const btn = document.getElementById('gpsBtn');
    const coordBox = document.getElementById('coordDisplay');
    const coordTxt = document.getElementById('coordText');
    const mapDiv   = document.getElementById('gps-map-preview');
    const navLink  = document.getElementById('gmapsNavLink');

    if (!navigator.geolocation) { showStatus('error','bi-x-circle-fill','Geolocation not supported.'); return; }
    btn.classList.add('loading');
    btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Getting location...';
    showStatus('info','bi-info-circle-fill','Requesting your location...');

    navigator.geolocation.getCurrentPosition(
        function(pos) {
            const lat = pos.coords.latitude.toFixed(6);
            const lng = pos.coords.longitude.toFixed(6);
            document.getElementById('latitude').value  = lat;
            document.getElementById('longitude').value = lng;
            coordTxt.textContent = 'Lat: ' + lat + '  |  Lng: ' + lng;
            coordBox.classList.add('show');
            mapDiv.style.display = 'block';
            navLink.href = 'https://www.google.com/maps?q=' + lat + ',' + lng;
            navLink.classList.add('show');
            if (!gpsMap) {
                gpsMap = L.map('gps-map-preview', { scrollWheelZoom: false }).setView([lat, lng], 16);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution:'© OpenStreetMap contributors', maxZoom:19
                }).addTo(gpsMap);
                const redIcon = L.icon({
                    iconUrl:'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
                    shadowUrl:'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                    iconSize:[25,41], iconAnchor:[12,41], popupAnchor:[1,-34], shadowSize:[41,41]
                });
                L.marker([lat, lng], {icon: redIcon}).addTo(gpsMap)
                 .bindPopup('<strong>Your Location</strong><br>Lat: ' + lat + '<br>Lng: ' + lng).openPopup();
            } else { gpsMap.setView([lat, lng], 16); }
            setTimeout(() => gpsMap.invalidateSize(), 200);
            const locField = document.getElementById('location');
            if (!locField.value) locField.value = 'Lat: ' + lat + ', Lng: ' + lng;
            btn.classList.remove('loading');
            btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Location Captured';
            btn.style.background = '#22c55e';
            showStatus('ok','bi-check-circle-fill','Location captured successfully!');
            fetch('https://nominatim.openstreetmap.org/reverse?lat=' + lat + '&lon=' + lng + '&format=json')
                .then(r => r.json())
                .then(data => {
                    if (data && data.display_name) {
                        const clean = data.display_name.split(',').slice(0,3).join(', ').trim();
                        if (locField.value.startsWith('Lat:')) locField.value = clean;
                        coordTxt.textContent = 'Lat: ' + lat + '  |  Lng: ' + lng + '  —  ' + clean;
                    }
                }).catch(() => {});
        },
        function(err) {
            btn.classList.remove('loading');
            btn.innerHTML = '<i class="bi bi-crosshair2"></i> Use My Location';
            let msg = 'Could not get your location.';
            if (err.code === 1) msg = 'Location permission denied.';
            if (err.code === 2) msg = 'Location unavailable. Try again.';
            if (err.code === 3) msg = 'Request timed out. Try again.';
            showStatus('error','bi-x-circle-fill', msg);
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
}

function showStatus(type, icon, message) {
    const el = document.getElementById('gpsStatus');
    el.className = 'gps-status show ' + type;
    el.innerHTML = '<i class="bi ' + icon + '"></i> ' + message;
}

document.getElementById('reportForm')?.addEventListener('submit', function(e) {
    if (!document.getElementById('name').value.trim() ||
        !document.getElementById('contact').value.trim() ||
        !document.getElementById('description').value.trim() ||
        !document.getElementById('location').value.trim()) {
        e.preventDefault(); alert('Please fill in all required fields.'); return;
    }
    if (!document.getElementById('incident_type_hidden').value) {
        e.preventDefault(); alert('Please select an incident type.'); return;
    }
});
</script>
</body>
</html>
