<?php
/* =========================================================
   timeout.php - inactivity-based session timeout.
   Included (through includes/auth.php) by EVERY protected page,
   so all dashboards use exactly the same rule.

   HOW IT WORKS
   - authenticate.php stores $_SESSION['last_activity'] = time() at login.
   - On every protected request we compare "now" with that time.
   - Idle longer than SESSION_TIMEOUT seconds -> destroy the session and
     send the user to login.php?status=timeout.
   - Otherwise the timestamp is refreshed (the clock restarts).
   The check runs on the SERVER, so it cannot be bypassed by switching off JavaScript.
   ========================================================= */
require_once __DIR__ . '/includes/config.php';

// ---- THE ONE SETTING ----
// Normal value: 300 seconds (5 minutes).
// To test quickly, change 300 to 30 (30 seconds), save, test, then change it back to 300.
const SESSION_TIMEOUT = 300;

// Has the logged-in user been idle for too long?
function session_has_expired(): bool
{
    $last = $_SESSION['last_activity'] ?? 0;
    return !is_int($last) || (time() - $last) > SESSION_TIMEOUT;
}

// Call this at the top of every protected page.
function enforce_session_timeout(): void
{
    if (!isset($_SESSION['user_id'])) {
        return;                                   // nobody is logged in: nothing to time out
    }
    if (session_has_expired()) {
        destroy_session_completely();             // invalidate the expired session
        redirect('login.php?status=timeout');     // and send the user to the login page
    }
    // Not expired: only NOW do we refresh the activity time.
    $_SESSION['last_activity'] = time();
}
