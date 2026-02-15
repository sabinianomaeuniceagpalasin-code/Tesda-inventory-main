document.addEventListener("DOMContentLoaded", function () {
    const bell = document.getElementById("notifBell");
    const panel = document.getElementById("notificationPanel");

    // Toggle notification panel
    bell.addEventListener("click", () => {
        panel.classList.toggle("hidden");
    });

    // Hide panel when clicking outside
    document.addEventListener("click", (e) => {
        if (!bell.contains(e.target) && !panel.contains(e.target)) {
            panel.classList.add("hidden");
        }
    });

    // Notification "View" link click
    document.querySelectorAll(".notif-view").forEach((link) => {
        link.addEventListener("click", function (e) {
            e.preventDefault();
            const target = this.dataset.target;

            // Switch active menu item
            document
                .querySelectorAll("nav.menu a")
                .forEach((a) => a.classList.remove("active"));
            const menuLink = document.querySelector(
                `nav.menu a[data-target='${target}']`
            );
            if (menuLink) menuLink.classList.add("active");

            // Switch visible section
            document
                .querySelectorAll(".content-section")
                .forEach((section) => section.classList.remove("active"));
            const section = document.getElementById(target);
            if (section) section.classList.add("active");

            // Close the notification panel
            panel.classList.add("hidden");
        });
    });
});
