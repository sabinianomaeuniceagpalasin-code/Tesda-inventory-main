// public/js/damage.js
//
// Features:
// 1) Report Damage from Issued table (.damaged-btn-issued)
// 2) Create Maintenance Ticket from Damage table (.maintenance-btn-issued)
// 3) Reload page and return to Damage Report section after creating maintenance ticket

(() => {
  const csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

  async function safeLoadTable(tbodySelector, url) {
    const tbody = document.querySelector(tbodySelector);
    if (!tbody) return;

    tbody.innerHTML = `<tr><td colspan="99" style="text-align:center; padding:16px;">Loading...</td></tr>`;

    try {
      const res = await fetch(url, {
        headers: { Accept: "text/html" },
        credentials: "same-origin",
      });

      const html = await res.text();

      if (!res.ok) {
        console.error("Table reload failed:", url, res.status, html);

        tbody.innerHTML = `<tr><td colspan="99" style="text-align:center; padding:16px;">
          Failed to reload table (${res.status})
        </td></tr>`;

        Swal.fire("Error", `Failed to reload table (${res.status}).`, "error");
        return;
      }

      if (/<html|<body/i.test(html)) {
        console.error("Blocked full HTML document injection for:", url, html);
        tbody.innerHTML = `<tr><td colspan="99" style="text-align:center; padding:16px;">
          Unexpected response returned. Check route/controller.
        </td></tr>`;
        Swal.fire("Error", "Unexpected response while reloading table.", "error");
        return;
      }

      tbody.innerHTML = html;
    } catch (err) {
      console.error(err);
      tbody.innerHTML = `<tr><td colspan="99" style="text-align:center; padding:16px;">
        Network error while reloading table.
      </td></tr>`;
      Swal.fire("Error", "Network error while reloading table.", "error");
    }
  }

  window.reloadDamageTable = () =>
    safeLoadTable("#damageTable tbody", "/dashboard/damage/table-html");

  window.reloadMaintenanceTable = () =>
    safeLoadTable("#maintenanceTable tbody", "/dashboard/maintenance/table-html");

  async function reportDamage(serialNo, observation) {
    const res = await fetch("/damage-reports/store", {
      method: "POST",
      headers: {
        "X-CSRF-TOKEN": csrf(),
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      credentials: "same-origin",
      body: JSON.stringify({ serial_no: serialNo, observation }),
    });

    const data = await res.json().catch(() => ({}));

    if (res.status === 401 || res.status === 403) {
      Swal.fire("Unauthorized", "You are not allowed to do this action.", "error");
      return;
    }

    if (!res.ok || !data.success) {
      Swal.fire("Error", data.message || "Failed to report damage.", "error");
      return;
    }

    Swal.fire({
      title: "Success",
      text: data.message || "Damage reported.",
      icon: "success",
      timer: 2000,
      showConfirmButton: false,
    }).then(() => {
      localStorage.setItem("activeSection", "damaged");
      window.location.reload();
    });
  }

  async function createTicketFromDamage(damageId) {
    const res = await fetch(`/damage/move/${encodeURIComponent(damageId)}`, {
      method: "POST",
      headers: {
        "X-CSRF-TOKEN": csrf(),
        Accept: "application/json",
      },
      credentials: "same-origin",
    });

    const data = await res.json().catch(() => ({}));

    if (res.status === 409) {
      Swal.fire("Already Ticketed", data.message || "Ticket already exists.", "info");
      return;
    }

    if (res.status === 401 || res.status === 403) {
      Swal.fire("Unauthorized", data.message || "You are not allowed.", "error");
      return;
    }

    if (!res.ok || !data.success) {
      Swal.fire("Error", data.message || "Failed to create maintenance ticket.", "error");
      return;
    }

    Swal.fire({
      title: "Ticket Created",
      text: data.message || "Maintenance ticket created.",
      icon: "success",
      timer: 1200,
      showConfirmButton: false,
    }).then(() => {
      localStorage.setItem("activeSection", "damaged");
      window.location.reload();
    });
  }

  function getSerialFromIssuedButton(btn) {
    let serial = btn.dataset.id;
    if (!serial) {
      const row = btn.closest("tr");
      serial = row?.querySelector("td")?.textContent?.trim();
    }
    return serial || null;
  }

  document.addEventListener("click", async (e) => {
    const damageBtn = e.target.closest(".damaged-btn-issued");
    if (damageBtn) {
      const serialNo = getSerialFromIssuedButton(damageBtn);

      if (!serialNo) {
        Swal.fire("Error", "Serial number missing!", "error");
        return;
      }

      const result = await Swal.fire({
        title: "Report Damage",
        html: `
          <p style="margin:0 0 10px 0;">Serial #: <b>${serialNo}</b></p>
          <textarea id="damageObservation" class="swal2-textarea"
            placeholder="Enter cause / observation (required)"></textarea>
        `,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Submit",
        cancelButtonText: "Cancel",
        preConfirm: () => {
          const obs = document.getElementById("damageObservation")?.value?.trim();
          if (!obs) {
            Swal.showValidationMessage("Observation is required.");
            return false;
          }
          return obs;
        },
      });

      if (result.isConfirmed) {
        await reportDamage(serialNo, result.value);
      }
      return;
    }

    const ticketBtn = e.target.closest(".maintenance-btn-issued");
    if (ticketBtn) {
      const damageId = ticketBtn.dataset.damageId;

      if (!damageId) {
        Swal.fire("Error", "Damage report ID missing!", "error");
        return;
      }

      const confirm = await Swal.fire({
        title: "Create Maintenance Ticket?",
        text: "This will create a maintenance record from this damage report.",
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Create Ticket",
        cancelButtonText: "Cancel",
      });

      if (!confirm.isConfirmed) return;

      ticketBtn.disabled = true;

      try {
        await createTicketFromDamage(damageId);
      } finally {
        ticketBtn.disabled = false;
      }
    }
  });
})();