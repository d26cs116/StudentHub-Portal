/* =========================================================
   practical13/script.js - talks to api.php with the Fetch API
   (the shared ../script.js is loaded first and only handles
   the theme button and the menu; everything here is wrapped in
   its own function so no names clash with it).

   Functions:  apiRequest()  loadNotices()  renderNotices()
               submitForm()  startEdit()    deleteNotice()
               showMessage() setState()     validateForm()
   ========================================================= */
(function () {
    "use strict";

    const API_URL = "api.php";

    // ---- page elements ----
    const form        = document.getElementById("notice-form");
    const idInput     = document.getElementById("notice-id");
    const titleInput  = document.getElementById("title");
    const catInput    = document.getElementById("category");
    const msgInput    = document.getElementById("notice-message");
    const saveBtn     = document.getElementById("save-btn");
    const cancelBtn   = document.getElementById("cancel-btn");
    const formTitle   = document.getElementById("form-title");
    const messageBox  = document.getElementById("message");
    const rowsBody    = document.getElementById("notice-rows");
    const tableWrap   = document.getElementById("table-wrap");
    const loadingBox  = document.getElementById("loading");
    const loadingText = document.getElementById("loading-text");
    const errorBox    = document.getElementById("load-error");
    const errorDetail = document.getElementById("load-error-detail");
    const emptyBox    = document.getElementById("empty");
    const retryBtn    = document.getElementById("retry-btn");
    const countLabel  = document.getElementById("result-count");

    let notices = [];        // the records last received from the API
    let busy = false;        // true while a request is running (stops double clicks)
    let messageTimer = null;

    /* ---------- 1. ONE function for every API call ---------- */
    // method: GET / POST / PUT / DELETE   url: api.php or api.php?id=5   body: object or undefined
    // Resolves with the parsed JSON when the API says success.
    // Rejects with an Error (and .errors for field problems) in EVERY other case:
    //   network failure, HTTP error (404, 500...), not-JSON answer, or success:false.
    async function apiRequest(method, url, body) {
        const options = { method: method, headers: { "Accept": "application/json" } };
        if (body !== undefined) {
            options.headers["Content-Type"] = "application/json";
            options.body = JSON.stringify(body);               // object -> JSON text
        }

        let response;
        try {
            response = await fetch(url, options);
        } catch (networkError) {
            // fetch() only rejects when the request could not be made at all
            throw new Error("Cannot reach the server. Check that Apache is running and you are online.");
        }

        // fetch() does NOT reject for 404 / 500, so read the body and check response.ok ourselves
        let result;
        try {
            result = await response.json();
        } catch (parseError) {
            throw new Error("The server did not return valid JSON (HTTP " + response.status + "). Check api.php and the PHP error log.");
        }

        if (!response.ok || !result.success) {
            const err = new Error(result.message || ("Request failed (HTTP " + response.status + ")."));
            err.status = response.status;
            err.errors = result.errors || null;
            throw err;
        }
        return result;
    }

    /* ---------- 2. screen states ---------- */
    // state: "loading" | "error" | "empty" | "list"
    function setState(state, detail) {
        loadingBox.hidden = state !== "loading";
        errorBox.hidden   = state !== "error";
        emptyBox.hidden   = state !== "empty";
        tableWrap.hidden  = state !== "list";
        if (state === "error") {
            errorDetail.textContent = detail || "";
        }
        if (state !== "list") {
            countLabel.textContent = "";
        }
    }

    // Green or red message above the form. Text is inserted with textContent (never innerHTML).
    function showMessage(type, text) {
        clearTimeout(messageTimer);
        messageBox.className = "form-status " + type;          // "success" or "error"
        messageBox.textContent = (type === "success" ? "✅ " : "❌ ") + text;
        messageBox.hidden = false;
        if (type === "success") {
            messageTimer = setTimeout(function () { messageBox.hidden = true; }, 5000);
        }
    }

    function hideMessage() {
        clearTimeout(messageTimer);
        messageBox.hidden = true;
    }

    // Disable/enable every button that sends a request
    function setBusy(isBusy) {
        busy = isBusy;
        saveBtn.disabled = isBusy;
        cancelBtn.disabled = isBusy;
        retryBtn.disabled = isBusy;
        rowsBody.querySelectorAll("button").forEach(function (b) { b.disabled = isBusy; });
    }

    /* ---------- 3. LIST (GET) ---------- */
    async function loadNotices() {
        setState("loading");
        setBusy(true);
        try {
            const result = await apiRequest("GET", API_URL);
            notices = result.data;
            renderNotices();
        } catch (error) {
            notices = [];
            setState("error", error.message);
        } finally {
            setBusy(false);
        }
    }

    function renderNotices() {
        rowsBody.textContent = "";                              // clear old rows
        if (notices.length === 0) {
            setState("empty");
            return;
        }
        notices.forEach(function (n) {
            const tr = document.createElement("tr");

            addCell(tr, String(n.id));
            addCell(tr, n.title);
            const catCell = document.createElement("td");
            const badge = document.createElement("span");
            badge.className = "status-badge status-active";
            badge.textContent = n.category;
            catCell.appendChild(badge);
            tr.appendChild(catCell);
            const msgCell = addCell(tr, n.message);
            msgCell.className = "notice-msg";
            addCell(tr, n.updated_at);

            const actions = document.createElement("td");
            actions.className = "actions";
            const editBtn = makeButton("Edit", "act-link", function () { startEdit(n.id); });
            const delBtn  = makeButton("Delete", "act-link act-danger", function () { deleteNotice(n.id); });
            actions.appendChild(editBtn);
            actions.appendChild(delBtn);
            tr.appendChild(actions);

            rowsBody.appendChild(tr);
        });
        countLabel.textContent = "Showing " + notices.length + " notice" + (notices.length === 1 ? "" : "s");
        setState("list");
    }

    function addCell(tr, text) {
        const td = document.createElement("td");
        td.textContent = text;                                  // textContent: HTML in a notice can never run
        tr.appendChild(td);
        return td;
    }

    function makeButton(label, className, handler) {
        const b = document.createElement("button");
        b.type = "button";
        b.className = className;
        b.textContent = label;
        b.addEventListener("click", handler);
        return b;
    }

    /* ---------- 4. client-side validation (the server validates again) ---------- */
    function setFieldError(name, text) {
        document.getElementById("field-" + name).classList.toggle("error", text !== "");
        document.getElementById("err-" + name).textContent = text ? "❌ " + text : "";
    }

    function clearFieldErrors() {
        ["title", "category", "message"].forEach(function (n) { setFieldError(n, ""); });
    }

    function validateForm(values) {
        const errors = {};
        if (values.title.length < 3 || values.title.length > 100) {
            errors.title = values.title === "" ? "Title is required." : "Title must be 3-100 characters.";
        }
        if (values.category === "") {
            errors.category = "Choose a category.";
        }
        if (values.message.length < 5 || values.message.length > 500) {
            errors.message = values.message === "" ? "Message is required." : "Message must be 5-500 characters.";
        }
        return errors;
    }

    /* ---------- 5. ADD (POST) and EDIT (PUT) ---------- */
    async function submitForm(event) {
        event.preventDefault();
        if (busy) { return; }                                    // ignore a second click

        const values = {
            title:    titleInput.value.trim(),
            category: catInput.value,
            message:  msgInput.value.trim()
        };

        clearFieldErrors();
        const problems = validateForm(values);
        if (Object.keys(problems).length > 0) {
            Object.keys(problems).forEach(function (k) { setFieldError(k, problems[k]); });
            showMessage("error", "Please correct the highlighted fields.");
            return;
        }

        const editingId = idInput.value;
        const isEdit = editingId !== "";
        hideMessage();
        setBusy(true);
        saveBtn.textContent = isEdit ? "Saving…" : "Adding…";
        try {
            const result = isEdit
                ? await apiRequest("PUT", API_URL + "?id=" + encodeURIComponent(editingId), values)
                : await apiRequest("POST", API_URL, values);
            resetForm();
            showMessage("success", result.message);
            await loadNotices();                                 // refresh the list from MySQL
        } catch (error) {
            // Show server-side field errors (400 / 409) next to the fields
            if (error.errors) {
                Object.keys(error.errors).forEach(function (k) { setFieldError(k, error.errors[k]); });
            }
            showMessage("error", error.message);
            if (error.status === 404 && isEdit) {                // the record vanished: leave edit mode
                resetForm();
                await loadNotices();
            }
        } finally {
            saveBtn.textContent = idInput.value !== "" ? "Save Changes" : "Add Notice";
            setBusy(false);
        }
    }

    /* ---------- 6. EDIT: load a row into the form ---------- */
    function startEdit(id) {
        const n = notices.find(function (item) { return item.id === id; });
        if (!n) { return; }
        idInput.value    = String(n.id);
        titleInput.value = n.title;
        catInput.value   = n.category;
        msgInput.value   = n.message;
        formTitle.textContent = "Edit notice #" + n.id;
        saveBtn.textContent = "Save Changes";
        cancelBtn.hidden = false;
        clearFieldErrors();
        hideMessage();
        form.scrollIntoView({ behavior: "smooth", block: "center" });
        titleInput.focus();
    }

    function resetForm() {
        form.reset();
        idInput.value = "";
        formTitle.textContent = "Add a notice";
        saveBtn.textContent = "Add Notice";
        cancelBtn.hidden = true;
        clearFieldErrors();
    }

    /* ---------- 7. DELETE (with confirmation) ---------- */
    async function deleteNotice(id) {
        if (busy) { return; }
        const n = notices.find(function (item) { return item.id === id; });
        const name = n ? "“" + n.title + "”" : "this notice";
        if (!window.confirm("Delete " + name + "? This cannot be undone.")) {
            return;                                              // user pressed Cancel
        }
        hideMessage();
        setBusy(true);
        try {
            const result = await apiRequest("DELETE", API_URL + "?id=" + encodeURIComponent(id));
            if (idInput.value === String(id)) { resetForm(); }   // deleting the row being edited
            showMessage("success", result.message);
            await loadNotices();
        } catch (error) {
            showMessage("error", error.message);
            if (error.status === 404) { await loadNotices(); }   // already gone: refresh the list
        } finally {
            setBusy(false);
        }
    }

    /* ---------- 8. start ---------- */
    form.addEventListener("submit", submitForm);
    cancelBtn.addEventListener("click", function () { resetForm(); hideMessage(); });
    retryBtn.addEventListener("click", loadNotices);
    loadNotices();                                               // fetch the records when the page opens
})();
