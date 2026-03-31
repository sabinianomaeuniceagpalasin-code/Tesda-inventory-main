function bindReturnButtons() {
    document.querySelectorAll(".return-btn-issued").forEach(btn => {
        // Prevent duplicate listeners by cloning the button
        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);

        newBtn.addEventListener("click", function () {
            const clickedBtn = this;
            const id = this.getAttribute("data-id");

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

                    // ✅ Disable button immediately to prevent double submission
                    clickedBtn.disabled = true;
                    clickedBtn.textContent = "Processing...";

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
                            // ✅ Re-enable on error so user can retry
                            clickedBtn.disabled = false;
                            clickedBtn.textContent = "Return";
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
                        reloadInventoryTable();
                        refreshFormTable();
                    })
                    .catch(err => {
                        // ✅ Re-enable on network failure so user can retry
                        clickedBtn.disabled = false;
                        clickedBtn.textContent = "Return";
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
            // ✅ Rebind after table reload since new buttons are injected into DOM
            bindReturnButtons();
        });
}

function reloadInventoryTable() {
    fetch('/dashboard/inventory/table', {
        headers: { "Accept": "application/json" }
    })
        .then(res => res.json())
        .then(data => {
            document.querySelector('#inventoryTable tbody').innerHTML = data.html;
        })
        .catch(err => console.error("Error loading inventory table:", err));
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
document.addEventListener("DOMContentLoaded", function () {
    bindReturnButtons();
});