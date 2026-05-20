<?php
session_start();
require('../connection.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['user_id'];

// Get customer info
$customer_query = "SELECT * FROM customers WHERE id = ?";
$stmt = mysqli_prepare($connection, $customer_query);
mysqli_stmt_bind_param($stmt, "i", $customer_id);
mysqli_stmt_execute($stmt);
$customer = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Get cart count
$cart_query = "SELECT COUNT(*) as count FROM shopping_cart WHERE customer_id = ?";
$cart_stmt = mysqli_prepare($connection, $cart_query);
mysqli_stmt_bind_param($cart_stmt, "i", $customer_id);
mysqli_stmt_execute($cart_stmt);
$cart_count = mysqli_fetch_assoc(mysqli_stmt_get_result($cart_stmt))['count'];

// Get order statistics
$total_orders = 0;
$pending_orders = 0;
$completed_orders = 0;

$orders_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status IN('Draft', 'Pending Payment', 'Pending Approval', 'Approved', 'Processed', 'Shipped') THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'Delivered' THEN 1 ELSE 0 END) as completed
    FROM orders WHERE customer_id = ?";
$orders_stmt = mysqli_prepare($connection, $orders_query);
mysqli_stmt_bind_param($orders_stmt, "i", $customer_id);
mysqli_stmt_execute($orders_stmt);
$order_stats = mysqli_fetch_assoc(mysqli_stmt_get_result($orders_stmt));

if ($order_stats) {
    $total_orders = $order_stats['total'];
    $pending_orders = $order_stats['pending'];
    $completed_orders = $order_stats['completed'];
}

