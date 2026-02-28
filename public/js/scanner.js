// public/js/scanner.js
// Inventory scanner modal
// POST /items/scan/validate
// POST /items/receive-batch

(() => {
  const scannerModal = document.getElementById("scannerModal");
  const scannerInput = document.getElementById("scannerInput");
  const scannedList = document.getElementById("scanned-items-list");
  const markReceivedBtn = document.getElementById("markReceivedBtn");
  const addItemBtn = document.getElementById("addItemBtn");

  // Must exist
  if (!scannerModal || !scannerInput || !scannedList || !markReceivedBtn || !addItemBtn) {
    console.warn("[scanner.js] Missing elements:", {
      scannerModal: !!scannerModal,
      scannerInput: !!scannerInput,
      scannedList: !!scannedList,
      markReceivedBtn: !!markReceivedBtn,
      addItemBtn: !!addItemBtn,
    });
    return;
  }

  const scannedSerials = new Set();

  const getCSRFToken = () =>
    document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

  const closeBtn = scannerModal.querySelector(".scanner-modal__close");
  const cancelBtn = scannerModal.querySelector(".scanner-btn--cancel");

  // ✅ show row + optionally auto remove after X ms
  const showInfoRow = (title, message, ok = true, autoRemoveMs = 0) => {
    const row = document.createElement("div");
    row.className = "scanned-item-entry";
    row.style.cssText =
      "padding:12px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;margin-bottom:5px;border-radius:4px;border-left:5px solid;"
      + (ok
          ? "background:#f0fff4;border-left-color:#2ecc71;"
          : "background:#fff5f5;border-left-color:#e74c3c;");

    row.innerHTML = `
      <span>
        <b style="color:${ok ? "#2c3e50" : "#c0392b"};">${title}</b><br>
        <small style="color:#7f8c8d;">${message || ""}</small>
      </span>
      <span style="color:${ok ? "#27ae60" : "#c0392b"};font-weight:bold;font-size:0.85em;">
        ${ok ? "✓" : "✗"}
      </span>
    `;

    scannedList.prepend(row);

    // ✅ Auto remove after X ms (nice fade)
    if (autoRemoveMs > 0) {
      setTimeout(() => {
        row.style.transition = "opacity 250ms ease";
        row.style.opacity = "0";
        setTimeout(() => row.remove(), 260);
      }, autoRemoveMs);
    }

    return row;
  };

  const resetModal = () => {
    scannerInput.value = "";
    scannedList.innerHTML = "";
    scannedSerials.clear();
    markReceivedBtn.disabled = true;
    markReceivedBtn.textContent = "Mark as Received";
  };

  const openModal = () => {
    scannerModal.classList.remove("hidden");
    setTimeout(() => scannerInput.focus(), 0);
  };

  const closeModal = () => {
    scannerModal.classList.add("hidden");
    resetModal();
  };

  // OPEN
  addItemBtn.addEventListener("click", (e) => {
    e.preventDefault();
    openModal();
  });

  // CLOSE
  closeBtn?.addEventListener("click", (e) => {
    e.preventDefault();
    closeModal();
  });

  cancelBtn?.addEventListener("click", (e) => {
    e.preventDefault();
    closeModal();
  });

  // click outside closes
  scannerModal.addEventListener("click", (e) => {
    if (e.target === scannerModal) closeModal();
  });

  const postJSON = async (url, body) => {
    const res = await fetch(url, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json",
        "X-CSRF-TOKEN": getCSRFToken(),
      },
      body: JSON.stringify(body),
    });

    const contentType = res.headers.get("content-type") || "";

    if (!contentType.includes("application/json")) {
      const text = await res.text().catch(() => "");
      return {
        ok: false,
        data: {
          success: false,
          code: "non_json",
          message: `Server returned non-JSON (${res.status}). Likely 419 CSRF or redirected to login.`,
          _debug: text.slice(0, 200),
        },
        status: res.status,
      };
    }

    const data = await res.json().catch(() => ({}));
    return { ok: res.ok, data, status: res.status };
  };

  // SCAN (validate only)
  scannerInput.addEventListener("keydown", async (e) => {
    if (!(e.key === "Enter" || e.code === "Enter" || e.code === "NumpadEnter")) return;

    e.preventDefault();

    const rawData = scannerInput.value.trim();
    scannerInput.value = "";
    if (!rawData) return;

    const { ok, data, status } = await postJSON("/items/scan/validate", { input: rawData });

    // ✅ blocked cases: auto-remove after 3 seconds
    if (!ok || !data.success) {
      const code = data?.code || "blocked";

      if (code === "already_exists") {
        showInfoRow("ALREADY EXISTS", data.message || "This serial already exists.", false, 1000);
        return;
      }

      if (code === "rejected") {
        showInfoRow("REJECTED", data.message || "This serial was rejected.", false, 1000);
        return;
      }

      showInfoRow("NOT ADDED", `${data.message || "Scan blocked."} (HTTP ${status})`, false, 1000);
      return;
    }

    const item = data.item || {};
    const serial = item.serial_no;

    if (!serial) {
      showInfoRow("NOT ADDED", `Missing serial_no from server response (HTTP ${status})`, false, 1000);
      return;
    }

    // prevent duplicates (READY items stay)
    if (scannedSerials.has(serial)) return;

    scannedSerials.add(serial);
    showInfoRow(
      item.item_name || "READY",
      `SN: ${serial} | Prop: ${item.property_no || "—"}`,
      true,
      0 // ✅ do not auto-remove READY rows
    );

    markReceivedBtn.disabled = scannedSerials.size === 0;
  });

  // COMMIT
  markReceivedBtn.disabled = true;

  markReceivedBtn.addEventListener("click", async (e) => {
    e.preventDefault();

    if (scannedSerials.size === 0) return;

    markReceivedBtn.disabled = true;
    markReceivedBtn.textContent = "Processing...";

    const serials = Array.from(scannedSerials);
    const { ok, data, status } = await postJSON("/items/receive-batch", { serials });

    if (!ok || !data.success) {
      showInfoRow("FAILED", `${data.message || "Failed to mark received."} (HTTP ${status})`, false, 1000);
      markReceivedBtn.disabled = false;
      markReceivedBtn.textContent = "Mark as Received";
      return;
    }

    closeModal();
    location.reload();
  });

  console.log("[scanner.js] Loaded OK");
})();