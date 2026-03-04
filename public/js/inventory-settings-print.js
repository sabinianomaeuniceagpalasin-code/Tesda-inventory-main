// public/js/inventory-settings-print.js

document.addEventListener("DOMContentLoaded", function () {
  const modalEl = document.getElementById("printPreviewModal");
  if (!modalEl) return;

  const modal = new bootstrap.Modal(modalEl);
  const container = document.getElementById("qrContainer");

  if (!container) return;

  // ===============================
  // OPEN BATCH PRINT PREVIEW
  // ===============================
  document.addEventListener("click", function (e) {
    const btn = e.target.closest(".openBatchPrintModal");
    if (!btn) return;

    const batchId = btn.dataset.batch;
    const scriptTag = document.getElementById(`batch-data-${batchId}`);

    if (!scriptTag) {
      console.error("Batch data not found");
      return;
    }

    let rows;

    try {
      rows = JSON.parse(scriptTag.textContent);
    } catch (err) {
      console.error("Invalid batch JSON");
      return;
    }

    container.innerHTML = "";

    rows.forEach((row) => {
      const itemName = (row.item_name || "").trim();
      const description = (row.description || "").trim();
      const type = (row.request_type || "qr").trim().toLowerCase();

      const serials = String(row.serial_number || "")
        .split(",")
        .map((s) => s.trim())
        .filter(Boolean);

      serials.forEach((serial) => {
        const box = document.createElement("div");
        box.className = "qr-box";

        const codeDiv = document.createElement("div");
        box.appendChild(codeDiv);

        // ===============================
        // QR CODE
        // ===============================
        if (type === "qr") {
          new QRCode(codeDiv, {
            text: serial,
            width: 70,
            height: 70,
          });
        }

        // ===============================
        // BARCODE
        // ===============================
        else {
          const wrap = document.createElement("div");
          wrap.className = "barcode-wrap";

          const img = document.createElement("img");
          img.alt = `Barcode ${serial}`;
          img.src = `https://barcode.tec-it.com/barcode.ashx?data=${encodeURIComponent(
            serial
          )}&code=Code128&translate-esc=false&multiplebarcodes=false&quiet=0&showtext=0`;

          img.style.width = "120px";
          img.style.height = "55px";

          wrap.appendChild(img);
          codeDiv.appendChild(wrap);
        }

        // ===============================
        // LABEL
        // ===============================
        const label = document.createElement("div");
        label.className = "qr-title";

        label.innerHTML = `
          <div><strong>${escapeHtml(itemName)}</strong></div>
          <div class="qr-desc">${escapeHtml(description)}</div>
          <div class="qr-serial">${escapeHtml(serial)}</div>
        `;

        box.appendChild(label);
        container.appendChild(box);
      });
    });

    modal.show();
  });
});


// ===============================
// PRINT FUNCTION
// ===============================
function printPreview() {
  const container = document.getElementById("qrContainer");
  if (!container) return;

  const printContents = container.innerHTML;

  const printWindow = window.open("", "", "width=900,height=1200");
  if (!printWindow) return;

  printWindow.document.write(`
    <html>
    <head>
      <title>Print Codes</title>
      <style>

        @media print {
          body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }

        body {
          margin: 0;
          padding: 20px;
          font-family: Arial, sans-serif;
        }

        .qr-container {
          display: grid;
          grid-template-columns: repeat(6, 120px);
          justify-content: center;
          gap: 15px;
        }

        .qr-box {
          display: flex;
          flex-direction: column;
          align-items: center;
          text-align: center;
          width: 120px;
        }

        .qr-box canvas {
          width: 70px !important;
          height: 70px !important;
        }

        .barcode-wrap {
          width: 120px;
          height: 40px;
          overflow: hidden;
        }

        .qr-box img {
          display: block;
          margin: 0 auto;
        }

        .qr-title {
        font-size: 9px;
        margin-top: 4px;
        line-height: 1.2;
      }

      .qr-desc {
        font-size: 8px;
      }

        .qr-serial {
          font-size: 9px;
        }

      </style>
    </head>
    <body>

      <div class="qr-container">
        ${printContents}
      </div>

    </body>
    </html>
  `);

  printWindow.document.close();
  printWindow.focus();

  setTimeout(() => {
    printWindow.print();
    printWindow.close();
  }, 500);
}


// ===============================
// ESCAPE HTML
// ===============================
function escapeHtml(str) {
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}