<?php
/* =========================================================
   logout.php - ends the session safely.
   It only accepts a POST from the Logout button (with the CSRF token),
   so another website cannot log a user out by embedding a link or image.
   ========================================================= */
require_once __DIR__ . '/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');        // opening the URL directly does nothing (login page if not logged in)
}

$token = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    redirect('dashboard.php');        // forged or stale request: keep the session as it is
}

destroy_session_completely();         // clear data + expire cookie + destroy server session
redirect('login.php?status=logout');  // the success message is shown by login.php
