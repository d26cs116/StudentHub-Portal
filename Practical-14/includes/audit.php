<?php
/* =========================================================
   includes/audit.php - Practical 14: audit logging.
   An AUDIT LOG is a permanent diary of important administrative actions:
   WHO did WHAT, to WHICH record, WHEN and from WHICH address.

   audit_log()       writes one row to the audit_logs table. It THROWS
                     if the insert fails, so pages that call it inside a
                     transaction can roll everything back (no admin change
                     without a log entry).
   audit_log_safe()  same, but never throws - used where a logging problem
                     must not stop the user (for example at login).

   Rules followed here
   - Only prepared statements are used.
   - Nothing secret is ever logged: callers pass short descriptions such as
     the event name, never passwords, hashes or session tokens.
   - There is NO function or page that edits or deletes audit rows.
   Requires $conn (MySQLi) from db.php, already loaded by includes/auth.php.
   ========================================================= */

// The only action names that may be written (keeps the log consistent and searchable).
const AUDIT_ACTIONS = [
    'ADMIN_LOGIN',
    'EVENT_CREATE', 'EVENT_UPDATE', 'EVENT_DELETE',
    'STUDENT_CREATE', 'STUDENT_UPDATE', 'STUDENT_DEACTIVATE',
];

// Insert one audit row for the currently logged-in user.
//   $action      one of AUDIT_ACTIONS
//   $entityType  e.g. 'event', 'student', 'user'   (what was changed)
//   $entityId    the record's id                   (which one)
//   $details     short human-readable text         (no secrets!)
function audit_log(mysqli $conn, string $action, ?string $entityType = null, ?int $entityId = null, ?string $details = null): void
{
    if (!in_array($action, AUDIT_ACTIONS, true)) {
        throw new InvalidArgumentException('Unknown audit action.');
    }

    // WHO: only ever taken from the server-side session, never from the request.
    $userId = (isset($_SESSION['user_id']) && is_int($_SESSION['user_id'])) ? $_SESSION['user_id'] : null;

    // FROM WHERE: the address PHP saw (not a header the visitor can forge).
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $ip = ($ip !== '') ? substr($ip, 0, 45) : null;

    // Keep the text short enough for the column (255 characters, counted as characters).
    if ($details !== null && preg_match_all('/./su', $details) > 255) {
        $details = preg_replace('/^(.{252}).*$/su', '$1...', $details);
    }

    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('ississ', $userId, $action, $entityType, $entityId, $details, $ip);
    $stmt->execute();
    $stmt->close();
}

// Same as audit_log(), but a logging failure is only written to the server log.
function audit_log_safe(mysqli $conn, string $action, ?string $entityType = null, ?int $entityId = null, ?string $details = null): bool
{
    try {
        audit_log($conn, $action, $entityType, $entityId, $details);
        return true;
    } catch (Throwable $ex) {
        error_log('Audit log failed: ' . $ex->getMessage());
        return false;
    }
}

// Undo an unfinished transaction without ever throwing (used inside catch blocks).
function db_rollback_quietly(mysqli $conn): void
{
    try {
        $conn->rollback();
    } catch (Throwable $ex) {
        error_log('Rollback failed: ' . $ex->getMessage());
    }
}
