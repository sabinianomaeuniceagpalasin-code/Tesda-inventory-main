const form = document.getElementById("createAccountForm");
let emailExists = false;
let debounceTimer = null;

/* -----------------------------------------
   ERROR HANDLING
------------------------------------------ */
function setError(input, message) {
    // Look for the nearest .error inside the form-group
    const container = input.closest(".form-group") || input.closest(".role");
    const errorSmall = container ? container.querySelector("small.error") : null;

    if (errorSmall) {
        errorSmall.textContent = message;
    }
}

function clearError(input) {
    const container = input.closest(".form-group") || input.closest(".role");
    const errorSmall = container ? container.querySelector("small.error") : null;

    if (errorSmall) errorSmall.textContent = "";
}

/* -----------------------------------------
   SHOW / HIDE PASSWORD
------------------------------------------ */
function togglePassword(id, icon) {
    const field = document.getElementById(id);
    field.type = field.type === "password" ? "text" : "password";
    icon.textContent = field.type === "password" ? "👁" : "🙈";
}

/* -----------------------------------------
   REAL-TIME EMAIL CHECK (AJAX + DEBOUNCE)
------------------------------------------ */
document.getElementById("email").addEventListener("keyup", function () {
    const email = this.value;
    const emailError = document.getElementById("emailError");

    emailError.style.color = "red";

    if (debounceTimer) clearTimeout(debounceTimer);

    debounceTimer = setTimeout(() => {
        if (email.length < 5) {
            emailError.textContent = "";
            emailExists = false;
            return;
        }

        fetch("/check-email", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ email: email })
        })
        .then(res => res.json())
        .then(data => {
            emailExists = data.exists;

            if (data.exists) {
                emailError.textContent = "❌ Email already exists";
                emailError.style.color = "red";
            } else {
                emailError.textContent = "✔ Email available";
                emailError.style.color = "green";
            }
        });
    }, 500);
});

/* -----------------------------------------
   REAL-TIME PASSWORD STRENGTH CHECKER
------------------------------------------ */
document.getElementById("password").addEventListener("input", function () {
    const pwd = this.value;
    const strengthText = document.getElementById("password-strength");

    let score = 0;
    if (pwd.length >= 8) score++;
    if (/[A-Z]/.test(pwd)) score++;
    if (/[0-9]/.test(pwd)) score++;
    if (/[^A-Za-z0-9]/.test(pwd)) score++;

    if (pwd.length === 0) {
        strengthText.textContent = "";
        return;
    }

    if (score <= 1) {
        strengthText.textContent = "Weak Password";
        strengthText.style.color = "red";
    } else if (score === 2 || score === 3) {
        strengthText.textContent = "Medium Password";
        strengthText.style.color = "orange";
    } else {
        strengthText.textContent = "Strong Password";
        strengthText.style.color = "green";
    }
});

/* -----------------------------------------
   FINAL FORM VALIDATION
------------------------------------------ */
form.addEventListener("submit", function (e) {
    let isValid = true;

    const fullName = form.querySelector("[name='full_name']");
    const contact = form.querySelector("[name='contact_no']");
    const email = form.querySelector("[name='email']");
    const password = form.querySelector("[name='password']");
    const confirmPassword = form.querySelector("[name='password_confirmation']");
    const role = form.querySelector('input[name="role"]:checked');

    document.querySelectorAll("small.error").forEach(el => el.textContent = "");

    if (fullName.value.trim() === "") {
        setError(fullName, "Full name is required");
        isValid = false;
    }

    if (contact.value.trim() === "" || !/^[0-9]{10,}$/.test(contact.value)) {
        setError(contact, "Enter a valid contact number (at least 10 digits)");
        isValid = false;
    }

    if (!role) {
        document.querySelector(".role small.error").textContent = "Please select a role";
        isValid = false;
    }

    if (email.value.trim() === "") {
        setError(email, "Enter a valid email");
        isValid = false;
    }

    if (emailExists) {
        setError(email, "Email already exists");
        isValid = false;
    }

    // Password rules
    const pwd = password.value;
    const passwordRules =
        pwd.length >= 8 &&
        /[A-Z]/.test(pwd) &&
        /[0-9]/.test(pwd) &&
        /[^A-Za-z0-9]/.test(pwd);

    if (!passwordRules) {
        setError(password, "Password must be 8+ chars, uppercase, number, special char");
        isValid = false;
    }

    if (confirmPassword.value !== password.value) {
        setError(confirmPassword, "Passwords do not match");
        isValid = false;
    }

    if (!isValid) e.preventDefault();
});
