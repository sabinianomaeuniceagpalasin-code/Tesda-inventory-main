async function loadAvailableSerialsForModal() {
    const tbody = document.getElementById("serialItemsBody");
    const typeInput = document.getElementById("form_type_input");
    const searchInput = document.getElementById("serialSearchFilter");

    if (!tbody) return;
    if (searchInput) searchInput.value = "";

    const formType = typeInput ? typeInput.value : 'ICS';
    tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;">Fetching items...</td></tr>';

    try {
        const response = await fetch(`/issued/available-serials?form_type=${formType}&property_no=ALL&_=${Date.now()}`);
        const items = await response.json();

        tbody.innerHTML = "";

        if (!items || items.length === 0) {
            tbody.innerHTML = `<tr><td colspan="3" style="text-align:center; color: orange;">No available ${formType} items found.</td></tr>`;
            return;
        }

        tbody.innerHTML = items.map(item => `
            <tr class="serial-row">
                <td style="text-align:center;">
                    <input type="checkbox" class="serial-checkbox" data-serial="${item.serial_no}">
                </td>
                <td class="serial-number-cell">${item.serial_no}</td>
                <td>${item.item_name}</td>
            </tr>
        `).join('');

    } catch (err) {
        tbody.innerHTML = '<tr><td colspan="3" style="color:red; text-align:center;">Error loading database items.</td></tr>';
    }
}

document.getElementById("serialSearchFilter")?.addEventListener("input", function () {
    const filter = this.value.toUpperCase().trim();
    const rows = document.querySelectorAll("#serialItemsBody tr.serial-row");

    rows.forEach(row => {
        const serialNo = row.querySelector(".serial-number-cell").textContent.toUpperCase();
        if (serialNo.includes(filter)) {
            row.style.display = "";
            if (serialNo === filter) {
                const cb = row.querySelector(".serial-checkbox");
                if (cb && !cb.checked) cb.checked = true;
            }
        } else {
            row.style.display = "none";
        }
    });
});