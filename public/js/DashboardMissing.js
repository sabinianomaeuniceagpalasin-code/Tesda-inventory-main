document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".missing-btn-issued").forEach(button => {
        button.addEventListener("click", function () {
            const serial = this.dataset.serial;
            const borrower = this.dataset.borrower;

            Swal.fire({
                title: "Mark item as missing?",
                text: "This item will be recorded as missing.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, mark as missing",
                cancelButtonText: "Cancel"
            }).then((result) => {
                if (!result.isConfirmed) return;

                Swal.fire({
                    title: "Processing...",
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch("/items/missing", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content")
                    },
                    body: JSON.stringify({
                        serial_no: serial,
                        borrower_name: borrower
                    })
                })
                .then(async (response) => {
                    const contentType = response.headers.get("content-type") || "";
                    let data = {};

                    if (contentType.includes("application/json")) {
                        data = await response.json();
                    } else {
                        const text = await response.text();
                        throw new Error(text);
                    }

                    if (!response.ok) {
                        throw {
                            message: data.message || "Request failed.",
                            error: data.error || ""
                        };
                    }

                    return data;
                })
                .then((data) => {
                    Swal.fire({
                        icon: "success",
                        title: "Item Marked Missing",
                        text: data.message || "The item has been recorded successfully."
                    }).then(() => {
                        // stay in issued section
                        window.location.href = "/dashboard?section=issued";
                    });
                })
                .catch((error) => {
                    console.error("Missing Item Error:", error);

                    Swal.fire({
                        icon: "error",
                        title: "Request Failed",
                        html: `
                            <div style="text-align:left;">
                                <p><strong>Message:</strong> ${error.message || 'Something went wrong.'}</p>
                                <p><strong>Error:</strong> ${error.error || 'No detailed error returned.'}</p>
                            </div>
                        `
                    });
                });
            });
        });
    });
});