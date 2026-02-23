const scannerModal = document.getElementById("scannerModal");
const scannerInput = document.getElementById("scannerInput");
const scannedList = document.getElementById("scanned-items-list");
const markReceivedBtn = document.getElementById("markReceivedBtn");

const csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute("content");

document.getElementById("addItemBtn").addEventListener("click", () => {
    scannerModal.classList.remove("hidden");
    scannerInput.focus();
});

function closeScannerModal() {
    scannerModal.classList.add("hidden");
    scannerInput.value = "";
    scannedList.innerHTML = "";
}

scannerInput.addEventListener("keydown", function (e) {
    if (e.key === "Enter") {
        e.preventDefault(); // Prevent form submission if inside a form

        const rawData = this.value.trim();
        this.value = "";

        if (!rawData) return;

        let itemName = "Unknown Item";
        let serialNo = rawData;

        // If the QR contains the pipe symbol, split it. Otherwise, use rawData as Serial
        if (rawData.includes("|")) {
            const parts = rawData.split("|").map((s) => s.trim());
            itemName = parts[0];
            serialNo = parts[1];
        }

        fetch(`/inventory/scan/${encodeURIComponent(serialNo)}`)
            .then((res) => res.json())
            .then((data) => {
                if (!data.success) {
                    alert(
                        `Serial Number: ${serialNo} not found in inventory database.`,
                    );
                    return;
                }

                const item = data.item;

                // Prevent duplicate scanning in the same session
                const existing = Array.from(
                    scannedList.querySelectorAll(".serial-val"),
                ).find((el) => el.textContent === serialNo);
                if (existing) return;

                const row = document.createElement("div");
                row.className = "scanned-item-entry";
                row.style.padding = "8px";
                row.style.borderBottom = "1px solid #eee";
                row.innerHTML = `
                    <strong>${item.item_name || itemName}</strong> | 
                    INV: <span class="serial-val">${serialNo}</span> | 
                    Status: <span style="color:blue">${item.status}</span>
                `;
                scannedList.appendChild(row);
            })
            .catch((err) => {
                console.error("Fetch Error:", err);
                alert("Database connection error.");
            });
    }
});

markReceivedBtn.addEventListener("click", () => {
    const entries = scannedList.querySelectorAll(".scanned-item-entry");
    const serialNumbers = Array.from(entries).map((entry) => {
        return entry.querySelector(".serial-val").textContent.trim();
    });

    if (serialNumbers.length === 0) {
        alert("Please scan at least one item first.");
        return;
    }

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
                alert("Items successfully marked as Received!");
                closeScannerModal();
                location.reload(); // Refresh to show updated inventory status
            } else {
                alert("Update failed: " + (data.message || "Unknown error"));
            }
        })
        .catch((err) => console.error("Batch Error:", err));
});
