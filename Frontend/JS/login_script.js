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

   
});