// Get recent orders
$recent_query = "SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC LIMIT 5";
$recent_stmt = mysqli_prepare($connection, $recent_query);
mysqli_stmt_bind_param($recent_stmt, "i", $customer_id);
mysqli_stmt_execute($recent_stmt);
$recent_orders = mysqli_stmt_get_result($recent_stmt);
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard</title>
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
            --secondary: #34A853;
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

        .user-info{
            display:flex;
            align-items:center;
            gap:15px;
        }

        .user-info span{
            color:#94a3b8;
            font-size:14px;
        }

        .user-info strong{
            color:#fff;
            font-size:16px;
        }

        /* STATS GRID */
        .stats-grid{
            display:grid;
            grid-template-columns:repeat(auto-fit, minmax(250px, 1fr));
            gap:20px;
            margin-bottom:30px;
        }

        .stat-card{
            background:rgba(255,255,255,.05);
            backdrop-filter:blur(20px);
            border-radius:20px;
            padding:25px;
            border:1px solid rgba(255,255,255,.1);
            transition:.3s ease;
        }

        .stat-card:hover{
            transform:translateY(-5px);
            box-shadow:var(--blue-glow);
            border-color:rgba(245, 158, 11, 0.3);
        }

        .stat-card .icon{
            width:50px;
            height:50px;
            background:rgba(245, 158, 11, 0.15);
            border-radius:12px;
            display:flex;
            align-items:center;
            justify-content:center;
            margin-bottom:15px;
        }

        .stat-card .icon .material-symbols-outlined{
            font-size:28px;
            color:var(--primary);
        }

        .stat-card h3{
            font-size:14px;
            color:#94a3b8;
            margin-bottom:5px;
        }

        .stat-card .value{
            font-size:32px;
            font-weight:700;
            color:#fff;
        }

        /* QUICK ACTIONS */
        .quick-actions{
            display:grid;
            grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));
            gap:15px;
            margin-bottom:30px;
        }

        .action-btn{
            background:rgba(245, 158, 11, 0.15);
            border:1px solid rgba(245, 158, 11, 0.25);
            color:white;
            padding:20px;
            border-radius:16px;
            text-decoration:none;
            display:flex;
            flex-direction:column;
            align-items:center;
            gap:10px;
            transition:.3s ease;
            backdrop-filter:blur(15px);
        }

        .action-btn:hover{
            background:var(--primary);
            transform:translateY(-3px);
            box-shadow:var(--blue-glow);
        }

        .action-btn .material-symbols-outlined{
            font-size:32px;
        }

        .action-btn span{
            font-weight:600;
            font-size:14px;
        }

        /* RECENT ORDERS */
        .recent-orders{
            background:rgba(255,255,255,.05);
            backdrop-filter:blur(20px);
            border-radius:20px;
            padding:25px;
            border:1px solid rgba(255,255,255,.1);
        }

        .recent-orders h2{
            font-size:22px;
            font-weight:600;
            color:#fff;
            margin-bottom:20px;
        }

        table{
            width:100%;
            border-collapse:collapse;
        }

        th{
            text-align:left;
            padding:15px;
            color:#94a3b8;
            font-weight:600;
            font-size:13px;
            text-transform:uppercase;
            letter-spacing:1px;
            border-bottom:1px solid rgba(255,255,255,.1);
        }

        td{
            padding:15px;
            color:#fff;
            font-size:14px;
            border-bottom:1px solid rgba(255,255,255,.05);
        }

        tr:last-child td{
            border-bottom:none;
        }

        .status{
            padding:6px 12px;
            border-radius:8px;
            font-size:12px;
            font-weight:600;
        }

        .status.pending{
            background:rgba(251,188,5,.15);
            color:#fbbf05;
            border:1px solid rgba(251,188,5,.25);
        }

        .status.approved{
            background:rgba(245, 158, 11, 0.15);
            color:#4285F4;
            border:1px solid rgba(245, 158, 11, 0.25);
        }

        .status.delivered{
            background:rgba(52,168,83,.15);
            color:#34A853;
            border:1px solid rgba(52,168,83,.25);
        }

        .status.cancelled{
            background:rgba(217, 119, 6,.15);
            color:#ea4335;
            border:1px solid rgba(217, 119, 6,.25);
        }

        /* BUTTONS */
        .btn{
            border:none;
            outline:none;
            cursor:pointer;
            border-radius:14px;
            font-weight:600;
            transition:.3s ease;
            padding:10px 16px;
            color:white;
            text-decoration:none;
            background:rgba(245, 158, 11, 0.15);
            border:1px solid rgba(245, 158, 11, 0.2);
            backdrop-filter:blur(12px);
        }

        .btn:hover{
            background:var(--primary);
            transform:translateY(-2px);
            box-shadow:0 0 20px rgba(245, 158, 11, 0.35);
        }

        .logout-btn{
            background:rgba(217, 119, 6,.15);
            border-color:rgba(217, 119, 6,.25);
        }

        .logout-btn:hover{
            background:var(--accent);
            box-shadow:0 0 20px rgba(217, 119, 6,.35);
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
        @media(max-width:1024px){
            .header{
                flex-direction:column;
                align-items:flex-start;
                gap:15px;
            }

            .stats-grid{
                grid-template-columns:1fr;
            }

            .quick-actions{
                grid-template-columns:1fr;
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
                <li><a href="dashboard.php" class="active"><span class="material-symbols-outlined">dashboard</span> Dashboard</a></li>
                <li><a href="browse_products.php"><span class="material-symbols-outlined">shopping_bag</span> Browse Products</a></li>
                <li><a href="cart.php"><span class="material-symbols-outlined">shopping_cart</span> My Cart <span class="cart-badge"><?= $cart_count ?></span></a></li>
                <li><a href="view_order_history.php"><span class="material-symbols-outlined">history</span> Order History</a></li>
                <li><a href="make_payment.php"><span class="material-symbols-outlined">payment</span> Make Payment</a></li>
                <li><a href="logout.php" class="logout-btn"><span class="material-symbols-outlined">logout</span> Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="header">
                <div style="display:flex; align-items:center;">
                    <button class="menu-toggle" onclick="toggleSidebar()"><span class="material-symbols-outlined">menu</span></button>
                    <h1>Welcome, <?= htmlspecialchars($customer['fullname']) ?></h1>
                </div>
                <div class="user-info">
                    <span><?= htmlspecialchars($customer['email']) ?></span>
                </div>
                <a href="change_password.php" style="color:var(--primary); font-size:13px; text-decoration:none;">Change Password</a>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="icon">
                        <span class="material-symbols-outlined">shopping_bag</span>
                    </div>
                    <h3>Total Orders</h3>
                    <div class="value"><?= $total_orders ?></div>
                </div>
                <div class="stat-card">
                    <div class="icon">
                        <span class="material-symbols-outlined">pending</span>
                    </div>
                    <h3>Pending Orders</h3>
                    <div class="value"><?= $pending_orders ?></div>
                </div>
                <div class="stat-card">
                    <div class="icon">
                        <span class="material-symbols-outlined">check_circle</span>
                    </div>
                    <h3>Completed Orders</h3>
                    <div class="value"><?= $completed_orders ?></div>
                </div>
                <div class="stat-card">
                    <div class="icon">
                        <span class="material-symbols-outlined">shopping_cart</span>
                    </div>
                    <h3>Cart Items</h3>
                    <div class="value"><?= $cart_count ?></div>
                </div>
            </div>
  
            <div class="recent-orders">
                <h2>Recent Orders</h2>
                <?php if (mysqli_num_rows($recent_orders) > 0): ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = mysqli_fetch_assoc($recent_orders)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($order['order_number']) ?></td>
                                        <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                                        <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                                        <td>
                                            <span class="status <?= strtolower(str_replace(' ', '', $order['status'])) ?>">
                                                <?= htmlspecialchars($order['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view_order_history.php?order_id=<?= $order['id'] ?>" class="btn">View</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color:#94a3b8; text-align:center; padding:20px;">No orders yet. Start shopping!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    
<script src="../assets/sidebar.js"></script>
</body>
</html>
