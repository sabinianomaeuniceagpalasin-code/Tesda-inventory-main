document.addEventListener("DOMContentLoaded", () => {
    if (!window.usageLabels?.length || !window.usageValues?.length) {
        console.warn("Usage Trends: no data available");
        return;
    }

    const usageData = usageLabels
        .map((label, i) => ({ label, value: usageValues[i] }))
        .sort((a, b) => b.value - a.value);

    const top5 = usageData.slice(0, 5);
    const topLabels = top5.map((d) => d.label);
    const topValues = top5.map((d) => d.value);

    const usageColorMap = {};
    usageLabels.forEach((tool) => {
        if (!usageColorMap[tool]) {
            const r = Math.floor(Math.random() * 156) + 80;
            const g = Math.floor(Math.random() * 156) + 80;
            const b = Math.floor(Math.random() * 156) + 80;
            usageColorMap[tool] = `rgb(${r}, ${g}, ${b})`;
        }
    });

    const usageChartEl = document.getElementById("usageChart");
    if (usageChartEl) {
        new Chart(usageChartEl, {
            type: "bar",
            data: {
                labels: topLabels,
                datasets: [{ data: topValues, backgroundColor: topLabels.map((l) => usageColorMap[l]) }],
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } },
            },
        });
    }

    let usageModalChart = null;

    function renderUsageModalChart(labels, values) {
        const ctx = document.getElementById("usageModalChart");
        if (!ctx) return;

        if (usageModalChart) usageModalChart.destroy();

        usageModalChart = new Chart(ctx, {
            type: "bar",
            data: {
                labels,
                datasets: [{ data: values, backgroundColor: labels.map((l) => usageColorMap[l]) }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } },
            },
        });
    }

    const usageModal = document.getElementById("usageModal");
    const usageBox = document.getElementById("usageTrendsBox");
    const closeUsageBtn = document.getElementById("closeUsageModal");
    const searchInput = document.getElementById("usageSearch");

    if (usageBox && usageModal) {
        usageBox.addEventListener("click", () => {
            usageModal.style.display = "flex";
            renderUsageModalChart(
                usageData.map((d) => d.label),
                usageData.map((d) => d.value)
            );
        });
    }

    if (closeUsageBtn && usageModal) {
        closeUsageBtn.addEventListener("click", () => {
            usageModal.style.display = "none";
        });
    }

    if (usageModal) {
        usageModal.addEventListener("click", (e) => {
            if (e.target === usageModal) usageModal.style.display = "none";
        });
    }

    if (searchInput) {
        searchInput.addEventListener("input", function () {
            const keyword = this.value.toLowerCase();
            const filtered = usageData.filter((d) => d.label.toLowerCase().includes(keyword));
            renderUsageModalChart(
                filtered.map((d) => d.label),
                filtered.map((d) => d.value)
            );
        });
    }
});

let issuedChart = null;

function renderIssuedChart(labels, values) {
    const ctx = document.getElementById("issuedModalChart");
    if (!ctx) return;

    if (issuedChart) issuedChart.destroy();

    issuedChart = new Chart(ctx, {
        type: "pie",
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: labels.map(() => {
                    const r = Math.floor(Math.random() * 156) + 80;
                    const g = Math.floor(Math.random() * 156) + 80;
                    const b = Math.floor(Math.random() * 156) + 80;
                    return `rgb(${r}, ${g}, ${b})`;
                }),
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: "bottom" } },
        },
    });
}

function openIssuedModal() {
    const modal = document.getElementById("issuedModal");
    if (!modal) return;
    modal.style.display = "flex";
    setTimeout(() => renderIssuedChart(issuedLabels, issuedValues), 100);
}

const closeIssuedBtn = document.getElementById("closeIssuedModal");
if (closeIssuedBtn) {
    closeIssuedBtn.addEventListener("click", () => {
        const modal = document.getElementById("issuedModal");
        if (modal) modal.style.display = "none";
    });
}