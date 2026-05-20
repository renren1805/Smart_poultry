<?php
session_start();
require('../connection.php');

$customer_id = $_SESSION['user_id'];

// Handle cart updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'update_quantity') {
            $cart_id = intval($_POST['cart_id']);
            $quantity = intval($_POST['quantity']);
            
            $update_query = "UPDATE shopping_cart SET quantity = ? WHERE id = ? AND customer_id = ?";
            $stmt = mysqli_prepare($connection, $update_query);
            mysqli_stmt_bind_param($stmt, "iii", $quantity, $cart_id, $customer_id);
            mysqli_stmt_execute($stmt);
        } elseif ($_POST['action'] == 'remove_item') {
            $cart_id = intval($_POST['cart_id']);
            
            $delete_query = "DELETE FROM shopping_cart WHERE id = ? AND customer_id = ?";
            $stmt = mysqli_prepare($connection, $delete_query);
            mysqli_stmt_bind_param($stmt, "ii", $cart_id, $customer_id);
            mysqli_stmt_execute($stmt);
        }
        header("Location: cart.php");
        exit();
    }
}

// Get cart items
$cart_query = "SELECT sc.*, name, selling_price, image_path, current_quantity, unit 
                FROM shopping_cart sc
                JOIN inventory_items ON product_id = inventory_items.id
                WHERE sc.customer_id = ?";
$stmt = mysqli_prepare($connection, $cart_query);
mysqli_stmt_bind_param($stmt, "i", $customer_id);
mysqli_stmt_execute($stmt);
$cart_items = mysqli_stmt_get_result($stmt);

// Calculate totals
$total_amount = 0;
$total_items = 0;
$cart_data = [];

