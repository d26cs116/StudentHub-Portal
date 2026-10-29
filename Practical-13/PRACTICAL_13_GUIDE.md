# Practical 13 – Fetch API with a PHP JSON API (CRUD)
Student: Arshit Vaghela (D26CS116) · Stack: PHP · MySQL (PDO) · HTML · CSS · JavaScript (Fetch API) · XAMPP on macOS

---

## 1. Requirements and what exists

| Required | File | Status |
|---|---|---|
| PHP database connection | `practical13/db.php` | Done |
| PHP JSON API (list, add, edit, delete) | `practical13/api.php` | Done |
| User interface | `practical13/index.html` | Done |
| Fetch API JavaScript | `practical13/script.js` | Done |
| MySQL schema | `practical13/schema.sql` | Done |
| UI screenshots (list, add, edit, delete, loading/error) | `practical13/evidence/screenshots/` | Captured from a **stand-in test run** (see section 6) |
| Network tab screenshots | – | **You must take these** on your Mac (section 5) |

**Why a `practical13/` sub-folder?** Your project already uses `index.html` (Home page), `script.js` (theme/menu for every page) and `db.php` (MySQLi login system). Replacing them would break Practicals 1–12, so the five required files live in `practical13/`. The page reuses `../style.css` and the existing `../script.js` (theme and menu buttons), so it looks like the rest of Student Hub. **No existing file was changed.**

**Entity:** `notices` (a new table in the same database `user_registration_db`). I did not reuse `students` because Practical 11 deliberately soft-deletes students, and an open API that hard-deletes them would conflict with that.

**PDO instead of MySQLi:** Practical 9 already used PDO, and PDO lets the same code run against a different database for testing. `db.php` here only offers `db_connect()`; `api.php` turns connection failures into JSON (the main `db.php` prints plain text, which would break `response.json()`).

## 2. Run it on macOS with XAMPP

1. Copy the whole `Practical-13` folder into `/Applications/XAMPP/xamppfiles/htdocs/` (or copy only the new `practical13` folder into your existing project folder).
2. Open XAMPP → start **Apache** and **MySQL**.
3. Open <http://localhost/phpmyadmin> → **Import** tab → choose `practical13/schema.sql` → **Import**. Check that `user_registration_db` now has a `notices` table with 4 sample rows. (If you never imported Practical 10 the database will not exist – import `user_registration.sql` first.)
4. Check `practical13/db.php`: host `127.0.0.1`, port `3306`, user `root`, empty password, database `user_registration_db`. Change only if your XAMPP differs.
5. Open **<http://localhost/Practical-13/practical13/index.html>** (use your real folder name). Do **not** open the file by double-clicking: `file://` cannot run PHP.
6. Try Add, Edit, Delete. Test the API directly at <http://localhost/Practical-13/practical13/api.php> (you should see JSON).

## 3. The API

Every response: `{ "success": true|false, "message": "...", "data": ... }` (invalid input also has `"errors": {"field": "problem"}`). Header: `Content-Type: application/json; charset=utf-8`.

| Action | Request | Success | Errors |
|---|---|---|---|
| List | `GET api.php` | 200 + array | 500 database unavailable |
| One | `GET api.php?id=5` | 200 | 400 bad id, 404 not found |
| Add | `POST api.php` (JSON body) | **201 Created** | 400 invalid input / bad JSON, 409 duplicate title |
| Edit | `PUT api.php?id=5` (JSON body) | 200 | 400, 404, 409 |
| Delete | `DELETE api.php?id=5` | 200 | 400 missing/bad id, 404 |
| Other | `PATCH`, etc. | – | 405 + `Allow` header |

Fields: `title` (3–100, unique), `category` (Academic, Exam, Event, Holiday, General), `message` (5–500). All queries are prepared statements; SQL errors and DB passwords are only written to the PHP error log, never sent to the browser. The client does `response.ok` / `result.success` checks, handles non-JSON replies and network failures, disables buttons while a request runs, asks `confirm()` before deleting, and writes records with `textContent` (so HTML in a notice cannot run).

## 4. Lab report (short)

**Objective.** Build a CRUD application where JavaScript's Fetch API talks to a PHP JSON API backed by MySQL, without reloading the page.

**Tools.** XAMPP (Apache, PHP, MySQL), phpMyAdmin, VS Code, Chrome DevTools, HTML/CSS/JavaScript.

