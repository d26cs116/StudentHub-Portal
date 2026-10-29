/* =========================================================
   script.js – Dynamic UI for Student Hub (Practical 4)
   One file is shared by all pages. Each section first checks
   that its elements exist (if (element) { ... }) because not
   every page contains every component.
   ========================================================= */


/* ---------- 1. LIGHT / DARK THEME (uses localStorage) ---------- */
const themeToggle = document.getElementById("theme-toggle");   // DOM: getElementById

// Apply a theme ("light" or "dark") to the page
function applyTheme(theme) {
    if (theme === "dark") {
        document.body.classList.add("dark-mode");              // DOM: classList.add
    } else {
        document.body.classList.remove("dark-mode");           // DOM: classList.remove
    }

    // Tells Bootstrap (contact + results pages) which theme to use
    document.documentElement.setAttribute("data-bs-theme", theme);

    // Update the button text and its accessibility attributes
    if (themeToggle) {
        if (theme === "dark") {
            themeToggle.innerHTML = '<span aria-hidden="true">☀️</span> Light';   // DOM: innerHTML
            themeToggle.setAttribute("aria-label", "Switch to light mode");
            themeToggle.setAttribute("aria-pressed", "true");
        } else {
            themeToggle.innerHTML = '<span aria-hidden="true">🌙</span> Dark';
            themeToggle.setAttribute("aria-label", "Switch to dark mode");
            themeToggle.setAttribute("aria-pressed", "false");
        }
    }
}

// When the page loads: read the saved theme. If nothing is saved, use "light".
const savedTheme = localStorage.getItem("theme") || "light";   // localStorage.getItem
applyTheme(savedTheme);

// When the button is clicked: switch theme and save the choice
if (themeToggle) {
    themeToggle.addEventListener("click", function () {
        const newTheme = document.body.classList.contains("dark-mode") ? "light" : "dark";
        applyTheme(newTheme);
        localStorage.setItem("theme", newTheme);               // localStorage.setItem
    });
}


/* ---------- 2. HAMBURGER MENU ---------- */
const menuToggle = document.getElementById("menu-toggle");
const mainNav = document.getElementById("main-nav");

// Dark background shown behind the side menu (created with JavaScript, so no HTML change is needed)
const navOverlay = document.createElement("div");              // DOM: createElement
navOverlay.className = "nav-overlay";
document.body.appendChild(navOverlay);                         // DOM: appendChild

// Open (true) or close (false) the side menu
function setMenu(isOpen) {
    mainNav.classList.toggle("open", isOpen);                  // DOM: classList.toggle (panel slides in/out)
    navOverlay.classList.toggle("show", isOpen);               // dark background
    document.body.classList.toggle("menu-open", isOpen);       // stops the page scrolling behind the menu
    menuToggle.classList.toggle("open", isOpen);               // turns the 3 lines into an X
    menuToggle.setAttribute("aria-expanded", isOpen);
    menuToggle.setAttribute("aria-label", isOpen ? "Close navigation menu" : "Open navigation menu");
}

if (menuToggle && mainNav) {
    menuToggle.addEventListener("click", function () {
        setMenu(!mainNav.classList.contains("open"));
    });

    // Close the menu after a link is clicked
    const navLinks = document.querySelectorAll("#main-nav a"); // DOM: querySelectorAll
    navLinks.forEach(function (link) {
        link.addEventListener("click", function () {
            setMenu(false);
        });
    });

    // Clicking the dark background closes the menu
    navOverlay.addEventListener("click", function () {
        setMenu(false);
    });

    // Close the menu with the Escape key
    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape" && mainNav.classList.contains("open")) {
            setMenu(false);
            menuToggle.focus();                                // keyboard focus returns to the menu button
        }
    });

    // Reset the menu if the window becomes wide again
    window.addEventListener("resize", function () {
        if (window.innerWidth > 1480) {
            setMenu(false);
        }
    });
}


/* ---------- 3. NOTIFICATION BANNER ---------- */
const banner = document.getElementById("notice-banner");
const bannerClose = document.getElementById("banner-close");

