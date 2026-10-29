<?php
/* edit_event.php - update an event; the poster can be kept or replaced (Admin only). */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/event_helpers.php';
require_once __DIR__ . '/includes/event_form.php';
require_role('Admin');

$isPost = ($_SERVER['REQUEST_METHOD'] === 'POST');
$tooBig = $isPost && request_exceeds_post_max();       // upload larger than post_max_size: the id is lost too
$id     = clean_id($isPost ? ($_POST['id'] ?? null) : ($_GET['id'] ?? null));
if ($tooBig) {
    // $_POST is empty here, so the id from the address bar (?id=) is not available either; go back to the list.
    flash_set('error', MSG_POSTER_SIZE);
    redirect('events.php');
}
if ($id === 0) {
    flash_set('error', 'Event not found.');
    redirect('events.php');
}

try {
    $current = find_event($conn, $id);
} catch (mysqli_sql_exception $ex) {
    error_log('Edit event: load failed (' . $ex->getCode() . ')');
    flash_set('error', 'Unable to complete the operation.');
    redirect('events.php');
}
if ($current === null) {
    flash_set('error', 'Event not found.');
    redirect('events.php');
}

$values = $current;
$errors = [];
$banner = '';

if ($isPost) {
    $values = read_event_input();
    if (!csrf_is_valid()) {
        $banner = 'Your session expired. Please check the form and submit it again.';
    } else {
        $errors = validate_event($values);
        $upload = validate_poster_upload();
        if ($upload['status'] === 'error') {
            $errors['poster'] = $upload['message'];
        }

        if ($errors) {
            $banner = event_error_banner($errors, $values);
        } else {
            $newPoster = null;
            try {
                $posterForDb = $current['poster'];                 // default: keep the poster that is already there
                if ($upload['status'] === 'ok') {
                    $newPoster = save_poster($upload);             // 1. save the NEW file first
                    if ($newPoster === null) {
                        throw new RuntimeException('poster not saved');
                    }
                    $posterForDb = $newPoster;
                }
                $time = $values['event_time'] === '' ? null : $values['event_time'] . ':00';
                $conn->begin_transaction();                         // the update and its audit entry succeed or fail together
                $stmt = $conn->prepare("UPDATE events SET event_name = ?, description = ?, event_date = ?, event_time = ?, venue = ?, poster = ? WHERE id = ?");
                $stmt->bind_param('ssssssi', $values['event_name'], $values['description'], $values['event_date'], $time, $values['venue'], $posterForDb, $id);
                $stmt->execute();                                   // 2. update the database
                $stmt->close();
                audit_log($conn, 'EVENT_UPDATE', 'event', $id, 'Updated event "' . $values['event_name'] . '"');   // Practical 14
                $conn->commit();

                if ($newPoster !== null && $current['poster'] !== $newPoster) {
                    delete_poster_file($current['poster']);         // 3. only now remove the old file (if it is one of ours)
                }
                flash_set('success', 'Event updated successfully.' . ($newPoster !== null ? ' Poster uploaded successfully.' : ''));
                redirect('events.php');
            } catch (mysqli_sql_exception | RuntimeException $ex) {
                db_rollback_quietly($conn);
                delete_poster_file($newPoster);                     // the update failed: drop the new file, old poster stays
                error_log('Edit event failed: ' . $ex->getMessage());
                $banner = ($newPoster === null && $upload['status'] === 'ok') ? MSG_POSTER_FAIL : 'Unable to complete the operation.';
            }
        }
    }
}

render_header('Edit Event', 'events');
?>
        <section class="registration-section" aria-labelledby="edit-ev-title">
            <h2 id="edit-ev-title">Edit Event</h2>
            <p class="section-description">Editing <strong><?= e($current['event_name']) ?></strong> (ID <?= (int) $current['id'] ?>)</p>
            <?php if ($banner !== ''): ?><div class="form-status error" role="alert">❌ <?= e($banner) ?></div><?php endif; ?>
<?php render_event_form('edit_event.php', $values, $errors, $id, $current['poster'], 'Save Changes'); ?>
        </section>
<?php render_footer(); ?>
