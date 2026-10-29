/* =========================================================
   js/query.js – search, filter, sort and pagination
   Every function receives an array and RETURNS a new array,
   so the original data loaded from the JSON file is never changed.
   ========================================================= */

// SEARCH: keep records where any of the chosen fields contains the text (case-insensitive)
export function searchItems(items, text, fields) {
    const term = text.trim().toLowerCase();
    if (term === "") return items;                       // empty search = everything

    return items.filter(function (item) {
        return fields.some(function (field) {
            return String(item[field]).toLowerCase().includes(term);
        });
    });
}

// FILTER: filters looks like { category: "Sports", year: "2" }; "all" means no filter
export function filterItems(items, filters) {
    return items.filter(function (item) {
        return Object.keys(filters).every(function (field) {
            const wanted = filters[field];
            return wanted === "all" || String(item[field]) === wanted;
        });
    });
}

// SORT: sortValue looks like "date:date:asc" = field : type : direction
export function sortItems(items, sortValue) {
    const parts = sortValue.split(":");
    const field = parts[0];
    const type = parts[1];
    const direction = parts[2];

    const copy = items.slice();                          // copy, so the original order is kept

    copy.sort(function (a, b) {
        let result;
        if (type === "date") {
            result = new Date(a[field]) - new Date(b[field]);      // compare real dates, not text
        } else if (type === "number") {
            result = a[field] - b[field];
        } else {
            result = String(a[field]).localeCompare(String(b[field]), undefined, { sensitivity: "base" });
        }
        return direction === "desc" ? -result : result;
    });

    return copy;
}

// PAGINATION: returns the records of one page plus the numbers needed for the buttons
export function paginate(items, page, perPage) {
    const totalItems = items.length;
    const totalPages = Math.ceil(totalItems / perPage);
    const currentPage = Math.min(Math.max(page, 1), Math.max(totalPages, 1));   // keep page inside 1..totalPages
    const start = (currentPage - 1) * perPage;

    return {
        pageItems: items.slice(start, start + perPage),
        currentPage: currentPage,
        totalPages: totalPages,
        totalItems: totalItems,
        start: start
    };
}

// Unique values of one field (used to fill the filter dropdowns)
export function getUniqueValues(items, field) {
    const values = [];
    items.forEach(function (item) {
        const value = String(item[field]);
        if (!values.includes(value)) values.push(value);
    });
    return values.sort(function (a, b) {
        return a.localeCompare(b, undefined, { numeric: true });
    });
}
