/* ============================
   DASHBOARD – ISSUED CHART ONLY
============================ */

document.addEventListener("DOMContentLoaded", () => {

  // Generate consistent colors
  const toolNames = [...new Set([...issuedLabels])];
  const colorMap = {};

  toolNames.forEach(tool => {
    const r = Math.floor(Math.random() * 156) + 100;
    const g = Math.floor(Math.random() * 156) + 100;
    const b = Math.floor(Math.random() * 156) + 100;
    colorMap[tool] = `rgb(${r}, ${g}, ${b})`;
  });

  /* ===== ISSUED ITEMS PIE CHART ===== */

  new Chart(document.getElementById("issuedChart"), {
    type: "pie",
    data: {
      labels: issuedLabels,
      datasets: [{
        data: issuedValues,
        backgroundColor: issuedLabels.map(l => colorMap[l]),
        borderColor: "#fff",
        borderWidth: 2,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: "right" },
        tooltip: {
          callbacks: {
            label(context) {
              const total = context.dataset.data.reduce((a, b) => a + b, 0);
              const value = context.parsed;
              const percent = ((value / total) * 100).toFixed(1);
              return `${context.label}: ${value} (${percent}%)`;
            },
          },
        },
      },
    },
  });

});
