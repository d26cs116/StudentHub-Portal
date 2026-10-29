/* =========================================================
   js/app.js – starts the page and connects all the modules
   (data.js = fetch, query.js = search/filter/sort/pages,
    render.js = DOM). Loaded with <script type="module">.
   ========================================================= */
import { loadData } from "./data.js";
import { searchItems, filterItems, sortItems, paginate, getUniqueValues } from "./query.js";
import { YEAR_LABELS, showLoading, showError, showEmpty, hideStatus, setControlsDisabled,
         fillSelect, renderPagination, renderEvents, renderStudents, renderFAQs } from "./render.js";

const PER_PAGE = 5;                                     // records shown on one page

/* ----- One settings object for each dataset ----- */
const datasets = [
    {
        key: "events", label: "events", url: "data/events.json",
        searchFields: ["title", "category", "location"],
        defaultSort: "date:date:asc",
        render: renderEvents
    },
    {
        key: "students", label: "students", url: "data/students.json",
        searchFields: ["name", "email", "course"],
        defaultSort: "id:number:asc",
        filterLabels: { year: function (value) { return YEAR_LABELS[value]; } },
        render: renderStudents
    },
    {
        key: "faqs", label: "FAQs", url: "data/faqs.json",
        searchFields: ["question", "answer"],
        defaultSort: "id:number:asc",
        render: renderFAQs
    }
];

/* ----- Find all the HTML elements of one dataset (ids start with its key) ----- */
function getUI(key) {
    const panel = document.getElementById("panel-" + key);
    const search = document.getElementById(key + "-search");
    const sort = document.getElementById(key + "-sort");
    const reset = document.getElementById(key + "-reset");
    const filterSelects = Array.from(panel.querySelectorAll("select[data-filter]"));

    return {
        panel: panel, search: search, sort: sort, reset: reset, filterSelects: filterSelects,
        controls: [search, sort, reset].concat(filterSelects),
        status: document.getElementById(key + "-status"),
        list: document.getElementById(key + "-list"),
        count: document.getElementById(key + "-count"),
        pagination: document.getElementById(key + "-pagination"),
        prev: document.getElementById(key + "-prev"),
        next: document.getElementById(key + "-next"),
        pageLabel: document.getElementById(key + "-page")
    };
}

/* ----- Turn technical errors into a helpful sentence ----- */
function explainError(error) {
    if (window.location.protocol === "file:") {
        return "This page was opened with file://. Start a local server and open http://localhost:8000 instead.";
    }
    if (error instanceof SyntaxError) {
        return "The JSON file is not valid JSON.";
    }
    return error.message;
}

/* ----- Show the current page: search -> filter -> sort -> paginate -> render ----- */
function updateView(config, ui, state) {
    let result = searchItems(state.items, state.search, config.searchFields);
    result = filterItems(result, state.filters);
    result = sortItems(result, state.sort);

    const info = paginate(result, state.page, PER_PAGE);
    state.page = info.currentPage;

    if (result.length === 0) {
        showEmpty(ui);                                   // "No matching records found."
    } else {
        hideStatus(ui);
        config.render(ui.list, info.pageItems);
    }
    renderPagination(ui, info);
}

/* ----- Fetch the JSON file, then show the data (or the error) ----- */
function loadDataset(config, ui, state) {
    showLoading(ui, "Loading " + config.label + "...");
    setControlsDisabled(ui, true);

    loadData(config.key, config.url)
        .then(function (data) {
            state.items = data;

            // Fill each filter dropdown with the values found in the data
            ui.filterSelects.forEach(function (select) {
                const field = select.dataset.filter;
                fillSelect(select, getUniqueValues(data, field), config.filterLabels && config.filterLabels[field]);
                select.value = state.filters[field] || "all";
            });

            setControlsDisabled(ui, false);
            updateView(config, ui, state);
        })
        .catch(function (error) {
            showError(ui, "Unable to load " + config.label + ". Please try again.", explainError(error), function () {
                loadDataset(config, ui, state);          // Retry button
            });
        });
}

/* ----- Connect the controls of one dataset to its state ----- */
function setupDataset(config) {
    const ui = getUI(config.key);
    const state = { items: [], search: "", filters: {}, sort: config.defaultSort, page: 1 };

    ui.filterSelects.forEach(function (select) {
        state.filters[select.dataset.filter] = "all";
    });

    // Typing in the search box: new search -> back to page 1
    ui.search.addEventListener("input", function () {
        state.search = ui.search.value;
        state.page = 1;
        updateView(config, ui, state);
    });

    // Changing a filter: back to page 1
    ui.filterSelects.forEach(function (select) {
        select.addEventListener("change", function () {
            state.filters[select.dataset.filter] = select.value;
            state.page = 1;
            updateView(config, ui, state);
        });
    });

    // Changing the sort order: back to page 1
    ui.sort.addEventListener("change", function () {
        state.sort = ui.sort.value;
        state.page = 1;
        updateView(config, ui, state);
    });

    // Reset button: clear search, filters and sorting
    ui.reset.addEventListener("click", function () {
        ui.search.value = "";
        state.search = "";
        state.sort = config.defaultSort;
        ui.sort.value = config.defaultSort;
        ui.filterSelects.forEach(function (select) {
            select.value = "all";
            state.filters[select.dataset.filter] = "all";
        });
        state.page = 1;
        updateView(config, ui, state);
    });

    // Previous / Next buttons
    ui.prev.addEventListener("click", function () {
        state.page = state.page - 1;
        updateView(config, ui, state);
    });
    ui.next.addEventListener("click", function () {
        state.page = state.page + 1;
        updateView(config, ui, state);
    });

    loadDataset(config, ui, state);
}

/* ----- Tabs: Events | Students | FAQs ----- */
function setupTabs() {
    const tabs = Array.from(document.querySelectorAll(".data-tab"));
    const panels = Array.from(document.querySelectorAll(".data-panel"));

    function showTab(key) {
        tabs.forEach(function (tab) {
            const isActive = tab.dataset.target === key;
            tab.classList.toggle("active", isActive);
            tab.setAttribute("aria-selected", isActive);
            tab.tabIndex = isActive ? 0 : -1;
        });
        panels.forEach(function (panel) {
            panel.hidden = panel.id !== "panel-" + key;
        });
        history.replaceState(null, "", "#" + key);       // so explore.html#students opens the Students tab
    }

    tabs.forEach(function (tab, index) {
        tab.addEventListener("click", function () {
            showTab(tab.dataset.target);
        });
        // Left / Right arrow keys move between tabs
        tab.addEventListener("keydown", function (event) {
            let newIndex = null;
            if (event.key === "ArrowRight") newIndex = (index + 1) % tabs.length;
            if (event.key === "ArrowLeft") newIndex = (index - 1 + tabs.length) % tabs.length;
            if (newIndex !== null) {
                showTab(tabs[newIndex].dataset.target);
                tabs[newIndex].focus();
            }
        });
    });

    // Open the tab named in the address (explore.html#students), or Events by default
    function showTabFromAddress() {
        const key = window.location.hash.replace("#", "");
        showTab(datasets.some(function (d) { return d.key === key; }) ? key : "events");
    }
    window.addEventListener("hashchange", showTabFromAddress);   // also works if only the #part changes
    showTabFromAddress();
}

/* ----- Start everything ----- */
setupTabs();
datasets.forEach(setupDataset);
