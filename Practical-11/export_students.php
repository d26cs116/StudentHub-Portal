<?php
/* =========================================================
   export_students.php - downloads the students as a CSV file (Admin only).
   Uses the same ?q= &course= &status= filters as students.php, so the file
   contains exactly the records the list is showing.
   ========================================================= */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/student_helpers.php';
require_role('Admin');

// CSV formula injection guard: Excel treats a cell that starts with = + - @ (or a tab / CR) as a formula.
// A leading apostrophe makes it plain text instead.
function csv_safe(string $value): string
{
    return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
}

$f = read_student_filters();
$rows = [];
try {
    [$where, $types, $vals] = build_student_where($f);
    $stmt = $conn->prepare("SELECT student_id, name, email, phone, course, `year`, status, created_at FROM students" . $where . " ORDER BY student_id");
    if ($types !== '') {
        $stmt->bind_param($types, ...$vals);
    }
    $stmt->execute();
    $stmt->bind_result($sid, $name, $email, $phone, $course, $year, $status, $created);
    while ($stmt->fetch()) {
        $rows[] = [$sid, $name, $email, $phone, $course, $year, $status, $created];
    }
    $stmt->close();
} catch (mysqli_sql_exception $ex) {
    error_log('CSV export failed: ' . $ex->getMessage());
    flash_set('error', 'Unable to complete the operation.');
    redirect('students.php');
}

if (!$rows) {                                   // nothing to export: do not send an empty file
    flash_set('error', 'No students found to export.');
    redirect('students.php');
}

flash_set('success', 'CSV exported successfully.');   // shown on the list page after the download
session_write_close();

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="student_records.csv"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");                    // UTF-8 BOM so Excel shows names correctly
fputcsv($out, ['Student ID', 'Name', 'Email', 'Phone', 'Course', 'Year', 'Status', 'Created At'], ',', '"', '');
foreach ($rows as $r) {
    fputcsv($out, array_map(fn($v) => csv_safe((string) $v), $r), ',', '"', '');   // fputcsv handles commas and quotes
}
fclose($out);
exit;
