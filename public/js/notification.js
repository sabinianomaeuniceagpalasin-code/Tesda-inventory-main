document.addEventListener("DOMContentLoaded", function () {
    const bell = document.getElementById("notifBell");
    const panel = document.getElementById("notifDropdown");
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");
    const markAllBtn = document.getElementById("markAllReadBtn");

    if (!bell || !panel) return;

    // Toggle dropdown
    bell.addEventListener("click", function (e) {
        e.stopPropagation();
        panel.classList.toggle("show");
    });

    // Prevent closing when clicking inside panel
    panel.addEventListener("click", function (e) {
        e.stopPropagation();
    });

    // Close when clicking outside
    document.addEventListener("click", function () {
        panel.classList.remove("show");
    });

    // Single notification click
    panel.querySelectorAll(".notif-card").forEach((card) => {
        card.addEventListener("click", async function (e) {
            const recipientId = this.dataset.recipientId;
            const isUnread = this.classList.contains("unread");
            const href = this.getAttribute("href");

            // mark as read first
            if (recipientId && isUnread) {
                try {
                    const res = await fetch(`/notifications/${recipientId}/read`, {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": csrfToken,
                            "Accept": "application/json",
                        },
                    });

                    if (res.ok) {
                        this.classList.remove("unread");

                        const dot = this.querySelector(".notif-dot");
                        if (dot) dot.remove();

                        const badge = document.querySelector(".notif-badge");
                        if (badge) {
                            let count = parseInt(badge.textContent, 10) || 0;
                            count = Math.max(0, count - 1);

                            if (count <= 0) {
                                badge.remove();
                            } else {
                                badge.textContent = count > 99 ? "99+" : count;
                            }
                        }

                        const unreadText = panel.querySelector(".notif-header p");
                        if (unreadText) {
                            const current = parseInt(unreadText.textContent, 10) || 0;
                            const next = Math.max(0, current - 1);
                            unreadText.textContent = `${next} unread`;
                        }
                    }
                } catch (err) {
                    console.error("Failed to mark notification as read:", err);
                }
            }

            // allow real navigation if href exists and is not javascript:void(0)
            if (href && href !== "#" && href !== "javascript:void(0)") {
                return;
            }

            e.preventDefault();
            panel.classList.remove("show");
        });
    });

    // Mark all as read
    if (markAllBtn) {
        markAllBtn.addEventListener("click", async function (e) {
            e.preventDefault();

            try {
                const res = await fetch("/notifications/read-all", {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                        "Accept": "application/json",
                    },
                });

                if (res.ok) {
                    panel.querySelectorAll(".notif-card.unread").forEach((card) => {
                        card.classList.remove("unread");
                    });

                    panel.querySelectorAll(".notif-dot").forEach((dot) => dot.remove());

                    const badge = document.querySelector(".notif-badge");
                    if (badge) badge.remove();

                    const unreadText = panel.querySelector(".notif-header p");
                    if (unreadText) unreadText.textContent = "0 unread";
                }
            } catch (err) {
                console.error("Failed to mark all as read:", err);
            }
        });
    }
});