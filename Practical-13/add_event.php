<?php
/* add_event.php - create an event with an optional poster (Admin only). */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/event_helpers.php';
require_once __DIR__ . '/includes/event_form.php';
require_role('Admin');                 // checked on the server for every request, GET and POST

$values = ['event_name' => '', 'description' => '', 'event_date' => '', 'event_time' => '', 'venue' => ''];
$errors = [];
$banner = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (request_exceeds_post_max()) {
        // The upload was bigger than post_max_size, so PHP threw the whole form away.
        $banner = MSG_POSTER_SIZE;
        $errors['poster'] = MSG_POSTER_SIZE;
    } elseif (!csrf_is_valid()) {
        $banner = 'Your session expired. Please check the form and submit it again.';
        $values = read_event_input();
    } else {
        $values = read_event_input();
        $errors = validate_event($values);
        $upload = validate_poster_upload();
        if ($upload['status'] === 'error') {
            $errors['poster'] = $upload['message'];
        }

        if ($errors) {
            $banner = event_error_banner($errors, $values);       // nothing is saved, no file is stored
        } else {
            $saved = null;
            try {
                if ($upload['status'] === 'ok') {
                    $saved = save_poster($upload);
                    if ($saved === null) {
                        throw new RuntimeException('poster not saved');
                    }
                }
                $time = $values['event_time'] === '' ? null : $values['event_time'] . ':00';
                $stmt = $conn->prepare("INSERT INTO events (event_name, description, event_date, event_time, venue, poster) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('ssssss', $values['event_name'], $values['description'], $values['event_date'], $time, $values['venue'], $saved);
                $stmt->execute();
                $ok = ($stmt->affected_rows === 1);
                $stmt->close();
                if (!$ok) {
                    throw new RuntimeException('insert failed');
                }
                flash_set('success', 'Event created successfully.' . ($saved !== null ? ' Poster uploaded successfully.' : ''));
                redirect('events.php');                           // Post/Redirect/Get: refresh cannot re-submit
            } catch (mysqli_sql_exception | RuntimeException $ex) {
                delete_poster_file($saved);                       // do not leave an orphaned file behind
                error_log('Add event failed: ' . $ex->getMessage());
                $banner = ($saved === null && $upload['status'] === 'ok') ? MSG_POSTER_FAIL : 'Unable to complete the operation.';
            }
        }
    }
}

render_header('Add Event', 'events');
?>
        <section class="registration-section" aria-labelledby="add-ev-title">
            <h2 id="add-ev-title">Add Event</h2>
            <p class="section-description">Fields marked <span aria-hidden="true">*</span> are required.</p>
            <?php if ($banner !== ''): ?><div class="form-status error" role="alert">❌ <?= e($banner) ?></div><?php endif; ?>
<?php render_event_form('add_event.php', $values, $errors, null, null, 'Create Event'); ?>
        </section>
<?php render_footer(); ?>
