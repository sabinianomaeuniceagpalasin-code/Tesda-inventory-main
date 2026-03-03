// public/js/inventory-settings-print.js

document.addEventListener("DOMContentLoaded", function () {
  const modalEl = document.getElementById("printPreviewModal");
  if (!modalEl) return;

  const modal = new bootstrap.Modal(modalEl);

  document.querySelectorAll(".openPrintModal").forEach((button) => {
    button.addEventListener("click", function () {
      const itemName = (this.dataset.item || "").trim();
      const description = (this.dataset.description || "").trim();
      const serialString = (this.dataset.serials || "").trim();
      const type = (this.dataset.type || "qr").trim().toLowerCase(); // "qr" | "barcode"

      const serials = serialString
        .split(",")
        .map((s) => s.trim())
        .filter(Boolean);

      const container = document.getElementById("qrContainer");
      if (!container) return;

      container.innerHTML = "";
      container.setAttribute("data-type", type);

      serials.forEach((serial) => {
        const box = document.createElement("div");
        box.className = "qr-box";

        const codeDiv = document.createElement("div");
        box.appendChild(codeDiv);

        if (type === "qr") {
          // QR image only
          new QRCode(codeDiv, {
            text: serial,
            width: 70,
            height: 70,
          });
        } else {
          // BARCODE image only (NO text), then we print our own label below
          const wrap = document.createElement("div");
          wrap.className = "barcode-wrap";

          const img = document.createElement("img");
          img.alt = `Barcode ${serial}`;
          img.src = `https://barcode.tec-it.com/barcode.ashx?data=${encodeURIComponent(
            serial
          )}&code=Code128&translate-esc=false&multiplebarcodes=false&quiet=0&showtext=0`;

          // Make the image slightly taller, wrap will crop any extra bottom text/space
          img.style.width = "120px";
          img.style.height = "55px";

          wrap.appendChild(img);
          codeDiv.appendChild(wrap);
        }

        // Label layout for BOTH:
        // item name
        // serial number
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

      modal.show();
    });
  });
});

function printPreview() {
  const container = document.getElementById("qrContainer");
  if (!container) return;

  const type = (container.getAttribute("data-type") || "qr").toLowerCase();
  const printContents = container.innerHTML;

  const printWindow = window.open("", "", "width=900,height=1200");
  if (!printWindow) return;

  // Grid layout
  let gridColumns;
  let boxWidth;

  if (type === "barcode") {
    gridColumns = "repeat(5, 130px)"; // 5 per row
    boxWidth = "130px";
  } else {
    gridColumns = "repeat(7, 90px)"; // 7 per row
    boxWidth = "90px";
  }

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

        .qr-desc {
          font-size: 8px;
          margin-top: 1px;
          line-height: 1.1;
        }

        .qr-container {
          display: grid;
          grid-template-columns: ${gridColumns};
          justify-content: center;
          gap: 8px;
        }

        .qr-box {
          display: flex;
          flex-direction: column;
          align-items: center;
          text-align: center;
          width: ${boxWidth};
        }

        /* QR canvases */
        .qr-box canvas {
          width: 70px !important;
          height: 70px !important;
        }

        /* Barcode crop wrapper: guarantees NO serial printed inside the image area */
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
          text-align: center;
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

/** Prevent breaking HTML if itemName/serial contains special chars */
function escapeHtml(str) {
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}