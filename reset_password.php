<?php
require('../connection.php');

$error = '';
$success = '';
$valid = false;
$token = '';

/* =========================
   CHECK TOKEN
========================= */
if (isset($_GET['token'])) {

    $token = $_GET['token'];
    $now = date('Y-m-d H:i:s');

    $stmt = $connection->prepare("
        SELECT id, reset_token 
        FROM admins 
        WHERE reset_token = ? 
        AND token_expiry > ?
        LIMIT 1
    ");

    $stmt->bind_param("ss", $token, $now);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $valid = true;
        $user = $result->fetch_assoc();
    } else {
        $error = "Invalid o expired na ang reset link. Subukan ulit.";
    }

    $stmt->close();
}

/* =========================
   RESET PASSWORD
========================= */
if (isset($_POST['reset']) && $valid) {

    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($newPassword !== $confirmPassword) {
        $error = "Hindi magkatugma ang passwords!";
    }
    elseif (strlen($newPassword) < 6) {
        $error = "Dapat hindi bababa sa 6 characters ang password!";
    }
    else {
        // Hash the new password before storing
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // I-update ang password at i-clear ang token
        mysqli_query($connection, 
            "UPDATE admins SET password='$hashedPassword', reset_token=NULL, token_expiry=NULL 
             WHERE reset_token='$token'");
        $success = "Na-reset na ang iyong password! Maaari ka nang mag-login.";
        $valid = false;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined');

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background: radial-gradient(circle at top left, #0a1a3a, #090e1a 40%, #0f172a 100%);
    overflow: hidden;
    color: #e6f1ff;
}

/* animated glow background */
body::before,
body::after {
    content: "";
    position: absolute;
    width: 500px;
    height: 500px;
    border-radius: 50%;
    filter: blur(140px);
    opacity: 0.35;
    z-index: 0;
    animation: floatGlow 10s ease-in-out infinite;
}

body::before {
    background: #1e90ff;
    top: -150px;
    left: -120px;
}

body::after {
    background: #00c6ff;
    bottom: -150px;
    right: -120px;
    animation-delay: 3s;
}

@keyframes floatGlow {
    0%, 100% { transform: translateY(0) scale(1); }
    50% { transform: translateY(40px) scale(1.15); }
}

/* glass card */
.box {
    position: relative;
    z-index: 1;
    width: 380px;
    padding: 40px;
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.12);
    box-shadow: 0 0 35px rgba(0, 140, 255, 0.25);
    animation: fadeIn 0.8s ease;
}

/* fade in */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(25px); }
    to { opacity: 1; transform: translateY(0); }
}

h2 {
    text-align: center;
    margin-bottom: 20px;
    font-weight: 600;
    color: #cfe8ff;
    letter-spacing: 0.5px;
}

/* Material Icons */
.material-symbols-outlined {
    font-variation-settings:
    'FILL' 1,
    'wght' 400,
    'GRAD' 0,
    'opsz' 24;
    vertical-align: middle;
}

/* inputs */
input {
    width: 100%;
    padding: 12px;
    margin-bottom: 15px;
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.12);
    background: rgba(10, 18, 40, 0.7);
    color: #ffffff;
    outline: none;
    transition: 0.3s ease;
}

input:focus {
    border-color: #1e90ff;
    box-shadow: 0 0 15px rgba(30,144,255,0.6);
    transform: scale(1.03);
}

/* button */
button {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(90deg, #1e90ff, #00c6ff);
    color: white;
    font-weight: 500;
    cursor: pointer;
    transition: 0.3s ease;
    box-shadow: 0 0 18px rgba(0, 140, 255, 0.35);
}

button:hover {
    transform: translateY(-2px);
    box-shadow: 0 0 30px rgba(0, 140, 255, 0.6);
}

/* messages */
.success {
    color: #00ffb3;
    text-align: center;
    margin-bottom: 10px;
    font-size: 14px;
    animation: fadeIn 0.5s ease;
}

.error {
    color: #ff4d4d;
    text-align: center;
    margin-bottom: 10px;
    font-size: 14px;
    animation: shake 0.3s ease;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    50% { transform: translateX(5px); }
    75% { transform: translateX(-5px); }
}

/* back link */
.back {
    text-align: center;
    margin-top: 15px;
    font-size: 13px;
}

.back a {
    color: #7cc7ff;
    text-decoration: none;
    transition: 0.3s;
}

.back a:hover {
    text-shadow: 0 0 12px rgba(30,144,255,0.9);
}

.password-field {
    position: relative;
    width: 100%;
    margin-bottom: 15px;
}

.password-field input {
    width: 100%;
    padding: 12px 45px 12px 12px; /* space for icon */
}

.toggle-pass {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: rgba(255,255,255,0.6);
    transition: 0.3s;
    user-select: none;
}

.toggle-pass:hover {
    color: #1e90ff;
}
</style>
<link rel="stylesheet" href="../assets/responsive.css">
</head>
<body>
<div class="box">
    <h2>Reset Password</h2>

    <?php if ($success): ?>
        <p class="success"><?= $success ?></p>
        <div class="back"><a href="login.php"> Go to Login</a></div>

    <?php elseif ($error && !$valid): ?>
        <p class="error"><?= $error ?></p>
        <div class="back"><a href="forgot_password.php">Try again</a></div>

    <?php elseif ($valid): ?>
        <?php if ($error) echo "<p class='error'>$error</p>"; ?>

        <form method="POST">
            
<div class="password-field">
    <input type="password" id="new_password" name="new_password" placeholder="New Password" required>

    <span class="material-symbols-outlined toggle-pass"
          onclick="togglePass('new_password', this)">
        visibility
    </span>
</div>

<div class="password-field">
    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>

    <span class="material-symbols-outlined toggle-pass"
          onclick="togglePass('confirm_password', this)">
        visibility
    </span>
</div>
            <button type="submit" name="reset" id="resetBtn">
                Reset Password
            </button>

        </form>
    <?php endif; ?>
</div>

<!-- JS (required for toggle + loading animation) -->
<script>
function togglePass(id, icon) {
    const input = document.getElementById(id);

    if (input.type === "password") {
        input.type = "text";
        icon.textContent = "visibility_off";
    } else {
        input.type = "password";
        icon.textContent = "visibility";
    }
}

// loading animation
document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector("form");
    const button = document.getElementById("resetBtn");

    if (form && button) {
        form.addEventListener("submit", function () {
            button.classList.add("loading");
            button.innerHTML = "Processing...";
        });
    }
});
</script>

</body>
</html>