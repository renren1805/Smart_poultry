<?php
session_start();
require('../connection.php');

date_default_timezone_set("Asia/Manila");

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $password = $_POST['password'];

    $created_at = date("Y-m-d H:i:s");
    $updated_at = date("Y-m-d H:i:s");
    $status = "active";

    if (empty($fullname) || empty($email) || empty($phone) || empty($address) || empty($password)) {
        $error = "All fields are required!";
    } else {

        $check = $connection->prepare("SELECT id FROM customers WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Email already exists!";
        } else {

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $connection->prepare("
                INSERT INTO customers 
                (fullname, email, phone, address, password, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "ssssssss",
                $fullname,
                $email,
                $phone,
                $address,
                $hashed_password,
                $status,
                $created_at,
                $updated_at
            );

            if ($stmt->execute()) {
                $success = "Account created successfully! Redirecting...";
                header("refresh:2; url=login.php");
            } else {
                $error = "Something went wrong!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register</title>

<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');

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
    padding:20px;
    overflow:hidden;
    color:#fff;

    background:
    radial-gradient(circle at top left, rgba(245, 158, 11,0.18), transparent 30%),
    radial-gradient(circle at bottom right, rgba(120,0,0,0.18), transparent 30%),
    linear-gradient(135deg,#0f172a,#090e1a,#1a0000,#000);
}

/* Animated Background Glow */
body::before{
    content:'';
    position:fixed;
    width:500px;
    height:500px;
    background:radial-gradient(circle, rgba(245, 158, 11,0.12), transparent 70%);
    top:-120px;
    left:-100px;
    animation:floatGlow 8s ease-in-out infinite;
    z-index:-1;
}

body::after{
    content:'';
    position:fixed;
    width:400px;
    height:400px;
    background:radial-gradient(circle, rgba(245, 158, 11,0.08), transparent 70%);
    bottom:-100px;
    right:-100px;
    animation:floatGlow2 9s ease-in-out infinite;
    z-index:-1;
}

@keyframes floatGlow{
    0%,100%{
        transform:translateY(0px);
    }
    50%{
        transform:translateY(30px);
    }
}

@keyframes floatGlow2{
    0%,100%{
        transform:translateY(0px);
    }
    50%{
        transform:translateY(-30px);
    }
}

.register-container{
    width:100%;
    display:flex;
    justify-content:center;
    align-items:center;
}

/* Premium Glassmorphism Card */
.register-card{
    width:100%;
    max-width:430px;
    padding:35px;

    background:rgba(255,255,255,0.04);
    border:1px solid rgba(255,255,255,0.08);

    backdrop-filter:blur(20px);
    -webkit-backdrop-filter:blur(20px);

    border-radius:28px;

    box-shadow:
    0 0 50px rgba(245, 158, 11,0.12),
    inset 0 0 30px rgba(255,255,255,0.03);

    animation:fadeSlide .8s ease;
    position:relative;
    overflow:hidden;
}

.register-card::before{
    content:'';
    position:absolute;
    inset:0;
    border-radius:28px;
    padding:1px;
    background:linear-gradient(
        135deg,
        rgba(245, 158, 11,.25),
        rgba(255,255,255,.04),
        rgba(245, 158, 11,.2)
    );
    -webkit-mask:
    linear-gradient(#fff 0 0) content-box,
    linear-gradient(#fff 0 0);
    -webkit-mask-composite:xor;
    pointer-events:none;
}

@keyframes fadeSlide{
    from{
        opacity:0;
        transform:translateY(30px) scale(.96);
    }
    to{
        opacity:1;
        transform:translateY(0) scale(1);
    }
}

/* Header */
.register-header{
    text-align:center;
    margin-bottom:25px;
}

.register-header .logo{
    width:85px;
    height:85px;
    margin:auto;
    border-radius:50%;

    display:flex;
    align-items:center;
    justify-content:center;

    background:rgba(245, 158, 11,0.12);

    border:1px solid rgba(245, 158, 11,0.25);

    box-shadow:
    0 0 30px rgba(245, 158, 11,.25),
    inset 0 0 20px rgba(245, 158, 11,.08);

    animation:pulseGlow 2.5s infinite;
}

.register-header .material-icons{
    font-size:42px;
    color:#ff3d3d;
}

@keyframes pulseGlow{
    0%,100%{
        box-shadow:
        0 0 20px rgba(245, 158, 11,.18);
    }
    50%{
        box-shadow:
        0 0 40px rgba(245, 158, 11,.35);
    }
}

.register-header h2{
    margin-top:18px;
    font-size:28px;
    font-weight:600;
    letter-spacing:.5px;
}

.register-header p{
    font-size:14px;
    color:rgba(255,255,255,.65);
}

/* Inputs */
.form-group{
    margin-bottom:18px;
}

label{
    display:block;
    margin-bottom:8px;
    font-size:13px;
    color:rgba(255,255,255,.75);
    font-weight:500;
}

.input-wrapper{
    position:relative;
}

.input-wrapper .material-icons{
    position:absolute;
    left:15px;
    top:50%;
    transform:translateY(-50%);
    color:#f59e0b;
    font-size:22px;
    transition:.3s;
}

input,
textarea{
    width:100%;
    border:none;
    outline:none;

    padding:14px 18px 14px 50px;
    border-radius:16px;

    background:rgba(255,255,255,.06);
    border:1px solid rgba(255,255,255,.08);

    color:#fff;
    font-size:14px;

    transition:.35s ease;
    box-shadow:inset 0 0 10px rgba(0,0,0,.3);
}

textarea{
    resize:none;
    height:85px;
}

input::placeholder,
textarea::placeholder{
    color:rgba(255,255,255,.35);
}

/* Animated Input Effects */
input:hover,
textarea:hover{
    border-color:rgba(245, 158, 11,.25);
}

input:focus,
textarea:focus{
    border:1px solid rgba(245, 158, 11,.5);
    background:rgba(255,255,255,.08);

    box-shadow:
    0 0 18px rgba(245, 158, 11,.22),
    inset 0 0 10px rgba(245, 158, 11,.05);

    transform:translateY(-2px);
}

.input-wrapper:focus-within .material-icons{
    color:#f59e0b;
    transform:translateY(-50%) scale(1.1);
    text-shadow:0 0 12px rgba(245, 158, 11,.5);
}

/* Button */
.btn-register{
    width:100%;
    border:none;
    outline:none;

    padding:15px;
    border-radius:16px;

    background:
    linear-gradient(135deg,#f59e0b,#d97706);

    color:#fff;
    font-size:15px;
    font-weight:600;
    letter-spacing:.5px;

    cursor:pointer;
    transition:.35s ease;

    display:flex;
    align-items:center;
    justify-content:center;
    gap:10px;

    margin-top:8px;

    box-shadow:
    0 10px 30px rgba(245, 158, 11,.25);
}

.btn-register:hover{
    transform:translateY(-3px) scale(1.02);

    box-shadow:
    0 18px 35px rgba(245, 158, 11,.35);
}

.btn-register:active{
    transform:scale(.98);
}

/* Alerts */
.error-message,
.success-message{
    padding:14px;
    border-radius:14px;
    margin-bottom:18px;
    font-size:13px;
    backdrop-filter:blur(10px);
}

.error-message{
    background:rgba(245, 158, 11,.08);
    border:1px solid rgba(245, 158, 11,.2);
    color:#ff8a8a;
}

.success-message{
    background:rgba(0,255,100,.08);
    border:1px solid rgba(0,255,100,.2);
    color:#7dffae;
}

/* Login Link */
.login-link{
    text-align:center;
    margin-top:22px;
    font-size:13px;
    color:rgba(255,255,255,.65);
}

.login-link a{
    color:#f59e0b;
    text-decoration:none;
    font-weight:600;
    transition:.3s;
}

.login-link a:hover{
    color:#f59e0b;
    text-shadow:0 0 10px rgba(245, 158, 11,.5);
}

/* Responsive */
@media (max-width: 1024px){
    .register-card{
        max-width:100%;
        padding:28px;
        border-radius:24px;
    }

    .register-header h2{
        font-size:24px;
    }
}

@media(max-width:480px){
    body{
        padding:15px;
    }

    .register-card{
        padding:22px;
        border-radius:22px;
    }

    .register-header .logo{
        width:75px;
        height:75px;
    }

    .register-header h2{
        font-size:22px;
    }

    input,
    textarea{
        font-size:13px;
    }
}
</style>
<link rel="stylesheet" href="../assets/responsive.css">
</head>

<body>

<div class="register-container">
    <div class="register-card">

        <div class="register-header">
            <div class="logo">
                <span class="material-icons">person_add</span>
            </div>
            <h2>Create Account</h2>
        </div>

        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">

            <div class="form-group">
                <label>Full Name</label>
                <div class="input-wrapper">
                    <span class="material-icons">person</span>
                    <input type="text" name="fullname" placeholder="Full Name" required>
                </div>
            </div>

            <div class="form-group">
                <label>Email</label>
                <div class="input-wrapper">
                    <span class="material-icons">email</span>
                    <input type="email" name="email" placeholder="Email" required>
                </div>
            </div>

            <div class="form-group">
                <label>Phone</label>
                <div class="input-wrapper">
                    <span class="material-icons">phone</span>
                    <input type="text" name="phone" placeholder="Phone" required>
                </div>
            </div>

            <div class="form-group">
                <label>Address</label>
                <div class="input-wrapper">
                    <span class="material-icons">location_on</span>
                    <textarea name="address" placeholder="Address" required></textarea>
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
        </div>

            <button class="btn-register">
                <span class="material-icons" style="vertical-align:middle;">how_to_reg</span>
                Create Account
            </button>

        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Login</a>
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