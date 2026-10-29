<?php
/* check.php – quick test page: confirms that Apache is running PHP and that the storage folder can be written.
   Open http://localhost/<your-folder>/check.php   (delete this file when you have finished the practical) */
require __DIR__ . '/includes/config.php';
$writable = is_writable(__DIR__ . '/storage');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Hub - PHP Check</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="portal">
    <main>
        <section>
            <h2>PHP check</h2>
            <p>✅ PHP is working. Version: <strong><?= e(PHP_VERSION) ?></strong></p>
            <p><?= $writable ? '✅' : '❌' ?> The <code>storage</code> folder is <strong><?= $writable ? 'writable' : 'NOT writable' ?></strong>.
            <?php if (!$writable): ?>Run <code>chmod 777 storage</code> inside the project folder (see the setup notes).<?php endif; ?></p>
            <p><a href="contact.php">Go to the contact form</a></p>
        </section>
    </main>
</body>
</html>
