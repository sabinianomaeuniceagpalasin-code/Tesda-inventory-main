/* ============================
   PAGE SWITCHING
============================ */
document.addEventListener("DOMContentLoaded", () => {
    const menuLinks = document.querySelectorAll(".menu a[data-target]");
    const sections = document.querySelectorAll(".content-section");
    const pageTitle = document.getElementById("page-title");

    function activateSection(targetId) {
        if (!targetId) return;

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
        }
    }

    menuLinks.forEach((link) => {
        link.addEventListener("click", (e) => {
            e.preventDefault();

            const targetId = link.dataset.target;
            activateSection(targetId);

            localStorage.setItem("activeSection", targetId);
        });
    });

    // Auto-open saved section after reload
    const savedSection = localStorage.getItem("activeSection");

    if (savedSection && document.getElementById(savedSection)) {
        activateSection(savedSection);
        localStorage.removeItem("activeSection");
    } else {
        // fallback: ensure active menu and section are synced
        const activeLink = document.querySelector(".menu a.active[data-target]");
        if (activeLink) {
            activateSection(activeLink.dataset.target);
        }
    }
});