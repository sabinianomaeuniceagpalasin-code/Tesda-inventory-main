window.showItemDetails = function (item) {
    const elItem = document.getElementById("modal-item");
    const elDisplay = document.getElementById("modal-item-display");
    const elSerial = document.getElementById("modal-serial");
    const statusEl = document.getElementById("modal-status");
    const dateEl = document.getElementById("modal-date");
    const qrImg = document.getElementById("modal-qr");

    // NEW FIELDS
    const sourceOfFundEl = document.getElementById("modal-source-of-fund");
    const classificationEl = document.getElementById("modal-classification");
    const unitCostEl = document.getElementById("modal-unit-cost");

    // EDITOR INPUTS
    const sourceOfFundInput = document.getElementById("modal-source-of-fund-input");
    const classificationInput = document.getElementById("modal-classification-input");
    const unitCostInput = document.getElementById("modal-unit-cost-input");

    if (elItem) elItem.innerText = item.item_name || "---";
    if (elDisplay) elDisplay.innerText = item.item_name || "---";
    if (elSerial) elSerial.innerText = item.serial_no || "---";

    if (statusEl) {
        statusEl.innerText = item.status || "---";
        statusEl.className = "detail-value fw-bold";

        if (item.status === "Available") {
            statusEl.classList.add("text-success");
        } else if (item.status === "For Repair") {
            statusEl.classList.add("text-warning");
        } else {
            statusEl.classList.add("text-danger");
        }
    }

    if (dateEl && item.date_acquired) {
        const d = new Date(item.date_acquired);
        dateEl.innerText = d.toLocaleDateString("en-US", {
            year: "numeric",
            month: "long",
            day: "numeric",
        });
    } else if (dateEl) {
        dateEl.innerText = "---";
    }

    if (qrImg) {
        qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(item.serial_no || "")}`;
    }

    // =========================
    // SAVE CURRENT SERIAL
    // =========================
    window.currentModalSerialNo = item.serial_no || null;

    // =========================
    // SOURCE OF FUND
    // =========================
    const sourceOfFundValue = (item.source_of_fund || "").trim();

    if (sourceOfFundEl) {
        sourceOfFundEl.textContent = sourceOfFundValue || "Not set";
        sourceOfFundEl.classList.toggle("text-muted", !sourceOfFundValue);
        sourceOfFundEl.classList.remove("d-none");
    }

    if (sourceOfFundInput) {
        sourceOfFundInput.value = sourceOfFundValue;
        sourceOfFundInput.classList.remove("input-error");
    }

    if (typeof resetSingleFieldEditorState === "function") {
        resetSingleFieldEditorState("source_of_fund");
    }

    // =========================
    // CLASSIFICATION
    // =========================
    const classificationValue = (item.classification || "").trim();

    if (classificationEl) {
        classificationEl.textContent = classificationValue || "Not set";
        classificationEl.classList.toggle("text-muted", !classificationValue);
        classificationEl.classList.remove("d-none");
    }

    if (classificationInput) {
        classificationInput.value = classificationValue;
        classificationInput.classList.remove("input-error");
    }

    if (typeof resetSingleFieldEditorState === "function") {
        resetSingleFieldEditorState("classification");
    }

    // =========================
    // UNIT COST
    // =========================
    const rawUnitCost = item.unit_cost;

    if (unitCostEl) {
        if (rawUnitCost !== null && rawUnitCost !== undefined && rawUnitCost !== "") {
            unitCostEl.textContent = `₱${Number(rawUnitCost).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })}`;
            unitCostEl.classList.remove("text-muted");
        } else {
            unitCostEl.textContent = "Not set";
            unitCostEl.classList.add("text-muted");
        }

        unitCostEl.classList.remove("d-none");
    }

    if (unitCostInput) {
        unitCostInput.value =
            rawUnitCost !== null && rawUnitCost !== undefined && rawUnitCost !== ""
                ? Number(rawUnitCost).toFixed(2)
                : "";
        unitCostInput.classList.remove("input-error");
    }

    if (typeof resetSingleFieldEditorState === "function") {
        resetSingleFieldEditorState("unit_cost");
    }

    // =========================
    // SPECIFICATIONS SECTION
    // =========================
    const specsEl = document.getElementById("modal-specifications");
    const specsInput = document.getElementById("modal-specifications-input");
    const specsEditor = document.getElementById("modal-specifications-editor");
    const specsBtn = document.getElementById("specsEditBtn");
    const specsCounter = document.getElementById("specifications-counter");

    const specsValue = item.specification ? item.specification.trim() : "";

    if (specsEl) {
        specsEl.textContent = specsValue || "Not set";
        specsEl.classList.toggle("text-muted", !specsValue);
        specsEl.classList.remove("d-none");
    }

    if (specsInput) {
        specsInput.value = specsValue;
        specsInput.style.height = "auto";
        specsInput.classList.remove("input-error");
    }

    if (specsEditor) {
        specsEditor.classList.add("d-none");
        specsEditor.classList.remove("show-editor");
    }

    if (specsBtn) {
        specsBtn.textContent = "Edit";
        specsBtn.dataset.mode = "view";
        specsBtn.style.pointerEvents = "auto";
    }

    if (specsCounter) {
        const max = parseInt(specsInput?.getAttribute("maxlength")) || 1000;
        specsCounter.textContent = `${specsValue.length} / ${max}`;
        specsCounter.classList.remove("limit-near", "limit-reached");
    }

    if (typeof resetSpecificationsEditorState === "function") {
        resetSpecificationsEditorState();
    }

    if (typeof autoResizeTextarea === "function" && specsInput) {
        autoResizeTextarea(specsInput);
    }

    if (typeof updateSpecsCounter === "function") {
        updateSpecsCounter();
    }

    // =========================
    // EXPECTED LIFESPAN
    // =========================
    const lifespanEl = document.getElementById("modal-lifespan");
    const expectedLifeYears = Number(item.expected_life_years || 0);

    let lifespanValue = "";
    if (expectedLifeYears > 0) {
        lifespanValue = `${expectedLifeYears} year${expectedLifeYears === 1 ? "" : "s"}`;
    }

    if (lifespanEl) {
        lifespanEl.textContent = lifespanValue || "Not set";
        lifespanEl.classList.toggle("text-muted", !lifespanValue);
    }

    const modalEl = document.getElementById("inventoryModal");
        if (modalEl && typeof bootstrap !== "undefined") {
            const myModal = bootstrap.Modal.getOrCreateInstance(modalEl, {
                backdrop: true,
                keyboard: true
            });
            myModal.show();
        } else if (modalEl) {
            modalEl.style.display = "flex";
        }
};

window.openInventoryEditModal = function (button) {
    const modal = document.getElementById("inventoryEditModal");
    if (!modal) return;

    modal.classList.add("active");

    const row = button.closest("tr");
    if (!row) return;

    const raw = row.dataset.item;
    if (!raw) return;

    let item;
    try {
        item = JSON.parse(raw);
    } catch (err) {
        console.error("Failed to parse row data-item:", err);
        return;
    }

    const serialInput = document.getElementById("edit_serial_no");
    const itemNameInput = document.getElementById("edit_item_name");
    const fundInput = document.getElementById("edit_source_of_fund");
    const classInput = document.getElementById("edit_classification");
    const dateInput = document.getElementById("edit_date_acquired");
    const statusInput = document.getElementById("edit_status");

    if (serialInput) serialInput.value = item.serial_no || "";
    if (itemNameInput) itemNameInput.value = item.item_name || "";
    if (fundInput) fundInput.value = item.source_of_fund || "";
    if (classInput) classInput.value = item.classification || "";
    if (dateInput) dateInput.value = item.date_acquired || "";
    if (statusInput) statusInput.value = item.status || "";

    const form = document.getElementById("inventoryEditForm");
    if (form) {
        form.action = "/inventory/update/" + encodeURIComponent(item.serial_no || "");
    }
};

window.closeInventoryEditModal = function () {
    const modal = document.getElementById("inventoryEditModal");
    if (modal) {
        modal.classList.remove("active");
    }
};

window.addEventListener("click", function (event) {
    const modal = document.getElementById("inventoryEditModal");
    if (modal && event.target === modal) {
        modal.classList.remove("active");
    }
});

window.deleteItem = function (serial_no) {
    if (!confirm("Are you sure you want to archive this item?")) return;

    fetch(`/inventory/${encodeURIComponent(serial_no)}`, {
        method: "DELETE",
        headers: {
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
            "Accept": "application/json",
            "Content-Type": "application/json"
        }
    })
        .then((res) => {
            if (!res.ok) {
                throw new Error("Failed to delete item.");
            }
            return res.json();
        })
        .then((data) => {
            if (data.success) {
                alert("Item archived successfully!");
                location.reload();
            } else {
                alert(data.message || "Failed to delete item.");
            }
        })
        .catch((err) => {
            console.error(err);
            alert("Error deleting item.");
        });
};

document.addEventListener("click", function (e) {
    if (e.target && e.target.classList.contains("view-btn")) {
        if (typeof showUsageHistory === "function") {
            showUsageHistory();
        }
    }
});

document.addEventListener("click", function (e) {
    const row = e.target.closest("#inventoryTable tbody tr.inventory-row");
    if (!row) return;

    if (e.target.closest("button")) return;

    const raw = row.getAttribute("data-item");
    if (!raw) return;

    try {
        const item = JSON.parse(raw);
        console.log("Clicked item:", item); // debug
        window.showItemDetails(item);
    } catch (err) {
        console.error("Failed to parse data-item:", raw, err);
    }
});

// =========================
// USAGE HISTORY PAGINATION
// =========================
const USAGE_ROWS_PER_PAGE = 10;
let usageAllRows = [];
let usageCurrentPage = 1;

/**
 * Renders the current page slice of usageAllRows into the
 * #usage-history-body table body and updates the pagination controls.
 */
function renderUsagePage() {
    const tbody = document.getElementById("usage-history-body");
    if (!tbody) return;

    const totalRows  = usageAllRows.length;
    const totalPages = Math.max(1, Math.ceil(totalRows / USAGE_ROWS_PER_PAGE));

    // Clamp current page within valid range
    usageCurrentPage = Math.min(Math.max(1, usageCurrentPage), totalPages);

    const start = (usageCurrentPage - 1) * USAGE_ROWS_PER_PAGE;
    const slice = usageAllRows.slice(start, start + USAGE_ROWS_PER_PAGE);

    if (slice.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:20px;">No usage history found.</td></tr>`;
    } else {
        tbody.innerHTML = slice.map(function (row) {
            // Colour-code the return status badge
            const statusClass =
                row.return_status === "Returned" ? "text-green"  :
                row.return_status === "Overdue"  ? "text-red"    : "text-blue";

            return `
                <tr>
                    <td>${row.issued_date ?? "-"} → ${row.return_date ?? "-"}</td>
                    <td>${row.issued_to ?? "-"}</td>
                    <td>${row.issued_by ?? "-"}</td>
                    <td><span class="${statusClass}">${row.return_status ?? "-"}</span></td>
                </tr>
            `;
        }).join("");
    }

    // Update "Showing X–Y of Z entries" label
    const entriesCount = document.querySelector(".entries-count");
    if (entriesCount) {
        const showing = totalRows === 0 ? 0 : start + 1;
        const showEnd = Math.min(start + USAGE_ROWS_PER_PAGE, totalRows);
        entriesCount.textContent = `Showing ${showing}–${showEnd} of ${totalRows} entries`;
    }

    renderUsagePagination(totalPages);
}

