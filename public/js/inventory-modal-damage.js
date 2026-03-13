function markInventoryDamage() {
    const serialEl = document.getElementById('modal-serial');
    const itemEl = document.getElementById('modal-item');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const serialNo = serialEl ? serialEl.textContent.trim() : '';
    const itemName = itemEl ? itemEl.textContent.trim() : 'this item';

    if (!serialNo) {
        Swal.fire({
            icon: 'error',
            title: 'Missing Serial Number',
            text: 'Unable to find the selected item serial number.',
            confirmButtonColor: '#2563eb'
        });
        return;
    }

    Swal.fire({
        title: 'Mark as Damaged',
        html: `
            <div class="damage-modal-content">
                <div class="damage-modal-icon-wrap">
                    <div class="damage-modal-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>

                <p class="damage-modal-subtitle">
                    Report the damage found on
                    <span class="damage-item-name">${itemName}</span>
                    upon arrival.
                </p>

                <div class="damage-input-group">
                    <label for="inventoryDamageReason" class="damage-label">Reason / Observation</label>
                    <textarea 
                        id="inventoryDamageReason"
                        class="damage-textarea"
                        placeholder="Example: Dents, cracked screen, broken handle..."
                        maxlength="255"
                    ></textarea>
                    <div class="damage-counter">
                        <span id="damageCharCount">0</span>/255
                    </div>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Save Report',
        cancelButtonText: 'Cancel',
        focusConfirm: false,
        width: 600,
        customClass: {
            popup: 'damage-swal-popup',
            title: 'damage-swal-title',
            confirmButton: 'damage-swal-confirm',
            cancelButton: 'damage-swal-cancel'
        },
        didOpen: () => {
            const textarea = document.getElementById('inventoryDamageReason');
            const counter = document.getElementById('damageCharCount');

            if (textarea && counter) {
                textarea.addEventListener('input', function () {
                    counter.textContent = this.value.length;
                });

                textarea.focus();
            }
        },
        preConfirm: () => {
            const reason = document.getElementById('inventoryDamageReason')?.value.trim();

            if (!reason) {
                Swal.showValidationMessage('Please enter the damage reason.');
                return false;
            }

            return reason;
        }
    }).then((result) => {
        if (!result.isConfirmed) return;

        const reason = result.value;

        Swal.fire({
            title: 'Saving...',
            text: 'Please wait while the damage report is being created.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch('/inventory/item/mark-damaged-upon-arrival', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                serial_no: serialNo,
                reason: reason
            })
        })
        .then(async response => {
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || 'Something went wrong.');
            }
            return data;
        })
        .then(data => {
            Swal.fire({
                icon: 'success',
                title: 'Damage Report Saved',
                html: `
                    <div style="text-align:left; line-height:1.7;">
                        <div><strong>Item:</strong> ${itemName}</div>
                        <div><strong>Serial No.:</strong> ${serialNo}</div>
                        <div><strong>Saved Observation:</strong> ${data.data.observation}</div>
                    </div>
                `,
                confirmButtonText: 'OK',
                confirmButtonColor: '#2563eb'
            }).then(() => {
                const statusEl = document.getElementById('modal-status');
                if (statusEl) {
                    statusEl.textContent = 'Damaged';
                    statusEl.className = 'detail-value text-red';
                }

                window.location.reload();
            });
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Save Failed',
                text: error.message || 'Failed to save damage report.',
                confirmButtonColor: '#dc2626'
            });
        });
    });
}