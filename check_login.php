<?php
session_start();
require('../connection.php');

// SHOW ERRORS FOR DEBUGGING
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // CHECK EMPTY FIELDS
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "All fields are required!";
        header("Location: login.php");
        exit();
    }

    // GET USER FROM DATABASE
    $stmt = $connection->prepare("
        SELECT id, fullname, email, password, status 
        FROM customers 
        WHERE email = ? 
        LIMIT 1
    ");

    if (!$stmt) {
        die("Prepare failed: " . $connection->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    // USER FOUND
    if ($result->num_rows === 1) {

        $user = $result->fetch_assoc();

        // DEBUG (REMOVE LATER)
        // echo "<pre>";
        // print_r($user);
        // exit();

        // VERIFY PASSWORD
        if (password_verify($password, $user['password'])) {

            // CHECK STATUS
            if (strtolower($user['status']) !== "active") {
                $_SESSION['error'] = "Account is not active.";
                header("Location: login.php");
                exit();
            }

            // CREATE SESSION
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['email'] = $user['email'];

            // FORCE SESSION SAVE
            session_write_close();

            // REDIRECT TO DASHBOARD
            header("Location: dashboard.php");
            exit();

        } else {
            $_SESSION['error'] = "Incorrect password!";
            header("Location: login.php");
            exit();
        }

    } else {
        $_SESSION['error'] = "No account found with that email!";
        header("Location: login.php");
        exit();
    }

} else {
    header("Location: login.php");
    exit();
}
?>