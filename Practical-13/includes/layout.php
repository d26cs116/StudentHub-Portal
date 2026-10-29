<?php
/* =========================================================
   includes/layout.php - the Student Hub page frame (header, nav, footer)
   shared by the protected pages, so they look like the rest of the site.
   ========================================================= */

// Prints everything from <!DOCTYPE> down to the opening <main> tag.
// $loggedIn = true shows the Logout button and the role-based links.
function render_header(string $title, string $active = '', bool $loggedIn = true): void
{
    $role = $_SESSION['role'] ?? '';
    $link = function (string $href, string $text, string $key) use ($active): string {
        $cls = ($active === $key) ? ' class="active"' : '';
        return '<a href="' . e($href) . '"' . $cls . '>' . e($text) . '</a>';
    };
    ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Hub - <?= e($title) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="portal">
    <header>
        <div class="logo">Student Hub</div>
        <nav id="main-nav" aria-label="Main navigation">
            <?= $link('index.html', 'Home', 'home') ?>
<?php if ($loggedIn): ?>
            <?= $link('dashboard.php', 'Dashboard', 'dashboard') ?>
<?php if ($role === 'Admin'): ?>
            <?= $link('admin_dashboard.php', 'Admin Panel', 'admin') ?>
            <?= $link('students.php', 'Students', 'students') ?>
            <?= $link('events.php', 'Events', 'events') ?>
<?php elseif ($role === 'Student'): ?>
            <?= $link('student_dashboard.php', 'Student Area', 'student') ?>
<?php endif; ?>
            <form action="logout.php" method="POST" class="logout-form">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                <button type="submit" class="logout-btn">Logout</button>
            </form>
<?php else: ?>
            <?= $link('register.php', 'Register', 'register') ?>
            <?= $link('login.php', 'Login', 'login') ?>
<?php endif; ?>
        </nav>
        <div class="header-actions">
            <button type="button" id="theme-toggle" class="theme-toggle" aria-label="Switch to dark mode" aria-pressed="false"><span aria-hidden="true">🌙</span> Dark</button>
            <button type="button" id="menu-toggle" class="menu-toggle" aria-label="Open navigation menu" aria-expanded="false" aria-controls="main-nav"><span></span><span></span><span></span></button>
        </div>
    </header>
    <main>
<?php
}

// Prints </main>, the footer and the shared script.
function render_footer(): void
{
    ?>
    </main>
    <footer>
        <p>Student Hub Portal</p>
        <p>Developed by Arshit Vaghela</p>
    </footer>
    <script src="script.js"></script>
</body>
</html>
<?php
}
