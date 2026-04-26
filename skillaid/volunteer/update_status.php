 <?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['volunteer_id'])) {
    header("Location: login.php");
    exit();
}

$volunteer_id  = $_SESSION['volunteer_id'];
$assignment_id = intval($_GET['id']     ?? 0);
$new_status    = trim($_GET['status']   ?? '');

$allowed = ['Accepted', 'Completed', 'Declined'];
if (!in_array($new_status, $allowed) || $assignment_id === 0) {
    header("Location: dashboard.php?msg=error&status=invalid");
    exit();
}

// Verify assignment belongs to this volunteer
$check = mysqli_prepare($conn, "
    SELECT ta.assignment_id, ta.incident_id, ta.assignment_status
    FROM task_assignment ta
    WHERE ta.assignment_id = ? AND ta.volunteer_id = ?
");
mysqli_stmt_bind_param($check, "ii", $assignment_id, $volunteer_id);
mysqli_stmt_execute($check);
$assignment = mysqli_fetch_assoc(mysqli_stmt_get_result($check));

if (!$assignment) {
    header("Location: dashboard.php?msg=error&status=notfound");
    exit();
}

$incident_id = $assignment['incident_id'];

// Get volunteers_needed for this incident
$inc_stmt = mysqli_prepare($conn, "SELECT volunteers_needed FROM incident WHERE incident_id = ?");
mysqli_stmt_bind_param($inc_stmt, "i", $incident_id);
mysqli_stmt_execute($inc_stmt);
$inc_data = mysqli_fetch_assoc(mysqli_stmt_get_result($inc_stmt));
$volunteers_needed = intval($inc_data['volunteers_needed'] ?? 1);

// ── ACCEPTANCE CONTROL ────────────────────────────────────────────────
if ($new_status === 'Accepted') {
    $already = mysqli_prepare($conn, "
        SELECT COUNT(*) AS cnt FROM task_assignment
        WHERE incident_id = ? AND assignment_status = 'Accepted' AND volunteer_id != ?
    ");
    mysqli_stmt_bind_param($already, "ii", $incident_id, $volunteer_id);
    mysqli_stmt_execute($already);
    $already_count = mysqli_fetch_assoc(mysqli_stmt_get_result($already))['cnt'];

    if ($already_count >= $volunteers_needed) {
        header("Location: dashboard.php?msg=locked&status=Accepted");
        exit();
    }
}

// ── UPDATE STATUS ─────────────────────────────────────────────────────
$upd = mysqli_prepare($conn, "UPDATE task_assignment SET assignment_status=? WHERE assignment_id=? AND volunteer_id=?");
mysqli_stmt_bind_param($upd, "sii", $new_status, $assignment_id, $volunteer_id);
if (!mysqli_stmt_execute($upd)) {
    header("Location: dashboard.php?msg=error&status=$new_status");
    exit();
}

// ── SYNC INCIDENT STATUS ──────────────────────────────────────────────
if ($new_status === 'Accepted') {
    $inc_upd = mysqli_prepare($conn, "UPDATE incident SET status='Assigned' WHERE incident_id=?");
    mysqli_stmt_bind_param($inc_upd, "i", $incident_id);
    mysqli_stmt_execute($inc_upd);
}

if ($new_status === 'Completed') {
    // Mark incident Completed only when accepted count reaches volunteers_needed
    $comp = mysqli_prepare($conn, "SELECT SUM(assignment_status='Completed') AS done FROM task_assignment WHERE incident_id=?");
    mysqli_stmt_bind_param($comp, "i", $incident_id);
    mysqli_stmt_execute($comp);
    $done = intval(mysqli_fetch_assoc(mysqli_stmt_get_result($comp))['done']);

    if ($done >= $volunteers_needed) {
        $inc_upd = mysqli_prepare($conn, "UPDATE incident SET status='Completed' WHERE incident_id=?");
        mysqli_stmt_bind_param($inc_upd, "i", $incident_id);
        mysqli_stmt_execute($inc_upd);
    }
}

if ($new_status === 'Declined') {
    $active = mysqli_prepare($conn, "
        SELECT COUNT(*) AS cnt FROM task_assignment
        WHERE incident_id=? AND volunteer_id!=? AND assignment_status IN ('Accepted','Pending')
    ");
    mysqli_stmt_bind_param($active, "ii", $incident_id, $volunteer_id);
    mysqli_stmt_execute($active);
    $active_count = mysqli_fetch_assoc(mysqli_stmt_get_result($active))['cnt'];

    if ($active_count === 0) {
        $inc_upd = mysqli_prepare($conn, "UPDATE incident SET status='Pending' WHERE incident_id=?");
        mysqli_stmt_bind_param($inc_upd, "i", $incident_id);
        mysqli_stmt_execute($inc_upd);
    }
}

header("Location: dashboard.php?msg=success&status=$new_status");
exit();
?>