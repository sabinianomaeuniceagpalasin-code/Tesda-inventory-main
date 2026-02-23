function bindMaintenanceButtons() {
    document.querySelectorAll(".maintenance-btn-issued").forEach((btn) => {
        btn.addEventListener("click", function () {
            const id = this.getAttribute("data-id");
            const row = this.closest("tr");

            Swal.fire({
                title: "Move to Maintenance?",
                text: "This damage report will be transferred into maintenance records.",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, proceed",
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/damage/move/${id}`, {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                            Accept: "application/json",
                        },
                    })
                        .then((res) => res.json())
                        .then((data) => {
                            if (data.error) {
                                Swal.fire("Error", data.error, "error");
                                return;
                            }

                            if (row) row.remove();

                            Swal.fire({
                                title: "Success!",
                                text: data.message,
                                icon: "success",
                                timer: 1500,
                                showConfirmButton: false,
                            });

                            if (typeof reloadDamageTable === "function") reloadDamageTable();
                            if (typeof reloadMaintenanceTable === "function") reloadMaintenanceTable();
                        })
                        .catch((err) => {
                            Swal.fire("Error", "Something went wrong.", "error");
                        });
                }
            });
        });
    });
}

document.addEventListener("DOMContentLoaded", bindMaintenanceButtons);