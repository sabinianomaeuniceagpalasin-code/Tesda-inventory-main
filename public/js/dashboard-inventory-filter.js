(function () {
  const statusSelect = document.getElementById("inventoryStatusFilter");
  const clearBtn = document.getElementById("clearInventoryFiltersBtn");

  function reloadInventoryTableWithFilters() {
    const status = statusSelect ? statusSelect.value : "All";

    fetch(`/dashboard/inventory/table?status=${encodeURIComponent(status)}`, {
      headers: { "Accept": "application/json" }
    })
      .then(res => res.json())
      .then(data => {
        const tbody = document.querySelector("#inventoryTable tbody");
        if (tbody) tbody.innerHTML = data.html;
      })
      .catch(err => console.error("Inventory filter reload error:", err));
  }

  if (statusSelect) {
    statusSelect.addEventListener("change", reloadInventoryTableWithFilters);
  }

  if (clearBtn) {
    clearBtn.addEventListener("click", () => {
      if (statusSelect) statusSelect.value = "All";
      const search = document.getElementById("inventorySearchInput");
      if (search) search.value = "";
      reloadInventoryTableWithFilters();
    });
  }

  // Optional: load once on page load (keeps consistent)
  document.addEventListener("DOMContentLoaded", reloadInventoryTableWithFilters);
})();