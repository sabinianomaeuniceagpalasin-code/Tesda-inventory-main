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
