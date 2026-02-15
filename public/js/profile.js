document.addEventListener("DOMContentLoaded", function () {
    const profileIcon = document.getElementById("profileIcon");
    const profilePanel = document.getElementById("profilePanel");

    profileIcon.addEventListener("click", () => {
        profilePanel.classList.toggle("hidden");
    });

    document.addEventListener("click", (e) => {
        if (
            !profileIcon.contains(e.target) &&
            !profilePanel.contains(e.target)
        ) {
            profilePanel.classList.add("hidden");
        }
    });
});