/**
 * Rebuilds the pagination button row.
 */
function renderUsagePagination(totalPages) {
    const controls = document.querySelector(".pagination-controls");
    if (!controls) return;

    let html = `
        <button class="pag-btn"
            onclick="changeUsagePage(${usageCurrentPage - 1})"
            ${usageCurrentPage === 1 ? "disabled" : ""}>
            &#8249;
        </button>
    `;

    for (let i = 1; i <= totalPages; i++) {
        html += `
            <button class="pag-num ${i === usageCurrentPage ? "active" : ""}"
                onclick="changeUsagePage(${i})">
                ${i}
            </button>
        `;
    }

    html += `
        <button class="pag-btn"
            onclick="changeUsagePage(${usageCurrentPage + 1})"
            ${usageCurrentPage === totalPages ? "disabled" : ""}>
            &#8250;
        </button>
    `;

    controls.innerHTML = html;
}

/**
 * Called by pagination buttons to jump to a specific page.
 */
window.changeUsagePage = function (page) {
    usageCurrentPage = page;
    renderUsagePage();
};

/**
 * Fetches the usage / issuance history for the item currently open
 * in the inventory detail modal, then renders it into the history modal.
 * Reads the serial number from #modal-serial (set by showItemDetails).
 */
window.loadHistoryData = function () {
    const serialNo = window.currentModalSerialNo || null;
    const tbody    = document.getElementById("usage-history-body");

    if (!serialNo || serialNo === "---") {
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:20px;color:red;">No item selected.</td></tr>`;
        }
        return;
    }

    // Show loading state while the request is in flight
    if (tbody) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:20px;">Loading...</td></tr>`;
    }

    fetch(`/item/usage-history/${encodeURIComponent(serialNo)}`, {
        headers: {
            "X-Requested-With": "XMLHttpRequest",
            "Accept": "application/json",
        }
    })
    .then(function (res) {
        if (!res.ok) throw new Error("Server returned " + res.status);
        return res.json();
    })
    .then(function (data) {
        if (!data.success) {
            if (tbody) {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;color:red;padding:20px;">Failed to load history.</td></tr>`;
            }
            return;
        }

        // Update the item info header inside the history modal
        const nameEl = document.getElementById("history-item-name");
        const propEl = document.getElementById("history-property-no");
        if (nameEl) nameEl.innerText = data.item_name   || "---";
        if (propEl) propEl.innerText = data.property_no || "---";

        // Store rows and render the first page
        usageAllRows     = data.history || [];
        usageCurrentPage = 1;
        renderUsagePage();
    })
    .catch(function (err) {
        console.error("Usage history fetch error:", err);
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;color:red;padding:20px;">An error occurred while loading history.</td></tr>`;
        }
    });
};

