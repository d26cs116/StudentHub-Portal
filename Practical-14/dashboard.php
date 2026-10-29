<?php
/* =========================================================
   dashboard.php - Practical 14: dashboard for every logged-in user.
   - Everybody (Admin or Student) sees their own account details.
   - ONLY an Admin additionally sees the statistics and the latest
     registrations. That check is done here on the server, so a Student
     can never receive those numbers, even by editing the page in the browser.
   Bootstrap 5 (loaded by render_header) is used for the cards and the table.
   ========================================================= */
require_once __DIR__ . '/includes/auth.php';
require_login();      // not logged in -> login.php ; idle too long -> login.php?status=timeout

$role    = $_SESSION['role'];
$isAdmin = ($role === 'Admin');

/* ---------- Admin only: statistics + latest registrations ---------- */
$stats = null;          // null = could not be loaded (database error)
$latest = null;
if ($isAdmin) {
    try {
        // One COUNT(*) per table. COUNT(*) on an empty table returns 0, so empty tables show 0.
        $stats = ['users' => 0, 'students' => 0, 'events' => 0];
        $tables = ['users' => 'users', 'students' => 'students', 'events' => 'events'];   // fixed names, never user input
        foreach ($tables as $key => $table) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM `" . $table . "`");
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();
            $stats[$key] = (int) $count;
        }

        // Latest registrations = the newest accounts, by their real registration time (newest first).
        // (The project has no event-registration table; "registrations" are the user registrations from register.php.)
        $limit = 5;
        $stmt = $conn->prepare("SELECT id, full_name, username, email, role, created_at FROM users ORDER BY created_at DESC, id DESC LIMIT ?");
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $stmt->bind_result($rId, $rName, $rUser, $rEmail, $rRole, $rCreated);
        $latest = [];
        while ($stmt->fetch()) {
            $latest[] = ['id' => $rId, 'name' => $rName, 'username' => $rUser, 'email' => $rEmail, 'role' => $rRole, 'created' => $rCreated];
        }
        $stmt->close();
    } catch (mysqli_sql_exception $ex) {
        error_log('Dashboard: query failed (' . $ex->getCode() . ')');   // details only in the log
        $stats = null;
        $latest = null;
    }
}

render_header('Dashboard', 'dashboard', true, true);
?>
        <section aria-labelledby="dash-title">
            <h2 id="dash-title">Welcome, <span class="user-highlight"><?= e($_SESSION['user_name']) ?></span></h2>
            <p class="section-description">You are logged in to the Student Hub portal as <span class="role-badge role-<?= e(strtolower($role)) ?>"><?= e($role) ?></span></p>

<?php if ($isAdmin): ?>
            <?php if ($stats === null): ?>
                <div class="alert alert-danger" role="alert">❌ The dashboard data could not be loaded right now. Please try again later.</div>
            <?php else: ?>
            <!-- ===== Statistics (live COUNT(*) results) ===== -->
            <div class="row g-3 my-3" id="stats">
                <div class="col-12 col-md-4">
                    <div class="card h-100 border-0 shadow-sm text-center">
                        <div class="card-body">
                            <p class="text-uppercase fw-bold small mb-1">Total users</p>
                            <div class="display-5 fw-bold" id="count-users"><?= (int) $stats['users'] ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card h-100 border-0 shadow-sm text-center">
                        <div class="card-body">
                            <p class="text-uppercase fw-bold small mb-1">Total students</p>
                            <div class="display-5 fw-bold" id="count-students"><?= (int) $stats['students'] ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card h-100 border-0 shadow-sm text-center">
                        <div class="card-body">
                            <p class="text-uppercase fw-bold small mb-1">Total events</p>
                            <div class="display-5 fw-bold" id="count-events"><?= (int) $stats['events'] ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== Latest registrations ===== -->
            <h3 class="mt-4">Latest registrations</h3>
            <?php if (!$latest): ?>
                <div class="alert alert-info" role="status">ℹ️ No registrations yet.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle" id="latest-registrations">
                        <thead><tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Registered</th></tr></thead>
                        <tbody>
                        <?php foreach ($latest as $r): ?>
                            <tr>
                                <td><?= (int) $r['id'] ?></td>
                                <td><?= e($r['name']) ?></td>
                                <td><?= e($r['username']) ?></td>
                                <td><?= e($r['email']) ?></td>
                                <td><span class="role-badge role-<?= e(strtolower($r['role'])) ?>"><?= e($r['role']) ?></span></td>
                                <td><?= e((string) $r['created']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            <?php endif; ?>

            <p class="head-actions mt-3">
                <a class="btn-link" href="admin_dashboard.php">Admin Panel</a>
                <a class="btn-link btn-secondary" href="events.php">Manage Events</a>
                <a class="btn-link btn-secondary" href="students.php">Manage Students</a>
                <a class="btn-link btn-secondary" href="audit_logs.php">Audit Logs</a>
            </p>

<?php else: ?>
            <div class="info-grid">
                <div class="info-tile"><span class="info-label">Name</span><strong><?= e($_SESSION['user_name']) ?></strong></div>
                <div class="info-tile"><span class="info-label">Email</span><strong><?= e($_SESSION['user_email']) ?></strong></div>
                <div class="info-tile"><span class="info-label">Role</span><span class="role-badge role-<?= e(strtolower($role)) ?>"><?= e($role) ?></span></div>
                <div class="info-tile"><span class="info-label">Auto logout after</span><strong><?= e(SESSION_TIMEOUT >= 60 ? round(SESSION_TIMEOUT / 60, 1) . ' min' : SESSION_TIMEOUT . ' sec') ?> idle</strong></div>
            </div>
            <p style="margin-top:1.2rem"><a class="btn-link" href="<?= e(role_home($role)) ?>">Open my Student Area</a></p>
<?php endif; ?>
        </section>
<?php render_footer(); ?>
