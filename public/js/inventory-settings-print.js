document.addEventListener("DOMContentLoaded", function () {
  const modalEl = document.getElementById("printPreviewModal");
  if (!modalEl) return;

  const modal = new bootstrap.Modal(modalEl);
  const container = document.getElementById("qrContainer");

  if (!container) return;

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
        codeDiv.className = "code-holder";
        box.appendChild(codeDiv);

        // QR CODE
        if (type === "qr") {
          new QRCode(codeDiv, {
            text: serial,
            width: 70,
            height: 70,
          });
        }

        // BARCODE
        else {
          const wrap = document.createElement("div");
          wrap.className = "barcode-wrap";

          const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
          svg.classList.add("barcode-svg");
          wrap.appendChild(svg);

          codeDiv.appendChild(wrap);

          JsBarcode(svg, serial, {
            format: "CODE128",
            width: 1.4,
            height: 38,
            displayValue: false, // ✅ removes duplicate serial text
            margin: 0
          });
        }

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
        @page {
          size: A4 portrait;
          margin: 10mm;
        }

        @media print {
          body {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
          }
        }

        * {
          box-sizing: border-box;
        }

        body {
          margin: 0;
          padding: 10px;
          font-family: Arial, sans-serif;
        }

        .qr-container {
          display: grid;
          grid-template-columns: repeat(6, 105px);
          gap: 10px;
          justify-content: center;
          width: 100%;
        }

        .qr-box {
          width: 105px;
          display: flex;
          flex-direction: column;
          align-items: center;
          text-align: center;
          page-break-inside: avoid;
          break-inside: avoid;
        }

        .qr-box canvas {
          width: 68px !important;
          height: 68px !important;
        }

        .barcode-wrap {
          width: 105px;
          height: 42px;
          display: flex;
          justify-content: center;
          align-items: center;
          overflow: hidden;
        }

        .barcode-svg {
          width: 105px;
          height: 42px;
          display: block;
        }

        .qr-title {
          font-size: 8px;
          margin-top: 4px;
          line-height: 1.15;
          width: 100%;
          word-wrap: break-word;
          overflow-wrap: break-word;
        }

        .qr-desc {
          font-size: 7px;
        }

        .qr-serial {
          font-size: 8px;
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

function escapeHtml(str) {
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}