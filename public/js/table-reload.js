window.TableReloader = {
  async reloadIssued() {
    const tbody = document.querySelector("#issuedTable tbody");
    if (!tbody) return;

    const res = await fetch("/dashboard/issued/table-html", { headers: { Accept: "text/html" } });
    if (!res.ok) throw new Error("Failed to reload issued table");
    tbody.innerHTML = await res.text();
  },

  async reloadDamage() {
    const tbody = document.querySelector("#damageTable tbody");
    if (!tbody) return;

    const res = await fetch("/dashboard/damage/table-html", { headers: { Accept: "text/html" } });
    if (!res.ok) throw new Error("Failed to reload damage table");
    tbody.innerHTML = await res.text();
  },

  async reloadMaintenance() {
    const tbody = document.querySelector("#maintenanceTable tbody");
    if (!tbody) return;

    const res = await fetch("/dashboard/maintenance/table-html", { headers: { Accept: "text/html" } });
    if (!res.ok) throw new Error("Failed to reload maintenance table");
    tbody.innerHTML = await res.text();
  }
};

// Backward compatibility (your existing scripts call these)
window.reloadIssuedTable = () => TableReloader.reloadIssued().catch(console.error);
window.reloadDamageTable = () => TableReloader.reloadDamage().catch(console.error);
window.reloadMaintenanceTable = () => TableReloader.reloadMaintenance().catch(console.error);