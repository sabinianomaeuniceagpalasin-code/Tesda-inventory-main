document.addEventListener('DOMContentLoaded', function () {
    const exportBtn = document.getElementById('ExportMaintenanceBtn');
    const searchInput = document.getElementById('MaintenanceSearchInput');

    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            const search = searchInput ? searchInput.value.trim() : '';
            let url = '/dashboard/maintenance/export/pdf';

            if (search) {
                url += '?search=' + encodeURIComponent(search);
            }

            window.open(url, '_blank');
        });
    }
});

document.addEventListener("DOMContentLoaded", function () {

    const printBtn = document.getElementById("PrintMaintenanceBtn");

    if (!printBtn) return;

    printBtn.addEventListener("click", function () {

        const reportsSection = document.getElementById("reports");
        const table = document.getElementById("maintenanceTable");
        const summary = reportsSection.querySelector(".form-summary");

        if (!table) {
            alert("Maintenance table not found.");
            return;
        }

        // Clone table
        const clonedTable = table.cloneNode(true);

        // Remove ONLY the Actions column
        const rows = clonedTable.querySelectorAll("tr");
        rows.forEach(row => {
            if (row.cells.length > 0) {
                row.deleteCell(row.cells.length - 1);
            }
        });

        const printWindow = window.open("", "", "width=1100,height=700");

        printWindow.document.write(`
            <html>
            <head>
                <title>TESDA Maintenance Report</title>

                <style>
                    body{
                        font-family: Arial, sans-serif;
                        margin:20px;
                    }

                    h2{
                        text-align:center;
                        color:#0b3d91;
                        margin-bottom:5px;
                    }

                    .subtitle{
                        text-align:center;
                        margin-bottom:20px;
                    }

                    .form-summary{
                        display:flex;
                        gap:10px;
                        margin-bottom:20px;
                    }

                    .summary-card{
                        border:1px solid #333;
                        padding:10px;
                        flex:1;
                        text-align:center;
                    }

                    table{
                        width:100%;
                        border-collapse:collapse;
                        margin-top:20px;
                    }

                    th, td{
                        border:1px solid #333;
                        padding:8px;
                        font-size:12px;
                        text-align:left;
                    }

                    th{
                        background:#0b3d91;
                        color:white;
                    }
                </style>

            </head>

            <body>

                <h2>TESDA Maintenance Report</h2>
                <div class="subtitle">Automated Tools and Equipment Inventory Control System</div>

                ${summary ? summary.outerHTML : ""}

                ${clonedTable.outerHTML}

            </body>
            </html>
        `);

        printWindow.document.close();

        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 500);

    });

});