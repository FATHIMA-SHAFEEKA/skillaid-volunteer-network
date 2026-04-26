<?php
session_start();
include("../config/db.php");

// Redirect if not logged in
if (!isset($_SESSION['volunteer_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $volunteer_id   = $_SESSION['volunteer_id'];
    $full_name      = trim($_POST['full_name']      ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $location       = trim($_POST['location']       ?? '');
    $transport_mode = trim($_POST['transport_mode'] ?? '');
    $skills         = trim($_POST['skills']         ?? '');
    $availability   = trim($_POST['availability']   ?? '');

    // Update using prepared statement (safe, no SQL injection)
    $stmt = mysqli_prepare($conn, "
        UPDATE volunteer 
        SET full_name = ?, contact_number = ?, location = ?, 
            transport_mode = ?, skills = ?, availability = ?
        WHERE volunteer_id = ?
    ");
    mysqli_stmt_bind_param($stmt, "ssssssi",
        $full_name, $contact_number, $location,
        $transport_mode, $skills, $availability,
        $volunteer_id
    );

    if (mysqli_stmt_execute($stmt)) {
        // Update session name so navbar shows updated name immediately
        $_SESSION['volunteer_name'] = $full_name;
        header("Location: profile.php?updated=1");
    } else {
        header("Location: profile.php?updated=0");
    }
    exit();

} else {
    header("Location: profile.php");
    exit();
}
?>
