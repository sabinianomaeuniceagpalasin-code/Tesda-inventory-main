window.currentModalSerialNo = null;

function autoResizeTextarea(textarea) {
    if (!textarea) return;
    textarea.style.height = "auto";
    textarea.style.height = textarea.scrollHeight + "px";
}

function updateSpecsCounter() {
    const input = document.getElementById("modal-specifications-input");
    const counter = document.getElementById("specifications-counter");
    if (!input || !counter) return;

    const length = input.value.length;
    const max = parseInt(input.getAttribute("maxlength")) || 1000;

    counter.textContent = `${length} / ${max}`;
    counter.classList.remove("limit-near", "limit-reached");

    if (length >= max) {
        counter.classList.add("limit-reached");
    } else if (length >= max - 100) {
        counter.classList.add("limit-near");
    }
}

/* =========================
   SPECIFICATIONS
========================= */
function resetSpecificationsEditorState() {
    const displayEl = document.getElementById("modal-specifications");
    const editorEl = document.getElementById("modal-specifications-editor");
    const btnEl = document.getElementById("specsEditBtn");
    const inputEl = document.getElementById("modal-specifications-input");

    if (displayEl) displayEl.classList.remove("d-none");

    if (editorEl) {
        editorEl.classList.add("d-none");
        editorEl.classList.remove("show-editor");
    }

    if (btnEl) {
        btnEl.textContent = "Edit";
        btnEl.dataset.mode = "view";
        btnEl.style.pointerEvents = "auto";
    }

    if (inputEl) {
        inputEl.classList.remove("input-error");
    }
}

function openSpecificationsEditor() {
    const displayEl = document.getElementById("modal-specifications");
    const editorEl = document.getElementById("modal-specifications-editor");
    const inputEl = document.getElementById("modal-specifications-input");
    const btnEl = document.getElementById("specsEditBtn");

    if (!displayEl || !editorEl || !inputEl || !btnEl) return;

    const currentText = displayEl.textContent.trim();
    inputEl.value = currentText === "Not set" ? "" : currentText;

    displayEl.classList.add("d-none");
    editorEl.classList.remove("d-none");

    requestAnimationFrame(() => {
        editorEl.classList.add("show-editor");
        autoResizeTextarea(inputEl);
        updateSpecsCounter();
        inputEl.focus();
        inputEl.setSelectionRange(inputEl.value.length, inputEl.value.length);
    });

    btnEl.textContent = "Save";
    btnEl.dataset.mode = "edit";
}

async function saveSpecifications() {
    const displayEl = document.getElementById("modal-specifications");
    const editorEl = document.getElementById("modal-specifications-editor");
    const inputEl = document.getElementById("modal-specifications-input");
    const btnEl = document.getElementById("specsEditBtn");
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");

    if (!displayEl || !editorEl || !inputEl || !btnEl) return;

    if (!window.currentModalSerialNo) {
        Swal.fire({
            icon: "error",
            title: "Missing Item",
            text: "No serial number found for this item.",
            confirmButtonColor: "#d33"
        });
        return;
    }

    const value = inputEl.value.trim();

    if (!value) {
        inputEl.focus();
        inputEl.classList.add("input-error");

        Swal.fire({
            icon: "warning",
            title: "Missing Specifications",
            text: "Please enter the item specifications before saving.",
            confirmButtonColor: "#0d6efd"
        });

        setTimeout(() => inputEl.classList.remove("input-error"), 2000);
        return;
    }

    if (value.length < 5) {
        inputEl.focus();
        inputEl.classList.add("input-error");

        Swal.fire({
            icon: "info",
            title: "Too Short",
            text: "Specifications must be at least 5 characters.",
            confirmButtonColor: "#0d6efd"
        });

        setTimeout(() => inputEl.classList.remove("input-error"), 2000);
        return;
    }

    btnEl.textContent = "Saving...";
    btnEl.style.pointerEvents = "none";

    try {
        const response = await fetch("/inventory/update-specifications", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrf,
                "Accept": "application/json"
            },
            body: JSON.stringify({
                serial_no: window.currentModalSerialNo,
                specifications: value
            })
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || "Failed to save specifications.");
        }

        const savedValue = (data.specifications || "").trim();

        displayEl.textContent = savedValue || "Not set";
        displayEl.classList.toggle("text-muted", !savedValue);

        editorEl.classList.remove("show-editor");

        setTimeout(() => {
            editorEl.classList.add("d-none");
            displayEl.classList.remove("d-none");
        }, 200);

        btnEl.textContent = "Edit";
        btnEl.dataset.mode = "view";

        syncInventoryRows(window.currentModalSerialNo, {
            specification: savedValue
        });

        Swal.fire({
            icon: "success",
            title: "Saved!",
            text: "Specifications updated successfully.",
            timer: 1500,
            showConfirmButton: false
        });

    } catch (error) {
        Swal.fire({
            icon: "error",
            title: "Save Failed",
            text: error.message || "Failed to save specifications.",
            confirmButtonColor: "#d33"
        });

        btnEl.textContent = "Save";
    } finally {
        btnEl.style.pointerEvents = "auto";
    }
}

