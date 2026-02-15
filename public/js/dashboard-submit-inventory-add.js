document.addEventListener("DOMContentLoaded", function () {
    const addItemForm = document.getElementById("addItemForm");
    const addItemModal = document.getElementById("addItemModal");
    const closeModal = document.getElementById("closeModal");

    // Close modal
    closeModal.addEventListener("click", () => {
        addItemModal.style.display = "none";
    });

    // Calculate total cost
    const quantityInput = document.getElementById("quantity");
    const unitCostInput = document.getElementById("unit_cost");
    const totalCostInput = document.getElementById("total_cost");

    function updateTotal() {
        const quantity = parseFloat(quantityInput.value) || 0;
        const unitCost = parseFloat(unitCostInput.value) || 0;
        totalCostInput.value = (quantity * unitCost).toFixed(2);
    }

    quantityInput.addEventListener("input", updateTotal);
    unitCostInput.addEventListener("input", updateTotal);

    // AJAX form submission
    addItemForm.addEventListener("submit", async function (e) {
        e.preventDefault();

        const formData = new FormData(addItemForm);

        try {
            const res = await fetch(addItemForm.action, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            });

            const json = await res.json();

            if (json.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: json.message || 'Item added successfully!',
                    timer: 2500,
                    showConfirmButton: false
                });

                addItemForm.reset(); // Reset form
                addItemModal.style.display = "none"; // Hide modal
                // Optionally: reload table data or page
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: json.message || 'Failed to add item.'
                });
            }

        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Unexpected error: ' + err.message
            });
        }
    });
});
