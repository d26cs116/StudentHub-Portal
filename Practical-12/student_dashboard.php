<?php
/* student_dashboard.php - Student role ONLY (an Admin gets "Access Denied"). */
require_once __DIR__ . '/includes/auth.php';
require_role('Student');

render_header('Student Dashboard', 'student');
?>
        <section aria-labelledby="stu-title">
            <h2 id="stu-title">Student Dashboard</h2>
            <p class="section-description">Hello <strong><?= e($_SESSION['user_name']) ?></strong>, you are logged in as <span class="role-badge role-student"><?= e($_SESSION['role']) ?></span></p>
            <div class="info-grid">
                <div class="info-tile"><span class="info-label">Name</span><strong><?= e($_SESSION['user_name']) ?></strong></div>
                <div class="info-tile"><span class="info-label">Email</span><strong><?= e($_SESSION['user_email']) ?></strong></div>
                <div class="info-tile"><span class="info-label">Role</span><strong><?= e($_SESSION['role']) ?></strong></div>
            </div>
            <h3>Quick links</h3>
            <div class="cards">
                <a class="card" href="attendance.html"><div class="icon">📅</div><h3>Attendance</h3></a>
                <a class="card" href="assignments.html"><div class="icon">📝</div><h3>Assignments</h3></a>
                <a class="card" href="timetable.html"><div class="icon">🕒</div><h3>Timetable</h3></a>
                <a class="card" href="results.html"><div class="icon">📊</div><h3>Results</h3></a>
            </div>
        </section>
<?php render_footer(); ?>
