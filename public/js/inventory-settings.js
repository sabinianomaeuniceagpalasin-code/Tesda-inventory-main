// ===================================
// INVENTORY SETTINGS JAVASCRIPT
// ===================================

document.addEventListener("DOMContentLoaded", function () {
    console.log("Inventory Settings initialized");

    // ===================================
    // TOGGLE SWITCHES
    // ===================================
    const toggleSwitches = document.querySelectorAll(
        '.form-check-input[type="checkbox"]'
    );

    toggleSwitches.forEach((toggle) => {
        toggle.addEventListener("change", function () {
            const settingName = this.id;
            const isEnabled = this.checked;

            console.log(
                `${settingName} is now ${isEnabled ? "enabled" : "disabled"}`
            );

            // Show notification
            showNotification(
                `${formatSettingName(settingName)} ${
                    isEnabled ? "enabled" : "disabled"
                }`,
                "success"
            );

            // TODO: Send AJAX request to save setting
            // saveSettings(settingName, isEnabled);
        });
    });

    // ===================================
    // DROPDOWN CHANGES
    // ===================================
    const yearCycle = document.getElementById("yearCycle");
    const dateFormat = document.getElementById("dateFormat");

    if (yearCycle) {
        yearCycle.addEventListener("change", function () {
            console.log("Year cycle changed to:", this.value);
            showNotification(`Year cycle set to ${this.value}`, "info");
            // TODO: Save to backend
        });
    }

    if (dateFormat) {
        dateFormat.addEventListener("change", function () {
            console.log("Date format changed to:", this.value);
            showNotification(`Date format updated to ${this.value}`, "info");
            // TODO: Save to backend
        });
    }

    // ===================================
    // ITEM LIFESPAN - EDIT BUTTONS
    // ===================================
    const editButtons = document.querySelectorAll(".bi-pencil");
    editButtons.forEach((btn) => {
        btn.closest("button").addEventListener("click", function (e) {
            e.preventDefault();
            const row = this.closest("tr");
            const itemName = row.cells[0].textContent;
            const lifespan = row.cells[1].textContent.trim();

            console.log("Edit item:", itemName, "Lifespan:", lifespan);
            editItemLifespan(itemName, lifespan);
        });
    });

    // ===================================
    // ITEM LIFESPAN - DELETE BUTTONS
    // ===================================
    const deleteButtons = document.querySelectorAll(".bi-trash");
    deleteButtons.forEach((btn) => {
        btn.closest("button").addEventListener("click", function (e) {
            e.preventDefault();
            const row = this.closest("tr");
            const itemName = row.cells[0].textContent;

            if (confirm(`Are you sure you want to delete ${itemName}?`)) {
                console.log("Delete item:", itemName);
                row.remove();
                showNotification(`${itemName} removed successfully`, "success");
                // TODO: Send delete request to backend
            }
        });
    });

    // ===================================
    // ADD NEW ITEM BUTTON
    // ===================================
    const addItemButtons = document.querySelectorAll(".btn-outline-primary");
    addItemButtons.forEach((btn) => {
        if (btn.textContent.includes("Add new item")) {
            btn.addEventListener("click", function (e) {
                e.preventDefault();
                addNewItem();
            });
        }
        if (btn.textContent.includes("Add new classification")) {
            btn.addEventListener("click", function (e) {
                e.preventDefault();
                addNewClassification();
            });
        }
    });

    // ===================================
    // CLASSIFICATION - EDIT & DELETE
    // ===================================
    const classificationItems = document.querySelectorAll(".list-group-item");
    classificationItems.forEach((item) => {
        const editBtn = item.querySelector(".bi-pencil");
        const deleteBtn = item.querySelector(".bi-trash");
        const className = item.querySelector("span").textContent;

        if (editBtn) {
            editBtn.closest("button").addEventListener("click", function (e) {
                e.preventDefault();
                editClassification(className);
            });
        }

        if (deleteBtn) {
            deleteBtn.closest("button").addEventListener("click", function (e) {
                e.preventDefault();
                if (confirm(`Delete classification "${className}"?`)) {
                    item.remove();
                    showNotification(`${className} deleted`, "success");
                    // TODO: Send delete request to backend
                }
            });
        }
    });

    // ===================================
    // HELPER FUNCTIONS
    // ===================================

    // Format setting name for display
    function formatSettingName(name) {
        return name
            .replace(/([A-Z])/g, " $1")
            .replace(/^./, (str) => str.toUpperCase())
            .trim();
    }

    // Show notification (you can replace with a proper notification library)
    function showNotification(message, type = "info") {
        // Simple console notification
        console.log(`[${type.toUpperCase()}] ${message}`);

        // TODO: Implement proper notification UI
        // Example: Using Bootstrap Toast, Toastr, or custom notification
        alert(message);
    }

    // Edit item lifespan
    function editItemLifespan(itemName, currentLifespan) {
        const newLifespan = prompt(
            `Edit lifespan for ${itemName}:`,
            currentLifespan
        );

        if (newLifespan !== null && newLifespan.trim() !== "") {
            console.log(`Update ${itemName} lifespan to ${newLifespan}`);
            showNotification(
                `${itemName} lifespan updated to ${newLifespan} years`,
                "success"
            );

            // Update the table cell
            const rows = document.querySelectorAll(".table-sm tbody tr");
            rows.forEach((row) => {
                if (row.cells[0].textContent === itemName) {
                    row.cells[1].textContent = newLifespan;
                }
            });

            // TODO: Send update to backend
        }
    }

    // Add new item
    function addNewItem() {
        const itemName = prompt("Enter item name:");
        const lifespan = prompt("Enter lifespan (years):");

        if (itemName && lifespan) {
            console.log("Add new item:", itemName, "Lifespan:", lifespan);

            const tbody = document.querySelector(".table-sm tbody");
            const newRow = document.createElement("tr");
            newRow.innerHTML = `
                <td>${itemName}</td>
                <td class="text-end">${lifespan}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-link p-0 me-2"><i class="bi bi-pencil text-primary"></i></button>
                    <button class="btn btn-sm btn-link p-0"><i class="bi bi-trash text-danger"></i></button>
                </td>
            `;
            tbody.appendChild(newRow);

            // Re-attach event listeners
            attachItemEventListeners(newRow);

            showNotification(`${itemName} added successfully`, "success");
            // TODO: Send to backend
        }
    }

    // Add new classification
    function addNewClassification() {
        const className = prompt("Enter classification name:");

        if (className && className.trim() !== "") {
            console.log("Add new classification:", className);

            const listGroup = document.querySelector(".list-group");
            const newItem = document.createElement("div");
            newItem.className =
                "list-group-item d-flex justify-content-between align-items-center";
            newItem.innerHTML = `
                <span>${className}</span>
                <div>
                    <button class="btn btn-sm btn-link p-0 me-2"><i class="bi bi-pencil text-primary"></i></button>
                    <button class="btn btn-sm btn-link p-0"><i class="bi bi-trash text-danger"></i></button>
                </div>
            `;
            listGroup.appendChild(newItem);

            showNotification(`${className} classification added`, "success");
            // TODO: Send to backend
        }
    }

    // Edit classification
    function editClassification(currentName) {
        const newName = prompt(`Edit classification name:`, currentName);

        if (newName && newName.trim() !== "") {
            console.log(
                `Update classification from ${currentName} to ${newName}`
            );
            showNotification(`Classification updated to ${newName}`, "success");

            // Update the span text
            const items = document.querySelectorAll(".list-group-item span");
            items.forEach((item) => {
                if (item.textContent === currentName) {
                    item.textContent = newName;
                }
            });

            // TODO: Send update to backend
        }
    }

    // Attach event listeners to dynamically added items
    function attachItemEventListeners(row) {
        const editBtn = row.querySelector(".bi-pencil");
        const deleteBtn = row.querySelector(".bi-trash");

        if (editBtn) {
            editBtn.closest("button").addEventListener("click", function (e) {
                e.preventDefault();
                const itemName = row.cells[0].textContent;
                const lifespan = row.cells[1].textContent.trim();
                editItemLifespan(itemName, lifespan);
            });
        }

        if (deleteBtn) {
            deleteBtn.closest("button").addEventListener("click", function (e) {
                e.preventDefault();
                const itemName = row.cells[0].textContent;
                if (confirm(`Delete ${itemName}?`)) {
                    row.remove();
                    showNotification(`${itemName} deleted`, "success");
                }
            });
        }
    }

    // ===================================
    // AJAX FUNCTION (Template)
    // ===================================
    function saveSettings(settingName, value) {
        // Example AJAX call structure
        /*
        fetch('/api/inventory-settings/update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                setting: settingName,
                value: value
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Setting saved successfully', 'success');
            } else {
                showNotification('Failed to save setting', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred', 'error');
        });
        */
    }

    console.log("All event listeners attached successfully");
});

// ===================================
// SMOOTH SCROLL FOR BACK BUTTON
// ===================================
document
    .querySelector(".bi-arrow-left")
    ?.closest("a")
    .addEventListener("click", function (e) {
        e.preventDefault();
        window.history.back();
    });
