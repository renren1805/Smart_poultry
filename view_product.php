<?php
session_start();
require('../connection.php');

$customer_id = $_SESSION['user_id'];

// Get cart count
$cart_query = "SELECT COUNT(*) as count FROM shopping_cart WHERE customer_id = ?";
$cart_stmt = mysqli_prepare($connection, $cart_query);
mysqli_stmt_bind_param($cart_stmt, "i", $user_id);
mysqli_stmt_execute($cart_stmt);
$cart_count = mysqli_fetch_assoc(mysqli_stmt_get_result($cart_stmt))['count'];

// Get product details
if (!isset($_SESSION['user_id'])) {
    header("Location: browse_products.php");
    exit();
}

$product_id = intval($_GET['id']);
$product_query = "SELECT * FROM inventory_items WHERE id = ? AND status = 'active'";
$stmt = mysqli_prepare($connection, $product_query);
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$product) {
    header("Location: browse_products.php");
    exit();
}

// Determine stock status
$stock_class = 'in-stock';
$stock_text = 'In Stock';
if ($product['current_quantity'] <= 0) {
    $stock_class = 'out-stock';
    $stock_text = 'Out of Stock';
} elseif ($product['current_quantity'] <= $product['min_stock']) {
    $stock_class = 'low-stock';
    $stock_text = 'Low Stock';
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - Product Details</title>
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
            --blue-glow: 0 0 30px rgba(245, 158, 11, 0.4);
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
            background:rgba(245, 158, 11, 0.15);
            color:#fff;
            border:1px solid rgba(245, 158, 11, 0.25);
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

        /* PRODUCT DETAILS */
        .product-details{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:40px;
            background:rgba(255,255,255,.05);
            backdrop-filter:blur(20px);
            border-radius:20px;
            padding:40px;
            border:1px solid rgba(255,255,255,.1);
        }

        .product-image-section{
            background:rgba(255,255,255,.05);
            border-radius:20px;
            padding:30px;
            display:flex;
            align-items:center;
            justify-content:center;
            min-height:400px;
        }

        .product-image-section img{
            width:100%;
            max-height:400px;
            object-fit:contain;
            border-radius:16px;
        }

        .product-image-section .no-image{
            color:#94a3b8;
            font-size:120px;
        }

        .product-info-section{
            display:flex;
            flex-direction:column;
        }

        .product-category{
            font-size:14px;
            color:var(--primary);
            font-weight:600;
            text-transform:uppercase;
            letter-spacing:1px;
            margin-bottom:15px;
        }

        .product-name{
            font-size:36px;
            font-weight:700;
            color:#fff;
            margin-bottom:20px;
            line-height:1.2;
        }

        .product-price{
            font-size:42px;
            font-weight:700;
            color:var(--secondary);
            margin-bottom:20px;
        }

        .product-description{
            font-size:16px;
            color:#94a3b8;
            margin-bottom:30px;
            line-height:1.6;
        }

        .product-meta{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:20px;
            margin-bottom:30px;
        }

        .meta-item{
            background:rgba(255,255,255,.05);
            padding:20px;
            border-radius:14px;
            border:1px solid rgba(255,255,255,.1);
        }

        .meta-item label{
            font-size:12px;
            color:#94a3b8;
            text-transform:uppercase;
            letter-spacing:1px;
            margin-bottom:8px;
            display:block;
        }

        .meta-item span{
            font-size:18px;
            font-weight:600;
            color:#fff;
        }

        .stock-status{
            font-size:16px;
            font-weight:600;
            margin-bottom:30px;
        }

        .stock-status.in-stock{
            color:var(--secondary);
        }

        .stock-status.low-stock{
            color:var(--warning);
        }

        .stock-status.out-stock{
            color:var(--accent);
        }

        .quantity-selector{
            display:flex;
            align-items:center;
            gap:15px;
            margin-bottom:30px;
        }

        .quantity-selector label{
            font-size:16px;
            color:#94a3b8;
            font-weight:500;
        }

        .quantity-input{
            display:flex;
            align-items:center;
            gap:10px;
        }

        .quantity-btn{
            width:45px;
            height:45px;
            border:1px solid rgba(255,255,255,.1);
            background:rgba(255,255,255,.05);
            color:white;
            border-radius:12px;
            font-size:24px;
            cursor:pointer;
            transition:.3s ease;
        }

        .quantity-btn:hover{
            background:var(--primary);
            border-color:var(--primary);
        }

        .quantity-input input{
            width:80px;
            height:45px;
            border:1px solid rgba(255,255,255,.1);
            background:rgba(255,255,255,.05);
            color:white;
            border-radius:12px;
            text-align:center;
            font-size:18px;
            font-weight:600;
            outline:none;
        }

        .product-actions{
            display:flex;
            gap:15px;
        }

        .btn{
            border:none;
            outline:none;
            cursor:pointer;
            border-radius:14px;
            font-weight:600;
            transition:.3s ease;
            padding:16px 24px;
            color:white;
            text-decoration:none;
            font-size:16px;
            flex:1;
        }

        .btn-primary{
            background:var(--primary);
            border:1px solid rgba(245, 158, 11, 0.3);
        }

        .btn-primary:hover{
            background:var(--secondary);
            transform:translateY(-2px);
            box-shadow:0 0 20px rgba(168, 52, 52, 0.35);
        }

        .btn-secondary{
            background:rgba(255,255,255,.1);
            border:1px solid rgba(255,255,255,.15);
        }

        .btn-secondary:hover{
            background:rgba(255,255,255,.2);
            transform:translateY(-2px);
        }

        .btn:disabled{
            opacity:0.5;
            cursor:not-allowed;
        }

        /* BUTTONS */
        .back-btn{
            background:rgba(95, 11, 11, 0.15);
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

            .product-details{
                grid-template-columns:1fr;
                gap:30px;
                padding:25px;
            }

            .product-image-section{
                min-height:300px;
            }

            .product-name{
                font-size:28px;
            }

            .product-price{
                font-size:32px;
            }

            .product-meta{
                grid-template-columns:1fr;
            }

            .product-actions{
                flex-direction:column;
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
                <li><a href="browse_products.php" class="active"><span class="material-symbols-outlined">shopping_bag</span> Browse Products</a></li>
                <li><a href="cart.php"><span class="material-symbols-outlined">shopping_cart</span> My Cart <span class="cart-badge"><?= $cart_count ?></span></a></li>
                <li><a href="view_order_history.php"><span class="material-symbols-outlined">history</span> Order History</a></li>
                <li><a href="check_receipt.php"><span class="material-symbols-outlined">receipt_long</span> Check Receipt</a></li>
                <li><a href="logout.php" class="logout-btn"><span class="material-symbols-outlined">logout</span> Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="header">
                <div style="display:flex; align-items:center;">
                    <button class="menu-toggle" onclick="toggleSidebar()"><span class="material-symbols-outlined">menu</span></button>
                    <h1>Product Details</h1>
                </div>
                <a href="browse_products.php" class="back-btn">← Back to Products</a>
            </div>

            <div class="product-details">
                <div class="product-image-section">
                    <?php if (!empty($product['image_path'])): ?>
                        <img src="../<?= htmlspecialchars($product['image_path']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                    <?php else: ?>
                        <span class="material-symbols-outlined no-image">image_not_supported</span>
                    <?php endif; ?>
                </div>

                <div class="product-info-section">
                    <div class="product-category"><?= htmlspecialchars($product['category']) ?></div>
                    <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                    <div class="product-price">₱<?= number_format($product['selling_price'], 2) ?></div>
                    <div class="product-description"><?= htmlspecialchars($product['description']) ?></div>

                    <div class="product-meta">
                        <div class="meta-item">
                            <label>Unit</label>
                            <span><?= htmlspecialchars($product['unit']) ?></span>
                        </div>
                        <div class="meta-item">
                            <label>Category</label>
                            <span><?= htmlspecialchars($product['category']) ?></span>
                        </div>
                        <div class="meta-item">
                            <label>Available Stock</label>
                            <span><?= $product['current_quantity'] ?></span>
                        </div>
                        <div class="meta-item">
                            <label>Minimum Stock</label>
                            <span><?= $product['min_stock'] ?></span>
                        </div>
                    </div>

                    <div class="stock-status <?= $stock_class ?>">
                        <span class="material-symbols-outlined" style="vertical-align:middle; margin-right:8px;">
                            <?= $stock_class == 'in-stock' ? 'check_circle' : ($stock_class == 'low-stock' ? 'warning' : 'cancel') ?>
                        </span>
                        <?= $stock_text ?>
                    </div>

                    <div class="quantity-selector">
                        <label>Quantity:</label>
                        <div class="quantity-input">
                            <button class="quantity-btn" onclick="decreaseQuantity()" <?= $product['current_quantity'] <= 0 ? 'disabled' : '' ?>>−</button>
                            <input type="number" id="quantity" value="<?= $product['current_quantity'] <= 0 ? 0 : 1 ?>" min="<?= $product['current_quantity'] <= 0 ? 0 : 1 ?>" max="<?= $product['current_quantity'] ?>" <?= $product['current_quantity'] <= 0 ? 'disabled' : '' ?>>
                            <button class="quantity-btn" onclick="increaseQuantity()" <?= $product['current_quantity'] <= 0 ? 'disabled' : '' ?>>+</button>
                        </div>
                    </div>

                    <div class="product-actions">
                        <a href="browse_products.php" class="btn btn-secondary">Continue Shopping</a>
                        <button class="btn btn-primary" 
                                onclick="addToCart()"
                                <?= $product['current_quantity'] <= 0 ? 'disabled' : '' ?>>
                            <span class="material-symbols-outlined" style="vertical-align:middle; margin-right:8px; font-size:20px;">shopping_cart</span>
                            Add to Cart
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const maxQuantity = <?= $product['current_quantity'] ?>;
        const quantityInput = document.getElementById('quantity');

        function increaseQuantity() {
            let current = parseInt(quantityInput.value);
            if (isNaN(current)) current = 1;
            if (current < maxQuantity) {
                quantityInput.value = current + 1;
            }
        }

        function decreaseQuantity() {
            let current = parseInt(quantityInput.value);
            if (isNaN(current)) current = 1;
            if (current > 1) {
                quantityInput.value = current - 1;
            }
        }

        // Validate typed quantity
        quantityInput.addEventListener('input', function() {
            let current = parseInt(quantityInput.value);
            if (isNaN(current)) {
                return; // Let user clear the field, validated on blur
            }
            if (current > maxQuantity) {
                quantityInput.value = maxQuantity;
            } else if (current < 1) {
                quantityInput.value = 1;
            }
        });

        quantityInput.addEventListener('blur', function() {
            let current = parseInt(quantityInput.value);
            if (isNaN(current) || current < 1) {
                quantityInput.value = 1;
            }
        });

        // Add to cart on pressing Enter
        quantityInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                const addToCartBtn = document.querySelector('.btn-primary[onclick="addToCart()"]');
                if (addToCartBtn && !addToCartBtn.disabled) {
                    addToCart();
                }
            }
        });

        function addToCart() {
            const quantity = quantityInput.value;
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'add_to_cart.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    alert('Product added to cart!');
                    window.location.href = 'cart.php';
                }
            };
            xhr.send(`product_id=<?= $product_id ?>&quantity=${quantity}`);
        }
    </script>
<script src="../assets/sidebar.js"></script>
</body>
</html>
