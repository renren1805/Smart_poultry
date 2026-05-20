<?php
session_start();
require('../connection.php');

$message = '';
$error = '';
$sendEmailjs = false;
$email = '';
$adminName = '';
$resetLink = '';

if (isset($_GET['sent']) && $_GET['sent'] == 1) {
    $message = "Reset link sent successfully!";
}

if (isset($_POST['send'])) {
    $email = $_POST['email'];

    $check = mysqli_query($connection, "SELECT * FROM admins WHERE Email='$email'");

    if (mysqli_num_rows($check) > 0) {
        $admin = mysqli_fetch_assoc($check);
        $adminName = $admin['fullname'] ?? 'Admin';

        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        mysqli_query($connection, "UPDATE admins SET reset_token='$token', token_expiry='$expiry' WHERE Email='$email'");

        $resetLink = "http://localhost/FINALS/admin/reset_password.php?token=$token";
        $sendEmailjs = true;
    } else {
        $error = "Email not found.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>

    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        body {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: 
            radial-gradient(circle at top left, rgba(245, 158, 11,0.15), transparent 30%),
            radial-gradient(circle at bottom right, rgba(120,0,0,0.25), transparent 30%),
            linear-gradient(135deg,#0f172a,#090e1a,#000);
            overflow: hidden;
            color: white;
        }

        /* animated glow background */
        body::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: rgba(245, 158, 11, 0.12);
            filter: blur(140px);
            border-radius: 50%;
            top: -100px;
            left: -100px;
            animation: floatGlow 8s ease-in-out infinite;
        }

        @keyframes floatGlow {
            0%,100% { transform: translate(0, 0); }
            50% { transform: translate(40px, 30px); }
        }

        .box {
            position: relative;
            width: 400px;
            padding: 40px;
            border-radius: 30px;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 
                0 8px 32px rgba(0,0,0,0.5),
                0 0 30px rgba(245, 158, 11, 0.15);
            animation: fadeIn 0.8s ease;
        }

        .box::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 30px;
            padding: 1px;
            background: linear-gradient(
                135deg,
                rgba(245, 158, 11, 0.5),
                transparent,
                rgba(255, 80, 80, 0.3)
            );
            -webkit-mask:
                linear-gradient(#fff 0 0) content-box,
                linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            pointer-events: none;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(40px) scale(.96); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        h2 {
            text-align: center;
            color: #fff;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        h2 .material-symbols-outlined {
            font-size: 32px;
            color: #f59e0b;
        }

        .sub {
            text-align: center;
            color: #bfbfbf;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .input-group {
            position: relative;
            margin-bottom: 20px;
        }

        .input-group .material-symbols-outlined {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #f59e0b;
            font-size: 22px;
        }

        input {
            width: 100%;
            height: 55px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.06);
            color: white;
            padding: 0 15px 0 50px;
            font-size: 15px;
            outline: none;
            transition: 0.35s ease;
            backdrop-filter: blur(10px);
        }

        input::placeholder {
            color: #888;
        }

        input:focus {
            border-color: #f59e0b;
            box-shadow: 
                0 0 0 4px rgba(245, 158, 11, 0.12),
                0 0 18px rgba(245, 158, 11, 0.25);
            transform: translateY(-2px);
        }

        button {
            width: 100%;
            height: 55px;
            border: none;
            border-radius: 16px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: .35s ease;
            box-shadow: 0 8px 20px rgba(245, 158, 11, 0.25);
        }

        button:hover {
            transform: translateY(-3px);
            box-shadow: 
                0 10px 25px rgba(245, 158, 11, .4),
                0 0 20px rgba(245, 158, 11, .25);
        }

        button:active {
            transform: scale(.98);
        }

        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.2);
            color: #4ade80;
            padding: 14px;
            border-radius: 14px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
        }

        .error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #f87171;
            padding: 14px;
            border-radius: 14px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
        }

        .back {
            text-align: center;
            margin-top: 22px;
        }

        .back a {
            color: #f59e0b;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: 0.3s;
        }

        .back a:hover {
            text-shadow: 0 0 10px rgba(245, 158, 11, 0.5);
        }
    </style>
<link rel="stylesheet" href="../assets/responsive.css">
</head>

<body>

<div class="box">
    <h2><span class="material-symbols-outlined">lock_reset</span> Forgot Password</h2>
    <p class="sub">Enter your email to receive a reset link</p>

    <?php if ($message) echo "<div class='success'>✔ $message</div>"; ?>
    <?php if ($error) echo "<div class='error'>✖ $error</div>"; ?>

    <form method="POST">
        <div class="input-group">
            <span class="material-symbols-outlined">mail</span>
            <input type="email" name="email" placeholder="Email Address" required>
        </div>

        <button type="submit" name="send">Send Reset Link</button>
    </form>

    <div class="back">
        <a href="login.php">← Back to Login</a>
    </div>
</div>

<!-- EmailJS SDK -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const box = document.querySelector(".box");
    box.style.opacity = "0";
    box.style.transform = "translateY(20px)";
    setTimeout(() => {
        box.style.transition = "0.6s ease";
        box.style.opacity = "1";
        box.style.transform = "translateY(0)";
    }, 100);
});
</script>

<?php if ($sendEmailjs): ?>
<script type="text/javascript">
    (function() {
        // Initialize EmailJS with your Public Key
        emailjs.init({
            publicKey: "tPPG5kXv_HpogUqT6"
        });

        // Show visual state during sending
        const btn = document.querySelector("button[name='send']");
        if (btn) {
            btn.disabled = true;
            btn.textContent = "Sending via EmailJS...";
        }

        // Send reset email via EmailJS
        emailjs.send("service_omhbzem", "template_gmg3xll", {
            to_email: "<?= htmlspecialchars($email) ?>",
            email: "<?= htmlspecialchars($email) ?>",
            to_name: "<?= htmlspecialchars($adminName) ?>",
            name: "<?= htmlspecialchars($adminName) ?>",
            reset_link: "<?= htmlspecialchars($resetLink) ?>"
        }).then(
            function(response) {
                alert("Reset link sent successfully via EmailJS!");
                window.location.href = "forgot_password.php?sent=1";
            },
            function(error) {
                alert("EmailJS Send Failed: " + JSON.stringify(error));
                const btn = document.querySelector("button[name='send']");
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = "Send Reset Link";
                }
            }
        );
    })();
</script>
<?php endif; ?>

</body>
</html>