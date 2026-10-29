<?php
/* =========================================================
   delete_event.php - permanent deletion of an event (Admin only).
   GET  ?id=3 -> asks "Are you sure you want to delete this event?"
   POST       -> deletes the row, then removes its poster file.
   (Events are deleted for real. This is different from the students page,
    which only deactivates records.)
   ========================================================= */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/event_helpers.php';
require_role('Admin');

$isPost = ($_SERVER['REQUEST_METHOD'] === 'POST');
$id     = clean_id($isPost ? ($_POST['id'] ?? null) : ($_GET['id'] ?? null));
if ($id === 0) {
    flash_set('error', 'Event not found.');
    redirect('events.php');
}

try {
    $event = find_event($conn, $id);
} catch (mysqli_sql_exception $ex) {
    error_log('Delete event: load failed (' . $ex->getCode() . ')');
    flash_set('error', 'Unable to complete the operation.');
    redirect('events.php');
}
if ($event === null) {                       // already deleted, or never existed
    flash_set('error', 'Event not found.');
    redirect('events.php');
}

/* ---------- POST: delete ---------- */
if ($isPost) {
    if (!csrf_is_valid()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect('events.php');
    }
    try {
        $conn->begin_transaction();                                // the delete and its audit entry succeed or fail together
        $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();

        if ($deleted === 1) {
            audit_log($conn, 'EVENT_DELETE', 'event', $id, 'Deleted event "' . $event['event_name'] . '"');   // Practical 14
            $conn->commit();
            // Only AFTER the row is gone: remove the poster file, and only if it is one of our own files
            // and no other event still uses the same file name.
            $inUse = 0;
            if (is_managed_poster($event['poster'])) {
                $chk = $conn->prepare("SELECT COUNT(*) FROM events WHERE poster = ?");
                $chk->bind_param('s', $event['poster']);
                $chk->execute();
                $chk->bind_result($inUse);
                $chk->fetch();
                $chk->close();
            }
            if ((int) $inUse === 0) {
                delete_poster_file($event['poster']);
            }
            flash_set('success', 'Event deleted successfully.');
        } else {
            $conn->rollback();                                 // nothing was deleted, so nothing is logged
            flash_set('error', 'Event not found.');            // someone else deleted it in the meantime
        }
    } catch (mysqli_sql_exception $ex) {
        db_rollback_quietly($conn);
        error_log('Delete event failed: ' . $ex->getMessage());
        flash_set('error', 'Unable to complete the operation.');
    }
    redirect('events.php');
}

/* ---------- GET: confirmation page ---------- */
render_header('Delete Event', 'events');
?>
        <section class="denied-box" aria-labelledby="del-ev-title">
            <h2 id="del-ev-title">Confirm Deletion</h2>
            <div class="form-status error" role="alert">⚠️ Are you sure you want to delete this event?</div>
            <div class="info-grid">
                <div class="info-tile"><span class="info-label">Event</span><strong><?= e($event['event_name']) ?></strong></div>
                <div class="info-tile"><span class="info-label">Date</span><strong><?= e(date('d M Y', strtotime($event['event_date']))) ?></strong></div>
                <div class="info-tile"><span class="info-label">Venue</span><strong><?= e($event['venue']) ?></strong></div>
            </div>
            <?php if (is_managed_poster($event['poster'])): ?>
                <p><img class="poster-thumb" src="<?= e(POSTER_WEB_PATH . $event['poster']) ?>" alt="Poster"></p>
            <?php endif; ?>
            <p class="section-description">This permanently removes the event and its poster. It cannot be undone.</p>
            <form action="delete_event.php" method="POST" class="confirm-form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $id ?>">
                <div class="form-actions">
                    <button type="submit" class="reg-submit btn-danger">Yes, delete</button>
                    <a href="events.php" class="reg-reset">Cancel</a>
                </div>
            </form>
        </section>
<?php render_footer(); ?>
