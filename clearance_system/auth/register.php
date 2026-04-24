<?php session_start(); ?>

<?php
$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, Helvetica, sans-serif;
}

body.register-page {
    min-height: 100vh;
    background: url("../assets/southern-night.png") no-repeat center center/cover;
    overflow-x: hidden;
}

.register-bg-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.35);
    z-index: 1;
}

.register-wrapper {
    position: relative;
    z-index: 2;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    padding: 20px;
}

.register-card {
    position: relative;
    width: 100%;
    max-width: 1150px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    border-radius: 30px;
    overflow: hidden;
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    background: rgba(255,255,255,0.15);
    box-shadow: 0 25px 60px rgba(0,0,0,0.28);
}

.register-card:hover {
    transform: none;
}

.divider-glow {
    position: absolute;
    top: 10%;
    left: 50%;
    transform: translateX(-50%);
    width: 2px;
    height: 80%;
    background: linear-gradient(
        to bottom,
        transparent,
        rgba(255,255,255,0.95),
        transparent
    );
    box-shadow:
        0 0 10px rgba(255,255,255,0.55),
        0 0 20px rgba(0,255,140,0.25);
    z-index: 5;
    pointer-events: none;
}

.register-form-side {
    position: relative;
    background: rgba(255,255,255,0.95);
    padding: 40px;
    z-index: 2;
}

.back-btn {
    position: absolute;
    top: 20px;
    left: 20px;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: rgba(10, 92, 45, 0.10);
    color: #0a5c2d;
    text-decoration: none;
    font-size: 24px;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
}

.form-title {
    text-align: center;
    font-size: 32px;
    font-weight: 900;
    color: #0a5c2d;
    margin-bottom: 8px;
    margin-top: 6px;
}

.form-subtitle {
    text-align: center;
    font-size: 14px;
    color: #666;
    margin-bottom: 22px;
    font-weight: 600;
}

.grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.input-group {
    display: flex;
    flex-direction: column;
    position: relative;
}

.input-group label {
    font-size: 13px;
    font-weight: 700;
    color: #0a5c2d;
    margin-bottom: 6px;
}

.input-group input,
.input-group select {
    width: 100%;
    height: 52px;
    border-radius: 14px;
    border: 2px solid #ddd;
    padding: 0 15px;
    font-size: 15px;
    outline: none;
    transition: 0.3s;
    background: #fff;
}

.input-group input:focus,
.input-group select:focus {
    border-color: #0bb15d;
    box-shadow: 0 0 0 3px rgba(11,177,93,0.12);
}

.password-group input {
    padding-right: 48px;
}

.toggle-password {
    position: absolute;
    right: 15px;
    top: 40px;
    cursor: pointer;
    font-size: 18px;
    user-select: none;
}

.helper-text {
    margin-top: 6px;
    font-size: 12px;
    font-weight: 700;
    min-height: 18px;
}

.helper-error {
    color: #c62828;
}

.helper-success {
    color: #0a8a48;
}

.submit-btn {
    width: 100%;
    margin-top: 22px;
    height: 52px;
    border: none;
    border-radius: 14px;
    background: linear-gradient(135deg,#10c766,#06984b);
    color: #fff;
    font-weight: 800;
    font-size: 16px;
    cursor: pointer;
    box-shadow: 0 12px 24px rgba(5, 143, 72, 0.25);
}

.footer {
    text-align: center;
    margin-top: 14px;
    font-size: 14px;
    color: #333;
    font-weight: 600;
}

.footer a {
    color: #4f46e5;
    text-decoration: none;
    font-weight: 800;
}

.register-header {
    position: relative;
    padding: 50px 46px;
    color: #fff;
    background: linear-gradient(135deg,
        #0f5132 0%,
        #0b6b40 28%,
        #0d8c4e 62%,
        #10b15d 100%
    );
    display: flex;
    flex-direction: column;
    justify-content: center;
    box-shadow: inset 0 0 60px rgba(0,0,0,0.22);
    overflow: hidden;
}

.register-header::before {
    content: "";
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at top right, rgba(255,255,255,0.12), transparent 34%),
        radial-gradient(circle at bottom left, rgba(255,255,255,0.05), transparent 28%);
    pointer-events: none;
}

