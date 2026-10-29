# Practical 10 – Secure Login System with Role-Based Access Control
Student: Arshit Vaghela (D26CS116) · Stack: PHP · MySQL (MySQLi) · HTML · CSS · PHP sessions · XAMPP on macOS

---

## 1. Summary of the existing project (Practical 9)

| Item | What was found |
|---|---|
| Folder | `Practical-9/` (static Student Hub site + PHP registration) |
| Database | `user_registration_db`, table `users` (`id, full_name, username, email, password, created_at`) |
| Connection | `db.php` used **PDO** (`$pdo`), root / empty password, 127.0.0.1:3306 |
| Registration | `register.php` (form) → `process_registration.php` (validation, CSRF, duplicate check, `password_hash()`, insert) |
| Shared helpers | `includes/config.php` (session start, `e()`, `redirect()`), used by `contact.php`, `register.php` |
| Login | `login.html` – only a **mock** login using browser localStorage (no database, no real security) |
| Dashboard | `dashboard.html` – static page reading localStorage |
| Design | `style.css` (teal/mustard theme, light + dark mode, hamburger menu below 1480px), `script.js` |
| Missing | `role` column, MySQLi, real login, session-based protection, role dashboards, timeout, logout |

Nothing from the interrupted session was in the ZIP (no Practical 10 files existed), so Practical 10 was built from the Practical 9 files only.

## 2. Completed / changed requirements

| # | Requirement | Where | Status |
|---|---|---|---|
| 1 | MySQLi connection, utf8mb4, safe errors, one config place | `db.php` | Done |
| 2 | Users table: name, username, email, hash, role; unique email + username; roles Admin/Student | `user_registration.sql` (ENUM `role`) | Done |
| 3 | Practical 9 users and data preserved | SQL only **adds** the role column; existing users become `Student` | Done |
| 4 | Login form (email, password, validation, messages, link to register) | `login.php` | Done |
| 5 | POST-only authentication, prepared statement, `password_verify`, `session_regenerate_id(true)`, role from DB only, unsupported roles rejected | `authenticate.php` | Done |
| 6 | Redirect by stored role | `authenticate.php` → `role_home()` | Done |
| 7 | General dashboard | `dashboard.php` | Done |
| 8 | Admin-only dashboard (Students get a 403 page) | `admin_dashboard.php` | Done |
| 9 | Student-only dashboard (Admins get a 403 page – strict separation) | `student_dashboard.php` | Done |
| 10 | Server-side inactivity timeout shared by all protected pages | `timeout.php` + `includes/auth.php` | Done |
| 11 | Secure logout, no-cache headers | `logout.php`, `send_no_cache_headers()` | Done |
| 12 | HttpOnly + SameSite cookies, Secure only on HTTPS | `includes/config.php` | Done |
| 13 | Existing design kept; new styles appended | `style.css` (new block at the end) | Done |
| 14 | Practical 9 registration still works | `process_registration.php` converted from PDO to MySQLi (only the database calls changed; validation, CSRF and messages are the same) | Done |

Extra security: CSRF tokens on login and logout, POST-only logout, dummy-hash check so unknown emails take about as long as wrong passwords, role re-checked in the database on every protected request, all output escaped with `e()`.

Other changes you should know about
- `login.html` (the mock login) is now a tiny redirect to `login.php`, and every "Login" link in the site points to `login.php`. The static `dashboard.html` and other pages are otherwise untouched.
- `schema.sql` was replaced by `user_registration.sql` (same database, safe to import on top of Practical 9).
- `register.php` is unchanged except the "Go to Login" link.

## 3. Final folder structure

```
Practical-10/
├── db.php                    MySQLi connection (settings in one place)
├── login.php                 login form
├── authenticate.php          checks credentials, creates the session
├── dashboard.php             any logged-in user
├── admin_dashboard.php       Admin only
├── student_dashboard.php     Student only
├── logout.php                secure logout (POST)
├── timeout.php               inactivity timeout (SESSION_TIMEOUT)
├── style.css                 existing theme + Practical 10 styles
├── user_registration.sql     database script (safe to re-import)
├── register.php, process_registration.php   Practical 9 registration (now MySQLi)
├── contact.php, process.php, check.php, login.html (redirect), other *.html, script.js, js/, data/, storage/
└── includes/
    ├── config.php            session settings, e(), redirect(), destroy_session_completely()
    ├── auth.php              require_login(), require_role(), no-cache headers
    └── layout.php            shared header / footer for protected pages
```

## 4. Test accounts (created by the SQL file)

| Role | Email | Password |
|---|---|---|
| Admin | `admin@studenthub.local` | `Admin@12345` |
| Student | `student@studenthub.local` | `Student@12345` |

Delete these two rows before any real use. You can also register your own account on `register.php` (it becomes a Student automatically) and promote it in phpMyAdmin with: `UPDATE users SET role='Admin' WHERE email='you@example.com';`

## 5. XAMPP + phpMyAdmin setup (macOS)

