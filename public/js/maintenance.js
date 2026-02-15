document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("maintenanceEditModal");
    const closeBtn = document.getElementById("closeMaintenanceModal");
    const form = document.getElementById("maintenanceForm");
    const serialInput = document.getElementById("m_serial_no");
    const itemNameInput = document.getElementById("m_item_name");
    const dateInput = document.getElementById("m_date");
    const cancelBtn = form.querySelector(".cancel-btn");
    let currentRecordId = null;

    const openModal = () => (modal.style.display = "flex");
    const closeModal = () => {
        modal.style.display = "none";
        form.reset();
        currentRecordId = null;
        itemNameInput.value = "";
        dateInput.value = "";
    };

    const fillRecordDetails = async (maintenanceId, serialNo) => {
        try {
            currentRecordId = maintenanceId; // use for update
            serialInput.value = serialNo;

            const res = await fetch(`/maintenance/latest-damage/${serialNo}`);
            const data = await res.json();

            itemNameInput.value = data.damage?.item_name || "";
            dateInput.value = data.damage?.reported_at || "";

            openModal();
        } catch (err) {
            console.error(err);
            Swal.fire(
                "Error",
                err.message || "Failed to load record.",
                "error"
            );
            serialInput.value = "";
            itemNameInput.value = "";
            dateInput.value = "";
        }
    };

    serialInput.addEventListener("input", async () => {
        const serialNo = serialInput.value.trim();
        if (!serialNo) {
            itemNameInput.value = "";
            dateInput.value = "";
            return;
        }

        try {
            const res = await fetch(`/maintenance/latest-damage/${serialNo}`);
            const data = await res.json();

            itemNameInput.value = data.damage?.item_name || "";
            dateInput.value = data.damage?.reported_at || "";
        } catch (err) {
            console.error(err);
            itemNameInput.value = "";
            dateInput.value = "";
        }
    });

    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        if (!currentRecordId) return;

        const payload = {
            serial_no: serialInput.value,
            issue_type: document.getElementById("m_issue").value,
            date_reported: dateInput.value,
            repair_cost: document.getElementById("m_cost").value,
            expected_completion:
                document.getElementById("m_completion").value || null,
            remarks: document.getElementById("m_remarks").value || null,
        };

        try {
            const res = await fetch(`/maintenance/${currentRecordId}/update`, {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content,
                    Accept: "application/json",
                },
                body: JSON.stringify(payload),
            });

            const json = await res.json();
            if (!json.success) throw new Error(json.message);

            Swal.fire("Success", "Maintenance record updated!", "success");
            closeModal();
            if (typeof reloadMaintenanceTable === "function")
                reloadMaintenanceTable();
        } catch (err) {
            console.error(err);
            Swal.fire(
                "Error",
                err.message || "Failed to update record.",
                "error"
            );
        }
    });

    document.addEventListener("click", (e) => {
        const deleteBtn = e.target.closest(".delete-btn");
        if (deleteBtn) {
            const recordId = deleteBtn.dataset.id;
            Swal.fire({
                title: "Delete record?",
                text: "This action cannot be undone.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Yes, delete it!",
            }).then(async (result) => {
                if (!result.isConfirmed) return;

                try {
                    const res = await fetch(`/maintenance/${recordId}/delete`, {
                        method: "DELETE",
                        headers: {
                            "X-CSRF-TOKEN": document.querySelector(
                                'meta[name="csrf-token"]'
                            ).content,
                            Accept: "application/json",
                        },
                    });
                    const json = await res.json();
                    if (!json.success) throw new Error(json.message);

                    Swal.fire(
                        "Deleted!",
                        "Maintenance record has been deleted.",
                        "success"
                    );
                    if (typeof reloadMaintenanceTable === "function")
                        reloadMaintenanceTable();
                } catch (err) {
                    console.error(err);
                    Swal.fire(
                        "Error",
                        err.message || "Failed to delete record.",
                        "error"
                    );
                }
            });
        }

        const editBtn = e.target.closest(".edit-btn");
        if (editBtn) {
            const maintenanceId = editBtn.dataset.id;
            const serialNo = editBtn.dataset.serial;
            fillRecordDetails(maintenanceId, serialNo);
        }
    });

    closeBtn.addEventListener("click", closeModal);
    cancelBtn.addEventListener("click", closeModal);
    window.addEventListener("click", (e) => {
        if (e.target === modal) closeModal();
    });
});
