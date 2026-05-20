<?php
session_start();
require('../connection.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['user_id'];

if (!isset($_GET['order_id']) || !isset($_GET['product_id'])) {
    header("Location: view_order_history.php");
    exit();
}

$order_id = intval($_GET['order_id']);
$product_id = intval($_GET['product_id']);

/* GET ITEM FROM ORDER */
$query = "SELECT * FROM order_items 
          WHERE order_id = ? AND product_id = ?";

$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $product_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$item = mysqli_fetch_assoc($result);

if (!$item) {
    header("Location: view_order_history.php");
    exit();
}

/* CHECK IF ALREADY IN CART */
$check = "SELECT id, quantity FROM shopping_cart 
          WHERE customer_id = ? AND product_id = ?";

$stmt = mysqli_prepare($connection, $check);
mysqli_stmt_bind_param($stmt, "ii", $customer_id, $product_id);
mysqli_stmt_execute($stmt);

$res = mysqli_stmt_get_result($stmt);
$existing = mysqli_fetch_assoc($res);

if ($existing) {
    $new_qty = $existing['quantity'] + $item['quantity'];

    $update = "UPDATE shopping_cart SET quantity = ? WHERE id = ?";
    $stmt = mysqli_prepare($connection, $update);
    mysqli_stmt_bind_param($stmt, "ii", $new_qty, $existing['id']);
    mysqli_stmt_execute($stmt);

} else {

    $insert = "INSERT INTO shopping_cart (customer_id, product_id, quantity)
               VALUES (?, ?, ?)";

    $stmt = mysqli_prepare($connection, $insert);
    mysqli_stmt_bind_param($stmt, "iii", $customer_id, $product_id, $item['quantity']);
    mysqli_stmt_execute($stmt);
}

header("Location: cart.php");
exit();
?>