if (banner && bannerClose) {
    bannerClose.addEventListener("click", function () {
        banner.classList.add("hide");                          // CSS fades and collapses it
        setTimeout(function () {
            banner.style.display = "none";                     // DOM: style change, removes it fully
        }, 300);
    });
}


/* ---------- 4. FAQ (click to open / close) ---------- */
const faqQuestions = document.querySelectorAll(".faq-question");

faqQuestions.forEach(function (button) {
    button.addEventListener("click", function () {
        const item = button.closest(".faq-item");
        const answer = item.querySelector(".faq-answer");      // DOM: querySelector

        const isOpen = item.classList.toggle("open");          // add/remove "open" class
        button.setAttribute("aria-expanded", isOpen);

        // Animate the height: open = real content height, closed = back to CSS value (0)
        if (isOpen) {
            answer.style.maxHeight = answer.scrollHeight + "px";
        } else {
            answer.style.maxHeight = null;
        }
    });
});


/* ---------- 5. MODAL POPUP ---------- */
const modal = document.getElementById("guide-modal");
const openModalBtn = document.getElementById("open-modal");
let lastFocusedElement = null;   // remembers which button opened the modal

if (modal && openModalBtn) {
    const closeButtons = modal.querySelectorAll("[data-close-modal]");

    function openModal() {
        lastFocusedElement = document.activeElement;
        modal.classList.add("open");                           // CSS fades it in
        document.body.classList.add("modal-open");             // stops the page scrolling behind it
        modal.querySelector(".modal-close").focus();           // keyboard focus moves into the modal
    }

    function closeModal() {
        modal.classList.remove("open");
        document.body.classList.remove("modal-open");
        if (lastFocusedElement) {
            lastFocusedElement.focus();                        // focus goes back to the button
        }
    }

    openModalBtn.addEventListener("click", openModal);

    closeButtons.forEach(function (btn) {
        btn.addEventListener("click", closeModal);
    });

    // Click on the dark area outside the white box closes the modal
    modal.addEventListener("click", function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    // Escape key closes the modal
    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape" && modal.classList.contains("open")) {
            closeModal();
        }
    });

    // Keep Tab key focus inside the open modal
    modal.addEventListener("keydown", function (event) {
        if (event.key !== "Tab") return;
        const focusable = modal.querySelectorAll("button, a[href]");
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });
}


/* ---------- 6. SLIDER / CAROUSEL ---------- */
const slider = document.getElementById("slider");
const sliderTrack = document.getElementById("slider-track");

if (slider && sliderTrack) {
    const slides = sliderTrack.children;
    const dotsBox = document.getElementById("slider-dots");
    const slideCount = document.getElementById("slider-count");
    const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    let current = 0;          // index of the slide being shown
    let timer = null;         // holds the auto-play timer

    // Create one dot button for every slide (DOM: createElement + appendChild)
    for (let i = 0; i < slides.length; i++) {
        const dot = document.createElement("button");
        dot.type = "button";
        dot.setAttribute("aria-label", "Go to slide " + (i + 1));
        dot.addEventListener("click", function () {
            goToSlide(i);
            startAutoPlay();
        });
        dotsBox.appendChild(dot);
    }
    const dots = dotsBox.children;

    // Show slide number n (wraps around at both ends)
    function goToSlide(n) {
        current = (n + slides.length) % slides.length;
        sliderTrack.style.transform = "translateX(-" + current * 100 + "%)";   // slides the track

        for (let i = 0; i < slides.length; i++) {
            dots[i].classList.toggle("active", i === current);
            slides[i].setAttribute("aria-hidden", i !== current);
            slides[i].querySelector("a").tabIndex = (i === current) ? 0 : -1;  // hidden slides are skipped by Tab
        }
        slideCount.textContent = (current + 1) + " / " + slides.length;          // DOM: textContent
    }

    // Auto-play every 4 seconds
    function startAutoPlay() {
        if (reduceMotion) return;
        clearInterval(timer);
        timer = setInterval(function () {
            goToSlide(current + 1);
        }, 4000);
    }
    function stopAutoPlay() {
        clearInterval(timer);
    }

    document.getElementById("slider-prev").addEventListener("click", function () {
        goToSlide(current - 1);
        startAutoPlay();
    });
    document.getElementById("slider-next").addEventListener("click", function () {
        goToSlide(current + 1);
        startAutoPlay();
    });

    // Pause while the mouse is over the slider or a button inside it has focus
    slider.addEventListener("mouseenter", stopAutoPlay);
    slider.addEventListener("mouseleave", startAutoPlay);
    slider.addEventListener("focusin", stopAutoPlay);
    slider.addEventListener("focusout", startAutoPlay);

    // Swipe left / right on phones
    let touchStartX = 0;
    slider.addEventListener("touchstart", function (event) {
        touchStartX = event.touches[0].clientX;
    }, { passive: true });
    slider.addEventListener("touchend", function (event) {
        const distance = event.changedTouches[0].clientX - touchStartX;
        if (Math.abs(distance) > 50) {
            goToSlide(distance < 0 ? current + 1 : current - 1);
            startAutoPlay();
        }
    });

    goToSlide(0);
    startAutoPlay();
}