/**
 * Opens the usage history modal for the item that is currently open
 * in the inventory detail modal.
 * Reads item name and serial from the detail modal DOM elements so
 * the two modals always stay in sync.
 */
window.showUsageHistory = function () {
    const historyModal = document.getElementById("usageHistoryModal");

    const serialNo = window.currentModalSerialNo || window.currentSerialNo || null;

    if (!serialNo || serialNo === "---") {
        alert("Please click on an item first before viewing its usage history.");
        return;
    }

    window.currentModalSerialNo = serialNo;

    const itemName = document.getElementById("modal-item")
        ? document.getElementById("modal-item").innerText
        : "---";

    if (document.getElementById("history-item-name")) {
        document.getElementById("history-item-name").innerText = itemName;
    }

    if (document.getElementById("history-property-no")) {
        document.getElementById("history-property-no").innerText = serialNo;
    }

    if (historyModal) {
        historyModal.style.setProperty("display", "flex", "important");

        if (typeof loadHistoryData === "function") {
            loadHistoryData();
        }
    }
};w

document.addEventListener("DOMContentLoaded", function () {
    const popover = document.createElement("div");
    popover.classList.add("history-popover");
    popover.innerHTML = `<p class="loading-text">Loading...</p><div class="history-content"></div>`;
    document.body.appendChild(popover);

    document.querySelectorAll(".serial-cell").forEach((cell) => {
        let timer;

        cell.addEventListener("mouseenter", function () {
            const serial = this.dataset.serial;
            const content = popover.querySelector(".history-content");
            const loading = popover.querySelector(".loading-text");

            content.innerHTML = "";
            loading.style.display = "block";

            const rect = this.getBoundingClientRect();
            popover.style.top = `${window.scrollY + rect.bottom + 5}px`;
            popover.style.left = `${window.scrollX + rect.left}px`;
            popover.style.display = "block";

            fetch(`/maintenance/history/${serial}`)
                .then((res) => res.json())
                .then((data) => {
                    loading.style.display = "none";

                    if (data.error) {
                        content.innerHTML = `<p style="color:red">${data.error}</p>`;
                        return;
                    }

                    let html = "<strong>Maintenance History:</strong>";
                    if (data.maintenance.length) {
                        data.maintenance.forEach((m) => {
                            html += `<p>${m.date_reported}: ${m.issue_type} (Status: ${m.status || "N/A"})</p>`;
                        });
                    } else {
                        html += "<p>No maintenance records.</p>";
                    }

                    html += "<strong>Damage History:</strong>";
                    if (data.damage.length) {
                        data.damage.forEach((d) => {
                            html += `<p>${d.reported_at}: ${d.damage_type || "N/A"}</p>`;
                        });
                    } else {
                        html += "<p>No damage records.</p>";
                    }

                    content.innerHTML = html;
                })
                .catch((err) => {
                    loading.style.display = "none";
                    content.innerHTML = `<p style="color:red">Failed to load history.</p>`;
                    console.error(err);
                });
        });

        cell.addEventListener("mouseleave", function () {
            timer = setTimeout(() => {
                popover.style.display = "none";
            }, 200);
        });

        popover.addEventListener("mouseenter", function () {
            clearTimeout(timer);
        });

        popover.addEventListener("mouseleave", function () {
            popover.style.display = "none";
        });
    });
});