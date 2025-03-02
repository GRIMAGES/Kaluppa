document.addEventListener("DOMContentLoaded", function () {
    const container = document.getElementById("container");
    const registerBtn = document.getElementById("sign_up");
    const loginBtn = document.getElementById("login");
    const roleSelect = document.getElementById("role");
    const adminKeyInput = document.getElementById("adminKeyInput");
    const passwordInput = document.getElementById("password");
    const passwordStrength = document.getElementById("passwordStrength");

    // Toggle between login and register
    registerBtn.addEventListener("click", () => {
        container.classList.add("active");
    });

    loginBtn.addEventListener("click", () => {
        container.classList.remove("active");
    });

    // Show/Hide Admin Key field based on role selection
    function toggleAdminKeyInput() {
        if (roleSelect.value === "admin") {
            adminKeyInput.style.display = "block";
        } else {
            adminKeyInput.style.display = "none";
        }
    }
    
    roleSelect.addEventListener("change", toggleAdminKeyInput);

    // Password Strength Checker
    function checkPasswordStrength() {
        const password = passwordInput.value;
        const strongRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,20}$/;
        const mediumRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d@$!%*?&]{8,20}$/;

        if (strongRegex.test(password)) {
            passwordStrength.innerHTML = '<div class="alert alert-success">Strong password</div>';
        } else if (mediumRegex.test(password)) {
            passwordStrength.innerHTML = '<div class="alert alert-warning">Medium password</div>';
        } else {
            passwordStrength.innerHTML = '<div class="alert alert-danger">Weak password</div>';
        }
    }

    passwordInput.addEventListener("input", checkPasswordStrength);
});
