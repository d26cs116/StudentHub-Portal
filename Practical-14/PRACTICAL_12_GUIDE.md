# Practical 12 – Admin Event Management with Poster Upload
Student: Arshit Vaghela (D26CS116) · Stack: PHP · MySQL (MySQLi) · HTML · CSS · JavaScript · XAMPP on macOS

---

## 1. Existing project (what I built on)

Latest version = the **Practical 11** project from this chat (your Practical 9 files + Practical 10 login/roles + Practical 11 student management). Practical 12 is added inside it; nothing was replaced.

| Reused as it is | Purpose |
|---|---|
| `db.php` | the one MySQLi connection (`$conn`, database `user_registration_db`) |
| `login.php`, `authenticate.php`, `logout.php`, `timeout.php` | existing login, sessions, 5-minute timeout, logout |
| `includes/auth.php` | `require_role('Admin')` – the server-side Admin check used on every event page |
| `includes/student_helpers.php` | flash messages, CSRF token, `clean_id()` (reused by the event pages) |
| `admin_dashboard.php` | the Admin Dashboard (now has a **Manage Events** button) |
| `style.css`, `includes/layout.php` | design, header and menu (menu now has **Events** for Admins) |

## 2. Files created / modified

**New**
| File | What it does |
|---|---|
| `events.sql` | creates the `events` table (safe to import more than once) |
| `events.php` | event list with posters, Edit and Delete |
| `add_event.php` | create an event with an optional poster |
| `edit_event.php` | edit an event; keep or replace the poster |
| `delete_event.php` | confirmation page (GET) + permanent delete (POST) |
| `includes/event_helpers.php` | validation, **poster upload checks**, saving and deleting poster files |
| `includes/event_form.php` | the shared Add/Edit form |
| `uploads/event_posters/.htaccess` | blocks scripts and non-image files in the upload folder |
| `uploads/event_posters/index.html` | empty page so nobody can browse the folder |
| `PRACTICAL_12_GUIDE.md` | this guide |

**Modified (small additions only):** `includes/layout.php` (Events link for Admin), `admin_dashboard.php` (Manage Events button), `style.css` (Practical 12 block at the end).
**Removed:** nothing.

## 3. Final folder structure

```
Practical-12/
├── events.php  add_event.php  edit_event.php  delete_event.php      (new)
├── events.sql                                                        (new)
├── uploads/event_posters/   .htaccess  index.html  (posters are saved here)   (new)
├── includes/
│   ├── event_helpers.php  event_form.php                             (new)
│   ├── student_helpers.php  auth.php  config.php  layout.php         (existing)
├── students.php, add_student.php, edit_student.php, delete_student.php, export_students.php, students.sql   (Practical 11)
├── db.php, login.php, authenticate.php, logout.php, timeout.php, dashboard.php,
│   admin_dashboard.php, student_dashboard.php, register.php, user_registration.sql, style.css, ...   (earlier practicals)
└── PRACTICAL_10_GUIDE.md, PRACTICAL_11_GUIDE.md, PRACTICAL_12_GUIDE.md
```

## 4. Database change (safe)

`events.sql` only does `CREATE TABLE IF NOT EXISTS events (...)`. It does not touch `users` or `students`, drops nothing and inserts no sample rows (so every poster you see is a real upload).

Columns: `id` (primary key), `event_name`, `description`, `event_date` (DATE), `event_time` (TIME, optional), `venue`, `poster` (file **name** only, NULL = none), `created_at`, `updated_at`.

