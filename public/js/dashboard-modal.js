const dashboardConfigs = {
    inventory: {
        title: "List of All Items",
        headers: ["Item Name", "Classification", "Stock", "Location", "Status"],
        buttonText: "View Inventory Section",
        apiUrl: "/dashboard/get-total-items-and-equipment",
        targetSection: "inventory",
    },
    available: {
        title: "List of All Available Items",
        headers: ["Item name", "Classification", "Stock", "Location"],
        buttonText: "View Inventory Section",
        apiUrl: "/dashboard/get-available-items",
        targetSection: "inventory",
    },
    issued: {
        title: "List of Issued Items",
        headers: [
            "Item name",
            "Classification",
            "Issued to",
            "Date Issued",
            "Expected return",
        ],
        buttonText: "View Issued Item Section",
        apiUrl: "/dashboard/get-issued-items",
        targetSection: "issued",
    },
    repair: {
        title: "Under Maintenance List",
        headers: [
            "Item name",
            "Classification",
            "Date sent for Repair",
            "Repair Status",
            "Location",
        ],
        buttonText: "View Maintenance Section",
        apiUrl: "/dashboard/get-under-maintenance",
        targetSection: "reports",
    },
    lowstock: {
        title: "Low Stock Items",
        headers: [
            "Item name",
            "Classification",
            "Current Quantity",
            "Minimum Quantity",
            "Suggested Qty Order",
        ],
        buttonText: "View Inventory Section",
        apiUrl: "/dashboard/get-low-stock-items",
        targetSection: "inventory",
    },
    missing: {
        title: "Missing Items",
        headers: [
            "Item name",
            "Classification",
            "Last Known Location",
            "Date Reported Missing",
        ],
        buttonText: "View Inventory Section",
        apiUrl: "/dashboard/get-missing-items",
        targetSection: "inventory",
    },
};

/* ============================
       MODALS (Add Item & Forms)
    ============================ */
let html5QrCode = null;
const modals = {
    addItem: document.getElementById("addItemModal"),
    formType: document.getElementById("formTypeModal"),
    addForm: document.getElementById("addFormModal"),
};

document
    .getElementById("addItemBtn")
    .addEventListener("click", () => (modals.addItem.style.display = "flex"));
document
    .getElementById("closeModal")
    .addEventListener("click", () => (modals.addItem.style.display = "none"));

function openFormTypeModal() {
    modals.formType.style.display = "flex";
}
function closeFormTypeModal() {
    modals.formType.style.display = "none";
}
function openAddFormModal(type) {
    document.getElementById("form_type_input").value = type;
    document.getElementById("addFormTitle").textContent = `${type} - New Form`;
    closeFormTypeModal();
    loadAvailableSerials();
    modals.addForm.style.display = "flex";
}

function closeAddFormModal() {
    modals.addForm.style.display = "none";
    document.getElementById("addForm").reset();
    document.getElementById("studentSuggestion").innerHTML = "";
    document.getElementById("serialList").innerHTML = "";
    document.getElementById("refCheck").style.display = "none";
}

// Add Form buttons
document
    .getElementById("chooseIcs")
    .addEventListener("click", () => openAddFormModal("ICS"));
document
    .getElementById("choosePar")
    .addEventListener("click", () => openAddFormModal("PAR"));
document.querySelectorAll(".add-btn").forEach((el) =>
    el.addEventListener("click", (e) => {
        e.preventDefault();
        openFormTypeModal();
    }),
);

// Close modals if clicked outside
window.addEventListener("click", (e) => {
    if (e.target.classList.contains("modal-overlay"))
        e.target.style.display = "none";
});

// Open View Modal
function closeViewFormModal() {
    const modal = document.getElementById("viewFormModal");
    modal.style.display = "none";
    modal.querySelector(".modal-body").innerHTML = "";
}

// Attach click event to all "View" links in the form table
function closeViewFormModal() {
    const modal = document.getElementById("viewFormModal");
    modal.style.display = "none";
    modal.querySelector(".modal-body").innerHTML = "";
}

