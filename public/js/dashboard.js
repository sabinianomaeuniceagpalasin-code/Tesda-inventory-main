document.addEventListener("DOMContentLoaded", function () {
    document.getElementById("chooseIcs")?.addEventListener("click", () => openAddFormModal("ICS"));
    document.getElementById("choosePar")?.addEventListener("click", () => openAddFormModal("PAR"));

    document.querySelectorAll(".add-btn").forEach(el => {
        el.addEventListener("click", (e) => { e.preventDefault(); openFormTypeModal(); });
    });

    document.querySelectorAll("#form .form-table tbody tr td a").forEach(link => {
        if (link.textContent.trim() === "View") link.addEventListener("click", handleViewFormClick);
    });

    const manualBtn = document.getElementById("manualEntryBtn");
    if (manualBtn) {
        manualBtn.addEventListener("click", function () {
            const scannerMsg = document.querySelector(".scanner-container");
            const entryForm = document.getElementById("addItemForm");
            const isHidden = entryForm.style.display === "none";

            scannerMsg.style.display = isHidden ? "none" : "flex";
            entryForm.style.display = isHidden ? "block" : "none";
            this.innerText = isHidden ? "Back to Scanner" : "Manual Entry Mode";
        });
    }
});

function openDashboardModal(type) {
    const config = dashboardConfigs[type];
    if (!config) return;

    document.getElementById("dt-title").innerText = config.title;
    document.getElementById("dt-thead").innerHTML = `<tr>${config.headers.map(h => `<th>${h}</th>`).join("")}</tr>`;

    const footerBtn = document.querySelector(".btn-view-section");
    if (footerBtn) {
        footerBtn.innerText = config.buttonText;
        footerBtn.onclick = () => openViewSection(config.targetSection);
    }

    const tbody = document.getElementById("dt-tbody");
    tbody.innerHTML = `<tr><td colspan="${config.headers.length}">Loading...</td></tr>`;

    fetch(config.apiUrl)
        .then(res => res.json())
        .then(data => { tbody.innerHTML = data.html; })
        .catch(() => { tbody.innerHTML = `<tr><td colspan="${config.headers.length}">Error</td></tr>`; });

    document.getElementById("dashboardTableModal").style.display = "flex";
}

function openViewSection(section) {
    document.getElementById("dynamicModal").style.display = "none";
    document.getElementById("dashboardTableModal").style.display = "none";
    const link = document.querySelector(`a[data-target="${section}"]`);
    if (link) { link.click(); return; }

    document.querySelectorAll(".content-section").forEach(s => s.style.display = "none");
    const target = document.getElementById(section);
    if (target) target.style.display = "block";
}