**Apply it:** phpMyAdmin (<http://localhost/phpmyadmin>) → **Import** tab → choose `events.sql` → **Import**. Then open `user_registration_db` → you should see `events` next to `users` and `students`.

## 5. XAMPP setup (macOS)

1. Copy the whole `Practical-12` folder into `/Applications/XAMPP/xamppfiles/htdocs/` (so `.../htdocs/Practical-12/events.php` exists). If you prefer to keep your existing folder, copy the new files and the `uploads` folder into it and use that folder name in the URLs.
2. Start **Apache** and **MySQL** in the XAMPP app. Make sure Practical 10 (`user_registration.sql`) and Practical 11 (`students.sql`) were imported before, then import `events.sql` (section 4).
3. Connection check: settings are at the top of `db.php` (127.0.0.1, port 3306, user `root`, empty password, database `user_registration_db`). If login works, the connection works.
4. **Upload folder permission (important on macOS).** Apache runs as a different user from you, so it may not be allowed to write into `uploads/event_posters/`. If a valid poster fails with "Unable to upload poster.", run in Terminal:
   ```
   chmod -R 777 /Applications/XAMPP/xamppfiles/htdocs/Practical-12/uploads/event_posters
   ```
   (777 is fine for local practice only.) Details of any failure are written to `/Applications/XAMPP/xamppfiles/logs/php_error_log`.
5. Open <http://localhost/Practical-12/login.php> and log in:
   - Admin: `admin@studenthub.local` / `Admin@12345`
   - Student (for screenshot 10): `student@studenthub.local` / `Student@12345`
   (These are the accounts from `user_registration.sql`. A registered account is a Student; promote one to Admin in phpMyAdmin with `UPDATE users SET role='Admin' WHERE email='you@example.com';`)
6. As Admin you land on the **Admin Dashboard** → click **Manage Events** (or open <http://localhost/Practical-12/events.php>).

### PHP upload-size settings (`upload_max_filesize` and `post_max_size`)
The application limit is **2 MB** (`POSTER_MAX_BYTES` in `includes/event_helpers.php`). PHP has its own limits that apply first:
- `upload_max_filesize` – the biggest single file PHP accepts.
- `post_max_size` – the biggest whole form (all fields + files) PHP accepts. It must be at least as large as `upload_max_filesize`.

In XAMPP both are usually 40M, so a 3 MB file reaches the application and is rejected by the 2 MB rule. If a lower value is set (for example 2M), PHP rejects the file itself and the application still shows the same "too large" message. If `post_max_size` is exceeded PHP throws the whole form away; the application detects that and also shows the same message. To change them: edit `/Applications/XAMPP/xamppfiles/etc/php.ini` (lines `upload_max_filesize`, `post_max_size`, `file_uploads = On`), save, then **restart Apache**. You do not need to change them for this practical.

### Harmless test files for screenshots 7 and 8 (Terminal)
```
echo "This is only a harmless text file." > ~/Desktop/not_an_image.txt     # invalid type
mkfile 3m ~/Desktop/too_large_test.jpg                                      # 3 MB of zeros, over the 2 MB limit
```
For a valid poster use any small JPEG/PNG/WebP picture (under 2 MB) from your Mac.

## 6. Screenshot checklist (take them yourself; show the address bar)

| # | Screenshot | How to get it | Filename |
|---|---|---|---|
| 1 | Admin login + dashboard | Log in as Admin → Admin Dashboard with **Manage Events** and the Events menu item (☰ menu) | `D26CS116_Pr12_S1_admin_dashboard.png` |
| 2 | Successful creation | Manage Events → **+ Add Event** → fill all fields + a valid image → **Create Event**. Capture the list with "Event created successfully. Poster uploaded successfully." | `D26CS116_Pr12_S2_event_created.png` |
| 3 | Event list with posters | `events.php` showing your new event and its real poster image (add 2–3 events if you want a fuller list) | `D26CS116_Pr12_S3_event_list.png` |
| 4 | Successful update | **Edit** an event → change e.g. the venue or name → **Save Changes**. Capture the list with "Event updated successfully." and the new value | `D26CS116_Pr12_S4_event_updated.png` |
| 5 | Successful deletion | **Delete** → capture the confirmation ("Are you sure you want to delete this event?"), then **Yes, delete** → capture "Event deleted successfully." with the event gone from the list (two images, or one combined) | `D26CS116_Pr12_S5_delete_confirm.png`, `D26CS116_Pr12_S5_event_deleted.png` |
| 6 | Valid poster upload | Same as screenshot 2 or 4 with a JPEG/PNG/WebP under 2 MB; show the poster in the list | `D26CS116_Pr12_S6_valid_poster.png` |
| 7 | Invalid file type | Add Event with `not_an_image.txt` as poster → capture the red "Invalid poster format. Please upload a JPEG, PNG, or WebP image." Then check the list: no new event; and the folder `uploads/event_posters/` has no new file | `D26CS116_Pr12_S7_invalid_type.png` |
| 8 | File size limit | Add Event with `too_large_test.jpg` → capture "Poster file is too large. Maximum allowed size is 2 MB." No event is created | `D26CS116_Pr12_S8_too_large.png` |
| 9 | Success + error messages | One genuine green message (from 2, 4 or 5) and one genuine red message from a separate test (7, 8, or submit the Add form with empty fields → "Please fill in all required fields.") | `D26CS116_Pr12_S9_success.png`, `D26CS116_Pr12_S9_error.png` |
| 10 | Unauthorized access | Log out, log in as the **Student** account, type `http://localhost/Practical-12/events.php` (or `add_event.php`) in the address bar → "Access Denied". Optionally also open it while logged out → redirected to login | `D26CS116_Pr12_S10_student_denied.png` |

Screenshot 10 also needs proof that direct requests are refused. The same server check (`require_role('Admin')`) runs before any create/update/delete code, so a Student's direct POST gets the same "Access Denied" (403) and changes nothing. I tested this by sending such requests with curl (see section 7); you may repeat it with the optional Terminal commands in section 8.

## 7. Test report

**Status meaning:** "Passed (stand-in)" = I ran the real PHP files with PHP's built-in web server against an in-memory SQLite stand-in for MySQL (MySQL could not be installed in my environment), with real HTTP uploads. "Not tested" = needs your XAMPP. **No test has been run on real MySQL/XAMPP or in a real browser upload** (I did render the pages in a headless browser to check the layout). Fill "Your actual result" and "Final status" after your own run.

| Test case | Test steps | Expected result | Actual result (stand-in run) | Status | Your actual result / final status | Screenshot |
|---|---|---|---|---|---|---|
| 1 Admin login + dashboard | Log in as Admin | Admin Dashboard with Manage Events | Redirect to `admin_dashboard.php`; heading and button present | Passed (stand-in) | | `…_S1_admin_dashboard.png` |
| 2 Create event | Valid fields + PNG | Redirect to list, "Event created successfully. Poster uploaded successfully." | Done; refresh did not repeat the message or create a second event | Passed (stand-in) | | `…_S2_event_created.png` |
| 3 List with posters | Open `events.php` | Real poster, "No poster" placeholder when none | `<img>` points to the uploaded file; placeholder for the no-poster event | Passed (stand-in) | | `…_S3_event_list.png` |
| 4 Update | Edit text only; edit with new poster | Success; poster kept when no file chosen; replaced when chosen, old file removed | Poster name unchanged without a file; with a new WebP the DB name changed, the old file was deleted, the new one exists | Passed (stand-in) | | `…_S4_event_updated.png` |
| 5 Delete | Confirm page, POST delete | Confirmation first; row and poster file removed; missing event handled | GET changed nothing; no CSRF token rejected; POST removed row + file; deleting again → "Event not found." | Passed (stand-in) | | `…_S5_*.png` |
| 6 Valid upload | JPEG, PNG and WebP under 2 MB | All accepted, saved under a random name | All three accepted; file names are 32 random hex characters + `.jpg/.png/.webp`; permissions 644 | Passed (stand-in) | | `…_S6_valid_poster.png` |
| 7 Invalid type | `.txt`, `.pdf`, PHP code named `.jpg`, PHP code behind a PNG header, PNG renamed `.jpg`, empty `.png` | "Invalid poster format…", nothing saved | All six rejected with that message; database rows and files unchanged | Passed (stand-in) | | `…_S7_invalid_type.png` |
| 8 Size limit | 3 MB file; 10 MB file (over `post_max_size` 8M); 2.5 MB with PHP's own 2M limit | "Poster file is too large. Maximum allowed size is 2 MB." | Same message in all three cases; nothing saved; on edit the old poster stayed | Passed (stand-in) | | `…_S8_too_large.png` |
| 9 Messages | Empty form; bad date/time; no CSRF token; valid actions | Clear red/green messages, values kept, no SQL/path details | "Please fill in all required fields.", "Enter a valid date.", "Enter a valid time (HH:MM).", session-expired message; entered values kept | Passed (stand-in) | | `…_S9_*.png` |
| 10 Access control | Student and logged-out visitor open all four pages; Student sends a direct POST to add and delete | Denied / redirected, nothing changes | Student: HTTP 403 on every page and on both POSTs; logged-out: redirect to login; no event or file created | Passed (stand-in) | | `…_S10_student_denied.png` |
| Safety: unsafe `poster` value in DB | Set `poster` to `../../db.php` and delete that event | Never displayed as an image, never deleted | Not shown; `db.php` still existed | Passed (stand-in) | | – |
| Upload folder `.htaccess` | Open `http://localhost/Practical-12/uploads/event_posters/` and a poster URL | Folder listing → 403; the image itself loads | Apache rules written, **not testable here** | Not tested | | optional |
| MySQL `events.sql` import | Import in phpMyAdmin | `events` table created | **Not run on MySQL** | Not tested | | – |
| Real browser upload on XAMPP | All of the above in your browser | See above | **Not run** | Not tested | | all |

## 8. Optional: check direct requests yourself (Terminal)

With the Student logged in in your browser you cannot easily copy the cookie, so the simplest proof is the browser test in screenshot 10. For a logged-out check:
```
curl -i http://localhost/Practical-12/add_event.php
```
You should see `302 Found` with `Location: login.php?status=login_required`. A direct POST without logging in is redirected the same way.

## 9. Troubleshooting

| Problem | Fix |
|---|---|
| 404 on `events.php` | Folder not directly inside `htdocs` or wrong folder name in the URL |
| "Unable to complete the operation." on the events page | `events.sql` not imported, or MySQL stopped; see `php_error_log` |
| Valid poster gives "Unable to upload poster." | Folder permission (run the `chmod` in section 5); or the PHP **fileinfo** extension is disabled (enable `extension=fileinfo` in `php.ini`, restart Apache) |
| Poster saved but shows "Poster file missing" | The file was removed from `uploads/event_posters/` or the folder is not inside the project folder you are browsing |
| Every upload says "too large" | `post_max_size` / `upload_max_filesize` in `php.ini` too small (section 5) |
| Poster image is blocked (403) | The `.htaccess` only allows `.jpg .jpeg .png .webp`. Apache must allow `.htaccess` (XAMPP does by default) |
| "Access Denied" | You are logged in as a Student; use the Admin account |
| Redirected to login | Not logged in or session timed out (`SESSION_TIMEOUT` in `timeout.php`) |

## 10. How it works (beginner explanation)

- **Admin only:** every event page starts with `require_role('Admin')`. It checks the PHP session on the server (not the browser), so typing the URL or sending a hand-made request is refused (403 for a Student, redirect to login if not logged in). The existing timeout and logout are reused unchanged.
- **Create:** the form uses `enctype="multipart/form-data"` and POST with a CSRF token. The server checks every field, then the poster, then saves the file and inserts the row with a **prepared statement**. If the insert fails, the just-saved file is removed. After success the page redirects to the list (Post/Redirect/Get) so a refresh cannot create a duplicate.
- **Poster validation (all on the server)**
  1. PHP's upload error code (too big, partial, no temp folder…).
  2. The size must be ≤ 2 MB.
  3. The original extension must be jpg/jpeg/png/webp.
  4. The real content type is read from the file's bytes with **Fileinfo** (`finfo`), and `$_FILES['poster']['type']` is ignored. The type must be JPEG, PNG or WebP and must match the extension.
  5. `getimagesize()` must really decode it as an image.
  6. The file is stored under a random 32-character name chosen by the server, never the visitor's file name, so path tricks like `../` cannot work.
- **Safe folder:** `uploads/event_posters/.htaccess` turns off script handling and allows only image files, so an uploaded script could never run.
- **Update:** the new poster is saved first, then the row is updated, and only then is the old file deleted (and only if its name is one of our generated names). If the update fails, the new file is removed and the old poster stays. No new file = the old poster is kept.
- **Delete:** the Delete link opens a confirmation page; the actual delete is a POST with a CSRF token. The row is deleted first, then the poster file (only if it is a generated name inside the poster folder and no other event uses it). Events are deleted permanently, unlike the students from Practical 11, who are only set to inactive.
- **Messages:** one flash message is stored in the session and shown once after the redirect. Raw SQL errors and file paths go to the PHP error log, never to the page.

## 11. Viva questions

1. **What is CRUD?** Create, Read, Update, Delete: `add_event.php` (INSERT), `events.php` (SELECT), `edit_event.php` (UPDATE), `delete_event.php` (DELETE).
2. **Why prepared statements?** The SQL and the values are sent separately (`prepare`, `bind_param`, `execute`), so user text can never change the query – this stops SQL injection.
3. **Why not trust the file extension or `$_FILES['type']`?** Both are chosen by the visitor. A script can be renamed `poster.jpg`. We read the real content type with Fileinfo, decode it with `getimagesize()`, and save it under our own name and extension.
4. **What do `upload_max_filesize` and `post_max_size` do?** They are PHP's own limits for one file and for the whole form. Our 2 MB rule is checked in PHP code in addition, and the app shows the same message in all cases.
5. **How do you stop uploaded files from running as code?** Random names with a fixed image extension, content verified as an image, and an `.htaccess` in the upload folder that disables scripts and allows images only.
6. **How is access restricted?** Role-based access control: the role is stored in the database and in the session at login; every protected page calls `require_role('Admin')` on the server. Hiding a button would not be enough.
7. **What is CSRF and how is it prevented?** A forged request from another site. Every POST carries a secret token stored in the session; a request without it is rejected.
8. **How is deletion done securely?** Confirmation page, POST only, CSRF token, ID validated, prepared `DELETE`, and the poster file removed only after the row is gone and only if its name is one of ours.
9. **Why store only the file name in MySQL?** Images are large; the database keeps the small name and the file lives in the uploads folder. Different from the BLOB approach, which makes the database big and slow.
10. **Why delete the old poster only after the update succeeds?** If the update failed, deleting first would leave an event with no poster.
