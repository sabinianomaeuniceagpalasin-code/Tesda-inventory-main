const scannerModal = document.getElementById("scannerModal");
const scannerInput = document.getElementById("scannerInput");
const scannedList = document.getElementById("scanned-items-list");

document.getElementById("addItemBtn").addEventListener("click", () => {
    scannerModal.classList.remove("hidden");
    scannerInput.focus();
});

function closeScannerModal() {
    scannerModal.classList.add("hidden");
    scannerInput.value = "";
}

// Handle input from hardware scanner
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

        // Send to backend to mark as received
        fetch(`/inventory/receive/${serialNo}`)
            .then((res) => res.json())
            .then((data) => {
                if (!data.exists) {
                    alert(`Item ${serialNo} not found in inventory.`);
                    return;
                }

                // Append to modal list
                const item = data.data;
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

document.getElementById("markReceivedBtn").addEventListener("click", () => {
    const entries = scannedList.querySelectorAll(".scanned-item-entry");
    const serialNumbers = Array.from(entries).map((entry) => {
        const parts = entry.textContent.split("|");
        return parts[1].trim(); // serialNo
    });

    if (serialNumbers.length === 0) {
        alert("No items to mark as received.");
        return;
    }

    // Send all serial numbers to backend
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
                alert("Items marked as received successfully!");
                closeScannerModal();
                scannedList.innerHTML = "";
            } else {
                alert("Error updating items.");
            }
        })
        .catch((err) => console.error(err));
});