/* =========================================================
   7. STUDENT REGISTRATION FORM (Practical 5)
   Runs only on registration.html (the form must exist).
   The form has the "novalidate" attribute, so the browser's own
   pop-up messages are switched off and OUR JavaScript shows
   the error next to each field instead.
   ========================================================= */
const registrationForm = document.getElementById("registration-form");

if (registrationForm) {

    /* ----- Regular expressions (what each one checks) ----- */
    // Name: letters only; words are separated by a single space (no digits or symbols)
    const namePattern = /^[A-Za-z]+(?: [A-Za-z]+)*$/;
    // Email: some text, an @, a domain, a dot and an ending of 2+ letters (.com, .in ...)
    const emailPattern = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/;
    // Mobile: exactly 10 digits and the first digit must be 6, 7, 8 or 9 (Indian mobile numbers)
    const mobilePattern = /^[6-9]\d{9}$/;
    // Password: at least 8 characters containing a lowercase, an uppercase, a digit and a special character
    const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/;
    // Single-rule checks used by the password message and the strength meter
    const hasLower = /[a-z]/;          // at least one lowercase letter
    const hasUpper = /[A-Z]/;          // at least one uppercase letter
    const hasNumber = /\d/;            // at least one digit
    const hasSpecial = /[^A-Za-z0-9]/; // at least one character that is not a letter or digit

    /* ----- Form elements ----- */
    const nameInput = document.getElementById("fullName");
    const emailInput = document.getElementById("email");
    const mobileInput = document.getElementById("mobile");
    const passwordInput = document.getElementById("password");
    const confirmInput = document.getElementById("confirmPassword");
    const courseSelect = document.getElementById("course");
    const yearSelect = document.getElementById("year");
    const termsBox = document.getElementById("terms");
    const statusBox = document.getElementById("form-status");
    const strengthBar = document.getElementById("strength-bar");
    const strengthText = document.getElementById("strength-text");

    /* ----- Helpers that show / remove messages next to a field ----- */
    // Each field sits in <div id="field-KEY"> and has a message <p id="KEY-message">
    function showError(key, message) {
        const box = document.getElementById("field-" + key);
        box.classList.remove("success");
        box.classList.add("error");                                    // red border + red text
        document.getElementById(key + "-message").textContent = "❌ " + message;
        box.querySelectorAll("input, select").forEach(function (el) {
            el.setAttribute("aria-invalid", "true");                   // tells screen readers it is invalid
        });
        return false;
    }

    function showSuccess(key, message) {
        const box = document.getElementById("field-" + key);
        box.classList.remove("error");
        box.classList.add("success");                                  // green border + green text
        document.getElementById(key + "-message").textContent = "✔ " + message;
        box.querySelectorAll("input, select").forEach(function (el) {
            el.removeAttribute("aria-invalid");
        });
        return true;
    }

    function clearField(key) {
        const box = document.getElementById("field-" + key);
        box.classList.remove("error", "success");
        document.getElementById(key + "-message").textContent = "";
        box.querySelectorAll("input, select").forEach(function (el) {
            el.removeAttribute("aria-invalid");
        });
    }

    // True if this field has already been checked once (it has an error or success state)
    function isChecked(key) {
        const box = document.getElementById("field-" + key);
        return box.classList.contains("error") || box.classList.contains("success");
    }

    /* ----- One validation function per field (each returns true / false) ----- */
    function validateName() {
        const value = nameInput.value.trim();
        if (value === "") return showError("fullName", "Full name is required.");
        if (!namePattern.test(value)) return showError("fullName", "Name must contain only letters and spaces (no numbers or special characters).");
        if (value.length < 3) return showError("fullName", "Name must be at least 3 characters long.");
        return showSuccess("fullName", "Name looks good.");
    }

    function validateEmail() {
        const value = emailInput.value.trim();
        if (value === "") return showError("email", "Email is required.");
        if (!emailPattern.test(value)) return showError("email", "Enter a valid email address, for example name@example.com.");
        return showSuccess("email", "Email looks good.");
    }

    function validateMobile() {
        const value = mobileInput.value.trim();
        if (value === "") return showError("mobile", "Mobile number is required.");
        if (!mobilePattern.test(value)) return showError("mobile", "Enter a valid 10-digit mobile number starting with 6, 7, 8 or 9 (digits only).");
        return showSuccess("mobile", "Mobile number looks good.");
    }

    // Returns a list of the password rules that are NOT met yet
    function getMissingRules(password) {
        const missing = [];
        if (password.length < 8) missing.push("at least 8 characters");
        if (!hasUpper.test(password)) missing.push("an uppercase letter");
        if (!hasLower.test(password)) missing.push("a lowercase letter");
        if (!hasNumber.test(password)) missing.push("a number");
        if (!hasSpecial.test(password)) missing.push("a special character");
        return missing;
    }

    function validatePassword() {
        const value = passwordInput.value;
        if (value === "") return showError("password", "Password is required.");
        const missing = getMissingRules(value);
        if (missing.length > 0 || !passwordPattern.test(value)) {
            return showError("password", "Password is not strong enough. Still needed: " + missing.join(", ") + ".");
        }
        return showSuccess("password", "Password meets all the rules.");
    }

    function validateConfirm() {
        const value = confirmInput.value;
        if (value === "") return showError("confirmPassword", "Please confirm your password.");
        if (value !== passwordInput.value) return showError("confirmPassword", "Passwords do not match.");
        return showSuccess("confirmPassword", "Passwords match.");
    }

    function validateCourse() {
        if (courseSelect.selectedIndex === 0) return showError("course", "Please select your course.");   // index 0 = "Select your course"
        return showSuccess("course", "Course selected.");
    }

    function validateYear() {
        if (yearSelect.selectedIndex === 0) return showError("year", "Please select your year.");
        return showSuccess("year", "Year selected.");
    }

    function validateGender() {
        const chosen = document.querySelector('input[name="gender"]:checked');   // the selected radio button (or null)
        if (chosen === null) return showError("gender", "Please select your gender.");
        return showSuccess("gender", "Gender selected.");
    }

    function validateTerms() {
        if (!termsBox.checked) return showError("terms", "You must accept the Terms and Conditions to register.");
        return showSuccess("terms", "Terms accepted.");
    }

    // Field name -> its validation function (used when the form is submitted)
    const validators = {
        fullName: validateName,
        email: validateEmail,
        mobile: validateMobile,
        password: validatePassword,
        confirmPassword: validateConfirm,
        course: validateCourse,
        year: validateYear,
        gender: validateGender,
        terms: validateTerms
    };

    /* ----- Password strength meter (updates while typing) ----- */
    // Score = how many of the 5 checks are true: length, uppercase, lowercase, number, special
    function getStrengthScore(password) {
        let score = 0;
        if (password.length >= 8) score++;
        if (hasUpper.test(password)) score++;
        if (hasLower.test(password)) score++;
        if (hasNumber.test(password)) score++;
        if (hasSpecial.test(password)) score++;
        return score;
    }

    function updateStrength() {
        const password = passwordInput.value;
        strengthBar.classList.remove("weak", "medium", "strong");
        strengthText.classList.remove("weak", "medium", "strong");

        if (password === "") {
            strengthBar.style.width = "0%";
            strengthText.textContent = "Password strength: type a password";
            return;
        }

        const score = getStrengthScore(password);
        let level = "weak";
        let label = "Weak";                                  // score 1 or 2
        if (score === 5) {
            level = "strong";
            label = "Strong";                                // all 5 checks passed
        } else if (score >= 3) {
            level = "medium";
            label = "Medium";                                // score 3 or 4
        }

        strengthBar.style.width = (score / 5) * 100 + "%";   // bar length
        strengthBar.classList.add(level);                    // bar colour
        strengthText.classList.add(level);
        strengthText.textContent = "Password strength: " + label;
    }

    /* ----- Events while the user types ----- */
    // On leaving a field (blur) check it if something was typed;
    // after the first check, re-check on every keystroke so errors disappear as soon as they are fixed.
    function watchField(input, key, validator) {
        input.addEventListener("blur", function () {
            if (input.value !== "") validator();
        });
        input.addEventListener("input", function () {
            if (isChecked(key)) validator();
        });
    }

    watchField(nameInput, "fullName", validateName);
    watchField(emailInput, "email", validateEmail);
    watchField(mobileInput, "mobile", validateMobile);
    watchField(confirmInput, "confirmPassword", validateConfirm);

    // Password: update the strength meter on every keystroke
    passwordInput.addEventListener("input", function () {
        updateStrength();
        if (isChecked("password")) validatePassword();
        if (confirmInput.value !== "") validateConfirm();    // keep the confirm field in sync
    });
    passwordInput.addEventListener("blur", function () {
        if (passwordInput.value !== "") validatePassword();
    });

    // Selects, radio buttons and the checkbox are checked when they change
    courseSelect.addEventListener("change", validateCourse);
    yearSelect.addEventListener("change", validateYear);
    termsBox.addEventListener("change", validateTerms);
    document.querySelectorAll('input[name="gender"]').forEach(function (radio) {
        radio.addEventListener("change", validateGender);
    });

    /* ----- Form submit ----- */
    registrationForm.addEventListener("submit", function (event) {
        event.preventDefault();                              // stop the normal page submit

        // Run EVERY validator so all errors appear at the same time
        const invalidKeys = [];
        Object.keys(validators).forEach(function (key) {
            if (!validators[key]()) invalidKeys.push(key);
        });

        statusBox.hidden = false;

        if (invalidKeys.length > 0) {
            // Some fields are wrong: show a short summary and move to the first wrong field
            statusBox.classList.remove("success");
            statusBox.classList.add("error");
            statusBox.textContent = "❌ Please correct the " + invalidKeys.length + " highlighted field" +
                (invalidKeys.length === 1 ? "" : "s") + " below.";
            document.getElementById("field-" + invalidKeys[0]).querySelector("input, select").focus();
        } else {
            // Everything is valid
            const courseName = courseSelect.options[courseSelect.selectedIndex].textContent;
            const yearName = yearSelect.options[yearSelect.selectedIndex].textContent;
            statusBox.classList.remove("error");
            statusBox.classList.add("success");
            statusBox.textContent = "✅ Registration successful! Welcome to Student Hub, " +
                nameInput.value.trim() + " (" + courseName + ", " + yearName + ").";
            statusBox.scrollIntoView({ behavior: "smooth", block: "center" });
        }
    });

    /* ----- Reset button: clear every message and the strength meter ----- */
    registrationForm.addEventListener("reset", function () {
        Object.keys(validators).forEach(clearField);
        statusBox.hidden = true;
        statusBox.classList.remove("success", "error");
        statusBox.textContent = "";
        setTimeout(updateStrength, 0);                       // wait until the browser has emptied the fields
    });
}
