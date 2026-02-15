// issued-unserviceable.js

function bindUnserviceableButtons() {
    document.querySelectorAll(".unserviceable-btn-issued").forEach(btn => {
        btn.addEventListener("click", function () {
            // Get the issue_id from the corresponding return button
            let id = this.closest('tr').querySelector('.return-btn-issued').getAttribute("data-id");

            Swal.fire({
                title: "Mark Unserviceable?",
                text: "Are you sure you want to mark this item as unserviceable?",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, mark it"
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/issued/unserviceable/${id}`, {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                            "Accept": "application/json"
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === "error") {
                            Swal.fire("Error", data.message, "error");
                            return;
                        }

                        Swal.fire({
                            title: "Success!",
                            text: data.message,
                            icon: "success",
                            timer: 1500,
                            showConfirmButton: false
                        });

                        // Reload tables
                        reloadIssuedTable();
                        reloadInventoryTable();
                        refreshFormTable();
                    })
                    .catch(err => {
                        Swal.fire("Error", "Something went wrong, try again.", "error");
                        console.error(err);
                    });
                }
            });
        });
    });
}

function reloadIssuedTable() {
    fetch(`/dashboard/issued/items-table`)
        .then(res => res.json())
        .then(data => {
            document.querySelector(".issued-table tbody").innerHTML = data.html;
            bindReturnButtons();         // re-bind return buttons
            bindUnserviceableButtons();  // re-bind unserviceable buttons
        });
}

function reloadInventoryTable() {
    fetch('/dashboard/inventory/table')
        .then(res => res.text())
        .then(html => {
            document.querySelector('#inventoryTable tbody').innerHTML = html;
        });
}

function refreshFormTable() {
    fetch('/dashboard/form/table')
        .then(res => res.json())
        .then(data => {
            document.querySelector(".form-table tbody").innerHTML = data.html;
        })
        .catch(err => console.error("Error loading forms:", err));
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", function() {
    bindReturnButtons();          // existing return buttons
    bindUnserviceableButtons();   // new unserviceable buttons
});