1. **Copy the project.** Copy the `Practical-10` folder into `/Applications/XAMPP/xamppfiles/htdocs/` (Finder → Go → Go to Folder…). You should end up with `.../htdocs/Practical-10/login.php`.
2. **Start the servers.** Open XAMPP → *Manage Servers* → start **Apache Web Server** and **MySQL Database** (both green).
3. **Open phpMyAdmin:** <http://localhost/phpmyadmin> (use `localhost:8080` if your Apache runs on port 8080).
4. **Apply the SQL safely.** Click the **Import** tab at server level (not inside a database) → *Choose file* → `user_registration.sql` → *Import*. It never drops a table, deletes a user or changes a password, so it is safe on your Practical 9 database and safe to run twice.
5. **Verify the accounts.** Left list → `user_registration_db` → `users` → *Browse*. Check that: a `role` column exists; `admin@studenthub.local` is `Admin`; `student@studenthub.local` is `Student`; every `password` value starts with `$2y$` (a hash, never plain text); your old Practical 9 users are still there with role `Student`.
6. **Open the project:** <http://localhost/Practical-10/login.php> (add `:8080` after `localhost` if needed). Registration is at <http://localhost/Practical-10/register.php>.
7. **Test the nine cases** (sections 6 and 7).

If your MySQL uses another port or a root password, edit only the settings block in `db.php`.

### Short timeout for testing (Test 8)
1. In `timeout.php` change `const SESSION_TIMEOUT = 300;` to `const SESSION_TIMEOUT = 30;` and save.
2. Log in, wait more than 30 seconds without clicking, then click a protected page or reload it.
3. **Change it back to `300` (5 minutes) afterwards** and save.

### Temporary debugging for Test 9 (session ID regeneration)
In `authenticate.php` find the line `session_regenerate_id(true);` and temporarily replace it with:

```php
// TEMP-DEBUG-START
$sidBefore = session_id();
session_regenerate_id(true);
$sidAfter = session_id();
error_log('SID-DEBUG before=' . substr(hash('sha256', $sidBefore), 0, 8) . ' after=' . substr(hash('sha256', $sidAfter), 0, 8));
// TEMP-DEBUG-END
```
Log in, then open `/Applications/XAMPP/xamppfiles/logs/php_error_log` (Console app, or `tail -n 5` in Terminal). You should see two **different** short fingerprints. Only the first 8 characters of a hash are logged, never the real session ID. Take your screenshot of those lines. **Then put back the original single line `session_regenerate_id(true);`** and make sure no `TEMP-DEBUG` text remains in the file.

## 6. Nine-case screenshot checklist

| # | Test | Steps | Expected | Screenshot file |
|---|---|---|---|---|
| 1 | Successful Student login | `login.php` → student account | `student_dashboard.php` with name and role **Student** | `D26CS116_Pr10_T1_student_login.png` |
| 2 | Successful Admin login | Log out → admin account | `admin_dashboard.php` with role **Admin** and user table | `D26CS116_Pr10_T2_admin_login.png` |
| 3 | Invalid credentials | Student email + wrong password (also try an unknown email); then open `dashboard.php` | "Invalid email or password."; `dashboard.php` bounces to login (no session) | `D26CS116_Pr10_T3_invalid_login.png` |
| 4 | Student opens admin page | Login as Student → type `…/admin_dashboard.php` | "Access Denied" (HTTP 403), no admin data | `D26CS116_Pr10_T4_student_denied.png` |
| 5 | Admin opens student page | Login as Admin → type `…/student_dashboard.php` | "Access Denied" (strict separation) | `D26CS116_Pr10_T5_admin_denied.png` |
| 6 | Successful logout | Login → click **Logout** (inside the ☰ menu unless the window is very wide) | `login.php` with "You have been logged out successfully." | `D26CS116_Pr10_T6_logout.png` |
| 7 | Direct access after logout | After Test 6 open `dashboard.php`, `admin_dashboard.php`, `student_dashboard.php`; press Back | Always sent to login with "Please log in to view that page." | `D26CS116_Pr10_T7_after_logout.png` |
| 8 | Session timeout | Set `SESSION_TIMEOUT` to 30, login, wait 35 s, click Dashboard | Login page with "Your session expired because of inactivity." | `D26CS116_Pr10_T8_timeout.png` |
| 9 | Session ID regeneration | Temporary debug code above | Log shows `before=…` and `after=…` that differ | `D26CS116_Pr10_T9_session_regen.png` |

Include the address bar in each screenshot so the URL is visible.

## 7. Test report table

