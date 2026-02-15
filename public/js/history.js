document.addEventListener("DOMContentLoaded", function() {
    // Create a single popover element
    const popover = document.createElement("div");
    popover.classList.add("history-popover");
    popover.innerHTML = `<p class="loading-text">Loading...</p><div class="history-content"></div>`;
    document.body.appendChild(popover);

    document.querySelectorAll(".serial-cell").forEach(cell => {
        let timer;

        cell.addEventListener("mouseenter", function(e) {
            const serial = this.dataset.serial;
            const content = popover.querySelector(".history-content");
            const loading = popover.querySelector(".loading-text");

            // Reset content
            content.innerHTML = "";
            loading.style.display = "block";

            // Position popover
            const rect = this.getBoundingClientRect();
            popover.style.top = `${window.scrollY + rect.bottom + 5}px`;
            popover.style.left = `${window.scrollX + rect.left}px`;
            popover.style.display = "block";

            // Fetch history
            fetch(`/maintenance/history/${serial}`)
                .then(res => res.json())
                .then(data => {
                    loading.style.display = "none";

                    if (data.error) {
                        content.innerHTML = `<p style="color:red">${data.error}</p>`;
                        return;
                    }

                    let html = "<strong>Maintenance History:</strong>";
                    if (data.maintenance.length) {
                        data.maintenance.forEach(m => {
                            html += `<p>${m.date_reported}: ${m.issue_type} (Status: ${m.status || 'N/A'})</p>`;
                        });
                    } else {
                        html += "<p>No maintenance records.</p>";
                    }

                    html += "<strong>Damage History:</strong>";
                    if (data.damage.length) {
                        data.damage.forEach(d => {
                            html += `<p>${d.reported_at}: ${d.damage_type || 'N/A'}</p>`;
                        });
                    } else {
                        html += "<p>No damage records.</p>";
                    }

                    content.innerHTML = html;
                })
                .catch(err => {
                    loading.style.display = "none";
                    content.innerHTML = `<p style="color:red">Failed to load history.</p>`;
                    console.error(err);
                });
        });

        cell.addEventListener("mouseleave", function() {
            // Hide popover after short delay to prevent flicker
            timer = setTimeout(() => {
                popover.style.display = "none";
            }, 200);
        });

        popover.addEventListener("mouseenter", function() {
            clearTimeout(timer);
        });
        popover.addEventListener("mouseleave", function() {
            popover.style.display = "none";
        });
    });
});