**Implementation.** `schema.sql` creates the `notices` table (primary key, unique title, ENUM category). `db.php` opens a PDO connection. `api.php` reads the HTTP method and answers list/add/edit/delete in JSON with correct status codes. `index.html` holds the form, the table and the loading/error/empty areas. `script.js` has one `apiRequest()` function used by `loadNotices()` (GET), `submitForm()` (POST/PUT) and `deleteNotice()` (DELETE) and redraws the table after each change.

**Result.** All four operations work and the list always reflects the database. Loading, empty, validation and error states are shown. In my stand-in test run all checks passed (section 6); you should repeat them on XAMPP/MySQL.

## 5. Screenshots to submit

**UI screenshots** – genuine Chrome renders from my test run are in `practical13/evidence/screenshots/` (listed in section 6). They show real data, but they were produced against a SQLite stand-in, not your MySQL. For your submission, **retake them on XAMPP** with the same steps (address bar visible).

| Needed | Capture on your Mac | Suggested file name |
|---|---|---|
| List | Open `index.html` after importing the schema | `Practical13_List.png` |
| Add | Fill the form → Add Notice → screenshot with the green message and new row | `Practical13_Add.png` |
| Edit | Click Edit (form filled) → change → Save Changes → screenshot after | `Practical13_Edit.png` |
| Delete | Click Delete (screenshot the confirm box: **Cmd+Shift+4**, then **Space**, click the dialog) → OK → screenshot after | `Practical13_Delete.png` |
| Loading | Chrome DevTools → Network → throttling **Slow 3G** → reload and capture the spinner while it shows | `Practical13_Loading.png` |
| Error | In XAMPP **stop MySQL**, click *Try again* (or reload) → red "database is unavailable" box | `Practical13_Error.png` |

**Network screenshots (not capturable here – DevTools is browser UI; I did not fake them).**
1. Chrome → right-click page → **Inspect** → **Network** tab → filter **Fetch/XHR** → reload.
2. Click the `api.php` row → **Headers** tab: Request URL, **Request Method: GET**, **Status Code: 200 OK** → screenshot. Then **Response** (or Preview) tab → JSON → screenshot. Save as `Practical13_Network_GET.png`.
3. Repeat while adding (`POST`, expect **201 Created**; `…_POST.png`), editing (`PUT api.php?id=…`, 200; `…_UPDATE.png`) and deleting (`DELETE api.php?id=…`, 200; `…_DELETE.png`). For POST/PUT also show the **Payload** tab (the JSON you sent).
4. Error example: add a notice whose title already exists → `POST` **409 Conflict** with the JSON error (`Practical13_Network_Error.png`).

The expected request/response pairs from my run are in `practical13/evidence/fetch_requests_log.txt` and `api_test_output.txt` so you know what to expect.

## 6. Test report

**Honest scope.** MySQL cannot be installed in my environment. I ran the real `api.php`, `index.html`, `script.js` with PHP's built-in server and Chrome (Playwright), with `db.php` swapped for a **SQLite stand-in** (same `db_connect()`; the SQL in `api.php` is standard). The "database unavailable" test used your **real `db.php`** (MySQL not running there). **Nothing was run on XAMPP/MySQL or by hand in your browser.** `schema.sql` is **not tested** on MySQL.