while ($item = mysqli_fetch_assoc($cart_items)) {
    $item_total = $item['selling_price'] * $item['quantity'];
    $total_amount += $item_total;
    $total_items += $item['quantity'];
    $cart_data[] = $item;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
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
            --secondary: #a87a34ff;
            --accent: #d97706;
            --warning: #FBBC05;
            --dark: #0f172a;
            --light: #f8fafc;
            --blue-glow: 0 0 30px rgba(245, 158, 11, 0.4);
        }

        body{
            min-height:100vh;
            background:linear-gradient(135deg, #0f172a 0%, #0f172a 50%, #090e1a 100%);
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
            border-right:1px solid rgba(255, 255, 255, 0.1);
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
            background:rgba(245, 158, 11, 0.15);
            color:#fff;
            border:1px solid rgba(255, 6, 6, 0.25).25);
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
            text-shadow:0 0 20px rgba(245, 158, 11, 0.3);
        }

        /* CART CONTENT */
        .cart-content{
            display:grid;
            grid-template-columns:2fr 1fr;
            gap:30px;
        }

        .cart-items{
            background:rgba(255,255,255,.05);
            backdrop-filter:blur(20px);
            border-radius:20px;
            padding:30px;
            border:1px solid rgba(255,255,255,.1);
        }

        .cart-items h2{
            font-size:24px;
            font-weight:600;
            color:#fff;
            margin-bottom:25px;
        }

        .cart-item{
            display:flex;
            gap:20px;
            padding:20px;
            background:rgba(255,255,255,.03);
            border-radius:16px;
            margin-bottom:15px;
            border:1px solid rgba(255,255,255,.05);
            transition:.3s ease;
        }

        .cart-item:hover{
            border-color:rgba(245, 158, 11, 0.2);
            transform:translateX(5px);
        }

        .cart-item-image{
            width:100px;
            height:100px;
            background:rgba(255,255,255,.05);
            border-radius:12px;
            display:flex;
            align-items:center;
            justify-content:center;
            overflow:hidden;
            flex-shrink:0;
        }

        .cart-item-image img{
            width:100%;
            height:100%;
            object-fit:cover;
        }

        .cart-item-image .no-image{
            color:#94a3b8;
            font-size:40px;
        }

        .cart-item-details{
            flex:1;
        }

        .cart-item-name{
            font-size:18px;
            font-weight:600;
            color:#fff;
            margin-bottom:8px;
        }

        .cart-item-price{
            font-size:20px;
            font-weight:700;
            color:var(--secondary);
            margin-bottom:10px;
        }

        .cart-item-quantity{
            display:flex;
            align-items:center;
            gap:10px;
        }

        .quantity-input{
            width:70px;
            height:40px;
            border:1px solid rgba(255,255,255,.1);
            background:rgba(255,255,255,.05);
            color:white;
            border-radius:10px;
            text-align:center;
            font-size:16px;
            font-weight:600;
            outline:none;
        }

        .cart-item-actions{
            display:flex;
            flex-direction:column;
            gap:10px;
        }

        .btn-small{
            padding:8px 12px;
            border-radius:10px;
            font-size:12px;
            font-weight:600;
            cursor:pointer;
            transition:.3s ease;
            border:none;
            outline:none;
        }

        .btn-update{
            background:rgba(245, 158, 11, 0.15);
            color:white;
            border:1px solid rgba(245, 158, 11, 0.2);
        }

        .btn-update:hover{
            background:var(--primary);
        }

        .btn-remove{
            background:rgba(217, 119, 6,.15);
            color:white;
            border:1px solid rgba(217, 119, 6,.2);
        }

        .btn-remove:hover{
            background:var(--accent);
        }

        /* CART SUMMARY */
        .cart-summary{
            background:rgba(255,255,255,.05);
            backdrop-filter:blur(20px);
            border-radius:20px;
            padding:30px;
            border:1px solid rgba(255,255,255,.1);
            height:fit-content;
            position:sticky;
            top:30px;
        }

        .cart-summary h2{
            font-size:24px;
            font-weight:600;
            color:#fff;
            margin-bottom:25px;
        }

        .summary-row{
            display:flex;
            justify-content:space-between;
            margin-bottom:15px;
            padding-bottom:15px;
            border-bottom:1px solid rgba(255,255,255,.05);
        }

        .summary-row:last-child{
            border-bottom:none;
        }

        .summary-label{
            color:#94a3b8;
            font-size:14px;
        }

        .summary-value{
            color:#fff;
            font-weight:600;
            font-size:16px;
        }

        .summary-total{
            margin-top:20px;
            padding-top:20px;
            border-top:2px solid rgba(245, 158, 11, 0.2);
        }

        .summary-total .summary-value{
            font-size:28px;
            color:var(--secondary);
        }

        .checkout-btn{
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

        .checkout-btn:hover{
            background:var(--secondary);
            transform:translateY(-2px);
            box-shadow:0 0 20px rgba(52,168,83,.35);
        }

        .checkout-btn:disabled{
            opacity:0.5;
            cursor:not-allowed;
        }

        .empty-cart{
            text-align:center;
            padding:60px 20px;
            color:#94a3b8;
        }

        .empty-cart .material-symbols-outlined{
            font-size:80px;
            margin-bottom:20px;
        }

        .empty-cart p{
            font-size:18px;
            margin-bottom:20px;
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

        /* RESPONSIVE */
        @media (max-width: 1024px){
            .header{
                flex-direction:column;
                align-items:flex-start;
                gap:15px;
            }

            .cart-content{
                grid-template-columns:1fr;
            }

            .cart-summary{
                position:static;
            }

            .cart-item{
                flex-direction:column;
            }

            .cart-item-image{
                width:100%;
                height:200px;
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
                Smart Poultry
            </h2>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><span class="material-symbols-outlined">dashboard</span> Dashboard</a></li>
                <li><a href="browse_products.php"><span class="material-symbols-outlined">shopping_bag</span> Browse Products</a></li>
                <li><a href="cart.php" class="active"><span class="material-symbols-outlined">shopping_cart</span> My Cart <span class="cart-badge"><?= $total_items ?></span></a></li>
                <li><a href="view_order_history.php"><span class="material-symbols-outlined">history</span> Order History</a></li>
                <li><a href="logout.php" class="logout-btn"><span class="material-symbols-outlined">logout</span> Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="header">
                <div style="display:flex; align-items:center;">
                    <button class="menu-toggle" onclick="toggleSidebar()"><span class="material-symbols-outlined">menu</span></button>
                    <h1>Shopping Cart</h1>
                </div>
                <a href="browse_products.php" class="back-btn">Continue Shopping</a>
            </div>

            <?php if (empty($cart_data)): ?>
                <div class="empty-cart">
                    <span class="material-symbols-outlined">shopping_cart</span>
                    <p>Your cart is empty</p>
                    <a href="browse_products.php" class="back-btn">Start Shopping</a>
                </div>
            <?php else: ?>
                <div class="cart-content">
                    <div class="cart-items">
                        <h2>Cart Items (<?= $total_items ?>)</h2>
                        <?php foreach ($cart_data as $item): ?>
                            <div class="cart-item">
                                <div class="cart-item-image">
                                    <?php if (!empty($item['image_path'])): ?>
                                        <img src="../<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                                    <?php else: ?>
                                        <span class="material-symbols-outlined no-image">image_not_supported</span>
                                    <?php endif; ?>
                                </div>
                                <div class="cart-item-details">
                                    <div class="cart-item-name"><?= htmlspecialchars($item['name']) ?></div>
                                    <div class="cart-item-price">₱<?= number_format($item['selling_price'], 2) ?></div>
                                    <div class="cart-item-quantity">
                                        <form method="POST" style="display:flex; align-items:center; gap:10px;">
                                            <input type="hidden" name="action" value="update_quantity">
                                            <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                                            <input type="number" name="quantity" class="quantity-input" 
                                                   value="<?= $item['quantity'] ?>" 
                                                   min="1" 
                                                   max="<?= $item['current_quantity'] ?>">
                                            <button type="submit" class="btn-small btn-update">Update</button>
                                        </form>
                                    </div>
                                </div>
                                <div class="cart-item-actions">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="remove_item">
                                        <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                                        <button type="submit" class="btn-small btn-remove">Remove</button>
                                    </form>
                                    <div style="font-weight:700; color:#fff; font-size:18px;">
                                        ₱<?= number_format($item['selling_price'] * $item['quantity'], 2) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="cart-summary">
                        <h2>Order Summary</h2>
                        <div class="summary-row">
                            <span class="summary-label">Subtotal (<?= $total_items ?> items)</span>
                            <span class="summary-value">₱<?= number_format($total_amount, 2) ?></span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Shipping</span>
                            <span class="summary-value">Free</span>
                        </div>
                        <div class="summary-row summary-total">
                            <span class="summary-label">Total</span>
                            <span class="summary-value">₱<?= number_format($total_amount, 2) ?></span>
                        </div>
                        <button class="checkout-btn" onclick="placeOrder()">Place Order</button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    
<script src="../assets/sidebar.js"></script>
</body>
</html>