function toggleSpecificationsEdit() {
    const btnEl = document.getElementById("specsEditBtn");
    if (!btnEl) return;

    const mode = btnEl.dataset.mode || "view";

    if (mode === "edit") {
        saveSpecifications();
    } else {
        openSpecificationsEditor();
    }
}

/* =========================
   GENERIC SINGLE FIELD EDITOR
========================= */
const FIELD_CONFIG = {
    source_of_fund: {
        displayId: "modal-source-of-fund",
        editorId: "modal-source-of-fund-editor",
        inputId: "modal-source-of-fund-input",
        btnId: "sourceOfFundEditBtn",
        label: "Source of Fund",
        endpoint: "/inventory/update-source-of-fund",
        minLength: 2,
        type: "text"
    },
    classification: {
        displayId: "modal-classification",
        editorId: "modal-classification-editor",
        inputId: "modal-classification-input",
        btnId: "classificationEditBtn",
        label: "Classification",
        endpoint: "/inventory/update-classification",
        minLength: 2,
        type: "text"
    },
    unit_cost: {
        displayId: "modal-unit-cost",
        editorId: "modal-unit-cost-editor",
        inputId: "modal-unit-cost-input",
        btnId: "unitCostEditBtn",
        label: "Unit Cost",
        endpoint: "/inventory/update-unit-cost",
        minLength: 1,
        type: "number"
    }
};

function resetSingleFieldEditorState(fieldKey) {
    const cfg = FIELD_CONFIG[fieldKey];
    if (!cfg) return;

    const displayEl = document.getElementById(cfg.displayId);
    const editorEl = document.getElementById(cfg.editorId);
    const btnEl = document.getElementById(cfg.btnId);
    const inputEl = document.getElementById(cfg.inputId);

    if (displayEl) displayEl.classList.remove("d-none");

    if (editorEl) {
        editorEl.classList.add("d-none");
        editorEl.classList.remove("show-editor");
    }

    if (btnEl) {
        btnEl.textContent = "Edit";
        btnEl.dataset.mode = "view";
        btnEl.style.pointerEvents = "auto";
    }

    if (inputEl) inputEl.classList.remove("input-error");
}

function openSingleFieldEditor(fieldKey) {
    const cfg = FIELD_CONFIG[fieldKey];
    if (!cfg) return;

    const displayEl = document.getElementById(cfg.displayId);
    const editorEl = document.getElementById(cfg.editorId);
    const inputEl = document.getElementById(cfg.inputId);
    const btnEl = document.getElementById(cfg.btnId);

    if (!displayEl || !editorEl || !inputEl || !btnEl) return;

    let currentText = displayEl.textContent.trim();
    if (currentText === "Not set") currentText = "";

    if (fieldKey === "unit_cost") {
        currentText = currentText.replace(/₱|,/g, "").trim();
    }

    inputEl.value = currentText;

    displayEl.classList.add("d-none");
    editorEl.classList.remove("d-none");

    requestAnimationFrame(() => {
        editorEl.classList.add("show-editor");
        inputEl.focus();
        inputEl.setSelectionRange(inputEl.value.length, inputEl.value.length);
    });

    btnEl.textContent = "Save";
    btnEl.dataset.mode = "edit";
}

