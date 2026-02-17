// DOM elements
const scannerModal = document.getElementById("scannerModal");
const scannerInput = document.getElementById("scannerInput");
const scannedList = document.getElementById("scanned-items-list");
const markReceivedBtn = document.getElementById("markReceivedBtn");

// CSRF token for Laravel POST requests
const csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    .getAttribute("content");

// Open scanner modal
document.getElementById("addItemBtn").addEventListener("click", () => {
    scannerModal.classList.remove("hidden");
    scannerInput.focus();
});

// Close scanner modal
function closeScannerModal() {
    scannerModal.classList.add("hidden");
    scannerInput.value = "";
    scannedList.innerHTML = "";
}

// Handle hardware scanner input (Enter key)
scannerInput.addEventListener("keydown", function (e) {
    if (e.key === "Enter") {
        const scannedData = this.value.trim(); // expects "ItemName | SerialNo"
        this.value = ""; // clear input

        if (!scannedData) return;

        const [itemName, serialNo] = scannedData
            .split("|")
            .map((s) => s.trim());

        if (!serialNo) {
            alert("Invalid QR code format. Use ItemName | SerialNo");
            return;
        }

        // Check item existence in backend
        fetch(`/inventory/scan/${serialNo}`)
            .then((res) => res.json())
            .then((data) => {
                if (!data.success) {
                    alert(`Item ${serialNo} not found in inventory.`);
                    return;
                }

                const item = data.item;

                // Append to modal list
                const html = `
                    <div class="scanned-item-entry" style="margin-bottom: 10px;">
                        <strong>${itemName}</strong> | INV: ${serialNo} | Status: ${item.status}
                    </div>
                `;
                scannedList.innerHTML += html;
            })
            .catch((err) => console.error(err));
    }
});

// Mark scanned items as received
markReceivedBtn.addEventListener("click", () => {
    const entries = scannedList.querySelectorAll(".scanned-item-entry");
    const serialNumbers = Array.from(entries).map((entry) => {
        const parts = entry.textContent.split("|");
        return parts[1].replace("INV:", "").trim(); // get serialNo
    });

    if (serialNumbers.length === 0) {
        alert("No items to mark as received.");
        return;
    }

    // Send batch to backend
    fetch("/inventory/receive-batch", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrfToken,
        },
        body: JSON.stringify({ serial_numbers: serialNumbers }),
    })
        .then((res) => res.json())
        .then((data) => {
            if (data.success) {
                // Close scanner modal
                closeScannerModal();

                // Store received items temporarily for QR generator
                const receivedItems = data.received_items; // should return array from backend

                // Open QR generator section
                const qrLink = document.querySelector(
                    'a[data-target="Generate"]',
                );
                if (qrLink) qrLink.click();

                // Prefill QR generator with received items
                if (typeof prefillQRCodeList === "function") {
                    prefillQRCodeList(receivedItems);
                }
            } else {
                alert("Error updating items.");
            }
        })
        .catch((err) => console.error(err));
});