| # | Test | Expected | Result | Status |
|---|---|---|---|---|
| 1 | Open page | Records load | 4 seeded rows rendered | Passed (stand-in) |
| 2 | List API | Valid JSON, 200, correct header | `application/json; charset=utf-8`, 200 | Passed (stand-in) |
| 3 | Add | Stored, appears in list, 201 | POST 201, row shown, form reset | Passed (stand-in) |
| 4 | Edit | Changes saved and shown | PUT 200, new title/message in list | Passed (stand-in) |
| 5 | Delete | Confirm, then removed | Cancel kept the record; OK removed it, DELETE 200 | Passed (stand-in) |
| 6 | Empty database | Empty-state message | Message shown | Passed (stand-in) |
| 7 | Invalid input | Clear message | Client messages without any request; server 400 with `errors` for bad JSON, wrong types, bad category, short title | Passed (stand-in) |
| 8 | Duplicate title | Error | 409 (also different letter case); shown next to the field | Passed (stand-in) |
| 9 | Missing / bad id | Error JSON | PUT/DELETE without id → 400; `abc`, `0`, `1 OR 1=1` → 400; unknown id → 404 | Passed (stand-in) |
| 10 | Unsupported method | 405 | `PATCH` → 405 with `Allow` header | Passed (stand-in) |
| 11 | Record deleted elsewhere | 404 message, list refreshed | Shown | Passed (stand-in) |
| 12 | Database unavailable | Safe message | Real `db.php`, MySQL down: 500 JSON "database is unavailable"; no SQL/path/password in page | Passed (real db.php) |
| 13 | Network failure | Error state, not silent | "Cannot reach the server" box; *Try again* recovers | Passed (stand-in) |
| 14 | Loading state | Spinner during fetch | Visible during a delayed request | Passed (stand-in) |
| 15 | SQL injection / HTML in title | Stored literally, never executed | Literal text; `onerror` did not run | Passed (stand-in) |
| 16 | Network tab status + Response panel | Screenshots | Not captured (DevTools UI) | **Not tested** |
| 17 | `schema.sql` import and everything on MySQL/XAMPP | Works | Not run | **Not tested** |
| 18 | Real Safari/Chrome by hand on your Mac | Works | Only headless Chrome run | **Not tested** |

Screenshots from the test run: `Practical13_List`, `_Add_Form`, `_Add`, `_Duplicate_Error`, `_Validation_Error`, `_Edit_Before`, `_Edit`, `_Delete`, `_Empty`, `_Loading`, `_Error` (real db.php), `_Error_Network`, `_NotFound_Error`, `_Mobile_Empty`.

Requires PHP 8.1+ (`array_is_list`); current XAMPP ships 8.x.

## 7. How PHP, MySQL, JSON and Fetch work together

- **MySQL** stores the notices.
- **PHP (`api.php`)** is a small server program: it receives an HTTP request, runs a prepared SQL query, and answers with **JSON** (plain text structured like `{"success":true,"data":[...]}`) instead of an HTML page.
- **JSON** is the common language: PHP's `json_encode` writes it, JavaScript's `response.json()` reads it.
- **Fetch API** is JavaScript's way to send an HTTP request without reloading the page. `fetch()` returns a promise; `await` waits for the answer. The script then builds the table rows from the JSON.
- Flow: click Add → `fetch(POST, JSON)` → `api.php` validates → `INSERT` → JSON reply (201) → script shows a message and fetches the list again → table updates.
- Gotcha: `fetch()` does **not** fail on 404/500; you must check `response.ok` – the script does.

## 8. Troubleshooting

| Problem | Fix |
|---|---|
| "Cannot reach the server" | Apache not running, or the page was opened as `file://`; use the `http://localhost/...` URL |
| "database is unavailable" | MySQL stopped, wrong password/port in `db.php`, or `user_registration_db` missing |
| "The server did not return valid JSON" | PHP error or `schema.sql` not imported; open `api.php` directly and read `/Applications/XAMPP/xamppfiles/logs/php_error_log` |
| Empty list but no error | Table is empty; add a notice |
| 404 on the page | Wrong folder name in the URL |

## 9. Viva questions

1. **What is the Fetch API?** A built-in browser API to send HTTP requests from JavaScript and get a promise of the response.
2. **Why check `response.ok`?** `fetch` only rejects on network failure; 404 or 500 still resolve, so errors must be checked manually.
3. **What is JSON and why use it?** A light text format for data that both PHP and JavaScript can read and write.
4. **Which HTTP method for which CRUD action?** GET read, POST create, PUT update, DELETE delete.
5. **What does 201 mean? Why 409?** 201 Created for a new record; 409 Conflict for a duplicate unique title.
6. **Why prepared statements?** SQL and values are sent separately, so input cannot change the query (stops SQL injection).
7. **Why `textContent` and not `innerHTML`?** It shows user text as text, preventing XSS.
8. **Why validate on the server as well?** Browser checks can be bypassed (curl, DevTools).
9. **What does `async/await` do?** It lets asynchronous code (fetch) be written in a readable top-to-bottom way.
10. **Why return JSON errors too?** So `response.json()` never fails and the UI can show a meaningful message.
11. **Where do you see the requests?** Chrome DevTools → Network → Fetch/XHR.
