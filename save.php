<?php
session_start();
require('../connection.php');

// Only logged-in admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access denied!");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role']; // admin or customer

    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        die("All fields are required!");
    }

    // 🚨 CHECK: only ONE admin allowed
    if ($role === 'admin') {
        $checkAdmin = $connection->query("SELECT id FROM admins WHERE role = 'admin' LIMIT 1");
        if ($checkAdmin->num_rows > 0) {
            die("Admin already exists! You cannot create another admin.");
        }
    }

    // Check duplicate email
    $check = $connection->prepare("SELECT id FROM admins WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        die("Email already registered!");
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $stmt = $connection->prepare(
        "INSERT INTO admins (name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())"
    );
    $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);

    if ($stmt->execute()) {
        header("Location: admin_dashboard.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $connection->close();
}
?>