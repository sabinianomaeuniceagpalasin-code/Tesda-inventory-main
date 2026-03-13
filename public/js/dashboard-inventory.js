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
    if (!confirm("Are you sure you want to delete this item?")) return;

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
                alert("Item deleted successfully!");
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

window.showUsageHistory = function () {
    const historyModal = document.getElementById("usageHistoryModal");

    const itemName = document.getElementById("modal-item")
        ? document.getElementById("modal-item").innerText
        : "---";

    const serialNo = document.getElementById("modal-serial")
        ? document.getElementById("modal-serial").innerText
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
};

document.addEventListener("DOMContentLoaded", function () {
    const modalEl = document.getElementById("inventoryModal");
    if (!modalEl || typeof bootstrap === "undefined") return;

    const inventoryModal = bootstrap.Modal.getOrCreateInstance(modalEl);

    // Close when clicking sidebar / module links
    document.querySelectorAll(".sidebar a, .sidebar button, [data-target]").forEach((el) => {
        el.addEventListener("click", function () {
            inventoryModal.hide();
        });
    });

    // Close when clicking anywhere outside the modal panel
    document.addEventListener("click", function (e) {
        if (!modalEl.classList.contains("show")) return;

        const dialog = modalEl.querySelector(".modal-dialog");
        if (!dialog) return;

        const clickedInsideDialog = dialog.contains(e.target);
        const clickedInventoryRow = e.target.closest("#inventoryTable tbody tr.inventory-row");
        const clickedInventoryButton = e.target.closest("#inventoryTable button");

        if (!clickedInsideDialog && !clickedInventoryRow && !clickedInventoryButton) {
            inventoryModal.hide();
        }
    });
});