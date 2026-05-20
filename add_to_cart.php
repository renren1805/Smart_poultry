<?php
session_start();
require('../connection.php');

header('Content-Type: application/json');

// ✅ FIX 1: proper login check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$customer_id = $_SESSION['user_id'];

// ✅ FIX 2: validate request
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$product_id = intval($_POST['product_id']);
$quantity = intval($_POST['quantity']);

// Validate product exists and is active
$product_query = "SELECT * FROM inventory_items WHERE id = ? AND status = 'active'";
$stmt = mysqli_prepare($connection, $product_query);
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$product_result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($product_result);

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit();
}

// Check stock availability
if ($product['current_quantity'] < $quantity) {
    echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
    exit();
}

// Check if item already in cart
$cart_check = "SELECT * FROM shopping_cart WHERE customer_id = ? AND product_id = ?";
$check_stmt = mysqli_prepare($connection, $cart_check);
mysqli_stmt_bind_param($check_stmt, "ii", $customer_id, $product_id);
mysqli_stmt_execute($check_stmt);
$existing_item = mysqli_fetch_assoc(mysqli_stmt_get_result($check_stmt));

if ($existing_item) {

    $new_quantity = $existing_item['quantity'] + $quantity;

    if ($product['current_quantity'] < $new_quantity) {
        echo json_encode(['success' => false, 'message' => 'Cannot exceed stock']);
        exit();
    }

    $update_query = "UPDATE shopping_cart SET quantity = ? WHERE id = ?";
    $update_stmt = mysqli_prepare($connection, $update_query);
    mysqli_stmt_bind_param($update_stmt, "ii", $new_quantity, $existing_item['id']);
    mysqli_stmt_execute($update_stmt);

} else {

    $insert_query = "INSERT INTO shopping_cart (customer_id, product_id, quantity) VALUES (?, ?, ?)";
    $insert_stmt = mysqli_prepare($connection, $insert_query);
    mysqli_stmt_bind_param($insert_stmt, "iii", $customer_id, $product_id, $quantity);
    mysqli_stmt_execute($insert_stmt);
}

// OPTIONAL: return updated cart count (for badge update)
$count_query = "SELECT SUM(quantity) as total FROM shopping_cart WHERE customer_id = ?";
$count_stmt = mysqli_prepare($connection, $count_query);
mysqli_stmt_bind_param($count_stmt, "i", $customer_id);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$total = mysqli_fetch_assoc($count_result)['total'];

echo json_encode([
    'success' => true,
    'message' => 'Product added to cart',
    'cart_count' => $total ? $total : 0
]);
?>