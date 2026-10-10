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

// Open (true) or close (false) the mobile menu
function setMenu(isOpen) {
    mainNav.classList.toggle("open", isOpen);                  // DOM: classList.toggle
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

    // Close the menu with the Escape key
    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            setMenu(false);
        }
    });

    // Reset the menu if the window becomes wide again
    window.addEventListener("resize", function () {
        if (window.innerWidth > 1100) {
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
