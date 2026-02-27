// issued-unserviceable.js

function bindUnserviceableButtons() {
  document.addEventListener("click", async function (e) {
    const btn = e.target.closest(".unserviceable-btn-issued");
    if (!btn) return;

    // issue_id is stored in return button
    const row = btn.closest("tr");
    const issueId = row.querySelector(".return-btn-issued")?.dataset.id;

    if (!issueId) {
      Swal.fire("Error", "Issue ID not found.", "error");
      return;
    }

    const result = await Swal.fire({
      title: "Mark Item as Unserviceable",
      html: `
        <textarea id="unserviceableReason"
          class="swal2-textarea"
          placeholder="Enter reason why item is unserviceable (required)"></textarea>
      `,
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Submit",
      cancelButtonText: "Cancel",
      preConfirm: () => {
        const reason = document.getElementById("unserviceableReason").value.trim();
        if (!reason) {
          Swal.showValidationMessage("Reason is required.");
          return false;
        }
        return reason;
      }
    });

    if (result.isConfirmed) {
      submitUnserviceable(issueId, result.value);
    }
  });
}

function submitUnserviceable(issueId, reason) {
  fetch(`/issued/unserviceable/${issueId}`, {
    method: "POST",
    headers: {
      "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
      "Content-Type": "application/json",
      "Accept": "application/json"
    },
    body: JSON.stringify({ reason })
  })
    .then(res => res.json())
    .then(data => {
      if (data.status === "error") {
        Swal.fire("Error", data.message, "error");
        return;
      }

      Swal.fire({
        title: "Success",
        text: data.message,
        icon: "success",
        timer: 1500,
        showConfirmButton: false
      });

      reloadIssuedTable();
      refreshFormTable();
    })
    .catch(err => {
      console.error(err);
      Swal.fire("Error", "Something went wrong.", "error");
    });
}

document.addEventListener("DOMContentLoaded", function () {
  bindUnserviceableButtons();
});