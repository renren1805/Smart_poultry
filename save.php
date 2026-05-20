<?php
session_start();
require('../connection.php');

date_default_timezone_set("Asia/Manila");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: register.php");
    exit();
}

// INPUTS
$fullname = trim($_POST['fullname']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone']);
$address = trim($_POST['address']);
$password = $_POST['password'];

$created_at = date("Y-m-d H:i:s");
$updated_at = date("Y-m-d H:i:s");
$status = "active";

// VALIDATION
if (empty($fullname) || empty($email) || empty($phone) || empty($address) || empty($password)) {
    die("All fields are required!");
}

// CHECK EMAIL EXIST
$check = $connection->prepare("SELECT id FROM customers WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    die("Email already exists!");
}

// HASH PASSWORD
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// PROFILE IMAGE UPLOAD
$profile_image = "";

if (!empty($_FILES['profile_image']['name'])) {

    $upload_dir = "uploads/";

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $image_name = time() . "_" . basename($_FILES["profile_image"]["name"]);
    $target_file = $upload_dir . $image_name;

    move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file);

    $profile_image = $image_name;
}

// INSERT DATA
$stmt = $connection->prepare("
    INSERT INTO customers 
    (fullname, email, phone, address, password, profile_image, status, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "sssssssss",
    $fullname,
    $email,
    $phone,
    $address,
    $hashed_password,
    $profile_image,
    $status,
    $created_at,
    $updated_at
);

if ($stmt->execute()) {
    header("Location: login.php?registered=success");
    exit();
} else {
    die("Error saving data!");
}
?>