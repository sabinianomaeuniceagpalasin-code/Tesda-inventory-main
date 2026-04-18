window.currentSerialNo = null;

document.addEventListener("click", function (e) {
    const row = e.target.closest(".inventory-row");
    if (row) {
        const item = JSON.parse(row.dataset.item);
        window.currentSerialNo = item.serial_no;
    }
});

document.addEventListener("DOMContentLoaded", function () {
    const popover = document.createElement("div");
    popover.classList.add("history-popover");
    popover.innerHTML = `<p class="loading-text">Loading...</p><div class="history-content"></div>`;
    document.body.appendChild(popover);

    document.querySelectorAll(".serial-cell").forEach((cell) => {
        let timer;

        cell.addEventListener("mouseenter", function () {
            const serial = this.dataset.serial;
            const content = popover.querySelector(".history-content");
            const loading = popover.querySelector(".loading-text");

            content.innerHTML = "";
            loading.style.display = "block";

            const rect = this.getBoundingClientRect();
            popover.style.top = `${window.scrollY + rect.bottom + 5}px`;
            popover.style.left = `${window.scrollX + rect.left}px`;
            popover.style.display = "block";

            fetch(`/maintenance/history/${serial}`)
                .then((res) => res.json())
                .then((data) => {
                    loading.style.display = "none";

                    if (data.error) {
                        content.innerHTML = `<p style="color:red">${data.error}</p>`;
                        return;
                    }

                    let html = "<strong>Maintenance History:</strong>";
                    if (data.maintenance.length) {
                        data.maintenance.forEach((m) => {
                            html += `<p>${m.date_reported}: ${m.issue_type} (Status: ${m.status || "N/A"})</p>`;
                        });
                    } else {
                        html += "<p>No maintenance records.</p>";
                    }

                    html += "<strong>Damage History:</strong>";
                    if (data.damage.length) {
                        data.damage.forEach((d) => {
                            html += `<p>${d.reported_at}: ${d.damage_type || "N/A"}</p>`;
                        });
                    } else {
                        html += "<p>No damage records.</p>";
                    }

                    content.innerHTML = html;
                })
                .catch((err) => {
                    loading.style.display = "none";
                    content.innerHTML = `<p style="color:red">Failed to load history.</p>`;
                    console.error(err);
                });
        });

        cell.addEventListener("mouseleave", function () {
            timer = setTimeout(() => {
                popover.style.display = "none";
            }, 200);
        });

        popover.addEventListener("mouseenter", function () {
            clearTimeout(timer);
        });

        popover.addEventListener("mouseleave", function () {
            popover.style.display = "none";
        });
    });
});

// function showUsageHistory(serialNo) {
//     const resolvedSerial = serialNo || window.currentSerialNo;

//     if (!resolvedSerial) {
//         console.warn("No serial number provided to showUsageHistory");
//         return;
//     }

//     serialNo = resolvedSerial;

//     const modal = document.getElementById("usageHistoryModal");
//     modal.style.display = "flex";

//     document.getElementById("history-item-name").textContent = "Loading...";
//     document.getElementById("history-property-no").textContent = "...";
//     document.getElementById("usage-history-body").innerHTML =
//         '<tr><td colspan="7" style="text-align:center;">Loading...</td></tr>';

//     fetch(`/dashboard/item-usage-history/${serialNo}`)
//         .then((res) => res.json())
//         .then((data) => {
//             if (!data.success) {
//                 document.getElementById("usage-history-body").innerHTML =
//                     '<tr><td colspan="7" style="text-align:center;">No history found.</td></tr>';
//                 return;
//             }

//             document.getElementById("history-item-name").textContent = data.item_name;
//             document.getElementById("history-property-no").textContent = data.property_no;

//             if (!data.history.length) {
//                 document.getElementById("usage-history-body").innerHTML =
//                     '<tr><td colspan="7" style="text-align:center;">No usage history for this item.</td></tr>';
//                 return;
//             }

//             let rows = "";
//             data.history.forEach((h) => {
//                 const issuedPeriod = `${h.issued_date} → ${h.actual_return_date !== "-" ? h.actual_return_date : h.return_date}`;
//                 rows += `
//                     <tr>
//                         <td>${issuedPeriod}</td>
//                         <td>${h.issued_to}</td>
//                         <td>${h.purpose ?? "-"}</td>
//                         <td>${h.issued_by}</td>
//                         <td>${h.return_status}</td>
//                         <td>${h.condition_after_use ?? "-"}</td>
//                         <td>${h.remarks ?? "-"}</td>
//                     </tr>
//                 `;
//             });

//             document.getElementById("usage-history-body").innerHTML = rows;
//         })
//         .catch((err) => {
//             console.error("Usage history fetch error:", err);
//             document.getElementById("usage-history-body").innerHTML =
//                 '<tr><td colspan="7" style="text-align:center; color:red;">Failed to load history.</td></tr>';
//         });
// }

// function closeUsageHistory() {
//     document.getElementById("usageHistoryModal").style.display = "none";
// }
document.addEventListener("click", function (e) {
    const row = e.target.closest(".inventory-row");
    if (row) {
        const item = JSON.parse(row.dataset.item);
        window.currentSerialNo = item.serial_no;
        window.currentModalSerialNo = item.serial_no; // ✅ idagdag ito
    }
});