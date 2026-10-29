/* =========================================================
   js/data.js – getting data from the JSON files (Fetch API)
   ========================================================= */

// Fetch one JSON file and return its data (a Promise)
export function fetchJSON(url) {
    return fetch(url)
        .then(function (response) {
            // fetch() only fails on network problems, so we must check response.ok ourselves
            if (!response.ok) {
                throw new Error("Request failed (HTTP " + response.status + ")");
            }
            return response.json();               // turns the file text into a JavaScript array/object
        })
        .then(function (data) {
            if (!Array.isArray(data)) {
                throw new Error("The JSON file must contain an array of records.");
            }
            return data;
        });
}

/* ---------- Test helpers for the practical demo ----------
   Add these to the page address (nothing in the project is changed):
     explore.html?delay=3000          -> waits 3 seconds, so the loading message stays visible
     explore.html?fail=events         -> the FIRST events request fails, Retry then works
     explore.html?fail=events,faqs    -> same for several datasets (or fail=all)
*/
const failedOnce = new Set();

function getTestOptions() {
    const params = new URLSearchParams(window.location.search);
    return {
        delay: Number(params.get("delay")) || 0,
        fail: (params.get("fail") || "").split(",")
    };
}

// Used by the app: loads a dataset and applies the optional test settings
export function loadData(key, url) {
    const test = getTestOptions();

    // Make the first request go to a file that does not exist (404)
    const shouldFail = test.fail.includes(key) || test.fail.includes("all");
    if (shouldFail && !failedOnce.has(key)) {
        failedOnce.add(key);
        url = url.replace(".json", "-missing.json");
    }

    // Optional artificial delay so the loading message can be photographed
    const wait = new Promise(function (resolve) {
        setTimeout(resolve, test.delay);
    });

    return wait.then(function () {
        return fetchJSON(url);
    });
}
