<?php
/* events.php - admin event list with uploaded posters (Admin only). */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/event_helpers.php';
require_role('Admin');

$events  = [];
$dbError = false;
try {
    $stmt = $conn->prepare("SELECT id, event_name, description, event_date, event_time, venue, poster FROM events ORDER BY event_date ASC, id ASC");
    $stmt->execute();
    $stmt->bind_result($id, $name, $desc, $date, $time, $venue, $poster);
    while ($stmt->fetch()) {
        $events[] = ['id' => $id, 'event_name' => $name, 'description' => $desc, 'event_date' => $date,
                     'event_time' => $time === null ? '' : substr((string) $time, 0, 5), 'venue' => $venue, 'poster' => $poster];
    }
    $stmt->close();
} catch (mysqli_sql_exception $ex) {
    error_log('Event list failed: ' . $ex->getMessage());
    $dbError = true;
}

// Shortens long descriptions for the table (full text is on the edit page)
function short_text(string $s, int $max = 110): string
{
    return text_len($s) > $max ? preg_replace('/\s+\S*$/u', '', mb_substr($s, 0, $max)) . '…' : $s;
}

render_header('Events', 'events');
?>
        <section class="students-section" aria-labelledby="ev-title">
            <div class="section-head">
                <div>
                    <h2 id="ev-title">Manage Events</h2>
                    <p class="section-description">Create, edit and delete events and their posters.</p>
                </div>
                <div class="head-actions">
                    <a class="btn-link" href="add_event.php">+ Add Event</a>
                </div>
            </div>

            <?php flash_render(); ?>

            <?php if ($dbError): ?>
                <div class="form-status error" role="alert">❌ Unable to complete the operation.</div>
            <?php elseif (!$events): ?>
                <div class="form-status info" role="status">ℹ️ No events yet. Use “Add Event” to create the first one.</div>
            <?php else: ?>
                <p class="result-count"><strong><?= count($events) ?></strong> event<?= count($events) === 1 ? '' : 's' ?></p>
                <table class="students-table events-table">
                    <thead>
                        <tr><th>ID</th><th>Event Name</th><th>Description</th><th>Date &amp; Time</th><th>Venue</th><th>Poster</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($events as $ev): ?>
                        <tr>
                            <td><?= (int) $ev['id'] ?></td>
                            <td><strong><?= e($ev['event_name']) ?></strong></td>
                            <td class="ev-desc"><?= e(short_text($ev['description'])) ?></td>
                            <td>
                                <?= e(date('d M Y', strtotime($ev['event_date']))) ?>
                                <?php if ($ev['event_time'] !== ''): ?><br><span class="field-hint"><?= e($ev['event_time']) ?></span><?php endif; ?>
                            </td>
                            <td><?= e($ev['venue']) ?></td>
                            <td>
                                <?php if (is_managed_poster($ev['poster'])): ?>
                                    <img class="poster-thumb" src="<?= e(POSTER_WEB_PATH . $ev['poster']) ?>" alt="Poster of <?= e($ev['event_name']) ?>" loading="lazy"
                                         onerror="this.outerHTML='&lt;span class=&quot;poster-missing&quot;&gt;Poster file missing&lt;/span&gt;'">
                                <?php else: ?>
                                    <span class="poster-placeholder">No poster</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <a class="act-link" href="edit_event.php?id=<?= (int) $ev['id'] ?>">Edit</a>
                                <a class="act-link act-danger" href="delete_event.php?id=<?= (int) $ev['id'] ?>">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
<?php render_footer(); ?>
