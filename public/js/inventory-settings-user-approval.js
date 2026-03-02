document.addEventListener("DOMContentLoaded", () => {
  // Approve
  document.querySelectorAll(".approve-user").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();

      const form = btn.closest("form");
      if (!form) return;

      Swal.fire({
        title: "Approve this user?",
        text: "This will allow the user to log in.",
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Yes, approve",
        cancelButtonText: "Cancel",
      }).then((result) => {
        if (result.isConfirmed) {
          form.submit();
        }
      });
    });
  });

  // Reject
  document.querySelectorAll(".reject-user").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();

      const form = btn.closest("form");
      if (!form) return;

      Swal.fire({
        title: "Reject this user?",
        text: "This will delete the user account.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, reject",
        cancelButtonText: "Cancel",
        confirmButtonColor: "#d33",
      }).then((result) => {
        if (result.isConfirmed) {
          form.submit();
        }
      });
    });
  });
});