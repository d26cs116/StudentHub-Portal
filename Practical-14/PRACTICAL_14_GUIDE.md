# Practical 14 – Admin Dashboard, Role-Based Access Control (RBAC) and Audit Logs
Student: Arshit Vaghela (D26CS116) · Stack: PHP · MySQL (MySQLi) · Bootstrap 5 · XAMPP on macOS

---

## 1. What already existed and what was added

Practical 14 is added **inside your existing project** (the Practical 10–13 files). Reused unchanged: `login.php`, the session/timeout code, `users.role` (`Admin` / `Student`), and `includes/auth.php` – its `require_login()` / `require_role('Admin')` already check the role on the server **against the database on every request** and `deny_access()` already sends a real `http_response_code(403)` page.

**There is no "event registration" table in the project** (only `users`, `students`, `events`). So "Latest registrations" shows the newest **user registrations** (accounts created through `register.php`, newest `users.created_at` first). Nothing else was invented.

### Files created
| File | Purpose |
|---|---|
| `includes/audit.php` | `audit_log()` (writes one audit row, throws on failure), `audit_log_safe()`, `db_rollback_quietly()` |
| `audit_logs.php` | Admin-only, read-only list of the latest 50 audit entries (Bootstrap table) |
| `practical14/schema.sql` | `audit_logs` table with a foreign key to `users` (+ safe `IF NOT EXISTS` copies of `users`, `students`, `events`) |
| `PRACTICAL_14_GUIDE.md`, `practical14/evidence/` | this guide, test results and screenshots |

### Files modified (small additions only)
| File | Change |
|---|---|
| `dashboard.php` | Bootstrap dashboard: for **Admin** only – live `COUNT(*)` cards (users, students, events) and the 5 latest registrations. Students still get their account tiles |
| `includes/layout.php` | optional Bootstrap link (`render_header(..., $bootstrap)`), **Audit Logs** menu item for Admin only |
| `includes/auth.php` | one line: loads `audit.php` |
| `authenticate.php` | records a successful **Admin** login |
| `add_event.php`, `edit_event.php`, `delete_event.php` | event create / update / delete wrapped in a transaction + audit row |
| `add_student.php`, `edit_student.php`, `delete_student.php` | same for student create / update / deactivate |

Nothing was deleted; no existing table or record is changed.

## 2. Run it on macOS with XAMPP

1. Copy the project folder into `/Applications/XAMPP/xamppfiles/htdocs/` and start **Apache** and **MySQL** in XAMPP.
2. Open `http://localhost/phpmyadmin` → **Import** → choose `practical14/schema.sql` → **Import**. Check that `user_registration_db` now has the table **`audit_logs`** (Structure tab → Relation view shows the foreign key to `users`). Safe to import twice. If the database does not exist yet, import `user_registration.sql` first (it also creates the two test accounts).
3. Connection settings are the ones at the top of `db.php` (127.0.0.1, port 3306, user `root`, empty password, database `user_registration_db`) – unchanged.
4. Open `http://localhost/<your-folder>/login.php`.
   - Admin: `admin@studenthub.local` / `Admin@12345`
   - Student: `student@studenthub.local` / `Student@12345`
   (the two accounts from `user_registration.sql`)
5. Admin: after login click **Dashboard** in the menu (`dashboard.php`). Bootstrap is loaded from the CDN, so you need internet.

## 3. Screenshots to take (one by one)

| # | Screenshot | How | File name |
|---|---|---|---|
| 1 | Dynamic statistics | Admin → **Dashboard**: the three cards (users, students, events) | `Practical14_AdminDashboard.png` |
| 2 | Latest registrations | same page, table below the cards. To get several rows, register 2–3 accounts at `register.php` first | `Practical14_LatestRegistrations.png` |
| 3 | (optional) zero counts | not needed; see test table | – |
| 4 | Admin menu | Admin logged in: menu shows Home, Dashboard, Admin Panel, Students, Events, Audit Logs, Logout | `Practical14_AdminMenu.png` |
| 5 | Student menu | Log out, log in as Student: menu shows Home, Dashboard, Student Area, Logout | `Practical14_StudentMenu.png` |
| 6 | 403 Forbidden | As Student type `http://localhost/<folder>/audit_logs.php` (or `events.php`) in the address bar → "Access Denied". For the real status: Chrome → Inspect → **Network** → reload → the first row shows **403** (screenshot with the address bar and the Status column) | `Practical14_403Forbidden.png` |
| 7 | Do an admin action | As Admin add, edit or delete an event (menu → Events) and capture the green message | `Practical14_ActionDone.png` |
| 8 | Audit table in phpMyAdmin | `http://localhost/phpmyadmin` → `user_registration_db` → `audit_logs` → **Browse** (shows ADMIN_LOGIN, EVENT_CREATE …). Also the app page `audit_logs.php` if you like | `Practical14_AuditLogs.png` |

