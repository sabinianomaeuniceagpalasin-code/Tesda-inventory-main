// public/js/make-available.js

document.addEventListener("click", async function (e) {
    const btn = e.target.closest(".make-available-btn");
    if (!btn) return;

    const serial = btn.dataset.serial;
    if (!serial) {
        Swal.fire("Error", "Serial number not found.", "error");
        return;
    }

    const result = await Swal.fire({
        title: "Make item available?",
        text: `This will set item ${serial} back to Available.`,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Yes",
        cancelButtonText: "Cancel",
    });

    if (!result.isConfirmed) return;

    btn.disabled = true;

    try {
        const res = await fetch(`/maintenance/make-available/${encodeURIComponent(serial)}`, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                "Accept": "application/json",
            },
        });

        const data = await res.json();

        if (!res.ok) {
            throw new Error(data.error || data.message || "Failed to update item.");
        }

        await Swal.fire({
            title: "Success",
            text: data.message || "Item is now available.",
            icon: "success",
            timer: 1200,
            showConfirmButton: false,
        });

        // ✅ after reload, open Maintenance section again
        localStorage.setItem("activeSection", "reports");
        window.location.reload();

    } catch (err) {
        console.error(err);
        Swal.fire("Error", err.message || "Failed to update item.", "error");
        btn.disabled = false;
    }
});