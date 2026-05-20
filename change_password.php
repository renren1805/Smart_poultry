<?php
session_start();
require('../connection.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $user_id = $_SESSION['user_id'];

    // Get current password from database
    $stmt = $connection->prepare("SELECT password FROM customers WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        $error = "Current password is incorrect.";
    }
    elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    }
    elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters.";
    }
    else {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password in database
        $stmt = $connection->prepare("UPDATE customers SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($stmt->execute()) {
            $success = "Password changed successfully!";
        } else {
            $error = "Error changing password. Please try again.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <style>
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
            background: radial-gradient(circle at top left, #0a1a3a, #050816 40%, #000000 100%);
            overflow: hidden;
            color: #e6f1ff;
            padding: 20px;
        }

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

        .box {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            padding: 40px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 0 35px rgba(0, 140, 255, 0.25);
            animation: fadeIn 0.8s ease;
        }

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

        .password-field {
            position: relative;
            margin-bottom: 20px;
        }

        .password-field input {
            width: 100%;
            padding: 12px 45px 12px 12px;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.12);
            background: rgba(10, 18, 40, 0.7);
            color: #ffffff;
            outline: none;
            transition: 0.3s ease;
        }

        .password-field input:focus {
            border-color: #1e90ff;
            box-shadow: 0 0 15px rgba(30,144,255,0.6);
            transform: scale(1.03);
        }

        .toggle-pass {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: rgba(255,255,255,0.6);
            font-size: 20px;
            transition: 0.3s ease;
            user-select: none;
        }

        .toggle-pass:hover {
            color: #1e90ff;
        }

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

        .success {
            color: #00ffb3;
            text-align: center;
            margin-bottom: 15px;
            font-size: 14px;
            animation: fadeIn 0.5s ease;
        }

        .error {
            color: #f59e0b;
            text-align: center;
            margin-bottom: 15px;
            font-size: 14px;
            animation: shake 0.3s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            50% { transform: translateX(5px); }
            75% { transform: translateX(-5px); }
        }

        .back {
            text-align: center;
            margin-top: 20px;
        }

        .back a {
            color: #7cc7ff;
            text-decoration: none;
            transition: 0.3s;
        }

        .back a:hover {
            text-shadow: 0 0 12px rgba(30,144,255,0.9);
        }

        .material-symbols-outlined {
            font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }
    </style>
<link rel="stylesheet" href="../assets/responsive.css">
</head>
<body>
<div class="box">
    <h2>Change Password</h2>

    <?php if ($success): ?>
        <p class="success">✅ <?= $success ?></p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p class="error">❌ <?= $error ?></p>
    <?php endif; ?>

    <form method="POST">
        <div class="password-field">
            <input type="password" id="current_password" name="current_password" placeholder="Current Password" required>
            <span class="material-symbols-outlined toggle-pass" onclick="togglePass('current_password', this)">
                visibility
            </span>
        </div>

        <div class="password-field">
            <input type="password" id="new_password" name="new_password" placeholder="New Password" required>
            <span class="material-symbols-outlined toggle-pass" onclick="togglePass('new_password', this)">
                visibility
            </span>
        </div>

        <div class="password-field">
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm New Password" required>
            <span class="material-symbols-outlined toggle-pass" onclick="togglePass('confirm_password', this)">
                visibility
            </span>
        </div>

        <button type="submit">Change Password</button>
    </form>

    <div class="back">
        <a href="login.php">← Back to Login</a>
    </div>
</div>

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
</script>
</body>
</html>
