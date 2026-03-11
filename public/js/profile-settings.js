document.addEventListener('DOMContentLoaded', function () {
    const passwordInput = document.getElementById('new_password');

    const ruleLength = document.getElementById('rule-length');
    const ruleUpper = document.getElementById('rule-upper');
    const ruleLower = document.getElementById('rule-lower');
    const ruleNumber = document.getElementById('rule-number');
    const ruleSpecial = document.getElementById('rule-special');

    function setRuleState(element, isValid, validText, invalidText) {
        if (!element) return;
        element.style.color = isValid ? '#16a34a' : '#dc2626';
        element.textContent = isValid ? `✔ ${validText}` : `✖ ${invalidText}`;
    }

    function validatePasswordLive() {
        if (!passwordInput) return;

        const value = passwordInput.value;

        const hasLength = value.length >= 8;
        const hasUpper = /[A-Z]/.test(value);
        const hasLower = /[a-z]/.test(value);
        const hasNumber = /[0-9]/.test(value);
        const hasSpecial = /[^A-Za-z0-9]/.test(value);

        setRuleState(ruleLength, hasLength, 'At least 8 characters', 'At least 8 characters');
        setRuleState(ruleUpper, hasUpper, 'At least 1 uppercase letter', 'At least 1 uppercase letter');
        setRuleState(ruleLower, hasLower, 'At least 1 lowercase letter', 'At least 1 lowercase letter');
        setRuleState(ruleNumber, hasNumber, 'At least 1 number', 'At least 1 number');
        setRuleState(ruleSpecial, hasSpecial, 'At least 1 special character', 'At least 1 special character');
    }

    if (passwordInput) {
        passwordInput.addEventListener('input', validatePasswordLive);
        validatePasswordLive();
    }

    if (window.profileSuccessMessage) {
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: window.profileSuccessMessage,
            confirmButtonColor: '#2563eb'
        });
    }

    if (window.profileErrorMessages && window.profileErrorMessages.length > 0) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            html: window.profileErrorMessages.join('<br>'),
            confirmButtonColor: '#dc2626'
        });
    }
});