## 4. Dashboard queries (how they work)

```sql
SELECT COUNT(*) FROM users;      -- same for students and events; an empty table gives 0
SELECT id, full_name, username, email, role, created_at
FROM users ORDER BY created_at DESC, id DESC LIMIT ?;   -- latest registrations (? = 5, a bound value)
```
All output is escaped with `e()` (htmlspecialchars). A database error shows a generic Bootstrap alert and the real reason goes only to the PHP error log. Only an Admin session ever runs these queries.

## 5. RBAC and HTTP 403

- **Menu:** `render_header()` builds the menu from `$_SESSION['role']`: logged out → Home, Register, Login; Student → Home, Dashboard, Student Area; Admin → adds Admin Panel, Students, Events, Audit Logs.
- **The menu is not the security.** Every admin page starts with `require_role('Admin')`, which (1) checks the session timeout, (2) checks that a user is logged in (otherwise redirect to login), (3) re-reads the user's current role from MySQL, (4) calls `deny_access()` when it is not Admin: `http_response_code(403)` + the Access Denied page; the protected content is never sent. This also covers direct POST requests.
- Protected admin pages: `admin_dashboard.php`, `students.php`, `add/edit/delete_student.php`, `export_students.php`, `events.php`, `add/edit/delete_event.php`, `audit_logs.php`.

## 6. Audit logging

- `audit_logs` columns: `id`, `user_id` (FK → `users.id`, `ON DELETE SET NULL`), `action`, `entity_type`, `entity_id`, `details`, `ip_address`, `created_at`. `entity_id` has no foreign key on purpose, so a deleted event stays visible in the log.
- Logged actions: `ADMIN_LOGIN`, `EVENT_CREATE`, `EVENT_UPDATE`, `EVENT_DELETE`, `STUDENT_CREATE`, `STUDENT_UPDATE`, `STUDENT_DEACTIVATE` (the students page deactivates; it never deletes).
- **Only after success:** the log call comes after the SQL succeeded, inside the same **transaction** (`begin_transaction` … `commit`). If the audit insert fails, the whole action is rolled back, so there is never an admin change without a log entry. A failed, invalid, duplicate or "not found" action writes nothing.
- The admin id comes from the session, the IP from `REMOTE_ADDR`. Only short descriptions are stored (event name, student ID) – never passwords, hashes or session tokens.
- There is no page or function that changes or deletes audit rows, and `audit_logs.php` is Admin-only and read-only.

**Design limitations (honest):** (1) a poster file written to disk cannot join a database transaction – on failure the code removes the new file, as in Practical 12; (2) `ADMIN_LOGIN` is best-effort (a logging problem is only written to the error log so an admin is not locked out); (3) a person with MySQL access (phpMyAdmin as root) can still edit the table – for production, give the web app a MySQL user with only `INSERT` and `SELECT` on `audit_logs`.

## 7. Test report

**Scope – please read.** MySQL cannot be installed in my environment. I ran your real PHP pages with PHP's built-in server and headless Chrome, with `db.php` replaced by a small **MySQLi-compatible stand-in on SQLite** (test only, not in your project). The Bootstrap CDN is not reachable from my environment, so a local copy of Bootstrap 5.3 was served for the same URL. **Nothing was run on real MySQL/XAMPP, and `schema.sql` was not imported on MySQL.** Results are in `practical14/evidence/ui_test_results.json` and `ui_test2_results.json`.

| # | Test | Expected | Result | Status |
|---|---|---|---|---|
| 1 | Admin opens dashboard | Loads | HTTP 200, cards and table shown | Passed (stand-in) |
| 2 | User / student / event count | Match database | 5 / 7 / 0 equal to the SQL counts; event count updated to 1 after adding an event | Passed (stand-in) |
| 3 | Latest registrations | Newest first, real rows | 5 rows in the same order as `ORDER BY created_at DESC` | Passed (stand-in) |
| 4 | Zero counts | 0, no error | students/events empty → `0` shown | Passed (stand-in) |
| 5 | Empty registrations table | Empty-state message | The message is in the code but cannot appear while an admin (a registered user) is logged in | **Not tested** |
| 6 | Dashboard database error | Safe message | Generic alert, HTTP 200, no SQL/table names | Passed (stand-in) |
| 7 | Admin opens admin pages | Allowed | 200 for admin_dashboard, students, events, audit_logs | Passed (stand-in) |
| 8 | Student menu / Admin menu | Role-based | Student: Home, Dashboard, Student Area; Admin: + Admin Panel, Students, Events, Audit Logs; logged out: Home, Register, Login | Passed (stand-in) |
| 9 | Student opens admin URL | HTTP 403 | 403 for 9 admin URLs (list below); direct POST also 403 and nothing changed | Passed (stand-in) |
| 10 | Logged-out user opens protected URL | Redirect to login | Redirected | Passed (stand-in) |
| 11 | Admin actions | Audit row inserted | ADMIN_LOGIN, EVENT_CREATE/UPDATE/DELETE, STUDENT_CREATE/UPDATE/DEACTIVATE all recorded | Passed (stand-in) |
| 12 | Failed admin operation | Not logged as success | Empty event form, duplicate student ID, missing event, already-inactive student → no audit row | Passed (stand-in) |
| 13 | Audit insert fails | No unlogged change | Event not saved, safe message; admin login still works | Passed (stand-in) |
| 14 | No secrets in the log | None | No hash / password / session id found in `audit_logs` | Passed (stand-in) |
| 15 | `schema.sql` import | Tables and FK exist | Not run on MySQL (structure only checked by reading) | **Not tested** |
| 16 | phpMyAdmin audit screenshot, Network-tab 403 screenshot | Screenshots | Cannot be captured here | **Not tested** |

