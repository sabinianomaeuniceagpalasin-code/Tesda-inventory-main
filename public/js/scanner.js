const scannerModal = document.getElementById("scannerModal");
const scannerInput = document.getElementById("scannerInput");
const scannedList = document.getElementById("scanned-items-list");
const markReceivedBtn = document.getElementById("markReceivedBtn");
const addItemBtn = document.getElementById("addItemBtn");

if (addItemBtn) {
    addItemBtn.addEventListener("click", () => {
        scannerModal.classList.remove("hidden");
        scannerInput.focus();
    });
}

function closeScannerModal() {
    scannerModal.classList.add("hidden");
    scannerInput.value = "";
    scannedList.innerHTML = "";
}

if (scannerInput) {
    scannerInput.addEventListener("keydown", function (e) {
        if (e.key === "Enter") {
            e.preventDefault();
            const rawData = this.value.trim();
            this.value = "";

            if (!rawData) return;

            fetch(`/items/scan/${encodeURIComponent(rawData)}`)
                .then(res => res.json())
                .then(data => {
                    if (!data.success) return;

                    if (document.querySelector(`[data-serial="${data.item.serial_no}"]`)) return;

                    const itemRow = document.createElement("div");
                    itemRow.className = "scanned-item-entry";
                    itemRow.setAttribute("data-serial", data.item.serial_no);
                    itemRow.style.cssText = "padding: 12px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; background: #f0fff4; margin-bottom: 5px; border-radius: 4px; border-left: 5px solid #2ecc71;";
                    
                    itemRow.innerHTML = `
                        <span>
                            <b style="color: #2c3e50;">${data.item.item_name}</b><br>
                            <small style="color: #7f8c8d;">SN: ${data.item.serial_no} | Prop: ${data.item.property_no}</small>
                        </span>
                        <span style="color: #27ae60; font-weight: bold; font-size: 0.85em;">✓ ADDED</span>
                    `;
                    scannedList.prepend(itemRow);
                })
                .catch(err => console.error(err));
        }
    });
}

if (markReceivedBtn) {
    markReceivedBtn.addEventListener("click", () => {
        location.reload();
    });
}