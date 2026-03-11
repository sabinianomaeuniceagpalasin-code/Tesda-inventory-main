document.addEventListener('DOMContentLoaded', function () {
    const exportDamageBtn = document.getElementById('exportDamageBtn');
    const damageSearchInput = document.getElementById('damageSearchInput');

    if (exportDamageBtn) {
        exportDamageBtn.addEventListener('click', function () {
            const search = damageSearchInput ? damageSearchInput.value.trim() : '';

            const url = new URL('/damage-reports/export/pdf', window.location.origin);
            url.searchParams.append('search', search);

            window.open(url.toString(), '_blank');
        });
    }
});