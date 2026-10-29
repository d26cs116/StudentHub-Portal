/* =========================================================
   js/render.js – building the page with DOM methods
   Text from the JSON files is always added with textContent
   (never innerHTML), so it can never run as HTML.
   ========================================================= */

export const YEAR_LABELS = { 1: "First Year", 2: "Second Year", 3: "Third Year", 4: "Fourth Year" };

// Small helper: create an element, give it a class and some text
function createEl(tag, className, text) {
    const element = document.createElement(tag);
    if (className) element.className = className;
    if (text !== undefined) element.textContent = text;
    return element;
}

/* ---------- Messages: loading / error / empty ---------- */
export function showLoading(ui, message) {
    ui.status.className = "status loading";
    ui.status.textContent = "";
    ui.status.appendChild(createEl("span", "spinner"));
    ui.status.appendChild(createEl("span", "", message));
    ui.status.hidden = false;
    ui.list.hidden = true;
    ui.pagination.hidden = true;
    ui.count.textContent = "";
}

export function showError(ui, message, detail, onRetry) {
    ui.status.className = "status error";
    ui.status.textContent = "";
    ui.status.appendChild(createEl("strong", "", message));
    ui.status.appendChild(createEl("span", "status-detail", detail));

    const retryButton = createEl("button", "retry-btn", "Retry");
    retryButton.type = "button";
    retryButton.addEventListener("click", onRetry);                 // Retry loads the file again
    ui.status.appendChild(retryButton);

    ui.status.hidden = false;
    ui.list.hidden = true;
    ui.pagination.hidden = true;
    ui.count.textContent = "";
}

export function showEmpty(ui) {
    ui.status.className = "status empty";
    ui.status.textContent = "No matching records found.";
    ui.status.hidden = false;
    ui.list.hidden = true;
    ui.pagination.hidden = false;
}

export function hideStatus(ui) {
    ui.status.hidden = true;
    ui.list.hidden = false;
    ui.pagination.hidden = false;
}

// Turn the search box, dropdowns and Reset button on or off
export function setControlsDisabled(ui, disabled) {
    ui.controls.forEach(function (control) {
        control.disabled = disabled;
    });
}

/* ---------- Filter dropdowns ---------- */
// Keeps the "All" option and adds one option for each value
export function fillSelect(select, values, labelFunction) {
    select.textContent = "";
    select.appendChild(new Option("All", "all"));
    values.forEach(function (value) {
        const label = labelFunction ? labelFunction(value) : value;
        select.appendChild(new Option(label, value));
    });
}

/* ---------- Pagination text and buttons ---------- */
export function renderPagination(ui, info) {
    if (info.totalItems === 0) {
        ui.pageLabel.textContent = "Page 0 of 0";
        ui.count.textContent = "0 records found";
    } else {
        ui.pageLabel.textContent = "Page " + info.currentPage + " of " + info.totalPages;
        ui.count.textContent = "Showing " + (info.start + 1) + "–" + (info.start + info.pageItems.length) +
            " of " + info.totalItems + " records";
    }
    ui.prev.disabled = info.currentPage <= 1;                          // no Previous on the first page
    ui.next.disabled = info.currentPage >= info.totalPages;            // no Next on the last page
}

/* ---------- Records: one render function per dataset ---------- */
function formatDate(isoDate) {
    return new Date(isoDate).toLocaleDateString("en-IN", { day: "numeric", month: "short", year: "numeric", timeZone: "UTC" });
}

export function renderEvents(container, events) {
    container.textContent = "";                                        // remove the old cards
    events.forEach(function (event) {
        const card = createEl("article", "data-card event-card");

        const top = createEl("div", "event-top");
        const date = new Date(event.date);
        const dateBox = createEl("div", "event-date");
        dateBox.appendChild(createEl("span", "event-day", String(date.getUTCDate())));
        dateBox.appendChild(createEl("span", "event-month", date.toLocaleString("en-US", { month: "short", timeZone: "UTC" })));
        top.appendChild(dateBox);
        top.appendChild(createEl("span", "tag-pill", event.category));

        card.appendChild(top);
        card.appendChild(createEl("h3", "", event.title));
        card.appendChild(createEl("p", "card-meta", "📅 " + formatDate(event.date) + "   📍 " + event.location));
        card.appendChild(createEl("p", "", event.description));
        container.appendChild(card);
    });
}

export function renderStudents(container, students) {
    container.textContent = "";

    const wrap = createEl("div", "data-table-wrap");
    const table = createEl("table", "data-table");

    const headRow = createEl("tr");
    ["ID", "Name", "Email", "Course", "Year"].forEach(function (title) {
        headRow.appendChild(createEl("th", "", title));
    });
    table.appendChild(createEl("thead")).appendChild(headRow);

    const body = createEl("tbody");
    students.forEach(function (student) {
        const row = createEl("tr");
        [student.id, student.name, student.email, student.course, YEAR_LABELS[student.year] || student.year].forEach(function (value) {
            row.appendChild(createEl("td", "", String(value)));
        });
        body.appendChild(row);
    });
    table.appendChild(body);

    wrap.appendChild(table);
    container.appendChild(wrap);
}

export function renderFAQs(container, faqs) {
    container.textContent = "";
    faqs.forEach(function (faq) {
        const card = createEl("article", "data-card faq-card");
        card.appendChild(createEl("span", "tag-pill", faq.category));
        card.appendChild(createEl("h3", "", faq.question));
        card.appendChild(createEl("p", "", faq.answer));
        container.appendChild(card);
    });
}
