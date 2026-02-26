document.addEventListener("DOMContentLoaded", function () {
    const bell = document.getElementById("notifBell");
    const panel = document.getElementById("notificationPanel");

    // Toggle panel
    bell.addEventListener("click", () => panel.classList.toggle("hidden"));

    // Hide panel on outside click
    document.addEventListener("click", (e) => {
        if (!bell.contains(e.target) && !panel.contains(e.target)) {
            panel.classList.add("hidden");
        }
    });

    // Mark notification as read and redirect/tab switch
    document.querySelectorAll(".notif-view").forEach((link) => {
        link.addEventListener("click", async function (e) {
            e.preventDefault();
            const notifItem = this.closest(".notif-item");
            const notifId = notifItem.dataset.id;

            // Mark as read via backend
            if (notifId && notifItem.classList.contains("unread")) {
                try {
                    const token = document.querySelector('meta[name="csrf-token"]').getAttribute("content");
                    const res = await fetch(`/notifications/${notifId}/read`, {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": token,
                            "Accept": "application/json",
                        },
                    });

                    if (res.ok) {
                        notifItem.classList.remove("unread");

                        // Update bell badge
                        const badge = document.querySelector(".notif-badge");
                        if (badge) {
                            let count = parseInt(badge.textContent);
                            count = Math.max(0, count - 1);
                            if (count === 0) badge.remove();
                            else badge.textContent = count;
                        }
                    }
                } catch (err) {
                    console.error(err);
                }
            }

            // Redirect if approval
            if (this.dataset.url) {
                window.location.href = this.dataset.url;
                return;
            }

            // Switch dashboard tab
            if (this.dataset.target) {
                document.querySelectorAll("nav.menu a").forEach((a) => a.classList.remove("active"));
                const menuLink = document.querySelector(`nav.menu a[data-target='${this.dataset.target}']`);
                if (menuLink) menuLink.classList.add("active");

                document.querySelectorAll(".content-section").forEach((s) => s.classList.remove("active"));
                const section = document.getElementById(this.dataset.target);
                if (section) section.classList.add("active");
            }

            panel.classList.add("hidden");
        });
    });
});