.school-title {
    position: relative;
    z-index: 2;
    font-size: 34px;
    font-weight: 900;
    line-height: 1.15;
    margin-bottom: 15px;
    text-transform: uppercase;
    text-shadow: 0 4px 14px rgba(0,0,0,0.35);
}

.school-subtitle {
    position: relative;
    z-index: 2;
    font-size: 26px;
    font-weight: 800;
    margin-bottom: 14px;
    text-shadow: 0 3px 10px rgba(0,0,0,0.25);
}

.info-text {
    position: relative;
    z-index: 2;
    font-size: 15px;
    line-height: 1.8;
    color: rgba(255,255,255,0.94);
    margin-bottom: 24px;
}

.password-rules-side {
    position: relative;
    z-index: 2;
    background: rgba(255,255,255,0.10);
    border: 1.5px solid rgba(255,255,255,0.28);
    border-radius: 18px;
    padding: 18px 18px 14px;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    box-shadow: inset 0 0 14px rgba(255,255,255,0.05);
}

.password-rules-title {
    font-size: 14px;
    font-weight: 900;
    color: #ffffff;
    margin-bottom: 12px;
    letter-spacing: 0.4px;
}

.password-rules-side ul {
    list-style: none;
    padding: 0;
}

.password-rules-side li {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    line-height: 1.5;
    margin-bottom: 10px;
    color: rgba(255,255,255,0.92);
    transition: 0.2s ease;
}

.rule-dot {
    width: 11px;
    height: 11px;
    border-radius: 50%;
    border: 2px solid rgba(255,255,255,0.75);
    background: transparent;
    flex-shrink: 0;
    transition: 0.2s ease;
}

.password-rules-side li.valid {
    color: #ffffff;
    font-weight: 700;
}

.password-rules-side li.valid .rule-dot {
    background: #ffffff;
    border-color: #ffffff;
    box-shadow: 0 0 10px rgba(255,255,255,0.45);
}

.message-box {
    margin-bottom: 16px;
    padding: 12px 14px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 700;
    text-align: center;
}

.message-error {
    background: #ffe8e8;
    color: #b30000;
    border: 1px solid #ffb3b3;
}

.popup-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.55);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 999;
    padding: 20px;
}

.popup-overlay.show {
    display: flex;
}

.success-popup {
    width: 100%;
    max-width: 420px;
    background: #fff;
    border-radius: 24px;
    padding: 30px 24px;
    text-align: center;
    box-shadow: 0 25px 60px rgba(0,0,0,0.25);
    animation: popupFade 0.25s ease;
}

