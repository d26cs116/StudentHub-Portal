<?php
/* =========================================================
   includes/student_form.php - the Add / Edit form (one form, two pages).
   $v = current values, $errors = field messages, $isEdit = show the Status field.
   ========================================================= */
function render_student_form(string $action, array $v, array $errors, bool $isEdit, ?int $id, string $submitLabel): void
{
    $cls = function (string $f, bool $full = false) use ($errors): string {
        return 'form-field' . ($full ? ' full' : '') . (isset($errors[$f]) ? ' error' : '');
    };
    $msg = function (string $f) use ($errors): string {
        return isset($errors[$f]) ? '❌ ' . e($errors[$f]) : '';
    };
    ?>
            <form class="reg-form" action="<?= e($action) ?>" method="POST" novalidate>
                <?= csrf_field() ?>
                <?php if ($id !== null): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>
                <div class="form-grid">
                    <div class="<?= $cls('student_id') ?>">
                        <label for="student_id">Student ID <span aria-hidden="true">*</span></label>
                        <input type="text" id="student_id" name="student_id" value="<?= e($v['student_id']) ?>" maxlength="20" placeholder="e.g. D26CS110" required>
                        <p class="field-message"><?= $msg('student_id') ?></p>
                    </div>
                    <div class="<?= $cls('name') ?>">
                        <label for="name">Full Name <span aria-hidden="true">*</span></label>
                        <input type="text" id="name" name="name" value="<?= e($v['name']) ?>" maxlength="100" required>
                        <p class="field-message"><?= $msg('name') ?></p>
                    </div>
                    <div class="<?= $cls('email') ?>">
                        <label for="email">Email <span aria-hidden="true">*</span></label>
                        <input type="email" id="email" name="email" value="<?= e($v['email']) ?>" maxlength="100" required>
                        <p class="field-message"><?= $msg('email') ?></p>
                    </div>
                    <div class="<?= $cls('phone') ?>">
                        <label for="phone">Phone <span aria-hidden="true">*</span></label>
                        <input type="text" id="phone" name="phone" value="<?= e($v['phone']) ?>" maxlength="20" inputmode="tel" placeholder="e.g. 9876543210" required>
                        <p class="field-message"><?= $msg('phone') ?></p>
                    </div>
                    <div class="<?= $cls('course') ?>">
                        <label for="course">Course <span aria-hidden="true">*</span></label>
                        <select id="course" name="course" required>
                            <option value="">-- Select course --</option>
                            <?php foreach (STUDENT_COURSES as $c): ?>
                                <option value="<?= e($c) ?>"<?= $v['course'] === $c ? ' selected' : '' ?>><?= e($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="field-message"><?= $msg('course') ?></p>
                    </div>
                    <div class="<?= $cls('year') ?>">
                        <label for="year">Academic Year <span aria-hidden="true">*</span></label>
                        <select id="year" name="year" required>
                            <option value="">-- Select year --</option>
                            <?php foreach (STUDENT_YEARS as $y): ?>
                                <option value="<?= $y ?>"<?= $v['year'] === (string) $y ? ' selected' : '' ?>>Year <?= $y ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="field-message"><?= $msg('year') ?></p>
                    </div>
                    <?php if ($isEdit): ?>
                    <div class="<?= $cls('status') ?>">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <?php foreach (STUDENT_STATUSES as $s): ?>
                                <option value="<?= e($s) ?>"<?= $v['status'] === $s ? ' selected' : '' ?>><?= e(ucfirst($s)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="field-hint">Set back to Active to restore a deactivated student.</p>
                        <p class="field-message"><?= $msg('status') ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="form-actions">
                    <button type="submit" class="reg-submit"><?= e($submitLabel) ?></button>
                    <a href="students.php" class="reg-reset">Cancel</a>
                </div>
            </form>
<?php
}
