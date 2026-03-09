// public/js/scanner.js
(() => {
  const scannerModal = document.getElementById("scannerModal");
  const scannerInput = document.getElementById("scannerInput");
  const scannedList = document.getElementById("scanned-items-list");
  const markReceivedBtn = document.getElementById("markReceivedBtn");
  const addItemBtn = document.getElementById("addItemBtn");

  if (!scannerModal || !scannerInput || !scannedList || !markReceivedBtn || !addItemBtn) {
    console.warn("[scanner.js] Missing required elements");
    return;
  }

  const closeBtn = scannerModal.querySelector(".scanner-modal__close");
  const cancelBtn = scannerModal.querySelector(".scanner-btn--cancel");

  const scannedSerials = new Set();

  let modalOpen = false;
  let scanBuffer = "";
  let scanTimer = null;
  let isProcessing = false;

  const getCSRFToken = () =>
    document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

  const escapeHtml = (str) => {
    return String(str || "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  };

  const focusScannerInput = () => {
    if (!modalOpen) return;
    setTimeout(() => {
      scannerInput.focus();
      scannerInput.select();
    }, 20);
  };

  const resetModal = () => {
    scannerInput.value = "";
    scannedList.innerHTML = "";
    scannedSerials.clear();
    markReceivedBtn.disabled = true;
    markReceivedBtn.textContent = "Mark as Received";
    scanBuffer = "";
    isProcessing = false;

    if (scanTimer) {
      clearTimeout(scanTimer);
      scanTimer = null;
    }
  };

  const openModal = () => {
    scannerModal.classList.remove("hidden");
    modalOpen = true;
    focusScannerInput();
  };

  const closeModal = () => {
    scannerModal.classList.add("hidden");
    modalOpen = false;
    resetModal();
  };

  const showInfoRow = (title, message, ok = true, autoRemoveMs = 0) => {
    const row = document.createElement("div");
    row.className = "scanned-item-entry";
    row.style.cssText =
      "padding:12px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;border-radius:6px;border-left:5px solid;" +
      (ok
        ? "background:#f0fff4;border-left-color:#2ecc71;"
        : "background:#fff5f5;border-left-color:#e74c3c;");

    row.innerHTML = `
      <span>
        <b style="color:${ok ? "#2c3e50" : "#c0392b"};">${escapeHtml(title)}</b><br>
        <small style="color:#7f8c8d;">${escapeHtml(message || "")}</small>
      </span>
      <span style="color:${ok ? "#27ae60" : "#c0392b"};font-weight:bold;font-size:0.85em;">
        ${ok ? "✓" : "✗"}
      </span>
    `;

    scannedList.prepend(row);

    if (autoRemoveMs > 0) {
      setTimeout(() => {
        row.style.transition = "opacity 250ms ease";
        row.style.opacity = "0";
        setTimeout(() => row.remove(), 260);
      }, autoRemoveMs);
    }

    return row;
  };

  const postJSON = async (url, body) => {
    try {
      const res = await fetch(url, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "Accept": "application/json",
          "X-CSRF-TOKEN": getCSRFToken(),
          "X-Requested-With": "XMLHttpRequest",
        },
        body: JSON.stringify(body),
      });

      const contentType = res.headers.get("content-type") || "";

      if (!contentType.includes("application/json")) {
        const text = await res.text().catch(() => "");
        return {
          ok: false,
          status: res.status,
          data: {
            success: false,
            code: "non_json",
            message: `Server returned non-JSON (${res.status}).`,
            debug: text.slice(0, 300),
          },
        };
      }

      const data = await res.json().catch(() => ({}));
      return { ok: res.ok, status: res.status, data };
    } catch (err) {
      return {
        ok: false,
        status: 0,
        data: {
          success: false,
          code: "fetch_error",
          message: err.message || "Network error.",
        },
      };
    }
  };

  const validateAndRenderScan = async (rawData) => {
    const clean = String(rawData || "").trim();
    if (!clean || isProcessing) return;

    isProcessing = true;

    try {
      const { ok, data, status } = await postJSON("/items/scan/validate", { input: clean });

      if (!ok || !data.success) {
        const code = data?.code || "blocked";

        if (code === "already_exists") {
          showInfoRow("ALREADY EXISTS", data.message || "This serial already exists.", false, 1500);
          return;
        }

        if (code === "rejected") {
          showInfoRow("REJECTED", data.message || "This serial was rejected.", false, 1500);
          return;
        }

        if (code === "no_request") {
          showInfoRow("NO REQUEST", data.message || "This serial is not in approval requests.", false, 1500);
          return;
        }

        showInfoRow("NOT ADDED", `${data.message || "Scan blocked."} (HTTP ${status})`, false, 1500);
        return;
      }

      const item = data.item || {};
      const serial = String(item.serial_no || "").trim();

      if (!serial) {
        showInfoRow("NOT ADDED", "Missing serial number from server response.", false, 1500);
        return;
      }

      if (scannedSerials.has(serial)) {
        showInfoRow("DUPLICATE", `Serial ${serial} already scanned in this batch.`, false, 1200);
        return;
      }

      scannedSerials.add(serial);

      showInfoRow(
        item.item_name || "READY",
        `SN: ${serial} | Prop: ${item.property_no || "—"}`,
        true,
        0
      );

      markReceivedBtn.disabled = scannedSerials.size === 0;
    } finally {
      scannerInput.value = "";
      scanBuffer = "";
      if (scanTimer) {
        clearTimeout(scanTimer);
        scanTimer = null;
      }
      isProcessing = false;
      focusScannerInput();
    }
  };

  addItemBtn.addEventListener("click", (e) => {
    e.preventDefault();
    openModal();
  });

  closeBtn?.addEventListener("click", (e) => {
    e.preventDefault();
    closeModal();
  });

  cancelBtn?.addEventListener("click", (e) => {
    e.preventDefault();
    closeModal();
  });

  scannerModal.addEventListener("click", (e) => {
    if (e.target === scannerModal) {
      closeModal();
    } else {
      focusScannerInput();
    }
  });

  document.addEventListener("click", () => {
    if (modalOpen) focusScannerInput();
  });

  window.addEventListener("focus", () => {
    if (modalOpen) focusScannerInput();
  });

  // Only ONE scan handler: global capture
  document.addEventListener("keydown", async (e) => {
    if (!modalOpen) return;

    if (e.key === "Escape") {
      e.preventDefault();
      closeModal();
      return;
    }

    const active = document.activeElement;
    const typingElsewhere =
      active &&
      active !== scannerInput &&
      (active.tagName === "TEXTAREA" || active.isContentEditable);

    if (typingElsewhere) return;

    if (e.key === "Shift" || e.key === "Control" || e.key === "Alt" || e.key === "Meta" || e.key === "Tab") {
      return;
    }

    if (e.key === "Enter") {
      e.preventDefault();
      const raw = scanBuffer.trim() || scannerInput.value.trim();
      await validateAndRenderScan(raw);
      return;
    }

    if (e.key === "Backspace") {
      e.preventDefault();
      scanBuffer = scanBuffer.slice(0, -1);
      scannerInput.value = scanBuffer;
      return;
    }

    if (e.key.length === 1) {
      scanBuffer += e.key;
      scannerInput.value = scanBuffer;

      if (scanTimer) clearTimeout(scanTimer);
      scanTimer = setTimeout(() => {
        scanBuffer = "";
        scannerInput.value = "";
      }, 250);
    }
  });

  markReceivedBtn.disabled = true;

  markReceivedBtn.addEventListener("click", async (e) => {
    e.preventDefault();

    if (scannedSerials.size === 0) return;

    markReceivedBtn.disabled = true;
    markReceivedBtn.textContent = "Processing...";

    const serials = Array.from(scannedSerials);
    const { ok, data, status } = await postJSON("/items/receive-batch", { serials });

    if (!ok || !data.success) {
      showInfoRow("FAILED", `${data.message || "Failed to mark received."} (HTTP ${status})`, false, 1800);
      markReceivedBtn.disabled = false;
      markReceivedBtn.textContent = "Mark as Received";
      focusScannerInput();
      return;
    }

    closeModal();
    window.location.reload();
  });

  console.log("[scanner.js] Loaded OK");
})();