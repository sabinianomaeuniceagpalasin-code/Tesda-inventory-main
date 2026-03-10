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
        console.log("Inventory filter response:", data);

        const tbody = document.querySelector("#inventoryTable tbody");
        if (tbody) {
          tbody.innerHTML = data.html || `<tr><td colspan="8" style="text-align:center;">No items found.</td></tr>`;
        }
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

  document.addEventListener("DOMContentLoaded", reloadInventoryTableWithFilters);
})();