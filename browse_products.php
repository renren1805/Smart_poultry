<?php
session_start();
require('../connection.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['user_id'];

// Get cart count
$cart_query = "SELECT COUNT(*) as count FROM shopping_cart WHERE customer_id = ?";
$cart_stmt = mysqli_prepare($connection, $cart_query);
mysqli_stmt_bind_param($cart_stmt, "i", $customer_id);
mysqli_stmt_execute($cart_stmt);
$cart_count = mysqli_fetch_assoc(mysqli_stmt_get_result($cart_stmt))['count'];

// Search and filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($connection, $_GET['search']) : '';
$category = isset($_GET['category']) ? mysqli_real_escape_string($connection, $_GET['category']) : '';

// Build query
$where_conditions = ["status = 'active'"];
$params = [];
$types = "";

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if (!empty($category)) {
    $where_conditions[] = "category = ?";
    $params[] = $category;
    $types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);

// Get products
$products_query = "SELECT * FROM inventory_items WHERE $where_clause ORDER BY created_at DESC";
$stmt = mysqli_prepare($connection, $products_query);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$products = mysqli_stmt_get_result($stmt);

// Get categories
$categories_query = "SELECT DISTINCT category FROM inventory_items WHERE status = 'active' ORDER BY category";
$categories_result = mysqli_query($connection, $categories_query);
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Products</title>
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
            --secondary: #a87634ff;
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
            border:1px solid rgba(255, 255, 255, 0.1);
        }

        .header h1{
            font-size:32px;
            font-weight:700;
            color:#fff;
            text-shadow:0 0 20px rgba(245, 158, 11, 0.3);
        }

        /* SEARCH AND FILTER */
        .search-filter{
            display:flex;
            gap:15px;
            margin-bottom:30px;
            flex-wrap:wrap;
        }

        .search-box{
            flex:1;
            min-width:300px;
            position:relative;
        }

        .search-box input{
            width:100%;
            padding:14px 20px 14px 50px;
            border:1px solid rgba(255,255,255,.1);
            border-radius:14px;
            background:rgba(255,255,255,.05);
            backdrop-filter:blur(15px);
            color:white;
            font-size:14px;
            outline:none;
            transition:.3s ease;
        }

        .search-box input:focus{
            border-color:var(--primary);
            box-shadow:var(--blue-glow);
        }

        .search-box .material-symbols-outlined{
            position:absolute;
            left:18px;
            top:50%;
            transform:translateY(-50%);
            color:#94a3b8;
            font-size:22px;
        }

        .filter-select{
            padding:14px 20px;
            border:1px solid rgba(255,255,255,.1);
            border-radius:14px;
            background:rgba(255,255,255,.05);
            backdrop-filter:blur(15px);
            color:white;
            font-size:14px;
            outline:none;
            cursor:pointer;
            min-width:180px;
        }

        .filter-select option{
            background:#0f172a;
            color:white;
        }

        /* PRODUCTS GRID */
        .products-grid{
            display:grid;
            grid-template-columns:repeat(auto-fill, minmax(280px, 1fr));
            gap:25px;
        }

        .product-card{
            background:rgba(255,255,255,.05);
            backdrop-filter:blur(20px);
            border-radius:20px;
            overflow:hidden;
            border:1px solid rgba(255,255,255,.1);
            transition:.3s ease;
        }

        .product-card:hover{
            transform:translateY(-8px);
            box-shadow:var(--blue-glow);        
            border-color:rgba(245, 158, 11, 0.3);
        }

        .product-image{
            width:100%;
            height:200px;
            background:rgba(255,255,255,.05);
            display:flex;
            align-items:center;
            justify-content:center;
            overflow:hidden;
        }

        .product-image img{
            width:100%;
            height:100%;
            object-fit:cover;
        }

        .product-image .no-image{
            color:#94a3b8;
            font-size:48px;
        }

        .product-info{
            padding:20px;
        }

        .product-category{
            font-size:12px;
            color:var(--primary);
            font-weight:600;
            text-transform:uppercase;
            letter-spacing:1px;
            margin-bottom:8px;
        }

        .product-name{
            font-size:18px;
            font-weight:600;
            color:#fff;
            margin-bottom:10px;
            line-height:1.4;
        }

        .product-description{
            font-size:13px;
            color:#94a3b8;
            margin-bottom:15px;
            line-height:1.5;
            display:-webkit-box;
            -webkit-line-clamp:2;
            -webkit-box-orient:vertical;
            overflow:hidden;
        }

        .product-price{
            font-size:24px;
            font-weight:700;
            color:var(--secondary);
            margin-bottom:15px;
        }

        .product-stock{
            font-size:13px;
            color:#94a3b8;
            margin-bottom:15px;
        }

        .product-stock.in-stock{
            color:var(--secondary);
        }

        .product-stock.low-stock{
            color:var(--warning);
        }

        .product-stock.out-stock{
            color:var(--accent);
        }

        .product-actions{
            display:flex;
            gap:10px;
        }

        .btn{
            border:none;
            outline:none;
            cursor:pointer;
            border-radius:12px;
            font-weight:600;
            transition:.3s ease;
            padding:12px 16px;
            color:white;
            text-decoration:none;
            font-size:13px;
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
            .sidebar{
                transform:translateX(-100%);
                position:fixed;
                width:260px;
            }

            .sidebar.active{
                transform:translateX(0);
            }

            .main-content{
                margin-left:0;
                padding:20px;
            }

            .header{
                flex-direction:column;
                align-items:flex-start;
                gap:15px;
            }

            .menu-toggle{
                display:block;
            }

            .products-grid{
                grid-template-columns:1fr;
            }

            .search-filter{
                flex-direction:column;
            }

            .search-box{
                min-width:100%;
            }
        }
    </style>