.success-icon {
    width: 76px;
    height: 76px;
    margin: 0 auto 16px;
    border-radius: 50%;
    background: linear-gradient(135deg,#10c766,#06984b);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 38px;
    font-weight: 900;
}

.success-popup h2 {
    color: #0a5c2d;
    font-size: 28px;
    margin-bottom: 10px;
}

.success-popup p {
    color: #444;
    font-size: 15px;
    line-height: 1.7;
    margin-bottom: 14px;
}

.redirect-text {
    font-size: 13px;
    color: #666;
    font-weight: 700;
}

@keyframes popupFade {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

@media(max-width: 900px) {
    .register-card {
        grid-template-columns: 1fr;
    }

    .divider-glow {
        display: none;
    }

    .register-header {
        text-align: center;
    }
}

@media(max-width: 800px) {
    .grid {
        grid-template-columns: 1fr;
    }
}

@media(max-width: 500px) {
    .register-form-side {
        padding: 40px 20px 28px;
    }

    .register-header {
        padding: 35px 24px;
    }

    .school-title {
        font-size: 24px;
    }

    .school-subtitle {
        font-size: 20px;
    }

    .form-title {
        font-size: 27px;
    }
}
</style>
</head>

<body class="register-page">

<div class="register-bg-overlay"></div>

<div class="register-wrapper">
    <div class="register-card">

        <div class="divider-glow"></div>

        <div class="register-form-side">
            <a href="login.php" class="back-btn">✕</a>

            <h1 class="form-title">CREATE ACCOUNT</h1>
            <p class="form-subtitle">Fill in your details to continue</p>

            <?php if (!empty($error)): ?>
                <div class="message-box message-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="process_register.php" method="POST" id="registerForm">

                <div class="grid">

                    <div class="input-group">
                        <label for="firstname">First Name</label>
                        <input type="text" name="firstname" id="firstname" required>
                    </div>

                    <div class="input-group">
                        <label for="lastname">Last Name</label>
                        <input type="text" name="lastname" id="lastname" required>
                    </div>

                    <div class="input-group">
                        <label for="email">Email</label>
                        <input type="email" name="email" id="email" required>
                    </div>

                    <div class="input-group">
                        <label for="contact_number">Contact Number</label>
                        <input type="text" name="contact_number" id="contact_number" required>
                    </div>

                    <div class="input-group password-group">
                        <label for="pass">Password</label>
                        <input type="password" id="pass" name="password" required>
                        <span class="toggle-password" onclick="togglePassword('pass', this)">👁</span>
                        <div id="passwordStrengthText" class="helper-text"></div>
                    </div>

                    <div class="input-group">
                        <label for="role">Role</label>
                        <select name="role" id="role" onchange="toggleCourse()" required>
                            <option value="">Select Role</option>
                            <option value="student">STUDENT</option>
                            <option value="teacher">TEACHER</option>
                        </select>
                    </div>

                    <div class="input-group password-group">
                        <label for="cpass">Confirm Password</label>
                        <input type="password" id="cpass" name="confirm_password" required>
                        <span class="toggle-password" onclick="togglePassword('cpass', this)">👁</span>
                        <div id="confirmPasswordText" class="helper-text"></div>
                    </div>

                    <div class="input-group" id="courseBox" style="display:none;">
                        <label for="course">Course</label>
                        <select name="course" id="course">
                            <option value="">Select Course</option>
                            <option value="BSIT 1">BSIT 1</option>
                            <option value="BSIT 2">BSIT 2</option>
                            <option value="BSIT 3">BSIT 3</option>
                            <option value="BSIT 4">BSIT 4</option>
                        </select>
                    </div>

                </div>

                <button type="submit" class="submit-btn">SIGN UP</button>

                <div class="footer">
                    Already have an account? <a href="login.php">Back to Login</a>
                </div>

            </form>
        </div>

        <div class="register-header">
            <h2 class="school-title">SOUTHERN PHILIPPINES INSTITUTE OF SCIENCE AND TECHNOLOGY</h2>
            <p class="school-subtitle">Online Clearance Management System</p>
            <p class="info-text">
                Create your account to access a faster, smoother, and more organized academic clearance process for students and teachers.
            </p>

            <div class="password-rules-side">
                <div class="password-rules-title">🛡 PASSWORD REQUIREMENTS</div>
                <ul>
                    <li id="rule-length"><span class="rule-dot"></span>At least 12 characters long</li>
                    <li id="rule-uppercase"><span class="rule-dot"></span>At least 1 uppercase letter (A-Z)</li>
                    <li id="rule-number"><span class="rule-dot"></span>At least 1 number (0-9)</li>
                    <li id="rule-special"><span class="rule-dot"></span>At least 1 special character (!@#$%^&* etc.)</li>
                </ul>
            </div>
        </div>

    </div>
</div>

<div id="successPopup" class="popup-overlay <?php echo ($success === '1') ? 'show' : ''; ?>">
    <div class="success-popup">
        <div class="success-icon">✓</div>
        <h2>Success!</h2>
        <p>Your account has been created successfully.</p>
        <div class="redirect-text">Redirecting to login page in <span id="countdown">3</span>...</div>
    </div>
</div>

<script>
function togglePassword(id, icon) {
    let input = document.getElementById(id);

    if (input.type === "password") {
        input.type = "text";
        icon.textContent = "🙈";
    } else {
        input.type = "password";
        icon.textContent = "👁";
    }
}

function toggleCourse() {
    let role = document.getElementById("role").value;
    let course = document.getElementById("courseBox");
    let courseSelect = document.getElementById("course");

    if (role === "student") {
        course.style.display = "block";
        courseSelect.setAttribute("required", "required");
    } else {
        course.style.display = "none";
        courseSelect.removeAttribute("required");
        courseSelect.value = "";
    }
}

const passwordInput = document.getElementById("pass");
const confirmPasswordInput = document.getElementById("cpass");
const passwordStrengthText = document.getElementById("passwordStrengthText");
const confirmPasswordText = document.getElementById("confirmPasswordText");
const registerForm = document.getElementById("registerForm");

const ruleLength = document.getElementById("rule-length");
const ruleUppercase = document.getElementById("rule-uppercase");
const ruleNumber = document.getElementById("rule-number");
const ruleSpecial = document.getElementById("rule-special");

function updateRuleState(element, isValid) {
    if (isValid) {
        element.classList.add("valid");
    } else {
        element.classList.remove("valid");
    }
}

function validatePasswordRules() {
    const password = passwordInput.value;

    const hasLength = password.length >= 12;
    const hasUppercase = /[A-Z]/.test(password);
    const hasNumber = /\d/.test(password);
    const hasSpecial = /[\W_]/.test(password);

    updateRuleState(ruleLength, hasLength);
    updateRuleState(ruleUppercase, hasUppercase);
    updateRuleState(ruleNumber, hasNumber);
    updateRuleState(ruleSpecial, hasSpecial);

    const isStrong = hasLength && hasUppercase && hasNumber && hasSpecial;

    if (password.length === 0) {
        passwordStrengthText.textContent = "";
        passwordStrengthText.className = "helper-text";
    } else if (isStrong) {
        passwordStrengthText.textContent = "Strong password ✔";
        passwordStrengthText.className = "helper-text helper-success";
    } else {
        passwordStrengthText.textContent = "Password does not meet the required security rules.";
        passwordStrengthText.className = "helper-text helper-error";
    }

    return isStrong;
}

function validateConfirmPassword() {
    const password = passwordInput.value;
    const confirmPassword = confirmPasswordInput.value;

    if (confirmPassword.length === 0) {
        confirmPasswordText.textContent = "";
        confirmPasswordText.className = "helper-text";
        return false;
    }

    if (password === confirmPassword) {
        confirmPasswordText.textContent = "Password matched ✔";
        confirmPasswordText.className = "helper-text helper-success";
        return true;
    } else {
        confirmPasswordText.textContent = "Password does not match.";
        confirmPasswordText.className = "helper-text helper-error";
        return false;
    }
}

passwordInput.addEventListener("input", function() {
    validatePasswordRules();
    validateConfirmPassword();
});

confirmPasswordInput.addEventListener("input", function() {
    validateConfirmPassword();
});

registerForm.addEventListener("submit", function(e) {
    const strongPassword = validatePasswordRules();
    const matchedPassword = validateConfirmPassword();

    if (!strongPassword) {
        e.preventDefault();
        alert("Password must be at least 12 characters long and include 1 uppercase letter, 1 number, and 1 special character.");
        passwordInput.focus();
        return;
    }

    if (!matchedPassword) {
        e.preventDefault();
        alert("Confirm password does not match.");
        confirmPasswordInput.focus();
    }
});

<?php if ($success === '1'): ?>
let timeLeft = 3;
const countdown = document.getElementById("countdown");

const timer = setInterval(function() {
    timeLeft--;
    if (countdown) {
        countdown.textContent = timeLeft;
    }

    if (timeLeft <= 0) {
        clearInterval(timer);
        window.location.href = "login.php";
    }
}, 1000);
<?php endif; ?>
</script>

</body>
</html>
