document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("maintenanceEditModal");
    const closeBtn = document.getElementById("closeMaintenanceModal");
    const form = document.getElementById("maintenanceForm");
    const serialInput = document.getElementById("m_serial_no");
    const itemNameInput = document.getElementById("m_item_name");
    const issueInput = document.getElementById("m_issue");
    const dateInput = document.getElementById("m_date");
    const costInput = document.getElementById("m_cost");
    const completionInput = document.getElementById("m_completion");
    const remarksInput = document.getElementById("m_remarks");
    const cancelBtn = form.querySelector(".cancel-btn");

    let currentRecordId = null;

    const openModal = () => {
        if (modal) modal.style.display = "flex";
    };

    const closeModal = () => {
        if (modal) modal.style.display = "none";
        if (form) form.reset();

        currentRecordId = null;

        if (serialInput) serialInput.value = "";
        if (itemNameInput) itemNameInput.value = "";
        if (issueInput) issueInput.value = "";
        if (dateInput) dateInput.value = "";
        if (costInput) costInput.value = "";
        if (completionInput) completionInput.value = "";
        if (remarksInput) remarksInput.value = "";
    };

    const fillRecordDetails = async (maintenanceId) => {
        try {
            currentRecordId = maintenanceId;

            const res = await fetch(`/maintenance/${maintenanceId}`, {
                headers: {
                    Accept: "application/json",
                },
            });

            const data = await res.json();

            if (!data.success || !data.record) {
                throw new Error(data.message || "Failed to load maintenance record.");
            }

            const record = data.record;

            if (serialInput) serialInput.value = record.serial_no || "";
            if (itemNameInput) itemNameInput.value = record.item_name || "";
            if (issueInput) issueInput.value = record.issue_type || "";
            if (dateInput) dateInput.value = record.date_reported || "";
            if (costInput) costInput.value = record.repair_cost ?? "";
            if (completionInput) completionInput.value = record.expected_completion || "";
            if (remarksInput) remarksInput.value = record.remarks || "";

            openModal();
        } catch (err) {
            console.error(err);

            Swal.fire(
                "Error",
                err.message || "Failed to load maintenance record.",
                "error"
            );

            if (serialInput) serialInput.value = "";
            if (itemNameInput) itemNameInput.value = "";
            if (issueInput) issueInput.value = "";
            if (dateInput) dateInput.value = "";
            if (costInput) costInput.value = "";
            if (completionInput) completionInput.value = "";
            if (remarksInput) remarksInput.value = "";
        }
    };

    if (form) {
        form.addEventListener("submit", async (e) => {
            e.preventDefault();

            if (!currentRecordId) {
                Swal.fire("Error", "No maintenance record selected.", "error");
                return;
            }

            const payload = {
                serial_no: serialInput ? serialInput.value : "",
                issue_type: issueInput ? issueInput.value : "",
                date_reported: dateInput ? dateInput.value : "",
                repair_cost: costInput ? costInput.value : "",
                expected_completion: completionInput && completionInput.value ? completionInput.value : null,
                remarks: remarksInput && remarksInput.value ? remarksInput.value : null,
            };

            try {
                const res = await fetch(`/maintenance/${currentRecordId}/update`, {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                        Accept: "application/json",
                    },
                    body: JSON.stringify(payload),
                });

                const json = await res.json();

                if (!json.success) {
                    throw new Error(json.message || "Failed to update record.");
                }

                closeModal();

                Swal.fire({
                    title: "Success",
                    text: "Maintenance record updated!",
                    icon: "success",
                    timer: 1200,
                    showConfirmButton: false,
                }).then(() => {
                    localStorage.setItem("activeSection", "reports");
                    window.location.reload();
                });
            } catch (err) {
                console.error(err);

                Swal.fire(
                    "Error",
                    err.message || "Failed to update record.",
                    "error"
                );
            }
        });
    }

    document.addEventListener("click", (e) => {
        const editBtn = e.target.closest(".edit-btn");
        if (editBtn) {
            e.preventDefault();
            e.stopPropagation();

            const maintenanceId = editBtn.dataset.id;
            if (maintenanceId) {
                fillRecordDetails(maintenanceId);
            }
            return;
        }

        const deleteBtn = e.target.closest(".delete-btn");
        if (deleteBtn) {
            e.preventDefault();
            e.stopPropagation();

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
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                            Accept: "application/json",
                        },
                    });

                    const json = await res.json();

                    if (!json.success) {
                        throw new Error(json.message || "Failed to delete record.");
                    }

                    Swal.fire({
                        title: "Deleted!",
                        text: "Maintenance record has been deleted.",
                        icon: "success",
                        timer: 1200,
                        showConfirmButton: false,
                    }).then(() => {
                        localStorage.setItem("activeSection", "reports");
                        window.location.reload();
                    });
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
    });

    if (closeBtn) {
        closeBtn.addEventListener("click", closeModal);
    }

    if (cancelBtn) {
        cancelBtn.addEventListener("click", closeModal);
    }

    window.addEventListener("click", (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });
});