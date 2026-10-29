<?php
/* =========================================================
   audit_logs.php - Practical 14: read-only list of audit entries.
   Admin ONLY (a Student receives the 403 Forbidden page).
   There is no edit or delete here: audit rows can only be added by
   audit_log() when an administrator action succeeds.
   ========================================================= */
require_once __DIR__ . '/includes/auth.php';
require_role('Admin');

const AUDIT_PAGE_SIZE = 50;

$rows = null;           // null = could not be loaded
try {
    // LEFT JOIN: the entry stays visible even if the admin account was deleted later (user_id becomes NULL).
    $limit = AUDIT_PAGE_SIZE;
    $stmt = $conn->prepare(
        "SELECT a.id, a.created_at, u.full_name, a.action, a.entity_type, a.entity_id, a.details, a.ip_address
         FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id
         ORDER BY a.id DESC LIMIT ?");
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $stmt->bind_result($aId, $aWhen, $aUser, $aAction, $aType, $aEntity, $aDetails, $aIp);
    $rows = [];
    while ($stmt->fetch()) {
        $rows[] = ['id' => $aId, 'when' => $aWhen, 'user' => $aUser, 'action' => $aAction, 'type' => $aType,
                   'entity' => $aEntity, 'details' => $aDetails, 'ip' => $aIp];
    }
    $stmt->close();
} catch (mysqli_sql_exception $ex) {
    error_log('Audit log page: query failed (' . $ex->getCode() . ')');
    $rows = null;
}

render_header('Audit Logs', 'audit', true, true);
?>
        <section aria-labelledby="audit-title">
            <h2 id="audit-title">Audit Logs</h2>
            <p class="section-description">The latest <?= (int) AUDIT_PAGE_SIZE ?> administrative actions, newest first. This list is read-only.</p>
<?php if ($rows === null): ?>
            <div class="alert alert-danger" role="alert">❌ The audit log could not be loaded right now. Please try again later.</div>
<?php elseif (!$rows): ?>
            <div class="alert alert-info" role="status">ℹ️ No audit entries yet. Create, edit or delete an event to add one.</div>
<?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle" id="audit-table">
                    <thead><tr><th>ID</th><th>Time</th><th>Admin</th><th>Action</th><th>Record</th><th>Details</th><th>IP address</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= (int) $r['id'] ?></td>
                            <td><?= e((string) $r['when']) ?></td>
                            <td><?= e((string) ($r['user'] ?? '(deleted user)')) ?></td>
                            <td><span class="badge text-bg-secondary"><?= e($r['action']) ?></span></td>
                            <td><?= e(trim((string) $r['type'] . ' ' . ($r['entity'] !== null ? '#' . (int) $r['entity'] : ''))) ?></td>
                            <td><?= e((string) $r['details']) ?></td>
                            <td><?= e((string) $r['ip']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
<?php endif; ?>
        </section>
<?php render_footer(); ?>
