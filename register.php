<?php
session_start();
require('../connection.php');

$error = "";
$success = "";
$registrationDisabled = false;

// Check if admin already exists
$checkAdmin = $connection->query("SELECT id FROM admins LIMIT 1");

if ($checkAdmin && $checkAdmin->num_rows > 0) {
    $registrationDisabled = true;
    $error = "Registration is disabled. An account already exists.";
}

if (!$registrationDisabled && $_SERVER["REQUEST_METHOD"] == "POST") {

    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (
        empty($fullname) ||
        empty($username) ||
        empty($email) ||
        empty($password) ||
        empty($confirm_password)
    ) {
        $error = "All fields are required.";
    } 
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } 
    elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } 
    elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } 
    else {

        // Check duplicate username/email
        $checkUser = $connection->prepare("
            SELECT id 
            FROM admins 
            WHERE username = ? OR email = ?
            LIMIT 1
        ");

        $checkUser->bind_param("ss", $username, $email);
        $checkUser->execute();
        $result = $checkUser->get_result();

        if ($result->num_rows > 0) {
            $error = "Username or email already exists.";
        } else {

            // Secure password hashing
            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $connection->prepare("
                INSERT INTO admins
                (fullname, username, email, password)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "ssss",
                $fullname,
                $username,
                $email,
                $hashedPassword
            );

            if ($stmt->execute()) {

                $_SESSION['success'] =
                    "Admin registered successfully!";

                header("Location: login.php");
                exit();

            } else {
                $error = "Registration failed.";
            }

            $stmt->close();
        }

        $checkUser->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Register</title>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Material Symbols -->
    <link rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />

    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:'Outfit',sans-serif;
        }

        body{
            min-height:100vh;
            display:flex;
            justify-content:center;
            align-items:center;
            background:
                radial-gradient(circle at top left, #0f172a, #020617),
                linear-gradient(135deg,#020617,#0f172a);
            overflow:hidden;
            color:white;
        }

        body::before{
            content:'';
            position:absolute;
            width:400px;
            height:400px;
            background:#2563eb;
            border-radius:50%;
            filter:blur(140px);
            top:-120px;
            left:-120px;
            opacity:.35;
        }

        body::after{
            content:'';
            position:absolute;
            width:350px;
            height:350px;
            background:#2563eb;
            border-radius:50%;
            filter:blur(140px);
            bottom:-120px;
            right:-120px;
            opacity:.25;
        }

        form{
            position:relative;
            width:420px;
            padding:40px;
            border-radius:28px;
            backdrop-filter:blur(22px);
            background:rgba(255,255,255,0.06);
            border:1px solid rgba(255,255,255,0.1);
            box-shadow:0 8px 32px rgba(0,0,0,0.35);
            z-index:10;
        }

        h2{
            text-align:center;
            margin-bottom:25px;
            font-size:32px;
        }

        .input-group{
            position:relative;
            margin-bottom:18px;
        }

        .input-group input{
            width:100%;
            padding:16px 50px;
            border-radius:16px;
            border:1px solid rgba(255,255,255,.1);
            outline:none;
            background:rgba(255,255,255,.05);
            color:white;
        }

        .material-symbols-outlined{
            position:absolute;
            left:15px;
            top:50%;
            transform:translateY(-50%);
            color:#60a5fa;
        }

        .toggle-password{
            left:auto;
            right:15px;
            cursor:pointer;
        }

        button{
            width:100%;
            border:none;
            padding:15px;
            border-radius:16px;
            background:linear-gradient(
                135deg,
                #3b82f6,
                #2563eb
            );
            color:white;
            font-size:16px;
            font-weight:600;
            cursor:pointer;
            margin-top:10px;
        }

        button:disabled{
            opacity:.5;
            cursor:not-allowed;
        }

        .error,
        .success{
            padding:12px;
            border-radius:14px;
            text-align:center;
            margin-bottom:15px;
        }

        .error{
            background:rgba(239,68,68,.15);
            color:#fca5a5;
        }

        .success{
            background:rgba(34,197,94,.15);
            color:#86efac;
        }

        p{
            text-align:center;
            margin-top:15px;
        }

        a{
            color:#60a5fa;
            text-decoration:none;
        }
    </style>
<link rel="stylesheet" href="../assets/responsive.css">
</head>
<body>

<form method="POST">

    <h2>Registration</h2>

    <?php if($error): ?>
        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if($success): ?>
        <div class="success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <div class="input-group">
        <span class="material-symbols-outlined">person</span>
        <input type="text"
               name="fullname"
               placeholder="Full Name"
               required
               <?= $registrationDisabled ? 'disabled' : '' ?>>
    </div>

    <div class="input-group">
        <span class="material-symbols-outlined">badge</span>
        <input type="text"
               name="username"
               placeholder="Username"
               required
               <?= $registrationDisabled ? 'disabled' : '' ?>>
    </div>

    <div class="input-group">
        <span class="material-symbols-outlined">mail</span>
        <input type="email"
               name="email"
               placeholder="Email"
               required
               <?= $registrationDisabled ? 'disabled' : '' ?>>
    </div>

    <div class="input-group">
        <span class="material-symbols-outlined">lock</span>
        <input type="password"
               id="password"
               name="password"
               placeholder="Password"
               required
               <?= $registrationDisabled ? 'disabled' : '' ?>>

        <span class="material-symbols-outlined toggle-password"
              onclick="togglePassword('password', this)">
            visibility
        </span>
    </div>

    <div class="input-group">
        <span class="material-symbols-outlined">shield_lock</span>
        <input type="password"
               id="confirm_password"
               name="confirm_password"
               placeholder="Confirm Password"
               required
               <?= $registrationDisabled ? 'disabled' : '' ?>>

        <span class="material-symbols-outlined toggle-password"
              onclick="togglePassword('confirm_password', this)">
            visibility
        </span>
    </div>

    <button type="submit"
        <?= $registrationDisabled ? 'disabled' : '' ?>>
        Register
    </button>

    <p>
        Already have an account?
        <a href="login.php">Login here</a>
    </p>

</form>

<script>
function togglePassword(inputId, icon){
    const input =
        document.getElementById(inputId);

    if(input.type === "password"){
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