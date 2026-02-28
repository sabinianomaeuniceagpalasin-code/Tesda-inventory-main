document.addEventListener("DOMContentLoaded", function () {

    const statusFilter = document.getElementById("archiveStatusFilter");
    const specificDate = document.getElementById("archiveSpecificDate");
    const fromDate = document.getElementById("archiveFromDate");
    const toDate = document.getElementById("archiveToDate");
    const resetBtn = document.getElementById("archiveResetBtn");
    const tableRows = document.querySelectorAll("#archiveTable tbody tr");

    function filterArchive() {
        const statusValue = statusFilter.value;
        const specific = specificDate.value;
        const from = fromDate.value;
        const to = toDate.value;

        tableRows.forEach(row => {
            const rowStatus = row.getAttribute("data-status");
            const rowDate = row.getAttribute("data-date");

            let show = true;

            if (statusValue !== "all" && rowStatus !== statusValue) show = false;

            if (specific && rowDate !== specific) show = false;

            if (!specific) {
                if (from && rowDate < from) show = false;
                if (to && rowDate > to) show = false;
            }

            row.style.display = show ? "" : "none";
        });
    }

    /* =========================
       DATE LOGIC IMPROVEMENT
    ========================== */

    fromDate.addEventListener("change", function () {
        if (this.value !== "") {

            // Clear specific date
            specificDate.value = "";

            // Calculate next day
            let from = new Date(this.value);
            from.setDate(from.getDate() + 1);

            let minDate = from.toISOString().split("T")[0];

            // Set minimum selectable date for TO
            toDate.min = minDate;

            // If current TO date is invalid, clear it
            if (toDate.value && toDate.value < minDate) {
                toDate.value = "";
            }
        } else {
            toDate.min = "";
        }

        filterArchive();
    });

    toDate.addEventListener("change", function () {
        if (this.value !== "") {
            specificDate.value = "";
        }
        filterArchive();
    });

    specificDate.addEventListener("change", function () {
        if (this.value !== "") {
            fromDate.value = "";
            toDate.value = "";
            toDate.min = "";
        }
        filterArchive();
    });

    statusFilter.addEventListener("change", filterArchive);

    resetBtn.addEventListener("click", function () {
        statusFilter.value = "all";
        specificDate.value = "";
        fromDate.value = "";
        toDate.value = "";
        toDate.min = "";

        tableRows.forEach(row => row.style.display = "");
    });

});

document.addEventListener("DOMContentLoaded", function () {

    /* ===============================
       QR FILTER
    ================================ */

    const qrFrom = document.getElementById("qrFromDate");
    const qrTo = document.getElementById("qrToDate");
    const qrReset = document.getElementById("qrResetBtn");
    const qrCards = document.querySelectorAll(".qr-card");

    function filterQR() {
        const from = qrFrom.value;
        const to = qrTo.value;

        qrCards.forEach(card => {
            const date = card.getAttribute("data-date");
            let show = true;

            if (from && date < from) show = false;
            if (to && date > to) show = false;

            card.style.display = show ? "" : "none";
        });
    }

    qrFrom.addEventListener("change", function () {
        if (this.value) {
            let next = new Date(this.value);
            next.setDate(next.getDate() + 1);
            qrTo.min = next.toISOString().split("T")[0];

            if (qrTo.value && qrTo.value < qrTo.min) {
                qrTo.value = "";
            }
        } else {
            qrTo.min = "";
        }
        filterQR();
    });

    qrTo.addEventListener("change", filterQR);

    qrReset.addEventListener("click", function () {
        qrFrom.value = "";
        qrTo.value = "";
        qrTo.min = "";
        qrCards.forEach(c => c.style.display = "");
    });


    
    /* ===============================
       BARCODE FILTER
    ================================ */

    const barcodeFrom = document.getElementById("barcodeFromDate");
    const barcodeTo = document.getElementById("barcodeToDate");
    const barcodeReset = document.getElementById("barcodeResetBtn");
    const barcodeCards = document.querySelectorAll(".barcode-card");

    function filterBarcode() {
        const from = barcodeFrom.value;
        const to = barcodeTo.value;

        barcodeCards.forEach(card => {
            const date = card.getAttribute("data-date");
            let show = true;

            if (from && date < from) show = false;
            if (to && date > to) show = false;

            card.style.display = show ? "" : "none";
        });
    }

    barcodeFrom.addEventListener("change", function () {
        if (this.value) {
            let next = new Date(this.value);
            next.setDate(next.getDate() + 1);
            barcodeTo.min = next.toISOString().split("T")[0];

            if (barcodeTo.value && barcodeTo.value < barcodeTo.min) {
                barcodeTo.value = "";
            }
        } else {
            barcodeTo.min = "";
        }
        filterBarcode();
    });

    barcodeTo.addEventListener("change", filterBarcode);

    barcodeReset.addEventListener("click", function () {
        barcodeFrom.value = "";
        barcodeTo.value = "";
        barcodeTo.min = "";
        barcodeCards.forEach(c => c.style.display = "");
    });

});