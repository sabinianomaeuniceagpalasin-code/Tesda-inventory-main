document.addEventListener("DOMContentLoaded", function () {
    bindMakeAvailableButtons();
});

function bindMakeAvailableButtons() {
    document.querySelectorAll(".make-available-btn").forEach((btn) => {
        btn.addEventListener("click", function () {
            let serial = this.getAttribute("data-serial");

            Swal.fire({
                title: "Mark item as available?",
                text: "This will update the inventory status to 'Available'.",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, make available",
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/maintenance/make-available/${serial}`, {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": document.querySelector(
                                'meta[name="csrf-token"]'
                            ).content,
                            Accept: "application/json",
                        },
                    })
                        .then((res) => res.json())
                        .then((data) => {
                            if (data.error) {
                                Swal.fire("Error", data.error, "error");
                                return;
                            }

                            Swal.fire({
                                title: "Success!",
                                text: data.message,
                                icon: "success",
                                timer: 1500,
                                showConfirmButton: false,
                            });

                            // Optional: reload Maintenance and Inventory tables
                            reloadMaintenanceTable();
                            reloadInventoryTable();
                        })
                        .catch((err) => {
                            Swal.fire(
                                "Error",
                                "Something went wrong.",
                                "error"
                            );
                            console.error(err);
                        });
                }
            });
        });
    });
}

function reloadMaintenanceTable() {
    fetch("/dashboard/maintenance/table")
        .then((res) => res.text())
        .then((html) => {
            document.querySelector(".form-table tbody").innerHTML = html;
            bindMakeAvailableButtons(); 
        });
}

function reloadInventoryTable() {
    fetch("/dashboard/inventory/table")
        .then((res) => res.text())
        .then((html) => {
            document.querySelector("#inventoryTable tbody").innerHTML = html;
        });
}
