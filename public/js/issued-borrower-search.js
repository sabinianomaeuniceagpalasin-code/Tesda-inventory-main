function filterIssuedTable() {
  const input = document.getElementById("issuedSearchInput");
  const filter = input.value.toLowerCase();
  const table = document.querySelector(".issued-table");
  const rows = table.querySelectorAll("tbody tr");

  rows.forEach(row => {
    const serialCell = row.cells[0]; // Serial #
    const borrowerCell = row.cells[1]; // Issued to

    if (!serialCell || !borrowerCell) return;

    const serialText = serialCell.textContent.toLowerCase();
    const borrowerText = borrowerCell.textContent.toLowerCase();

    if (serialText.includes(filter) || borrowerText.includes(filter)) {
      row.style.display = "";
    } else {
      row.style.display = "none";
    }
  });
}

function handleScanEnter(event) {
  if (event.key === "Enter") {
    event.preventDefault();

    // Re-run filter just in case
    filterIssuedTable();

    // Optional: auto-clear after scan
    setTimeout(() => {
      event.target.value = "";
      filterIssuedTable();
    }, 300);
  }
}