MySQL could not be installed in my environment, so **none of these have been run on a real MySQL/XAMPP**. I ran the same PHP files against an in-memory stand-in for the database (fixed test users, 4-second timeout, PHP's built-in web server). The last column shows what I observed there; it is not a substitute for your own run. Fill in "Actual result" and "Status" from your XAMPP.

| Test | Steps | Expected | Actual result (yours) | Status | Screenshot | Observed with stand-in DB |
|---|---|---|---|---|---|---|
| 1 | Login as Student | Student dashboard + role | | | `D26CS116_Pr10_T1_student_login.png` | Redirected to `student_dashboard.php`, role Student shown |
| 2 | Login as Admin | Admin dashboard + role | | | `D26CS116_Pr10_T2_admin_login.png` | Redirected to `admin_dashboard.php`, user list shown |
| 3 | Wrong password | Generic error, no session | | | `D26CS116_Pr10_T3_invalid_login.png` | "Invalid email or password."; `dashboard.php` then redirected to login |
| 4 | Student → admin page | Access denied | | | `D26CS116_Pr10_T4_student_denied.png` | HTTP 403, no admin content |
| 5 | Admin → student page | Access denied | | | `D26CS116_Pr10_T5_admin_denied.png` | HTTP 403 |
| 6 | Logout | Logout message | | | `D26CS116_Pr10_T6_logout.png` | Cookie expired, redirect with success message |
| 7 | Direct URLs after logout | Redirect to login | | | `D26CS116_Pr10_T7_after_logout.png` | All three pages redirected to login |
| 8 | Timeout | Redirect + message | | | `D26CS116_Pr10_T8_timeout.png` | Activity kept the session alive; idle past the limit redirected with the timeout message |
| 9 | Session ID regeneration | ID changes | | | `D26CS116_Pr10_T9_session_regen.png` | Session ID differed before and after login |

## 8. Troubleshooting

| Problem | Fix |
|---|---|
| "Unable to connect to the database" | MySQL not started in XAMPP; wrong port/password in `db.php`; SQL not imported yet. Details are in `/Applications/XAMPP/xamppfiles/logs/php_error_log`. |
| MySQL won't start (port 3306 busy) | Another MySQL (Homebrew / mysql.com) is running. Stop it, or change the port in XAMPP and in `db.php`. |
| `Class "mysqli" not found` | Open the site through XAMPP's Apache (`http://localhost/…`), which has MySQLi enabled. |
| "Invalid email or password" for test accounts | SQL not imported, or `INSERT IGNORE` skipped them because that username/email already existed. Check `users` in phpMyAdmin, delete the conflicting row and import again. Passwords are case-sensitive. |
| Always returned to login | Cookies blocked, or session expired (check `SESSION_TIMEOUT`), or the user's role is not exactly `Admin`/`Student`. |
| "headers already sent" | Something is printed before a redirect – make sure `<?php` is the very first thing in the file (no blank line or space before it). |
| 404 on `login.php` | The folder is not directly inside `htdocs`, or the URL folder name differs. |
| Import error #1064 near PREPARE | Very old MySQL: run this one line instead, then import the rest: `ALTER TABLE users ADD COLUMN role ENUM('Admin','Student') NOT NULL DEFAULT 'Student' AFTER password;` |

## 9. Viva explanations (beginner friendly)

**MySQLi** – PHP's extension for talking to MySQL. `new mysqli(host, user, pass, db, port)` opens the connection and `$conn->prepare()` creates prepared statements. (Practical 9 used PDO; this practical asks for MySQLi.)

**Prepared statements** – the SQL with `?` placeholders is sent to MySQL first and the values are sent separately with `bind_param()`. The database treats values only as data, never as SQL, so typing `' OR 1=1 --` in the email box cannot change the query. This prevents SQL injection.

**Password hashing** – the real password is never stored. `password_hash()` makes a one-way, salted bcrypt hash (starts with `$2y$`); two users with the same password get different hashes. At login `password_verify($typed, $hash)` checks it. If the database leaks, the passwords are still not exposed. MD5/SHA1 are not suitable for passwords.

**Authentication vs authorization** – authentication proves *who you are* (email + password checked in `authenticate.php`). Authorization decides *what you may do* (the role check `require_role('Admin')` on each page). Both happen on the server; hiding a button is not security.

**Sessions** – HTTP forgets users between requests. PHP gives the browser a random session ID in a cookie (`PHPSESSID`) and keeps the data (`$_SESSION`: id, name, email, role) on the server. The cookie is `HttpOnly` (JavaScript cannot read it), `SameSite` (not sent on cross-site posts) and `Secure` when the site runs on HTTPS.

**Session ID regeneration** – `session_regenerate_id(true)` after a successful login issues a new ID and deletes the old one. This stops *session fixation*, where an attacker plants a known ID before the victim logs in.

**Timeout** – `last_activity` is stored in the session. On every protected request, if `now − last_activity > SESSION_TIMEOUT` the session is destroyed and the user goes to the login page with a message; otherwise the time is refreshed. It runs in PHP, so switching off JavaScript cannot bypass it.

**Logout** – clears `$_SESSION`, expires the session cookie, calls `session_destroy()`, then redirects to `login.php?status=logout`. It is a POST with a CSRF token so another site cannot log you out. No-cache headers on protected pages stop the Back button from showing old content.

**Role-based access control** – the role (`Admin`/`Student`) lives in the database, is copied into the session at login and is re-checked in the database on each request. It is never taken from a form field or the URL.

**CSRF** – a hidden random token in each form must match the one in the session, so a forged form from another website is rejected.
