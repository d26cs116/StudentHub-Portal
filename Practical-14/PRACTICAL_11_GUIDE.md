# Practical 11 – Student Management System (CRUD, Search, Filter, Soft Delete, CSV Export)
Student: Arshit Vaghela (D26CS116) · Stack: PHP · MySQL (MySQLi) · HTML · CSS · XAMPP on macOS

---

## 1. What I started from

No new ZIP came with the Practical 11 message, so I continued from the **Practical 10 project built earlier in this chat** (your Practical 9 files + the Practical 10 login system). That is the latest work I have. If your own copy has changes I haven't seen, send that ZIP and I will merge.

Existing and reused (unchanged in behaviour):
- Database `user_registration_db`, `users` table with `role` (`Admin` / `Student`)
- `db.php` (MySQLi `$conn`), `includes/config.php` (session settings, `e()`, `redirect()`)
- `includes/auth.php` (`require_role('Admin')`), `includes/layout.php` (header/footer), `timeout.php`, `login.php`, `authenticate.php`, `logout.php`, registration, dashboards
- `style.css` (existing classes reused: `.toolbar`, `.tool-btn`, `.form-status`, `.form-grid`, `.reg-submit`, tables, badges, dark mode)

No student table existed (`users` holds login accounts only), so a new **`students`** table was needed.

## 2. Requirement checklist

| Requirement | Where | Status |
|---|---|---|
| `students` table, unique ID + email, status, timestamps, no data loss | `students.sql` | Done |
| Student list from MySQL, all columns, empty message, escaped output | `students.php` | Done |
| Add student, server validation, duplicate check, prepared INSERT, PRG redirect | `add_student.php` | Done |
| Edit student, ID validation, not-found handling, prepared UPDATE | `edit_student.php` | Done |
| Search (name / student ID / email), keeps the term, clear button, no-match message | `students.php` | Done |
| Course filter from real DB values, works together with search | `students.php` | Done |
| Confirmation page + POST soft delete (`status = 'inactive'`, never `DELETE`) | `delete_student.php` | Done |
| Flash messages after redirects, no raw SQL errors | `includes/student_helpers.php` | Done |
| CSV export: `fputcsv()`, UTF-8 BOM, headers, formula-injection guard, respects search/filter | `export_students.php` | Done |
| Admin-only on every page, CSRF on every POST, existing sessions/timeout reused | `require_role('Admin')`, `csrf_is_valid()` | Done |
| Practical 9 / 10 features still work | untouched except small additions below | Done |

Small changes to existing files: `includes/layout.php` (Students link for Admin), `admin_dashboard.php` (button to Student Management), `style.css` (Practical 11 block at the end), `includes/auth.php` (the user id is now copied to a local variable before the database call).

Behaviour notes
- **Soft delete:** the list shows **active** students by default. The Status dropdown has *Inactive only* and *All students*. After deactivating, you are sent to the *All students* view so the new inactive status is visible. To bring a student back, open **Edit** and set Status to Active.
- **Student role:** a logged-in Student gets "Access Denied" (403) on all student-management pages and on the export.
- **Export message:** a file download does not reload the page, so "CSV exported successfully." appears the next time the student list is loaded (press refresh after downloading).
- **CSV safety:** a value starting with `=`, `+`, `-` or `@` gets a leading apostrophe so Excel cannot run it as a formula. Phone numbers that start with `+` therefore show as `'+91…` in the CSV (this is intended).
- **Sample data:** `students.sql` adds 7 demo students (one inactive) so your first screenshot isn't empty. You can delete or edit them in phpMyAdmin.

## 3. Final folder structure

```
Practical-11/
├── students.php            list + search + course filter + status view      (new)
├── add_student.php         add form + processing                            (new)
├── edit_student.php        edit form + processing                           (new)
├── delete_student.php      confirmation page + soft delete (POST)           (new)
├── export_students.php     CSV download                                     (new)
├── students.sql            migration: creates `students` + sample rows      (new)
├── includes/
│   ├── student_helpers.php validation, flash, CSRF, search/filter builder   (new)
│   ├── student_form.php    the shared Add/Edit form                         (new)
│   ├── auth.php, config.php, layout.php   (existing, small additions)
├── db.php, login.php, authenticate.php, logout.php, timeout.php,
│   dashboard.php, admin_dashboard.php, student_dashboard.php,
│   register.php, process_registration.php, user_registration.sql,
│   style.css, script.js, *.html ...   (existing)
├── PRACTICAL_10_GUIDE.md, PRACTICAL_11_GUIDE.md
```

## 4. XAMPP setup (macOS)

