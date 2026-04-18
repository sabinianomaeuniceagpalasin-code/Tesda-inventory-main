// public/js/damage.js
//
// Features:
// 1) Report Damage from Issued table (.damaged-btn-issued)
//    — Now includes an optional image upload field inside the SweetAlert modal
//    — Sends as multipart/form-data (FormData) instead of JSON so the file is included
// 2) Create Maintenance Ticket from Damage table (.maintenance-btn-issued)
// 3) Reload page and return to Damage Report section after creating maintenance ticket

(() => {
    // ── CSRF helper ──────────────────────────────────────────────────────────────
    const csrf = () =>
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content") || "";

    // ============================================================================
    //  safeLoadTable
    //  Fetches an HTML fragment from `url` and injects it into `tbodySelector`.
    //  Expects the endpoint to return plain <tr>…</tr> rows, NOT a full HTML doc.
    // ============================================================================
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

                Swal.fire(
                    "Error",
                    `Failed to reload table (${res.status}).`,
                    "error",
                );
                return;
            }

            // Guard: never inject a full HTML document into a tbody
            if (/<html|<body/i.test(html)) {
                console.error(
                    "Blocked full HTML document injection for:",
                    url,
                    html,
                );
                tbody.innerHTML = `<tr><td colspan="99" style="text-align:center; padding:16px;">
          Unexpected response returned. Check route/controller.
        </td></tr>`;
                Swal.fire(
                    "Error",
                    "Unexpected response while reloading table.",
                    "error",
                );
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

    // Expose reload helpers globally so other scripts can call them if needed
    window.reloadDamageTable = () =>
        safeLoadTable("#damageTable tbody", "/dashboard/damage/table-html");

    window.reloadMaintenanceTable = () =>
        safeLoadTable(
            "#maintenanceTable tbody",
            "/dashboard/maintenance/table-html",
        );

    // ============================================================================
    //  reportDamage
    //  Sends the damage report to /damage-reports/store.
    //
    //  ✅ CHANGED: Now accepts a `file` parameter (File object or null).
    //     Uses FormData instead of JSON so the image can be attached.
    //     Do NOT set Content-Type manually — the browser sets it automatically
    //     with the correct multipart boundary when using FormData.
    // ============================================================================
    async function reportDamage(serialNo, observation, file = null) {
        // ── Build FormData so we can attach the optional image ───────────────────
        const formData = new FormData();
        formData.append("serial_no", serialNo);
        formData.append("observation", observation);

        // ✅ Only append the image key when the user actually selected a file
        if (file) {
            formData.append("image", file);
        }

        const res = await fetch("/damage-reports/store", {
            method: "POST",
            headers: {
                // ✅ X-CSRF-TOKEN header is still needed for Laravel's CSRF middleware
                // ✅ Do NOT set Content-Type — FormData sets it automatically (multipart/form-data)
                "X-CSRF-TOKEN": csrf(),
                Accept: "application/json",
            },
            credentials: "same-origin",
            body: formData, // ✅ FormData, not JSON.stringify()
        });

        const data = await res.json().catch(() => ({}));

        if (res.status === 401 || res.status === 403) {
            Swal.fire(
                "Unauthorized",
                "You are not allowed to do this action.",
                "error",
            );
            return;
        }

        if (!res.ok || !data.success) {
            Swal.fire(
                "Error",
                data.message || "Failed to report damage.",
                "error",
            );
            return;
        }

        Swal.fire({
            title: "Success",
            text: data.message || "Damage reported.",
            icon: "success",
            timer: 2000,
            showConfirmButton: false,
        }).then(() => {
            // ✅ Navigate back to the Damage Report section after reload
            localStorage.setItem("activeSection", "damaged");
            window.location.reload();
        });
    }

    // ============================================================================
    //  createTicketFromDamage
    //  Sends a POST to /damage/move/{damageId} to convert a damage report into
    //  a maintenance ticket. No file upload needed here.
    // ============================================================================
    async function createTicketFromDamage(damageId) {
        const res = await fetch(
            `/damage/move/${encodeURIComponent(damageId)}`,
            {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": csrf(),
                    Accept: "application/json",
                },
                credentials: "same-origin",
            },
        );

        const data = await res.json().catch(() => ({}));

        // 409 = ticket already exists for this damage report
        if (res.status === 409) {
            Swal.fire(
                "Already Ticketed",
                data.message || "Ticket already exists.",
                "info",
            );
            return;
        }

        if (res.status === 401 || res.status === 403) {
            Swal.fire(
                "Unauthorized",
                data.message || "You are not allowed.",
                "error",
            );
            return;
        }

        if (!res.ok || !data.success) {
            Swal.fire(
                "Error",
                data.message || "Failed to create maintenance ticket.",
                "error",
            );
            return;
        }

        Swal.fire({
            title: "Ticket Created",
            text: data.message || "Maintenance ticket created.",
            icon: "success",
            timer: 1200,
            showConfirmButton: false,
        }).then(() => {
            // ✅ Return to the Damage section after the ticket is created
            localStorage.setItem("activeSection", "damaged");
            window.location.reload();
        });
    }

    // ============================================================================
    //  getSerialFromIssuedButton
    //  Reads the serial number from the button's data-id attribute,
    //  falling back to the first <td> in the row if data-id is missing.
    // ============================================================================
    function getSerialFromIssuedButton(btn) {
        let serial = btn.dataset.id;
        if (!serial) {
            const row = btn.closest("tr");
            serial = row?.querySelector("td")?.textContent?.trim();
        }
        return serial || null;
    }

    // ============================================================================
    //  Global click handler (event delegation)
    //  Handles two button types:
    //    .damaged-btn-issued      — opens SweetAlert to report damage (with image)
    //    .maintenance-btn-issued  — creates a maintenance ticket from a damage report
    // ============================================================================
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
                icon: "warning",
                customClass: {
                    popup: "damage-swal-popup",
                    confirmButton: "damage-swal-confirm",
                    cancelButton: "damage-swal-cancel",
                },
                html: `
                <p style="font-size:13px; color:#64748b; margin:0 0 14px;">
                    Serial #: <b style="color:#1e3a8a;">${serialNo}</b>
                </p>

                <label style="font-size:13px; font-weight:500; color:#374151; display:block; margin-bottom:6px;">
                    Cause / Observation <span style="color:#dc2626;">*</span>
                </label>
                <textarea
                    id="damageObservation"
                    class="swal2-textarea"
                    placeholder="Describe the damage in detail..."
                    maxlength="500"
                    oninput="document.getElementById('dmgCount').textContent=this.value.length"
                ></textarea>
                <div class="damage-char-counter"><span id="dmgCount">0</span> / 500</div>

                <div class="damage-upload-zone" onclick="document.getElementById('damageImageInput').click()">
                    <div class="upload-icon">📎</div>
                    <div class="upload-label">Click to upload or drag & drop</div>
                    <div class="upload-hint">JPEG, PNG, GIF, WEBP — max 5MB</div>
                    <input
                        type="file"
                        id="damageImageInput"
                        accept="image/jpeg,image/png,image/gif,image/webp"
                    />
                </div>
                <img id="damageImagePreview" class="damage-image-preview" />
            `,
                showCancelButton: true,
                confirmButtonText: "Submit",
                cancelButtonText: "Cancel",
                didOpen: () => {
                    const fileInput =
                        document.getElementById("damageImageInput");
                    const preview =
                        document.getElementById("damageImagePreview");

                    fileInput?.addEventListener("change", () => {
                        const file = fileInput.files[0];
                        if (file) {
                            preview.src = URL.createObjectURL(file);
                            preview.style.display = "block";
                        } else {
                            preview.src = "";
                            preview.style.display = "none";
                        }
                    });
                },
                preConfirm: () => {
                    const obs = document
                        .getElementById("damageObservation")
                        ?.value?.trim();
                    const file =
                        document.getElementById("damageImageInput")?.files[0] ||
                        null;

                    if (!obs) {
                        Swal.showValidationMessage("Observation is required.");
                        return false;
                    }

                    if (file && file.size > 5 * 1024 * 1024) {
                        Swal.showValidationMessage(
                            "Image must be 5MB or smaller.",
                        );
                        return false;
                    }

                    return { obs, file };
                },
            });

            if (result.isConfirmed) {
                const { obs, file } = result.value;
                await reportDamage(serialNo, obs, file);
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
