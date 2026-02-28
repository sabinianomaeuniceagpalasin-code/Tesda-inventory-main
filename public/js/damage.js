// damage.js (FULL REPLACEMENT)

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

      if (res.status === 401 || res.status === 403) {
        Swal.fire("Unauthorized", "You are not allowed to do this action.", "error");
        return;
      }

      const data = await res.json().catch(() => ({}));

      if (data.success) {
        Swal.fire("Success", data.message, "success");

        // Reload tables (issued + damage)
        if (typeof window.reloadIssuedTable === "function") {
          window.reloadIssuedTable();
        }
        reloadDamageTable();

      } else {
        Swal.fire("Error", data.message || "Failed to report damage.", "error");
      }
    } catch (err) {
      console.error(err);
      Swal.fire("Error", "Something went wrong.", "error");
    }
  }

  function reloadDamageTable() {
    fetch("/damage-reports/table", { headers: { "Accept": "text/html" } })
      .then(res => res.text())
      .then(html => {
        const tbody = document.querySelector("#damageTable tbody");
        if (tbody) tbody.innerHTML = html;
      })
      .catch(err => console.error("Damage table reload error:", err));
  }

  // ✅ Always get serial safely (works after reload)
  function getSerialFromButton(btn) {
    // 1) preferred: data-id on button
    let serial = btn.dataset.id;

    // 2) fallback: read first td (Serial # column)
    if (!serial) {
      const row = btn.closest("tr");
      serial = row?.querySelector("td")?.textContent?.trim();
    }

    return serial || null;
  }

  function bindDamageButtons() {
    // ✅ Event delegation (works even after AJAX table replacement)
    document.addEventListener("click", async (e) => {
      const btn = e.target.closest(".damaged-btn-issued");
      if (!btn) return;

      const serialNo = getSerialFromButton(btn);
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
        reportDamage(serialNo, result.value);
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