1. **Copy the project:** put the whole `Practical-11` folder inside `/Applications/XAMPP/xamppfiles/htdocs/` so that `.../htdocs/Practical-11/students.php` exists (not `Practical-11/Practical-11/`). *(If you keep using your `Practical-10` folder, just copy the new files into it — see the file list above — and use that folder name in the URLs.)*
2. **Start Apache and MySQL** in the XAMPP app (Manage Servers).
3. **Open phpMyAdmin:** <http://localhost/phpmyadmin>.
4. **Apply the SQL without losing data:** make sure `user_registration.sql` (Practical 10) has been imported once, then **Import** → choose `students.sql` → *Import*. It only creates the `students` table if it is missing and inserts sample rows that don't already exist; nothing is dropped or changed.
5. **Verify the database:** left list → `user_registration_db` → you should now see both `users` and `students`. Click `students` → *Browse*: 7 rows, with `status` `active` / `inactive`.
6. **Log in as an Admin:** <http://localhost/Practical-11/login.php> → `admin@studenthub.local` / `Admin@12345`. Click **Students** in the ☰ menu, or the button on the Admin Dashboard. Direct URL: <http://localhost/Practical-11/students.php>.
7. If the connection fails, check the settings at the top of `db.php` (port 3306, user `root`, empty password).

## 5. Screenshot checklist (take them yourself, with the address bar visible)

| # | Screenshot | How to get it | Filename |
|---|---|---|---|
| 1 | Records table | Open `students.php` → the table with its column headings | `D26CS116_Pr11_S1_student_list.png` |
| 2 | Successful addition | **+ Add Student** → fill e.g. ID `D26CS110`, Name `Yash Pandya`, Email `yash.pandya@example.edu`, Phone `9876543210`, a course, Year 2 → submit. Capture the list showing **"Student added successfully."** and the new row | `D26CS116_Pr11_S2_add_success.png` |
| 3 | Successful update | **Edit** on a row → change e.g. phone or year → *Save Changes*. Capture the list with **"Student updated successfully."** and the changed value | `D26CS116_Pr11_S3_update_success.png` |
| 4 | Search | Type `Aarav` (or an ID/email part) → *Search / Filter*. Capture the filtered rows with your term still in the box | `D26CS116_Pr11_S4_search.png` |
| 5 | Course filter | Choose *Information Technology* → *Search / Filter*; optionally add a search word too to show both together | `D26CS116_Pr11_S5_course_filter.png` |
| 6 | Delete confirmation | Click **Deactivate** on a row → capture the "Are you sure you want to deactivate this student?" page *before* clicking Yes | `D26CS116_Pr11_S6_delete_confirm.png` |
| 7 | Soft delete | Click **Yes, deactivate** → capture the list with **"Student deactivated successfully."** and the red *Inactive* badge. Then take a second capture of phpMyAdmin (see section 6) showing the row still exists with `status = inactive` | `D26CS116_Pr11_S7_soft_delete.png` (+ `…_S7b_phpmyadmin.png`) |
| 8 | Success + error messages | Success: any green message from tests 2, 3 or 7. Error (separate test): *Add Student* with a bad email / empty fields (shows "Please fill in all required fields." or "Invalid email address."), or add a duplicate Student ID (shows "A student with this ID or email already exists."). Capture both | `D26CS116_Pr11_S8_success_message.png`, `D26CS116_Pr11_S8_error_message.png` |
| 9 | CSV in Excel | Click **⬇ Export CSV** → open `student_records.csv` from Downloads in Excel (or Numbers/Google Sheets) → capture the grid with the headings | `D26CS116_Pr11_S9_csv_excel.png` |

## 6. How to verify soft delete in phpMyAdmin

1. Deactivate a student in the website (for example `D26IT101`).
2. In phpMyAdmin open `user_registration_db` → `students` → **Browse**.
3. The same row is still there and its `status` column now says **inactive** (and `updated_at` shows the time of the change). Nothing was removed.
4. Optional SQL tab: `SELECT student_id, name, status FROM students WHERE status = 'inactive';`

## 7. Test report (fill in "Actual result" and "Status" after YOUR run)

