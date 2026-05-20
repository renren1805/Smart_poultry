<?php
session_start();
require('../connection.php');

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "All fields are required!";
    } else {

        $stmt = $connection->prepare("SELECT id, fullname, email, password, status FROM customers WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {

            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {

                if ($user['status'] != "active") {
                    $error = "Account is not active.";
                } else {

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['fullname'] = $user['fullname'];
                    $_SESSION['email'] = $user['email'];
                    
                    header("Location: dashboard.php");
                    exit();
                }

            } else {
                $error = "Incorrect password!";
            }

        } else {
            $error = "No account found with that email!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login</title>

<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');

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
    overflow:hidden;
    background:
    radial-gradient(circle at top left, rgba(245, 158, 11,0.15), transparent 30%),
    radial-gradient(circle at bottom right, rgba(120,0,0,0.25), transparent 30%),
    linear-gradient(135deg,#0f172a,#090e1a,#000);
    color:white;
    padding:20px;
}

/* Floating glow animation */
body::before{
    content:'';
    position:absolute;
    width:350px;
    height:350px;
    background:rgba(245, 158, 11,0.12);
    border-radius:50%;
    top:-100px;
    left:-100px;
    filter:blur(80px);
    animation: floatGlow 8s ease-in-out infinite;
}

body::after{
    content:'';
    position:absolute;
    width:350px;
    height:350px;
    background:rgba(245, 158, 11,0.08);
    border-radius:50%;
    bottom:-120px;
    right:-100px;
    filter:blur(100px);
    animation: floatGlow2 10s ease-in-out infinite;
}

@keyframes floatGlow{
    0%,100%{
        transform:translate(0,0);
    }
    50%{
        transform:translate(40px,30px);
    }
}

@keyframes floatGlow2{
    0%,100%{
        transform:translate(0,0);
    }
    50%{
        transform:translate(-40px,-30px);
    }
}

/* Container */
.login-container{
    width:100%;
    display:flex;
    justify-content:center;
    z-index:2;
}

/* Glassmorphism Card */
.login-card{
    position:relative;
    width:100%;
    max-width:430px;
    padding:40px;
    border-radius:30px;
    background:rgba(255,255,255,0.05);
    border:1px solid rgba(255,255,255,0.08);
    backdrop-filter:blur(25px);
    -webkit-backdrop-filter:blur(25px);
    box-shadow:
        0 8px 32px rgba(0,0,0,0.5),
        0 0 30px rgba(245, 158, 11,0.15);
    overflow:hidden;
    animation: fadeUp .8s ease;
}

.login-card::before{
    content:'';
    position:absolute;
    inset:0;
    border-radius:30px;
    padding:1px;
    background:linear-gradient(
        135deg,
        rgba(245, 158, 11,0.5),
        transparent,
        rgba(255,80,80,0.3)
    );
    -webkit-mask:
        linear-gradient(#fff 0 0) content-box,
        linear-gradient(#fff 0 0);
    -webkit-mask-composite:xor;
    pointer-events:none;
}

@keyframes fadeUp{
    from{
        opacity:0;
        transform:translateY(40px) scale(.96);
    }
    to{
        opacity:1;
        transform:translateY(0) scale(1);
    }
}

/* Header */
.login-header{
    text-align:center;
    margin-bottom:30px;
}

.logo{
    width:80px;
    height:80px;
    margin:auto;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:50%;
    background:rgba(245, 158, 11,0.08);
    border:1px solid rgba(245, 158, 11,0.2);
    box-shadow:0 0 30px rgba(245, 158, 11,.25);
    animation:pulse 2.5s infinite;
}

.logo .material-icons{
    font-size:40px;
    color:#f59e0b;
}

@keyframes pulse{
    0%,100%{
        transform:scale(1);
    }
    50%{
        transform:scale(1.08);
    }
}

.login-header h2{
    margin-top:20px;
    font-size:28px;
    font-weight:600;
}

.login-header p{
    font-size:14px;
    color:#bfbfbf;
}

/* Form */
.form-group{
    margin-bottom:20px;
}

label{
    display:block;
    margin-bottom:8px;
    font-size:14px;
    color:#ddd;
}

/* Input */
.input-wrapper{
    position:relative;
}

.input-wrapper .material-icons{
    position:absolute;
    left:15px;
    top:50%;
    transform:translateY(-50%);
    color:#f59e0b;
    transition:.3s;
    font-size:22px;
}

input{
    width:100%;
    height:55px;
    border:none;
    outline:none;
    border-radius:16px;
    background:rgba(255,255,255,0.06);
    border:1px solid rgba(255,255,255,0.08);
    color:white;
    padding:0 50px;
    font-size:15px;
    transition:.35s ease;
    backdrop-filter:blur(10px);
}

input::placeholder{
    color:#888;
}

input:focus{
    border-color:#f59e0b;
    box-shadow:
        0 0 0 4px rgba(245, 158, 11,0.12),
        0 0 18px rgba(245, 158, 11,0.25);
    transform:translateY(-2px);
}

input:focus + .material-icons{
    color:#f59e0b;
}

/* Show Password Icon */
.toggle-password{
    position:absolute;
    right:15px;
    top:50%;
    transform:translateY(-50%);
    cursor:pointer;
    color:#aaa;
    transition:.3s;
}

.toggle-password:hover{
    color:#f59e0b;
}

/* Button */
.btn-login{
    width:100%;
    height:55px;
    border:none;
    border-radius:16px;
    cursor:pointer;
    color:white;
    font-size:15px;
    font-weight:600;
    background:linear-gradient(
        135deg,
        #f59e0b,
        #d97706
    );
    transition:.35s ease;
    box-shadow:
        0 8px 20px rgba(245, 158, 11,0.25);
}

.btn-login:hover{
    transform:translateY(-3px);
    box-shadow:
        0 10px 25px rgba(245, 158, 11,.4),
        0 0 20px rgba(245, 158, 11,.25);
}

.btn-login:active{
    transform:scale(.98);
}

/* Error */
.error-message{
    background:rgba(245, 158, 11,0.1);
    border:1px solid rgba(245, 158, 11,0.2);
    color:#ff8f8f;
    padding:14px;
    border-radius:14px;
    margin-bottom:20px;
    text-align:center;
    animation:shake .3s ease;
}

@keyframes shake{
    0%,100%{transform:translateX(0);}
    25%{transform:translateX(-5px);}
    50%{transform:translateX(5px);}
    75%{transform:translateX(-3px);}
}

/* Register Link */
.register-link{
    margin-top:22px;
    text-align:center;
    color:#ccc;
    font-size:14px;
}

.register-link a{
    color:#f59e0b;
    text-decoration:none;
    font-weight:600;
    transition:.3s;
}

.register-link a:hover{
    color:#f59e0b;
    text-shadow:0 0 10px rgba(245, 158, 11,.5);
}

/* Forgot Password Link */
.forgot-password-link{
    text-align:right;
    margin-top:8px;
    font-size:13px;
}

.forgot-password-link a{
    color:#bfbfbf;
    text-decoration:none;
    transition:.3s ease;
}

.forgot-password-link a:hover{
    color:#f59e0b;
    text-shadow:0 0 10px rgba(245, 158, 11,.5);
}

/* Responsive */
@media(max-width:480px){
    .login-card{
        padding:25px;
        border-radius:24px;
    }

    .login-header h2{
        font-size:24px;
    }
}
</style>
<link rel="stylesheet" href="../assets/responsive.css">
</head>

<body>

<div class="login-container">
    <div class="login-card">

        <div class="login-header">
            <div class="logo">
                <span class="material-icons">login</span>
            </div>
            <h2>Welcome Back</h2>
            <p>Sign in to your account</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- FIXED: form submits to SAME file -->
        <form method="POST">

            <div class="form-group">
                <label>Email</label>
                <div class="input-wrapper">
                    <span class="material-icons">email</span>
                    <input type="email" name="email" placeholder="Email" required>
                </div>
            </div>

            <div class="form-group">
                <label>Password</label>
                <div class="input-wrapper">
                    <span class="material-icons">lock</span>
                    <input type="password" name="password" id="password" placeholder="Password" required>
                    <span class="material-icons toggle-password" onclick="togglePassword()">
                        visibility
                    </span>
                </div>
                <div class="forgot-password-link">
                    <a href="forgot_password.php">Forgot Password?</a>
                </div>
            </div>
            <button class="btn-login">
                <span class="material-icons" style="vertical-align:middle;">login</span>
                Sign In
            </button>
        </form>

        <div class="register-link">
            Don't have an account? <a href="register.php">Register</a>
        </div>

    </div>
</div>
<script>
function togglePassword() {
    const password = document.getElementById("password");
    const icon = document.querySelector(".toggle-password");

    if (password.type === "password") {
        password.type = "text";
        icon.textContent = "visibility_off";
    } else {
        password.type = "password";
        icon.textContent = "visibility";
    }
}
</script>

    <style>
    .toggle-password{
        position:absolute !important;
        right:15px;
        left:auto !important;
        cursor:pointer;
        color:#f59e0b;
        transition:.3s;
    }

    .toggle-password:hover{
        color:#f59e0b;
        transform:translateY(-50%) scale(1.1);
    }
</style>
</body>
</html>