const DamageHandler = (() => {
    async function reportDamage(itemId) {
        try {
            const res = await fetch("/damage-reports/store", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                    "Content-Type": "application/json",
                    Accept: "application/json",
                },
                body: JSON.stringify({ serial_no: itemId }),
            });

            const data = await res.json();

            if (data.success) {
                Swal.fire("Success", data.message, "success");
                reloadIssuedTable();
                reloadDamageTable();
            } else {
                Swal.fire(
                    "Error",
                    data.message || "Failed to report damage.",
                    "error"
                );
            }
        } catch (err) {
            console.error(err);
            Swal.fire("Error", "Something went wrong.", "error");
        }
    }

    function reloadIssuedTable() {
        fetch("/dashboard/issued/items-table")
            .then((res) => res.json())
            .then((data) => {
                document.querySelector(".issued-table-section").innerHTML =
                    data.html;
            });
    }

    function reloadDamageTable() {
        fetch("/damage-reports/table")
            .then((res) => res.text())
            .then((html) => {
                document.querySelector(".damaged-table-section").innerHTML =
                    html;
            });
    }

    function bindDamageButtons() {
        document.addEventListener("click", async (e) => {
            if (e.target.closest(".damaged-btn-issued")) {
                const button = e.target.closest(".damaged-btn-issued");
                const itemId = button.dataset.id;
                if (!itemId) return alert("Item ID missing!");

                const confirmed = await Swal.fire({
                    title: "Report Damage?",
                    text: "Are you sure this item is damaged?",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    cancelButtonColor: "#3085d6",
                    confirmButtonText: "Yes, mark as damaged",
                });

                if (confirmed.isConfirmed) reportDamage(itemId);
            }
        });
    }

    function init() {
        bindDamageButtons();
    }

    return { init };
})();

document.addEventListener("DOMContentLoaded", () => {
    DamageHandler.init();
});

document.addEventListener("click", function (e) {
    if (e.target.closest(".damaged-btn-issued")) {
        const button = e.target.closest(".damaged-btn-issued");
        const itemId = button.dataset.id;

        fetch(`/damage-reports/${serialNo}`)
            .then((res) => res.json())
            .then((data) => {
                if (data.success) {
                    document.getElementById("m_property_no").value =
                        data.damage.property_no;
                    document.getElementById("m_item_name").value =
                        data.damage.item_name;
                    document.getElementById("m_date").value =
                        data.damage.reported_at;

                    document.getElementById(
                        "maintenanceAddModal"
                    ).style.display = "block";
                } else {
                    Swal.fire("Error", data.message, "error");
                }
            });
    }
});
