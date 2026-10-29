<?php
/* includes/event_form.php - the Add / Edit event form (multipart, because of the poster). */
function render_event_form(string $action, array $v, array $errors, ?int $id, ?string $currentPoster, string $submitLabel): void
{
    $cls = function (string $f, bool $full = false) use ($errors): string {
        return 'form-field' . ($full ? ' full' : '') . (isset($errors[$f]) ? ' error' : '');
    };
    $msg = function (string $f) use ($errors): string {
        return isset($errors[$f]) ? '❌ ' . e($errors[$f]) : '';
    };
    ?>
            <form class="reg-form" action="<?= e($action) ?>" method="POST" enctype="multipart/form-data" novalidate>
                <?= csrf_field() ?>
                <?php if ($id !== null): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>
                <div class="form-grid">
                    <div class="<?= $cls('event_name', true) ?>">
                        <label for="event_name">Event Name <span aria-hidden="true">*</span></label>
                        <input type="text" id="event_name" name="event_name" value="<?= e($v['event_name']) ?>" maxlength="150" required>
                        <p class="field-message"><?= $msg('event_name') ?></p>
                    </div>
                    <div class="<?= $cls('description', true) ?>">
                        <label for="description">Description <span aria-hidden="true">*</span></label>
                        <textarea id="description" name="description" rows="5" maxlength="2000" required><?= e($v['description']) ?></textarea>
                        <p class="field-message"><?= $msg('description') ?></p>
                    </div>
                    <div class="<?= $cls('event_date') ?>">
                        <label for="event_date">Event Date <span aria-hidden="true">*</span></label>
                        <input type="date" id="event_date" name="event_date" value="<?= e($v['event_date']) ?>" required>
                        <p class="field-message"><?= $msg('event_date') ?></p>
                    </div>
                    <div class="<?= $cls('event_time') ?>">
                        <label for="event_time">Event Time</label>
                        <input type="time" id="event_time" name="event_time" value="<?= e($v['event_time']) ?>">
                        <p class="field-hint">Optional.</p>
                        <p class="field-message"><?= $msg('event_time') ?></p>
                    </div>
                    <div class="<?= $cls('venue', true) ?>">
                        <label for="venue">Venue <span aria-hidden="true">*</span></label>
                        <input type="text" id="venue" name="venue" value="<?= e($v['venue']) ?>" maxlength="150" required>
                        <p class="field-message"><?= $msg('venue') ?></p>
                    </div>
                    <div class="<?= $cls('poster', true) ?>">
                        <label for="poster"><?= $currentPoster !== null ? 'Replace Poster' : 'Event Poster' ?></label>
                        <?php if ($currentPoster !== null && is_managed_poster($currentPoster)): ?>
                            <div class="current-poster">
                                <img src="<?= e(POSTER_WEB_PATH . $currentPoster) ?>" alt="Current poster" class="poster-thumb"
                                     onerror="this.outerHTML='&lt;span class=&quot;poster-missing&quot;&gt;Poster file missing&lt;/span&gt;'">
                                <span class="field-hint">Current poster. Leave the field below empty to keep it.</span>
                            </div>
                        <?php endif; ?>
                        <input type="file" id="poster" name="poster">
                        <p class="field-hint">Optional. JPEG, PNG or WebP, maximum <?= e(POSTER_MAX_LABEL) ?>.<?= $id === null ? ' If the form has an error, choose the file again.' : '' ?></p>
                        <p class="field-message"><?= $msg('poster') ?></p>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="reg-submit"><?= e($submitLabel) ?></button>
                    <a href="events.php" class="reg-reset">Cancel</a>
                </div>
            </form>
<?php
}
