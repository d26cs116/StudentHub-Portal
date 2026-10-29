<?php
/* admin_dashboard.php - Admin role ONLY (a Student gets "Access Denied"). */
require_once __DIR__ . '/includes/auth.php';
require_role('Admin');

// Admin-only data: the list of registered users (password hashes are NOT selected).
$users = [];
try {
    $stmt = $conn->prepare("SELECT id, full_name, username, email, role, created_at FROM users ORDER BY id");
    $stmt->execute();
    $stmt->bind_result($uId, $uName, $uUser, $uEmail, $uRole, $uCreated);
    while ($stmt->fetch()) {
        $users[] = ['id' => $uId, 'name' => $uName, 'username' => $uUser, 'email' => $uEmail, 'role' => $uRole, 'created' => $uCreated];
    }
    $stmt->close();
} catch (mysqli_sql_exception $ex) {
    error_log('Admin dashboard: user list failed (' . $ex->getCode() . ')');
    $users = null;
}

render_header('Admin Dashboard', 'admin');
?>
        <section aria-labelledby="adm-title">
            <h2 id="adm-title">Admin Dashboard</h2>
            <p class="section-description">Hello <strong><?= e($_SESSION['user_name']) ?></strong>, you are logged in as <span class="role-badge role-admin"><?= e($_SESSION['role']) ?></span></p>
            <p class="head-actions"><a class="btn-link" href="events.php">Manage Events</a> <a class="btn-link btn-secondary" href="students.php">Open Student Management</a></p>
            <h3>Registered users<?= $users !== null ? ' (' . count($users) . ')' : '' ?></h3>
            <?php if ($users === null): ?>
                <div class="form-status error" role="alert">❌ The user list could not be loaded right now.</div>
            <?php else: ?>
            <table>
                <thead><tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Registered</th></tr></thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= (int) $u['id'] ?></td>
                        <td><?= e($u['name']) ?></td>
                        <td><?= e($u['username']) ?></td>
                        <td><?= e($u['email']) ?></td>
                        <td><span class="role-badge role-<?= e(strtolower($u['role'])) ?>"><?= e($u['role']) ?></span></td>
                        <td><?= e((string) $u['created']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </section>
<?php render_footer(); ?>
