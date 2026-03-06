window.showItemDetails = function (item) {
    const elItem = document.getElementById("modal-item");
    const elDisplay = document.getElementById("modal-item-display");
    const elSerial = document.getElementById("modal-serial");
    const elFund = document.getElementById("modal-fund");
    const statusEl = document.getElementById("modal-status");
    const dateEl = document.getElementById("modal-date");
    const qrImg = document.getElementById("modal-qr");

    if (elItem) elItem.innerText = item.item_name || "---";
    if (elDisplay) elDisplay.innerText = item.item_name || "---";
    if (elSerial) elSerial.innerText = item.serial_no || "---";
    if (elFund) elFund.innerText = item.source_of_fund || "---";

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
    }

    if (qrImg) {
        qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(item.serial_no || "")}`;
    }

    const modalEl = document.getElementById("inventoryModal");
    if (modalEl && typeof bootstrap !== "undefined") {
        const myModal = new bootstrap.Modal(modalEl);
        myModal.show();
    } else if (modalEl) {
        modalEl.style.display = "flex";
    }
};

// ===============================
// OPEN INVENTORY EDIT MODAL
// ===============================
window.openInventoryEditModal = function (button) {
    console.log("button clicked!");

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

// ===============================
// CLOSE INVENTORY EDIT MODAL
// ===============================
window.closeInventoryEditModal = function () {
    const modal = document.getElementById("inventoryEditModal");
    if (modal) {
        modal.classList.remove("active");
    }
};

// ===============================
// CLOSE MODAL WHEN CLICKING OUTSIDE
// ===============================
window.onclick = function (event) {
    const modal = document.getElementById("inventoryEditModal");
    if (modal && event.target === modal) {
        modal.classList.remove("active");
    }
};

// ===============================
// DELETE ITEM
// ===============================
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

// ✅ Works even after table is replaced by AJAX
document.addEventListener("click", function (e) {
    const row = e.target.closest("#inventoryTable tbody tr.inventory-row");
    if (!row) return;

    // Prevent opening modal when clicking buttons
    if (e.target.closest("button")) return;

    const raw = row.getAttribute("data-item");
    if (!raw) return;

    try {
        const item = JSON.parse(raw);
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