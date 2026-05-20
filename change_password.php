<?php
session_start();
require('../connection.php');

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';
$sendChangeNotification = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $admin_id = $_SESSION['admin_id'];

    // Get current password from database
    $stmt = $connection->prepare("SELECT password FROM admins WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    $stmt->close();

    // Verify current password
    if (!password_verify($current_password, $admin['password'])) {
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
        $stmt = $connection->prepare("UPDATE admins SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $admin_id);
        
        if ($stmt->execute()) {
            $success = "Password changed successfully!";
            $sendChangeNotification = true;
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

    <!-- Google Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #050b1a, #071a33, #02040a);
            overflow: hidden;
            color: #fff;
        }

        /* animated glow background */
        body::before {
            content: "";
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, #1e90ff55, transparent 60%);
            top: -100px;
            left: -100px;
            animation: float 6s ease-in-out infinite;
        }

        body::after {
            content: "";
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, #00bfff33, transparent 60%);
            bottom: -150px;
            right: -150px;
            animation: float 8s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(20px); }
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #4da3ff;
            text-shadow: 0 0 10px #1e90ff;
            animation: fadeIn 1s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        form {
            position: relative;
            width: 360px;
            padding: 30px;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(77, 163, 255, 0.3);
            border-radius: 18px;
            box-shadow: 0 0 25px rgba(30, 144, 255, 0.25);
            animation: pop 0.8s ease;
        }

        @keyframes pop {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            color: #bcdcff;
        }

        .input-group {
            position: relative;
            width: 100%;
        }

        input {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: none;
            outline: none;
            border-radius: 10px;
            background: rgba(0, 0, 0, 0.4);
            color: #fff;
            border: 1px solid rgba(77, 163, 255, 0.2);
            transition: 0.3s ease;
        }

        input:focus {
            border: 1px solid #4da3ff;
            box-shadow: 0 0 12px #1e90ff;
        }

        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(90deg, #1e90ff, #0057ff);
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s ease;
            box-shadow: 0 0 15px #1e90ff55;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 25px #1e90ff;
        }

        .material-symbols-outlined {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #4da3ff;
            font-size: 20px;
            pointer-events: none;
        }

        .field {
            margin-bottom: 20px;
        }

        .back {
            text-align: center;
            margin-top: 20px;
        }

        .back a {
            color: #4da3ff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: 0.3s ease;
        }

        .back a:hover {
            color: #fff;
            text-shadow: 0 0 10px #1e90ff;
        }

        .error,
        .success {
            padding: 12px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 15px;
            font-size: 13px;
        }

        .error {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.25);
        }

        .success {
            background: rgba(34, 197, 94, 0.15);
            color: #86efac;
            border: 1px solid rgba(34, 197, 94, 0.25);
        }
    </style>
<link rel="stylesheet" href="../assets/responsive.css">
</head>

<body>

    <form method="POST">
        <h2>Change Password</h2>

        <?php if($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <div class="field">
            <label>Current Password:</label>
            <div class="input-group">
                <span class="material-symbols-outlined">lock</span>
                <input type="password" name="old_password" required>
            </div>
        </div>

        <div class="field">
            <label>New Password:</label>
            <div class="input-group">
                <span class="material-symbols-outlined">lock_reset</span>
                <input type="password" name="new_password" required>
            </div>
        </div>

        <div class="field">
            <label>Confirm New Password:</label>
            <div class="input-group">
                <span class="material-symbols-outlined">verified_user</span>
                <input type="password" name="confirm_password" required>
            </div>
        </div>

        <button type="submit" name="update_password">Update Password</button>

        <div class="back">
            <a href="login.php">← Back to Login</a>
        </div>
    </form>

<!-- EmailJS SDK -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>

<?php if ($sendChangeNotification): ?>
<script type="text/javascript">
    (function() {
        // Initialize EmailJS with your Public Key
        emailjs.init({
            publicKey: "tPPG5kXv_HpogUqT6"
        });

        // Send confirmation email via EmailJS
        emailjs.send("service_omhbzem", "YOUR_CHANGE_TEMPLATE_ID", { // Replace with your Password Changed Template ID if you create one
            to_email: "<?= htmlspecialchars($_SESSION['admin_email']) ?>",
            email: "<?= htmlspecialchars($_SESSION['admin_email']) ?>",
            to_name: "<?= htmlspecialchars($_SESSION['fullname']) ?>",
            name: "<?= htmlspecialchars($_SESSION['fullname']) ?>"
        }).then(
            function(response) {
                alert("Password changed successfully and notification sent!");
            },
            function(error) {
                console.error("EmailJS notification failed:", error);
            }
        );
    })();
</script>
<?php endif; ?>

</body>
</html>