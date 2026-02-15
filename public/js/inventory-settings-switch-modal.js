document.querySelectorAll('#approvalTabs .nav-link').forEach(tab => {
    tab.addEventListener('click', function () {
        document.querySelectorAll('#approvalTabs .nav-link').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('show', 'active'));

        this.classList.add('active');
        if (this.textContent.includes('QR')) {
            document.getElementById('qrRequests').classList.add('show', 'active');
        } else if (this.textContent.includes('Bar')) {
            document.getElementById('barcodeRequests').classList.add('show', 'active');
        }
    });
});