The 403 URLs tested: `admin_dashboard.php`, `students.php`, `events.php`, `add_event.php`, `edit_event.php?id=1`, `delete_event.php?id=1`, `add_student.php`, `audit_logs.php`, `export_students.php`.

## 8. Short practical report

**Objective.** Build an admin dashboard with live statistics and latest registrations, enforce role-based access on the server (HTTP 403 for non-admins), and keep an audit log of administrative actions.

**Implementation.** `dashboard.php` runs `COUNT(*)` queries and a `LIMIT` query for an Admin and renders them with Bootstrap cards and a table. `includes/auth.php` (`require_role`, `deny_access`) protects every admin page and returns a real 403. `includes/audit.php` writes audit rows from the admin pages inside the same transaction as the change. `schema.sql` adds the `audit_logs` table with a foreign key to `users`.

**Result.** In my stand-in test run all 40 checks passed; please repeat the run on XAMPP/MySQL for your own screenshots.

## 9. Beginner explanation

- **Dashboard queries:** `COUNT(*)` asks MySQL "how many rows?" – the page shows the answer, so it changes by itself when data changes. `ORDER BY created_at DESC LIMIT 5` returns the five newest rows.
- **RBAC:** Role-Based Access Control – what you may do depends on your role (`Admin` or `Student`) stored in the database. The server checks it on every request; hiding a menu link only helps the user, it does not protect anything.
- **HTTP 403 Forbidden:** the server's answer "I know who you are, but you are not allowed". It differs from 401/redirect to login ("who are you?") and 404 (not found). `http_response_code(403)` sets it, so browsers, tools and the Network tab show a real 403 – not just a page with words.
- **Audit logging:** a diary of important actions – who, what, which record, when, from where – written by the server only after the action succeeded, so you can investigate later.
- **Transaction:** several SQL steps that all succeed or are all undone (`begin_transaction`, `commit`, `rollback`).

## 10. Troubleshooting

| Problem | Fix |
|---|---|
| Dashboard looks unstyled | Bootstrap comes from the CDN – check your internet connection |
| "Unable to complete the operation" when adding an event | `schema.sql` not imported (no `audit_logs` table) – the action is rolled back by design |
| Audit Logs page says "could not be loaded" | Same reason; see `/Applications/XAMPP/xamppfiles/logs/php_error_log` |
| No statistics on the dashboard | You are logged in as Student; use the Admin account |

## 11. Viva questions

1. **What is RBAC?** Access decisions based on the user's role (Admin / Student) stored in the database.
2. **Why is hiding the menu not enough?** A user can type the URL; every protected page must check the role on the server.
3. **What does 403 mean and how do you send it?** Forbidden – authenticated but not allowed; `http_response_code(403)` before sending the page.
4. **403 vs 401 vs 404?** 401/login = not authenticated, 403 = not authorised, 404 = resource not found.
5. **How are the counts produced?** `SELECT COUNT(*) FROM table`; an empty table returns 0.
6. **Why prepared statements?** The values are sent separately from the SQL, which prevents SQL injection.
7. **What is an audit log and what does yours store?** A record of admin actions: user id, action, record, details, IP and time. Never passwords or tokens.
8. **Why log inside a transaction?** So the change and its log entry succeed or fail together.
9. **Why `ON DELETE SET NULL` on `user_id`?** The history stays even if the admin account is removed later.
10. **Why does `entity_id` have no foreign key?** A deleted event must still appear in the log.
11. **Why escape output with `htmlspecialchars`?** To stop stored text (names, event titles) from running as HTML/JavaScript (XSS).
12. **How could audit logs be protected further?** Use a MySQL user with only INSERT/SELECT on `audit_logs`.
