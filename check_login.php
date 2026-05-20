<?php
session_start();
require('../connection.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validation
    if (empty($email) || empty($password)) {
        die("All fields are required!");
    }

    // Get admin from admins table
    $stmt = $connection->prepare("SELECT id, name, email, password FROM admin WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $admin['password'])) {

            // ✅ Set session
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['name'] = $admin['name'];
            $_SESSION['is_admin'] = true;

            header("Location: dashboard.php");
            exit();

        } else {
            echo "Invalid password!";
        }

    } else {
        echo "Admin not found!";
    }

    $stmt->close();
    $connection->close();

} else {
    // Prevent direct access
    header("Location: login.php");
    exit();
}
?>