// Attach click event to all "View" links in the table
document.querySelectorAll("#form .form-table tbody tr td a").forEach((link) => {
    if (link.textContent.trim() === "View") {
        link.addEventListener("click", async function (e) {
            e.preventDefault();
            const row = this.closest("tr");
            const referenceNo = row.cells[1].textContent.trim();

            try {
                const res = await fetch(
                    `/issued/view/${encodeURIComponent(referenceNo)}`,
                );
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const data = await res.json();
                if (data.error) return alert(data.error);

                const grouped = {};
                data.details.forEach((d) => {
                    if (!grouped[d.property_no]) {
                        grouped[d.property_no] = {
                            property_no: d.property_no,
                            tool_name: d.tool_name,
                            quantity: 0,
                            unit_cost: 0,
                            total_cost: 0,
                            serials: [],
                        };
                    }
                    grouped[d.property_no].quantity += 1;
                    grouped[d.property_no].unit_cost = Number(d.unit_cost) || 0;
                    grouped[d.property_no].total_cost +=
                        Number(d.unit_cost) || 0;
                    grouped[d.property_no].serials.push(d.serial_no);
                });

                let html = `
                    <p><strong>Issued To:</strong> <u>${data.issued_to}</u></p>
                    <p><strong>Reference No.:</strong> <u>${data.reference_no}</u></p>
                    <table border="1" cellpadding="5" style="width:100%; margin-top:10px;">
                        <thead>
                            <tr>
                                <th>Property Number</th>
                                <th>Article/Property Name</th>
                                <th>Quantity</th>
                                <th>Unit Cost</th>
                                <th>Total Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                Object.values(grouped).forEach((item) => {
                    html += `<tr>
                        <td>${item.property_no}</td>
                        <td>${item.tool_name}</td>
                        <td>${item.quantity}</td>
                        <td>${item.unit_cost.toFixed(2)}</td>
                        <td>${item.total_cost.toFixed(2)}</td>
                    </tr>`;
                });
                html += `</tbody></table>`;

                html += `<h4 style="margin-top:15px;">Serial Numbers Issued</h4>
                    <table border="1" cellpadding="5" style="width:100%; margin-top:5px;">
                        <thead>
                            <tr><th>Property Number</th><th>Serial Number</th></tr>
                        </thead>
                        <tbody>
                `;
                data.details.forEach((d) => {
                    html += `<tr><td>${d.property_no}</td><td>${d.serial_no}</td></tr>`;
                });
                html += `</tbody></table>`;

                html += `<div style="margin-top:30px; font-size:14px; line-height:1.5;">
                    I hereby acknowledge receipt of the above-listed item(s) and accept full responsibility...
                </div>`;

                const modal = document.getElementById("viewFormModal");
                modal.dataset.formType = data.form_type; // Add this
                modal.querySelector(".modal-body").innerHTML = html;
                modal.style.display = "flex";
            } catch (err) {
                console.error(err);
                alert("Failed to load form details: " + err.message);
            }
        });
    }
});

// Close modal if clicked outside
window.addEventListener("click", (e) => {
    const modal = document.getElementById("viewFormModal");
    if (e.target === modal) closeViewFormModal();
});

document.addEventListener("DOMContentLoaded", function () {
    const modalElement = document.getElementById("inventoryModal");
    const inventoryModal = new bootstrap.Modal(modalElement);

    document.addEventListener("click", function (e) {
        const cell = e.target.closest(".serial-cell");
        if (!cell) return;

        const { serial, item, fund, classification, date, status } =
            cell.dataset;

        document.getElementById("modal-serial").textContent = serial;
        document.getElementById("modal-item").textContent = item;

        if (document.getElementById("modal-fund"))
            document.getElementById("modal-fund").textContent = fund;
        if (document.getElementById("modal-classification"))
            document.getElementById("modal-classification").textContent =
                classification;
        document.getElementById("modal-date").textContent = date;

        const statusEl = document.getElementById("modal-status");
        statusEl.textContent = status;
        statusEl.className = "detail-value";

        if (status === "Available") statusEl.classList.add("text-success");
        else if (status === "For Repair")
            statusEl.classList.add("text-warning");
        else if (status === "Unserviceable" || status === "Lost")
            statusEl.classList.add("text-danger");

        const qrImg = modalElement.querySelector(".qr-code");
        if (qrImg) {
            qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${serial}`;
        }

        inventoryModal.show();
    });
});

function openDynamicModal(type) {
    fetch(`/dashboard/summary/${type}`)
        .then((res) => {
            if (!res.ok) throw new Error("No data found");
            return res.json();
        })
        .then((data) => {
            document.getElementById("m-title").innerText = data.title;
            document.getElementById("m-summary").innerHTML = data.summary;
            document.getElementById("m-label").innerText = data.label;
            document.getElementById("m-list").innerHTML = data.list;

            const footer = document.getElementById("m-footer-info");
            if (data.footer) {
                footer.innerHTML = data.footer;
                footer.style.display = "block";
            } else {
                footer.style.display = "none";
            }

            document.getElementById("dynamicModal").style.display = "flex";
        })
        .catch((err) => alert(err.message));
}

function openViewSection(section) {
    document.getElementById("dynamicModal").style.display = "none";
    document.getElementById("dashboardTableModal").style.display = "none";

    const link = document.querySelector(`a[data-target="${section}"]`);

    if (link) {
        link.click();
        return;
    }

    document
        .querySelectorAll(".content-section")
        .forEach((s) => (s.style.display = "none"));

    const target = document.getElementById(section);
    if (target) {
        target.style.display = "block";
    }
}

function openDashboardModal(type) {
    console.log("clicked", type);

    const modal = document.getElementById("dashboardTableModal");

    if (typeof dashboardConfigs === "undefined") {
        console.warn("Dashboard configurations are still loading...");
        return;
    }

    const config = dashboardConfigs[type];

    if (!config) {
        console.error("Invalid dashboard type:", type);
        return;
    }

    console.log(config);

    document.getElementById("dt-title").innerText = config.title;

    document.getElementById("dt-thead").innerHTML = `
        <tr>${config.headers.map((h) => `<th>${h}</th>`).join("")}</tr>
    `;

    const footerBtn = document.querySelector(".btn-view-section");

    if (footerBtn) {
        footerBtn.innerText = config.buttonText;
        footerBtn.onclick = () => openViewSection(config.targetSection);
    }

    document.getElementById("dt-tbody").innerHTML = `
        <tr><td colspan="${config.headers.length}">Loading...</td></tr>
    `;

    fetch(config.apiUrl)
        .then((res) => res.json())
        .then((data) => {
            document.getElementById("dt-tbody").innerHTML = data.html;
        })
        .catch(() => {
            document.getElementById("dt-tbody").innerHTML = `
                <tr><td colspan="${config.headers.length}">Failed to load data</td></tr>
            `;
        });

    modal.style.display = "flex";
}

document
    .getElementById("manualEntryBtn")
    .addEventListener("click", function () {
        const scannerMsg = document.querySelector(".scanner-container");
        const entryForm = document.getElementById("addItemForm");

        if (entryForm.style.display === "none") {
            scannerMsg.style.display = "none";
            entryForm.style.display = "block";
            this.innerText = "Back to Scanner";
        } else {
            scannerMsg.style.display = "flex";
            entryForm.style.display = "none";
            this.innerText = "Manual Entry Mode";
        }
    });

function openScannerModal() {
    document.getElementById("addItemModal").style.display = "flex";
}

function showItemDetails(item) {
    const elItem = document.getElementById("modal-item");
    const elDisplay = document.getElementById("modal-item-display");
    const elSerial = document.getElementById("modal-serial");

    if (elItem) elItem.innerText = item.item_name || "---";
    if (elDisplay) elDisplay.innerText = item.item_name || "---";
    if (elSerial) elSerial.innerText = item.serial_no || "---";

    // 2. I-populate ang fund at classification
    const elFund = document.getElementById("modal-fund");
    if (elFund) elFund.innerText = item.source_of_fund || "---";

    // 3. Status logic
    const statusEl = document.getElementById("modal-status");
    if (statusEl) {
        statusEl.innerText = item.status;
        statusEl.className = "detail-value fw-bold";
        if (item.status === "Available") statusEl.classList.add("text-success");
        else if (item.status === "For Repair")
            statusEl.classList.add("text-warning");
        else statusEl.classList.add("text-danger");
    }

    const dateEl = document.getElementById("modal-date");
    if (dateEl && item.date_acquired) {
        const d = new Date(item.date_acquired);
        dateEl.innerText = d.toLocaleDateString("en-US", {
            year: "numeric",
            month: "long",
            day: "numeric",
        });
    }

    const qrImg = document.getElementById("modal-qr");
    if (qrImg) {
        qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${item.serial_no}`;
    }

    // 6. Tawagin ang Bootstrap Modal
    const modalEl = document.getElementById("inventoryModal");
    if (modalEl) {
        const myModal = new bootstrap.Modal(modalEl);
        myModal.show();
    }
}

// function updateStatus(newStatus) {
//   Swal.fire({
//     title: `Mark item as ${newStatus}?`,
//     text: "This will update the item's current condition.",
//     icon: "warning",
//     showCancelButton: true,
//     confirmButtonColor: "#737df2",
//     confirmButtonText: "Yes, update it!",
//   }).then((result) => {
//     if (result.isConfirmed) {
//       // Dito mo gagawin ang AJAX call para i-save sa database
//       console.log("Updating status to:", newStatus);

//       // I-update muna ang display sa modal
//       document.getElementById("modal-status").innerText = newStatus;

//       Swal.fire("Updated!", `Status is now ${newStatus}.`, "success");
//     }
//   });
// }

// Ilagay ito sa loob ng dashboard-modal.js
document.addEventListener("click", function (e) {
    // I-check kung ang clinick ay ang button na may class na 'view-btn'
    if (e.target && e.target.classList.contains("view-btn")) {
        console.log("Usage History button clicked!"); // Para makita mo sa Console (F12) kung gumagana
        showUsageHistory();
    }
});

function showUsageHistory() {
    // 1. Kunin ang IDs ng modal
    const historyModal = document.getElementById("usageHistoryModal");

    // 2. Siguraduhin na may data tayong makukuha sa main Item Detail modal
    const itemName = document.getElementById("modal-item")
        ? document.getElementById("modal-item").innerText
        : "---";
    const serialNo = document.getElementById("modal-serial")
        ? document.getElementById("modal-serial").innerText
        : "---";

    // 3. I-populate ang data sa Usage History Modal
    if (document.getElementById("history-item-name")) {
        document.getElementById("history-item-name").innerText = itemName;
    }
    if (document.getElementById("history-property-no")) {
        document.getElementById("history-property-no").innerText = serialNo;
    }

    // 4. Ipakita ang modal (Force display)
    if (historyModal) {
        historyModal.style.setProperty("display", "flex", "important");
        loadHistoryData(); // Ito yung function na naglalagay ng rows sa table
    } else {
        alert("Hindi mahanap ang usageHistoryModal sa HTML mo!");
    }
}

function closeUsageHistory() {
    const modal = document.getElementById("usageHistoryModal");
    if (modal) {
        modal.style.display = "none";
    }
}