async function saveSingleField(fieldKey) {
    const cfg = FIELD_CONFIG[fieldKey];
    if (!cfg) return;

    const displayEl = document.getElementById(cfg.displayId);
    const editorEl = document.getElementById(cfg.editorId);
    const inputEl = document.getElementById(cfg.inputId);
    const btnEl = document.getElementById(cfg.btnId);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");

    if (!displayEl || !editorEl || !inputEl || !btnEl) return;

    if (!window.currentModalSerialNo) {
        Swal.fire({
            icon: "error",
            title: "Missing Item",
            text: "No serial number found for this item.",
            confirmButtonColor: "#d33"
        });
        return;
    }

    let rawValue = inputEl.value.trim();

    if (!rawValue) {
        inputEl.focus();
        inputEl.classList.add("input-error");

        Swal.fire({
            icon: "warning",
            title: `Missing ${cfg.label}`,
            text: `Please enter ${cfg.label.toLowerCase()} before saving.`,
            confirmButtonColor: "#0d6efd"
        });

        setTimeout(() => inputEl.classList.remove("input-error"), 2000);
        return;
    }

    if (cfg.type === "number") {
        const num = parseFloat(rawValue);

        if (isNaN(num) || num < 0) {
            inputEl.focus();
            inputEl.classList.add("input-error");

            Swal.fire({
                icon: "warning",
                title: "Invalid Unit Cost",
                text: "Unit cost must be a valid number greater than or equal to 0.",
                confirmButtonColor: "#0d6efd"
            });

            setTimeout(() => inputEl.classList.remove("input-error"), 2000);
            return;
        }

        rawValue = num.toFixed(2);
    } else {
        if (rawValue.length < cfg.minLength) {
            inputEl.focus();
            inputEl.classList.add("input-error");

            Swal.fire({
                icon: "info",
                title: "Too Short",
                text: `${cfg.label} must be at least ${cfg.minLength} characters.`,
                confirmButtonColor: "#0d6efd"
            });

            setTimeout(() => inputEl.classList.remove("input-error"), 2000);
            return;
        }
    }

    btnEl.textContent = "Saving...";
    btnEl.style.pointerEvents = "none";

    try {
        const response = await fetch(cfg.endpoint, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrf,
                "Accept": "application/json"
            },
            body: JSON.stringify({
                serial_no: window.currentModalSerialNo,
                value: rawValue
            })
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || `Failed to save ${cfg.label.toLowerCase()}.`);
        }

        const savedValue = data.value ?? rawValue;

        if (fieldKey === "unit_cost") {
            displayEl.textContent = `₱${Number(savedValue).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })}`;
        } else {
            displayEl.textContent = savedValue || "Not set";
        }

        displayEl.classList.toggle("text-muted", !savedValue);

        editorEl.classList.remove("show-editor");

        setTimeout(() => {
            editorEl.classList.add("d-none");
            displayEl.classList.remove("d-none");
        }, 200);

        btnEl.textContent = "Edit";
        btnEl.dataset.mode = "view";

        if (fieldKey === "unit_cost") {
            syncInventoryRows(window.currentModalSerialNo, {
                unit_cost: savedValue
            });
        } else {
            syncInventoryRows(window.currentModalSerialNo, {
                [fieldKey]: savedValue
            });
        }

        Swal.fire({
            icon: "success",
            title: "Saved!",
            text: `${cfg.label} updated successfully.`,
            timer: 1500,
            showConfirmButton: false
        });

    } catch (error) {
        Swal.fire({
            icon: "error",
            title: "Save Failed",
            text: error.message || `Failed to save ${cfg.label.toLowerCase()}.`,
            confirmButtonColor: "#d33"
        });

        btnEl.textContent = "Save";
    } finally {
        btnEl.style.pointerEvents = "auto";
    }
}

function toggleSingleFieldEdit(fieldKey) {
    const cfg = FIELD_CONFIG[fieldKey];
    if (!cfg) return;

    const btnEl = document.getElementById(cfg.btnId);
    if (!btnEl) return;

    const mode = btnEl.dataset.mode || "view";

    if (mode === "edit") {
        saveSingleField(fieldKey);
    } else {
        openSingleFieldEditor(fieldKey);
    }
}

/* =========================
   SYNC TABLE ROW DATA
========================= */
function syncInventoryRows(serialNo, updates = {}) {
    const rows = document.querySelectorAll("#inventoryTable tbody tr.inventory-row");
    let currentItem = null;

    rows.forEach((row) => {
        try {
            const raw = row.getAttribute("data-item");
            if (!raw) return;

            const rowItem = JSON.parse(raw);
            if (rowItem.serial_no === serialNo) {
                currentItem = rowItem;
            }
        } catch (e) {
            console.error("Failed reading row data-item:", e);
        }
    });

    if (!currentItem) return;

    rows.forEach((row) => {
        try {
            const raw = row.getAttribute("data-item");
            if (!raw) return;

            const rowItem = JSON.parse(raw);

            const sameGroup =
                (rowItem.item_name || "").trim() === (currentItem.item_name || "").trim() &&
                (rowItem.description || "").trim() === (currentItem.description || "").trim() &&
                String(rowItem.created_at || "").trim() === String(currentItem.created_at || "").trim();

            if (sameGroup) {
                Object.keys(updates).forEach((key) => {
                    rowItem[key] = updates[key];
                });

                row.setAttribute("data-item", JSON.stringify(rowItem));
            }
        } catch (e) {
            console.error("Failed updating row data-item:", e);
        }
    });
}

/* =========================
   EVENTS
========================= */
document.addEventListener("DOMContentLoaded", function () {
    const specsInput = document.getElementById("modal-specifications-input");
    if (specsInput) {
        specsInput.addEventListener("input", function () {
            autoResizeTextarea(this);
            updateSpecsCounter();
        });

        specsInput.addEventListener("keydown", function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === "Enter") {
                e.preventDefault();
                saveSpecifications();
            }
        });
    }

    ["source_of_fund", "classification", "unit_cost"].forEach((fieldKey) => {
        const cfg = FIELD_CONFIG[fieldKey];
        const input = document.getElementById(cfg.inputId);

        if (!input) return;

        input.addEventListener("keydown", function (e) {
            if (e.key === "Enter") {
                e.preventDefault();
                saveSingleField(fieldKey);
            }
        });
    });
});