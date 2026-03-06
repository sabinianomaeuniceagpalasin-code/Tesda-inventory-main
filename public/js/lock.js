document.addEventListener("DOMContentLoaded", function () {
    let idleSeconds = 0;
    const lockAfterSeconds = 30;
    let isLocked = false;

    const overlay = document.getElementById("idleLockOverlay");
    const unlockForm = document.getElementById("unlockForm");
    const unlockPassword = document.getElementById("unlockPassword");
    const unlockError = document.getElementById("unlockError");

    if (!overlay || !unlockForm || !unlockPassword || !unlockError) return;

    function resetTimer() {
        if (!isLocked) {
            idleSeconds = 0;
        }
    }

    function lockScreen() {
        isLocked = true;
        sessionStorage.setItem("is_screen_locked", "true");
        overlay.style.display = "flex";
        unlockPassword.value = "";
        unlockError.textContent = "";
        setTimeout(() => unlockPassword.focus(), 100);
    }

    function unlockScreenUI() {
        isLocked = false;
        idleSeconds = 0;
        sessionStorage.removeItem("is_screen_locked");
        overlay.style.display = "none";
        unlockPassword.value = "";
        unlockError.textContent = "";
    }

    // restore lock after refresh
    if (sessionStorage.getItem("is_screen_locked") === "true") {
        lockScreen();
    }

    ['mousemove', 'keydown', 'click', 'scroll', 'touchstart', 'mousedown'].forEach(event => {
        document.addEventListener(event, resetTimer, true);
    });

    setInterval(() => {
        if (!isLocked) {
            idleSeconds++;
            if (idleSeconds >= lockAfterSeconds) {
                lockScreen();
            }
        }
    }, 1000);

    unlockForm.addEventListener("submit", function (e) {
        e.preventDefault();

        unlockError.textContent = "";

        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const password = unlockPassword.value;

        fetch("/unlock-screen", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": token,
                "Accept": "application/json"
            },
            body: JSON.stringify({ password: password })
        })
        .then(async response => {
            const data = await response.json();

            if (response.ok && data.success) {
                unlockScreenUI();
            } else {
                unlockError.textContent = data.message || "Incorrect password.";
            }
        })
        .catch(error => {
            console.error(error);
            unlockError.textContent = "Request failed.";
        });
    });
});