MySQL could not be installed in my environment, so **nothing below has been run on a real MySQL/XAMPP**. I ran the same PHP files with the PHP built-in web server against an in-memory SQLite stand-in (it can't check MySQL-specific behaviour such as the case-insensitive email uniqueness) and the last column records what I saw there. Treat it as a pre-check, not as your test result.

| Test case | Steps | Expected result | Actual result (yours) | Status | Screenshot | Observed with stand-in DB |
|---|---|---|---|---|---|---|
| 1 Student table | Admin opens `students.php` | Table with headings and real DB rows | | | `…_S1_student_list.png` | Rows from the database shown; inactive hidden by default |
| 2 Add student | Submit valid form | Redirect to list, "Student added successfully.", new row | | | `…_S2_add_success.png` | Added; flash shown once, not again on refresh; ID upper-cased; phone cleaned |
| 3 Update student | Edit → change → save | "Student updated successfully.", changed value in list | | | `…_S3_update_success.png` | Updated; duplicate email on edit rejected with the "already exists" message |
| 4 Search | Search `kabir`, an email part, an ID | Only matching rows; term kept; no-match message | | | `…_S4_search.png` | Correct rows; "No students found matching your search."; `%` and `' OR 1=1 --` matched nothing |
| 5 Course filter | Pick a course (+ search) | Only that course; works with search and status | | | `…_S5_course_filter.png` | Correct rows for filter alone and combined |
| 6 Delete confirmation | Click Deactivate | Confirmation page; nothing changed yet | | | `…_S6_delete_confirm.png` | "Are you sure you want to deactivate this student?"; GET did not change the row |
| 7 Soft delete | Click Yes | status = inactive, row kept, success message | | | `…_S7_soft_delete.png` | Row kept with `inactive`; second attempt → "already inactive."; unknown id → "Student not found."; missing CSRF token rejected |
| 8 Messages | One success + one error test | Green success, red error, no SQL details | | | `…_S8_*.png` | Required-field, invalid-email, phone, course/year tampering, duplicate and expired-token messages all shown |
| 9 CSV export | Export, open in Excel | Headings + real rows, accents correct, filter respected | | | `…_S9_csv_excel.png` | Headers `text/csv`, attachment `student_records.csv`, UTF-8 BOM present, filtered export correct, formula cell prefixed with `'`, empty result → "No students found to export." |
| Access control | Student account / logged-out opens the pages | Access Denied / redirect to login | | | (optional) | Student got 403 on all five endpoints; logged-out users redirected to login |

## 8. Troubleshooting

| Problem | Fix |
|---|---|
| 404 on `students.php` | Folder not directly inside `htdocs`, or wrong folder name in the URL |
| "Table 'user_registration_db.students' doesn't exist" (in `php_error_log`; the page shows "Unable to complete the operation.") | Import `students.sql` |
| Redirected to login | Not logged in, session timed out (`SESSION_TIMEOUT` in `timeout.php`) |
| "Access Denied" | You are logged in as a Student; log in as the Admin account |
| "Unable to connect to the database" | MySQL not running, or the settings in `db.php` are wrong |
| Excel shows `Ã©` instead of accents | Open via Excel's Data → From Text/CSV and choose UTF-8 (the file already has a UTF-8 BOM, so a normal double-click usually works) |
| Nothing downloads | Check the browser's Downloads, or whether the filters match no student (then you get "No students found to export.") |
| "headers already sent" | A blank line or space before `<?php` in an edited file |
| Errors in detail | `/Applications/XAMPP/xamppfiles/logs/php_error_log` |

## 9. Viva explanations

**CRUD** – Create, Read, Update, Delete: the four basic data operations. Here: *Create* = Add Student (INSERT), *Read* = student list (SELECT), *Update* = Edit Student (UPDATE), *Delete* = Deactivate (an UPDATE of `status`, because the practical requires soft delete).

**MySQLi prepared statements** – the SQL is written with `?` placeholders and sent to MySQL first (`prepare`), then the values are attached with `bind_param` (`s` = string, `i` = integer) and run (`execute`). Values can only be data, never SQL, so typing `'; DROP TABLE students; --` does nothing. Results are read with `bind_result` and `fetch`.

**Searching** – the form uses GET (`?q=aarav`), so the search is in the address bar. The query is `WHERE (name LIKE ? OR student_id LIKE ? OR email LIKE ?)` with the value `%aarav%` bound as a parameter. `%` and `_` typed by the user are escaped so they are treated as normal characters.

**Filtering** – the course dropdown is filled from `SELECT DISTINCT course`; the chosen course adds `AND course = ?`. Search, course and status are combined with `AND`, and only the conditions that are used are added.

**Soft deletion** – instead of `DELETE`, we run `UPDATE students SET status = 'inactive' WHERE id = ? AND status = 'active'`. The record stays in the database (history, reports, possible restore). The list hides inactive students by default; the Status dropdown can show them. Deactivation is a POST with a CSRF token, preceded by a confirmation page.

**CSV export** – the server sends headers `Content-Type: text/csv` and `Content-Disposition: attachment; filename="student_records.csv"` so the browser downloads a file. `fputcsv()` writes each row and correctly quotes commas and quotation marks. A UTF-8 BOM (`EF BB BF`) at the start lets Excel detect UTF-8. Cells starting with `= + - @` get an apostrophe to prevent CSV (formula) injection. Password hashes are never selected.

**Security extras** – only Admins can use these pages (`require_role('Admin')`, checked on the server for every request), every POST has a CSRF token, all output goes through `htmlspecialchars()` (`e()`), IDs are validated as positive integers, and database errors are logged, not shown.
