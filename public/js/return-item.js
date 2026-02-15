function bindReturnButtons() {
    document.querySelectorAll(".return-btn-issued").forEach(btn => {
        btn.addEventListener("click", function () {
            let id = this.getAttribute("data-id");

            Swal.fire({
                title: "Return Item?",
                text: "Are you sure you want to return this item?",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, return it"
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/issued/return/${id}`, {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                            "Accept": "application/json"
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.error) {
                            Swal.fire("Error", data.error, "error");
                            return;
                        }

                        Swal.fire({
                            title: "Success!",
                            text: data.message,
                            icon: "success",
                            timer: 1500,
                            showConfirmButton: false
                        });

                        
                            reloadIssuedTable();
                            reloadInventoryTable(); // <--- update inventory too
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
            bindReturnButtons(); // re-bind buttons
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


// Initialize buttons on page load
document.addEventListener("DOMContentLoaded", function() {
    bindReturnButtons();
});