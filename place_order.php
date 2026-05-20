<?php
session_start();
require('../connection.php');

// FIXED LOGIN CHECK
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['user_id'];

// Get customer info
$customer_query = "SELECT * FROM customers WHERE id = ?";
$stmt = mysqli_prepare($connection, $customer_query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$customer = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Get cart items
$cart_query = "SELECT 
                sc.id,
                sc.product_id,
                sc.quantity,
                ii.name,
                ii.selling_price,
                ii.current_quantity
            FROM shopping_cart sc
            LEFT JOIN inventory_items ii 
                ON sc.product_id = ii.id
            WHERE sc.customer_id = ?";

$cart_stmt = mysqli_prepare($connection, $cart_query);
mysqli_stmt_bind_param($cart_stmt, "i", $customer_id);
mysqli_stmt_execute($cart_stmt);

$cart_items = mysqli_stmt_get_result($cart_stmt);

// Calculate total
$cart_data = [];
$total_amount = 0;

if ($cart_items && mysqli_num_rows($cart_items) > 0) {

    while ($item = mysqli_fetch_assoc($cart_items)) {

        $price = floatval($item['selling_price'] ?? 0);
        $qty = intval($item['quantity'] ?? 0);

        $item_total = $price * $qty;

        $cart_data[] = [
            'product_id' => $item['product_id'],
            'name' => $item['name'] ?? 'Unknown Product',
            'selling_price' => $price,
            'quantity' => $qty,
            'item_total' => $item_total
        ];

        $total_amount += $item_total;
    }
}
// Handle order placement
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $delivery_address = mysqli_real_escape_string($connection, $_POST['delivery_address']);
    $payment_method = mysqli_real_escape_string($connection, $_POST['payment_method']);
    $notes = mysqli_real_escape_string($connection, $_POST['notes']);
    
    // Validate cart is not empty
    if (empty($cart_data)) {
        $error = "Your cart is empty";
    } else {
        // Generate order number
        $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(uniqid());
        
        // Create order
        $order_query = "INSERT INTO orders (customer_id, order_number, total_amount, status, payment_method, delivery_address, notes) 
                        VALUES (?, ?, ?, 'Draft', ?, ?, ?)";
        $order_stmt = mysqli_prepare($connection, $order_query);
        mysqli_stmt_bind_param($order_stmt, "isdsss", $customer_id, $order_number, $total_amount, $payment_method, $delivery_address, $notes);
        mysqli_stmt_execute($order_stmt);
        
        $order_id = mysqli_insert_id($connection);
        
        // Add order items
        foreach ($cart_data as $item) {
            $order_item_query = "INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price) 
                                  VALUES (?, ?, ?, ?, ?)";
            $item_stmt = mysqli_prepare($connection, $order_item_query);
            $item_total = $item['selling_price'] * $item['quantity'];
            mysqli_stmt_bind_param($item_stmt, "iiddd", $order_id, $item['product_id'], $item['quantity'], $item['selling_price'], $item_total);
            mysqli_stmt_execute($item_stmt);
            
            // DEDUCT INVENTORY
            $deduct_query = "UPDATE inventory_items SET current_quantity = GREATEST(0, current_quantity - ?) WHERE id = ?";
            $deduct_stmt = mysqli_prepare($connection, $deduct_query);
            mysqli_stmt_bind_param($deduct_stmt, "ii", $item['quantity'], $item['product_id']);
            mysqli_stmt_execute($deduct_stmt);
            
            // LOG TO stock_movements
            $log_query = "INSERT INTO stock_movements (product_id, product_name, movement_type, quantity, reference, user_name) 
                         VALUES (?, ?, 'out', ?, ?, ?)";
            $log_stmt = mysqli_prepare($connection, $log_query);
            $reference = "Order Placed #" . $order_number;
            $user_name = "System (Customer)";
            mysqli_stmt_bind_param($log_stmt, "issis", $item['product_id'], $item['name'], $item['quantity'], $reference, $user_name);
            mysqli_stmt_execute($log_stmt);
        }
        
        // Clear cart
        $clear_cart = "DELETE FROM shopping_cart WHERE customer_id = ?";
        $clear_stmt = mysqli_prepare($connection, $clear_cart);
        mysqli_stmt_bind_param($clear_stmt, "i", $customer_id);
        mysqli_stmt_execute($clear_stmt);
        
        // Redirect to payment or order history based on payment method
        if ($payment_method == 'cod') {
            header("Location: make_payment.php?order_id=$order_id");
        } else {
            header("Location: make_payment.php?order_id=$order_id");
        }
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place Order</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:'Outfit', sans-serif;
        }

        :root{
            --primary: #f59e0b;
            --secondary: #a83434;
            --accent: #d97706;
            --warning: #FBBC05;
            --dark: #0f172a;
            --light: #f8fafc;
            --blue-glow: 0 0 30px rgba(244,66,66,.4);
        }

        body{
            min-height:100vh;
            background:linear-gradient(135deg, #090e1a 0%, #0f172a 50%, #090e1a 100%);
            background-attachment:fixed;
            color:white;
            overflow-x:hidden;
        }

        .container{
            display:flex;
            min-height:100vh;
        }

        /* SIDEBAR */
        .sidebar{
            width:280px;
            background:rgba(15, 23, 42, 0.8);
            backdrop-filter:blur(20px);
            border-right:1px solid rgba(255,255,255,.1);
            padding:30px 20px;
            position:fixed;
            top:0;
            left:0;
            height:100vh;
            overflow-y:auto;
            transition:.3s ease;
            z-index:9999;
        }

        .sidebar h2{
            font-size:28px;
            font-weight:700;
            color:#fff;
            margin-bottom:40px;
            text-shadow:0 0 20px rgba(245, 158, 11, 0.5);
            display:flex;
            align-items:center;
            gap:10px;
        }

        .sidebar-menu{
            list-style:none;
        }

        .sidebar-menu li{
            margin-bottom:10px;
        }

        .sidebar-menu a{
            display:flex;
            align-items:center;
            gap:12px;
            padding:14px 18px;
            color:#94a3b8;
            text-decoration:none;
            border-radius:12px;
            transition:.3s ease;
            font-weight:500;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active{
            background:rgba(244,66,66,.15);
            color:#fff;
            border:1px solid rgba(244,66,66,.25);
        }

        .sidebar-menu .material-symbols-outlined{
            font-size:22px;
        }

        .cart-badge{
            background:var(--accent);
            color:white;
            padding:2px 8px;
            border-radius:10px;
            font-size:12px;
            font-weight:600;
            margin-left:auto;
        }

        /* MAIN CONTENT */
        .main-content{
            margin-left:280px;
            flex:1;
            padding:30px;
        }

        .header{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:30px;
            padding:20px 30px;
            background:rgba(255,255,255,.05);
            backdrop-filter:blur(15px);
            border-radius:20px;
            border:1px solid rgba(255,255,255,.1);
        }

        .header h1{
            font-size:32px;
            font-weight:700;
            color:#fff;
            text-shadow:0 0 20px rgba(244,66,66,.3);
        }

        /* ORDER FORM */
        .order-content{
            display:grid;
            grid-template-columns:1.5fr 1fr;
            gap:30px;
        }

        .order-form{
            background:rgba(255,255,255,.05);
            backdrop-filter:blur(20px);
            border-radius:20px;
            padding:30px;
            border:1px solid rgba(255,255,255,.1);
        }

        .order-form h2{
            font-size:24px;
            font-weight:600;
            color:#fff;
            margin-bottom:25px;
        }

        .form-group{
            margin-bottom:20px;
        }

        .form-group label{
            display:block;
            margin-bottom:8px;
            color:#94a3b8;
            font-size:14px;
            font-weight:500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea{
            width:100%;
            padding:14px 18px;
            border:1px solid rgba(255,255,255,.1);
            border-radius:12px;
            background:rgba(255,255,255,.05);
            backdrop-filter:blur(15px);
            color:white;
            font-size:14px;
            outline:none;
            transition:.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus{
            border-color:var(--primary);
            box-shadow:var(--blue-glow);
        }

        .form-group textarea{
            resize:vertical;
            min-height:100px;
        }

        .form-group select option{
            background:#1e293b;
            color:white;
        }

        /* ORDER SUMMARY */
        .order-summary{
            background:rgba(255,255,255,.05);
            backdrop-filter:blur(20px);
            border-radius:20px;
            padding:30px;
            border:1px solid rgba(255,255,255,.1);
            height:fit-content;
            position:sticky;
            top:30px;
        }

        .order-summary h2{
            font-size:24px;
            font-weight:600;
            color:#fff;
            margin-bottom:25px;
        }

        .summary-item{
            display:flex;
            justify-content:space-between;
            margin-bottom:15px;
            padding-bottom:15px;
            border-bottom:1px solid rgba(255,255,255,.05);
        }

        .summary-item:last-child{
            border-bottom:none;
        }

        .summary-item-name{
            color:#94a3b8;
            font-size:14px;
        }

        .summary-item-value{
            color:#fff;
            font-weight:600;
            font-size:16px;
        }

        .summary-total{
            margin-top:20px;
            padding-top:20px;
            border-top:2px solid rgba(245, 158, 11, 0.2);
        }

        .summary-total .summary-item-value{
            font-size:28px;
            color:var(--secondary);
        }

        .place-order-btn{
            width:100%;
            padding:18px;
            background:var(--primary);
            color:white;
            border:none;
            border-radius:14px;
            font-size:18px;
            font-weight:700;
            cursor:pointer;
            margin-top:25px;
            transition:.3s ease;
        }

        .place-order-btn:hover{
            background:var(--secondary);
            transform:translateY(-2px);
            box-shadow:0 0 20px rgba(168, 52, 52, 0.35);
        }

        .place-order-btn:disabled{
            opacity:0.5;
            cursor:not-allowed;
        }

        /* BUTTONS */
        .back-btn{
            background:rgba(245, 158, 11, 0.15);
            color:white;
            border:1px solid rgba(245, 158, 11, 0.2);
            padding:12px 20px;
            text-decoration:none;
            backdrop-filter:blur(12px);
            border-radius:14px;
            font-weight:600;
            transition:.3s ease;
        }

        .back-btn:hover{
            background:var(--primary);
            transform:translateY(-2px);
            box-shadow:0 0 20px rgba(245, 158, 11, 0.35);
        }

        .menu-toggle{
            background:rgba(245, 158, 11, 0.15);
            color:white;
            padding:12px 16px;
            margin-right:15px;
            border:1px solid rgba(255,255,255,.1);
            border-radius:14px;
            cursor:pointer;
            display:none;
        }

        .menu-toggle:hover{
            background:var(--primary);
        }

        .error{
            background:rgba(217, 119, 6,.15);
            color:#ea4335;
            padding:15px;
            border-radius:12px;
            margin-bottom:20px;
            border:1px solid rgba(217, 119, 6,.25);
        }

        /* RESPONSIVE */
        @media (max-width: 1024px){
            .header{
                flex-direction:column;
                align-items:flex-start;
                gap:15px;
            }

            .order-content{
                grid-template-columns:1fr;
            }

            .order-summary{
                position:static;
            }
        }
    </style>
<link rel="stylesheet" href="../assets/responsive.css">
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
    <div class="container">
        <div class="sidebar">
            <h2>
                <span class="material-symbols-outlined">storefront</span>
                Poultry Shop
            </h2>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><span class="material-symbols-outlined">dashboard</span> Dashboard</a></li>
                <li><a href="browse_products.php"><span class="material-symbols-outlined">shopping_bag</span> Browse Products</a></li>
                <li><a href="cart.php"><span class="material-symbols-outlined">shopping_cart</span> My Cart</a></li>
                <li><a href="view_order_history.php"><span class="material-symbols-outlined">history</span> Order History</a></li>
                <li><a href="check_receipt.php"><span class="material-symbols-outlined">receipt_long</span> Check Receipt</a></li>
                <li><a href="logout.php" class="logout-btn"><span class="material-symbols-outlined">logout</span> Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="header">
                <div style="display:flex; align-items:center;">
                    <button class="menu-toggle" onclick="toggleSidebar()"><span class="material-symbols-outlined">menu</span></button>
                    <h1>Place Order</h1>
                </div>
                <a href="cart.php" class="back-btn">← Back to Cart</a>
            </div>

            <?php if (empty($cart_data)): ?>
                <div style="text-align:center; padding:60px 20px; color:#94a3b8;">
                    <span class="material-symbols-outlined" style="font-size:80px; margin-bottom:20px;">shopping_cart</span>
                    <p style="font-size:18px; margin-bottom:20px;">Your cart is empty</p>
                    <a href="browse_products.php" class="back-btn">Start Shopping</a>
                </div>
            <?php else: ?>
                <?php if (isset($error)): ?>
                    <div class="error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" class="order-content">
                    <div class="order-form">
                        <h2>Delivery Information</h2>
                        
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" value="<?= htmlspecialchars($customer['fullname']) ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" value="<?= htmlspecialchars($customer['email']) ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" value="<?= htmlspecialchars($customer['phone']) ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label>Delivery Address *</label>
                            <textarea name="delivery_address" required placeholder="Enter your complete delivery address"><?= htmlspecialchars($customer['address']) ?></textarea>
                        </div>

                        <h2 style="margin-top:30px;">Payment Method</h2>

                        <div class="form-group">
                            <label>Select Payment Method *</label>
                            <select name="payment_method" required>
                                <option value="cod">Cash on Delivery (COD)</option>
                                <option value="gcash">GCash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Order Notes (Optional)</label>
                            <textarea name="notes" placeholder="Any special instructions for your order"></textarea>
                        </div>

                        <button type="submit" class="place-order-btn">Place Order</button>
                    </div>

                    <div class="order-summary">
                        <h2>Order Summary</h2>
                        <?php if (count($cart_data) > 0): ?>
                            <?php foreach ($cart_data as $item): ?>
                            <div class="summary-item">
                                <span class="summary-item-name"><?= htmlspecialchars($item['name']) ?> × <?= intval($item['quantity']) ?></span>
                                <span class="summary-item-value">₱<?= number_format($item['item_total'], 2) ?></span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <div class="summary-item">
                            <span class="summary-item-name">Subtotal</span>
                            <span class="summary-item-value">₱<?= number_format($total_amount, 2) ?></span>
                        </div>
                        
                        <div class="summary-item">
                            <span class="summary-item-name">Shipping</span>
                            <span class="summary-item-value">Free</span>
                        </div>
                        
                        <div class="summary-item summary-total">
                            <span class="summary-item-name">Total</span>
                            <span class="summary-item-value">₱<?= number_format($total_amount, 2) ?></span>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    
<script src="../assets/sidebar.js"></script>
</body>
</html>
