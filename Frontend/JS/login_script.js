const container = document.getElementById('container');
const registerBtn = document.getElementById('sign_up');
const loginBtn = document.getElementById('login');

registerBtn.addEventListener('click', () => {
    container.classList.add("active");
});

loginBtn.addEventListener('click', () => {
    container.classList.remove("active");
})

function toggleAdminKeyInput() {
    var roleSelect = document.getElementById("role");
    var adminKeyInput = document.getElementById("adminKeyInput");

    if (roleSelect.value === "admin") {
        adminKeyInput.style.display = "block";
    } else {
        adminKeyInput.style.display = "none";
    }
}

  // Add an event listener to the "Register" button
  document.getElementById('sign_up').addEventListener('click', function() {
    // Wait for the next animation frame to allow the DOM to update
    requestAnimationFrame(function() {
        // Scroll to the bottom of the container to ensure all elements are visible
        document.getElementById('container').scrollbottom = document.getElementById('container').scrollHeight;
    });
});

function checkPasswordStrength() {
    var password = document.getElementById("password").value;
    var passwordStrength = document.getElementById("passwordStrength");

    // Define regex patterns
    var strongRegex = new RegExp("^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\$%\^&\*])(?=.{8,20})");
    var mediumRegex = new RegExp("^((?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.{8,20}))|((?=.*[a-z])(?=.*[A-Z])(?=.*[!@#\$%\^&\*])(?=.{8,20}))|((?=.*[a-z])(?=.*[0-9])(?=.*[!@#\$%\^&\*])(?=.{8,20}))|((?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\$%\^&\*])(?=.{8,20}))");
    
    if(strongRegex.test(password)) {
        passwordStrength.innerHTML = '<div class="alert alert-success" role="alert">Strong password</div>';
    } else if(mediumRegex.test(password)) {
        passwordStrength.innerHTML = '<div class="alert alert-warning" role="alert">Medium password</div>';
    } else {
        passwordStrength.innerHTML = '<div class="alert alert-danger" role="alert">Weak password</div>';
    }
}
