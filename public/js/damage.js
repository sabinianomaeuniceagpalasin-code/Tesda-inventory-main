const DamageHandler = (() => {
  async function reportDamage(serialNo, observation) {
    try {
      const res = await fetch("/damage-reports/store", {
        method: "POST",
        headers: {
          "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
          "Content-Type": "application/json",
          "Accept": "application/json"
        },
        body: JSON.stringify({
          serial_no: serialNo,
          observation: observation
        })
      });

      // If Laravel returns 401/403, show a clear message
      if (res.status === 401 || res.status === 403) {
        Swal.fire("Unauthorized", "You are not allowed to do this action.", "error");
        return;
      }

      const data = await res.json();

      if (data.success) {
        Swal.fire("Success", data.message, "success");
        reloadIssuedTable();
        reloadDamageTable();

      } else {
        Swal.fire("Error", data.message || "Failed to report damage.", "error");
      }
    } catch (err) {
      console.error(err);
      Swal.fire("Error", "Something went wrong.", "error");
    }
  }

  function reloadIssuedTable() {
  fetch("/dashboard/issued/items-table", { headers: { "Accept": "application/json" } })
    .then(res => res.json())
    .then(data => {
      const tbody = document.querySelector(".issued-table tbody");
      if (!tbody) return;
      tbody.innerHTML = data.html;

      // Rebind buttons if you use direct listeners (return/unserviceable)
      if (typeof bindReturnButtons === "function") bindReturnButtons();
      if (typeof bindUnserviceableButtons === "function") bindUnserviceableButtons();
      // Damage uses event delegation, so it’s fine
    })
    .catch(err => console.error("Issued table reload error:", err));
}

        function reloadDamageTable() {
        fetch("/damage-reports/table")
            .then(res => res.text())
            .then(html => {
            const tbody = document.querySelector("#damageTable tbody");
            if (tbody) tbody.innerHTML = html;
            })
            .catch(err => console.error("Damage table reload error:", err));
        }

  function bindDamageButtons() {
    // Event delegation so it still works after table reload
    document.addEventListener("click", async (e) => {
      const btn = e.target.closest(".damaged-btn-issued");
      if (!btn) return;

      const serialNo = btn.dataset.id;
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
          const obs = document.getElementById("damageObservation").value.trim();
          if (!obs) {
            Swal.showValidationMessage("Observation is required.");
            return false;
          }
          return obs;
        }
      });

      if (result.isConfirmed) {
        const observation = result.value;
        reportDamage(serialNo, observation);
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