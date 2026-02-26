document.addEventListener('DOMContentLoaded', function () {

    // Approve
    document.querySelectorAll('.approve-item').forEach(button => {
        button.addEventListener('click', function () {
            let id     = this.dataset.id;
            let name   = this.dataset.name;
            let serial = this.dataset.serial;
            let qty    = parseInt(this.dataset.qty);
            let type   = this.dataset.type;

            Swal.fire({
                title: 'Approve this request?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Approve',
                confirmButtonColor: '#198754'
            }).then(result => {
                if (!result.isConfirmed) return;

                fetch(`/item-approval/${id}/approve`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    Swal.fire({ icon:'success', title:'Approved!' })
                        .then(() => openPrintPreview(name, serial, qty, type));
                });
            });
        });
    });

    // Reject
    document.querySelectorAll('.reject-item').forEach(button => {
        button.addEventListener('click', function () {
            let id = this.dataset.id;

            Swal.fire({
                title: 'Reject this request?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Reject',
                confirmButtonColor: '#dc3545'
            }).then(result => {
                if (!result.isConfirmed) return;

                fetch(`/item-approval/${id}/reject`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                })
                .then(res => res.json())
                .then(() => {
                    Swal.fire({ icon:'success', title:'Rejected' }).then(() => location.reload());
                });
            });
        });
    });

});

// Open Print Preview
function openPrintPreview(name, serial, qty, type) {
    const container = document.getElementById('printArea');
    container.innerHTML = '';

    let match = serial.match(/^(.+?)(\d+)$/);
    let prefix = match[1];
    let start  = parseInt(match[2]);

    for (let i = 0; i < qty; i++) {
        let currentSerial = prefix + String(start + i).padStart(match[2].length, '0');
        let imgSrc = type==='qr'
            ? `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${currentSerial}`
            : `https://barcode.tec-it.com/barcode.ashx?data=${currentSerial}&code=Code128`;

        container.innerHTML += `
            <div class="sticker">
                <div style="font-size:10pt;font-weight:600">${name}</div>
                <img src="${imgSrc}">
                <div style="font-size:9pt;margin-top:2mm">${currentSerial}</div>
            </div>
        `;
    }

    document.getElementById('printModal').style.display = 'flex';
}

function closePrint() {
    document.getElementById('printModal').style.display = 'none';
}