<link rel="stylesheet" href="assets/responsive.css">
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
                <li><a href="browse_products.php" class="active"><span class="material-symbols-outlined">shopping_bag</span> Browse Products</a></li>
                <li><a href="cart.php"><span class="material-symbols-outlined">shopping_cart</span> My Cart <span class="cart-badge"><?= $cart_count ?></span></a></li>
                <li><a href="view_order_history.php"><span class="material-symbols-outlined">history</span> Order History</a></li>
                <li><a href="logout.php" class="logout-btn"><span class="material-symbols-outlined">logout</span> Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="header">
                <div style="display:flex; align-items:center;">
                    <button class="menu-toggle" onclick="toggleSidebar()"><span class="material-symbols-outlined">menu</span></button>
                    <h1>Browse Products</h1>
                </div>
                <a href="cart.php" class="back-btn">
                    <span class="material-symbols-outlined" style="vertical-align:middle; font-size:18px; margin-right:5px;">shopping_cart</span>
                    Cart (<?= $cart_count ?>)
                </a>
            </div>

           <div class="search-filter">
                <form method="GET" action="browse_products.php" style="display:flex; gap:15px; flex-wrap:wrap; width:100%;">
                    
                    <div class="search-box">
                        <span class="material-symbols-outlined">search</span>
                        <input 
                            type="text" 
                            name="search" 
                            placeholder="Search products..." 
                            value="<?= htmlspecialchars($search) ?>"
                        >
                    </div>

                    <select class="filter-select" name="category">
                        <option value="">All Categories</option>
                        <?php while ($cat = mysqli_fetch_assoc($categories_result)): ?>
                            <option 
                                value="<?= htmlspecialchars($cat['category']) ?>" 
                                <?= $category == $cat['category'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['category']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <button type="submit" class="btn btn-primary">
                        Filter
                    </button>

                    <button type="button" class="btn btn-secondary" onclick="clearFilters()">
                        Clear
                    </button>
                </form>
            </div>

            <div class="products-grid">
                <?php if (mysqli_num_rows($products) > 0): ?>
                    <?php while ($product = mysqli_fetch_assoc($products)): ?>
                        <?php
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
                        <div class="product-card">
                            <div class="product-image">
                                <?php if (!empty($product['image_path'])): ?>
                                    <img src="../<?= htmlspecialchars($product['image_path']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                <?php else: ?>
                                    <span class="material-symbols-outlined no-image">image_not_supported</span>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <div class="product-category"><?= htmlspecialchars($product['category']) ?></div>
                                <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                                <div class="product-description"><?= htmlspecialchars($product['description']) ?></div>
                                <div class="product-price">₱<?= number_format($product['selling_price'], 2) ?></div>
                                <div class="product-stock <?= $stock_class ?>">
                                    <?= $stock_text ?> (<?= $product['current_quantity'] ?>)
                                </div>
                                <div class="product-actions">
                                    <a href="view_product.php?id=<?= $product['id'] ?>" class="btn btn-secondary">View Details</a>
                                    <button class="btn btn-primary" 
                                            onclick="addToCart(<?= $product['id'] ?>)"
                                            <?= $product['current_quantity'] <= 0 ? 'disabled' : '' ?>>
                                        Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="grid-column:1/-1; text-align:center; padding:60px; color:#94a3b8;">
                        <span class="material-symbols-outlined" style="font-size:64px; margin-bottom:20px;">search_off</span>
                        <p style="font-size:18px;">No products found matching your criteria.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function filterProducts() {
            document.querySelector('.search-filter form').submit();
        }

        function clearFilters() {
            window.location.href = 'browse_products.php';
        }

       function addToCart(productId) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'add_to_cart.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const res = JSON.parse(xhr.responseText);

                        if (res.success) {
                            // update cart badge instantly
                            const badge = document.querySelector('.cart-badge');
                            if (badge) {
                                badge.textContent = res.cart_count;
                            }

                            alert('Added to cart!');
                        } else {
                            alert(res.message || 'Failed to add to cart');
                        }

                    } catch (e) {
                        // fallback if PHP returns plain text   
                        alert('Added to cart!');
                    }
                }
            };

            xhr.send(`product_id=${productId}&quantity=1`);
        }
    </script>
<script src="assets/sidebar.js"></script>
</body>
</html>
