/* ============================
   PAGE SWITCHING
============================ */
document.addEventListener("DOMContentLoaded", () => {
    const menuLinks = document.querySelectorAll(".menu a[data-target]");
    const sections = document.querySelectorAll(".content-section");
    const pageTitle = document.getElementById("page-title");

    const validSections = Array.from(sections).map(sec => sec.id);

    function activateSection(targetId, updateUrl = true) {
        if (!targetId || !validSections.includes(targetId)) {
            targetId = "dashboard";
        }

        menuLinks.forEach((link) => {
            link.classList.remove("active");
        });

        sections.forEach((sec) => {
            sec.classList.remove("active");
        });

        const targetSection = document.getElementById(targetId);
        const targetLink = document.querySelector(`.menu a[data-target="${targetId}"]`);

        if (targetSection) {
            targetSection.classList.add("active");
        }

        if (targetLink) {
            targetLink.classList.add("active");

            if (pageTitle) {
                pageTitle.textContent = targetLink.textContent.trim();
            }
        } else if (pageTitle) {
            pageTitle.textContent = "Dashboard";
        }

        if (updateUrl) {
            const url = new URL(window.location.href);
            url.searchParams.set("section", targetId);
            window.history.replaceState({}, "", url);
        }
    }

    menuLinks.forEach((link) => {
        link.addEventListener("click", (e) => {
            e.preventDefault();

            const targetId = link.dataset.target;
            activateSection(targetId, true);

            localStorage.setItem("activeSection", targetId);
        });
    });

    // 1. URL section has priority
    const params = new URLSearchParams(window.location.search);
    const urlSection = params.get("section");

    if (urlSection && validSections.includes(urlSection)) {
        activateSection(urlSection, false);
        localStorage.removeItem("activeSection");
        return;
    }

    // 2. localStorage fallback
    const savedSection = localStorage.getItem("activeSection");
    if (savedSection && validSections.includes(savedSection)) {
        activateSection(savedSection, false);
        localStorage.removeItem("activeSection");
        return;
    }

    // 3. fallback to current active link
    const activeLink = document.querySelector(".menu a.active[data-target]");
    if (activeLink) {
        activateSection(activeLink.dataset.target, false);
    } else {
        activateSection("dashboard", false);
    }
});