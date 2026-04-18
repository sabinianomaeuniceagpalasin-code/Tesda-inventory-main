const CSRF = document.querySelector('meta[name="csrf-token"]').content;

// ── Open archive modal & load archived rows ───────────────
function openArchiveModal() {
    document.getElementById('archiveModal').style.display = 'flex';
    loadArchivedItems();
}

function closeArchiveModal() {
    document.getElementById('archiveModal').style.display = 'none';
}

function loadArchivedItems() {
    const tbody = document.getElementById('archive-table-body');
    tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:20px;">Loading…</td></tr>';

    fetch('/inventory/archived', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(r => r.json())
        .then(data => {
            tbody.innerHTML = data.html;
            bindArchiveTableButtons();
        })
        .catch(() => {
            tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;color:red;">Failed to load archived items.</td></tr>';
        });
}

// Bind restore/force-delete buttons after HTML is injected
function bindArchiveTableButtons() {
    document.querySelectorAll('#archive-table-body .restore-item-btn').forEach(btn => {
        btn.addEventListener('click', () => restoreItem(btn.dataset.serial));
    });
    document.querySelectorAll('#archive-table-body .force-delete-item-btn').forEach(btn => {
        btn.addEventListener('click', () => forceDeleteItem(btn.dataset.serial));
    });
}

// ── Soft-delete (archive) an active item ─────────────────
function archiveItem(serial_no) {
    Swal.fire({
        title: 'Archive this item?',
        text: `Serial: ${serial_no} will be moved to the archive. You can restore it later.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e67e22',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, archive it',
    }).then(result => {
        if (!result.isConfirmed) return;

        fetch(`/inventory/${serial_no}/archive`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Archived!', data.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message || 'Something went wrong.', 'error');
                }
            })
            .catch(() => Swal.fire('Error', 'Request failed.', 'error'));
    });
}

// ── Restore an archived item ──────────────────────────────
function restoreItem(serial_no) {
    Swal.fire({
        title: 'Restore this item?',
        text: `Serial: ${serial_no} will be moved back to the active inventory.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#27ae60',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, restore it',
    }).then(result => {
        if (!result.isConfirmed) return;

        fetch(`/inventory/${serial_no}/restore`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Restored!', data.message, 'success').then(() => loadArchivedItems());
                } else {
                    Swal.fire('Error', data.message || 'Something went wrong.', 'error');
                }
            })
            .catch(() => Swal.fire('Error', 'Request failed.', 'error'));
    });
}

// ── Permanently delete ────────────────────────────────────
function forceDeleteItem(serial_no) {
    Swal.fire({
        title: 'Delete permanently?',
        text: `Serial: ${serial_no} will be removed forever. This cannot be undone.`,
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#c0392b',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete forever',
    }).then(result => {
        if (!result.isConfirmed) return;

        fetch(`/inventory/${serial_no}/force-delete`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Deleted!', data.message, 'success').then(() => loadArchivedItems());
                } else {
                    Swal.fire('Error', data.message || 'Something went wrong.', 'error');
                }
            })
            .catch(() => Swal.fire('Error', 'Request failed.', 'error'));
    });
}

// ── Wire the existing "Archive" button in inventory controls ─
document.addEventListener('DOMContentLoaded', () => {
    const archiveBtn = document.getElementById('archiveInventoryBtn');
    if (archiveBtn) {
        archiveBtn.addEventListener('click', openArchiveModal);
    }
});