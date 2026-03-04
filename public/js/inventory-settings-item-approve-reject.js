document.addEventListener("DOMContentLoaded", function () {
  const meta = document.querySelector('meta[name="csrf-token"]');
  if (!meta) return console.error("CSRF token meta tag not found.");

  const csrfToken = meta.getAttribute("content");

  async function sendBatchAction(batchId, action) {
    const url =
      action === "approve"
        ? `/item-approval/batch/${batchId}/approve`
        : `/item-approval/batch/${batchId}/reject`;

    const confirm = await Swal.fire({
      title: action === "approve" ? "Approve this batch?" : "Reject this batch?",
      text:
        action === "approve"
          ? `This will approve ALL requests in Batch #${batchId}.`
          : `This will reject ALL requests in Batch #${batchId}.`,
      icon: action === "approve" ? "question" : "warning",
      showCancelButton: true,
      confirmButtonText: action === "approve" ? "Yes, Approve" : "Yes, Reject",
      confirmButtonColor: action === "approve" ? "#198754" : "#dc3545",
    });

    if (!confirm.isConfirmed) return;

    const res = await fetch(url, {
      method: "POST",
      headers: {
        "X-CSRF-TOKEN": csrfToken,
        Accept: "application/json",
      },
    });

    const data = await res.json().catch(() => null);

    if (!res.ok) {
      Swal.fire("Error", data?.message || "Request failed.", "error");
      return;
    }

    Swal.fire({
      icon: "success",
      title: action === "approve" ? "Batch Approved!" : "Batch Rejected!",
      timer: 1500,
      showConfirmButton: false,
    }).then(() => location.reload());
  }

  document.addEventListener("click", (e) => {
    const approveBtn = e.target.closest(".approve-batch");
    const rejectBtn = e.target.closest(".reject-batch");

    if (approveBtn) {
      const batchId = approveBtn.dataset.batch;
      sendBatchAction(batchId, "approve");
    }

    if (rejectBtn) {
      const batchId = rejectBtn.dataset.batch;
      sendBatchAction(batchId, "reject");
    }
  });
});