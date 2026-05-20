<?php
session_start();
require('../connection.php');

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $connection->prepare("
        SELECT id, fullname, username, email, password
        FROM admins
        WHERE email = ?
        LIMIT 1
    ");

    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $admin = $result->fetch_assoc();

        // Verify password securely
        if (password_verify($password, $admin['password'])) {

            session_regenerate_id(true);

            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['fullname'] = $admin['fullname'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['admin_email'] = $admin['email'];

            header("Location: dashboard.php");
            exit();

        } else {
            $error = "Invalid password.";
        }

    } else {
        $error = "Admin not found.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>

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
            font-family:'Outfit', sans-serif;
        }

        body{
            min-height:100vh;
            display:flex;
            justify-content:center;
            align-items:center;
            background:
            radial-gradient(circle at top left, rgba(59,130,246,.25), transparent 35%),
            radial-gradient(circle at bottom right, rgba(59,130,246,.15), transparent 25%),
            linear-gradient(135deg,#090e1a,#0f172a);
            overflow:hidden;
            padding:20px;
        }

        /* Glow effect */
        body::before{
            content:'';
            position:absolute;
            width:450px;
            height:450px;
            background:#3b82f6;
            border-radius:50%;
            filter:blur(180px);
            opacity:.18;
            top:-120px;
            left:-120px;
            animation:floatGlow 8s ease-in-out infinite;
        }

        body::after{
            content:'';
            position:absolute;
            width:350px;
            height:350px;
            background:#60a5fa;
            border-radius:50%;
            filter:blur(160px);
            opacity:.12;
            bottom:-100px;
            right:-100px;
            animation:floatGlow 10s ease-in-out infinite alternate;
        }

        @keyframes floatGlow{
            0%{
                transform:translateY(0px);
            }
            50%{
                transform:translateY(25px);
            }
            100%{
                transform:translateY(0px);
            }
        }

        form{
            width:100%;
            max-width:420px;
            background:rgba(255,255,255,0.08);
            backdrop-filter:blur(25px);
            -webkit-backdrop-filter:blur(25px);
            border:1px solid rgba(255,255,255,0.1);
            border-radius:28px;
            padding:40px;
            box-shadow:
            0 10px 40px rgba(0,0,0,.6),
            inset 0 0 20px rgba(255,255,255,.03);
            animation:fadeUp .8s ease;
            position:relative;
            z-index:10;
        }

        @keyframes fadeUp{
            from{
                opacity:0;
                transform:translateY(30px);
            }
            to{
                opacity:1;
                transform:translateY(0);
            }
        }

        h2{
            color:#fff;
            font-size:30px;
            font-weight:700;
            text-align:center;
            margin-bottom:30px;
            letter-spacing:.5px;
            text-shadow:0 0 20px rgba(59,130,246,.3);
        }

        .input-group{
            position:relative;
            margin-bottom:18px;
        }

        input{
            width:100%;
            padding:15px 60px;
            background:rgba(255,255,255,0.08);
            border:1px solid rgba(255,255,255,.12);
            border-radius:20px;
            color:#fff;
            font-size:15px;
            outline:none;
            transition:.35s ease;
        }

        input::placeholder{
            color:#9aa8c7;
        }

        input:focus{
            border-color:#3b82f6;
            box-shadow:
            0 0 0 4px rgba(59,130,246,.15),
            0 0 25px rgba(59,130,246,.25);
            transform:translateY(-2px);
        }

        .input-icon{
            position:absolute;
            left:15px;
            top:50%;
            transform:translateY(-50%);
            color:#6ca8ff;
            font-size:22px;
        }

        .password-group{
           margin-bottom:22px;
        }

        .password-toggle{
            position:absolute;
            right:18px;
            top:50%;
            transform:translateY(-50%);
            color:#7caeff;
            cursor:pointer;
            font-size:22px;
            display:flex;
            align-items:center;
            justify-content:center;
            transition:all .3s ease;
            user-select:none;
        }

        .password-toggle:hover{
            color:#ffffff;
            transform:translateY(-50%) scale(1.08);
        }

        .forgot-password-link{
            text-align:right;
            margin-top:8px;
            font-size:13px;
        }

        .forgot-password-link a{
            color:#9aa8c7;
            font-weight:500;
        }

        .forgot-password-link a:hover{
            color:#60a5fa;
            text-shadow:0 0 10px rgba(96,165,250,.4);
        }

        button{
            width:100%;
            border:none;
            padding:16px;
            border-radius:18px;
            background:linear-gradient(135deg,#3b82f6,#3b82f6);
            color:#fff;
            font-size:16px;
            font-weight:600;
            cursor:pointer;
            transition:.35s ease;
            box-shadow:0 8px 25px rgba(59,130,246,.35);
        }

        button:hover{
            transform:translateY(-3px);
            box-shadow:0 12px 35px rgba(59,130,246,.45);
        }

        button:active{
            transform:scale(.98);
        }

        p{
            margin-top:18px;
            text-align:center;
            color:#cbd5e1;
            font-size:14px;
        }

        a{
            color:#60a5fa;
            text-decoration:none;
            font-weight:600;
            transition:.3s;
        }

        a:hover{
            color:#93c5fd;
        }

        .error-message{
            background:rgba(255,0,0,.12);
            border:1px solid rgba(255,0,0,.25);
            color:#ff9b9b;
            padding:14px;
            border-radius:14px;
            margin-bottom:20px;
            text-align:center;
        }

        @media(max-width:480px){
            form{
                padding:30px 25px;
            }

            h2{
                font-size:28px;
            }
        }
    </style>
<link rel="stylesheet" href="../assets/responsive.css">
</head>
<body>

<form method="POST">

    <h2>Admin Login</h2>

    <?php if($error): ?>
        <div class="error-message">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

<div class="input-group">
    <span class="material-symbols-outlined input-icon">
        mail
    </span>

    <input type="email"
           name="email"
           placeholder="Email"
           required>
</div>
<div class="input-group password-group">
    <span class="material-symbols-outlined input-icon">
        lock
    </span>

    <input type="password"
           name="password"
           id="password"
           placeholder="Password"
           required>

    <span class="material-symbols-outlined password-toggle"
          id="toggleIcon"
          onclick="togglePassword()">
        visibility
    </span>

    <div class="forgot-password-link">
        <a href="forgot_password.php">Forgot Password?</a>
    </div>
</div>

    <button type="submit">
        Login
    </button>
    <p>
        Don't have an account?
        <a href="register.php">
            Register here
        </a>
    </p>

</form>

<script>
function togglePassword() {
    const password = document.getElementById("password");
    const icon = document.getElementById("toggleIcon");

    if (password.type === "password") {
        password.type = "text";
        icon.textContent = "visibility_off";
    } else {
        password.type = "password";
        icon.textContent = "visibility";
    }
}
</script>
</body>
</html>