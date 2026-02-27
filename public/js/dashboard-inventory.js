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
        statusEl.innerText = item.status;
        statusEl.className = "detail-value fw-bold";
        if (item.status === "Available") statusEl.classList.add("text-success");
        else if (item.status === "For Repair") statusEl.classList.add("text-warning");
        else statusEl.classList.add("text-danger");
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
        qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${item.serial_no}`;
    }

    const modalEl = document.getElementById("inventoryModal");
    if (modalEl && typeof bootstrap !== "undefined") {
        const myModal = new bootstrap.Modal(modalEl);
        myModal.show();
    } else {
        modalEl.style.display = "flex";
    }
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

    // Kinukuha ang details mula sa modal ng inventory para ilagay sa history modal
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
}