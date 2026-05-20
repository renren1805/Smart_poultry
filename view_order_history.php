<?php
session_start();
require('../connection.php');

// FIXED LOGIN CHECK
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
$cart_count = mysqli_fetch_assoc(mysqli_stmt_get_result($cart_stmt))['count'] ?? 0;

// Filter by status
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query
$where_conditions = ["customer_id = ?"];
$params = [$customer_id];
$types = "i";

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);

// Get orders
$orders_query = "SELECT * FROM orders WHERE $where_clause ORDER BY created_at DESC";
$stmt = mysqli_prepare($connection, $orders_query);

// FIX: proper dynamic binding (reference-based)
if ($stmt) {
    $bind_names[] = $types;

    for ($i = 0; $i < count($params); $i++) {
        $bind_names[] = &$params[$i];
    }

    call_user_func_array([$stmt, 'bind_param'], $bind_names);
}

mysqli_stmt_execute($stmt);
$orders = mysqli_stmt_get_result($stmt);

// FIX: map DB status → CSS class
function mapStatusClass($status) {
    $status = strtolower($status);

    return match ($status) {
        'draft' => 'draft',
        'pending payment' => 'pending',
        'pending approval' => 'pending',
        'approved' => 'approved',
        'processed' => 'processed',
        'shipped' => 'shipped',
        'delivered' => 'delivered',
        'cancelled' => 'cancelled',
        default => 'draft'
    };
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History</title>
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
            text-shadow:0 0 20px rgba(66,133,244,.3);
        }

        /* FILTERS */
        .filters{
            display:flex;
            gap:15px;
            margin-bottom:30px;
            flex-wrap:wrap;
        }

        .filter-btn{
            padding:12px 20px;
            border:1px solid rgba(255,255,255,.1);
            background:rgba(255,255,255,.05);
            backdrop-filter:blur(15px);
            color:white;
            border-radius:12px;
            cursor:pointer;
            font-size:14px;
            font-weight:500;
            transition:.3s ease;
        }

        .filter-btn:hover,
        .filter-btn.active{
            background:var(--primary);
            border-color:var(--primary);
        }

        /* ORDERS GRID */
        .orders-grid{
            display:grid;
            gap:20px;
        }

        .order-card{
            background:rgba(255,255,255,.05);
            backdrop-filter:blur(20px);
            border-radius:20px;
            padding:25px;
            border:1px solid rgba(255,255,255,.1);
            transition:.3s ease;
        }

        .order-card:hover{
            transform:translateY(-5px);
            box-shadow:var(--blue-glow);
            border-color:rgba(245, 158, 11, 0.3);
        }

        .order-header{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:20px;
            padding-bottom:20px;
            border-bottom:1px solid rgba(255,255,255,.05);
        }

        .order-number{
            font-size:18px;
            font-weight:700;
            color:#fff;
        }

        .order-date{
            color:#94a3b8;
            font-size:14px;
        }

        .order-status{
            padding:8px 16px;
            border-radius:10px;
            font-size:12px;
            font-weight:600;
        }

        .order-status.draft{
            background:rgba(184, 148, 148, 0.15);
            color:#94a3b8;
            border:1px solid rgba(184, 148, 148, 0.25);
        }

        .order-status.pending{
            background:rgba(251, 206, 5, 0.15);
            color:#fbbf05;
            border:1px solid rgba(251, 206, 5, 0.25);
        }

        .order-status.approved,
        .order-status.processed,
        .order-status.shipped{
            background:rgba(66,133,244,.15);
            color:#4285F4;
            border:1px solid rgba(66,133,244,.25);
        }

        .order-status.delivered{
            background:rgba(52,168,83,.15);
            color:#34A853;
            border:1px solid rgba(52,168,83,.25);
        }

        .order-status.cancelled{
            background:rgba(53, 234, 234, 0.15);
            color:#ea4335;
            border:1px solid rgba(53, 234, 234, 0.25);
        }

        .order-details{
            display:grid;
            grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));
            gap:20px;
            margin-bottom:20px;
        }

        .order-detail-item{
            background:rgba(255,255,255,.03);
            padding:15px;
            border-radius:12px;
            border:1px solid rgba(255,255,255,.05);
        }

        .order-detail-item label{
            display:block;
            color:#94a3b8;
            font-size:12px;
            text-transform:uppercase;
            letter-spacing:1px;
            margin-bottom:8px;
        }

        .order-detail-item span{
            color:#fff;
            font-weight:600;
            font-size:15px;
        }

        .order-detail-item .amount{
            color:var(--secondary);
            font-size:18px;
        }

        .order-actions{
            display:flex;
            gap:10px;
            justify-content:flex-end;
        }

        /* BUTTONS */
        .btn{
            border:none;
            outline:none;
            cursor:pointer;
            border-radius:12px;
            font-weight:600;
            transition:.3s ease;
            padding:10px 18px;
            color:white;
            text-decoration:none;
            font-size:13px;
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
            border:1px solid rgba(245, 158, 11, 0.1);
            border-radius:14px;
            cursor:pointer;
            display:none;
        }

        .menu-toggle:hover{
            background:var(--primary);
        }

        .empty-orders{
            text-align:center;
            padding:60px 20px;
            color:#94a3b8;
        }

        .empty-orders .material-symbols-outlined{
            font-size:80px;
            margin-bottom:20px;
        }

        .empty-orders p{
            font-size:18px;
            margin-bottom:20px;
        }

        /* RESPONSIVE - TABLETS & MOBILE */
        @media (max-width: 1024px){
            .orders-grid{
                grid-template-columns:1fr;
            }

            .header{
                flex-direction:column;
                align-items:flex-start;
                gap:15px;
                padding:15px 20px;
            }

            .header h1{
                font-size:24px;
            }

            .order-header{
                flex-direction:column;
                align-items:flex-start;
                gap:15px;
            }

            .order-details{
                grid-template-columns:1fr;
            }

            .order-actions{
                flex-direction:column;
            }

            .btn{
                width:100%;
            }

            .order-card{
                padding:20px;
            }

            .filter-buttons{
                flex-wrap:wrap;
            }

            .filter-btn{
                flex:1;
                min-width:120px;
            }
        }

        /* RESPONSIVE - SMALL MOBILE */
        @media(max-width:480px){
            .header{
                padding:12px 15px;
            }

            .header h1{
                font-size:20px;
            }

            .order-card{
                padding:15px;
            }

            .order-status{
                font-size:11px;
                padding:6px 12px;
            }

            .order-detail-item{
                padding:12px;
            }

            .filter-buttons{
                gap:8px;
            }

            .filter-btn{
                padding:10px;
                font-size:12px;
            }
        }

        /* RESPONSIVE - LARGE SCREENS */
        @media(min-width:1400px){
            .orders-grid{
                grid-template-columns:repeat(2,1fr);
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
                <li><a href="cart.php"><span class="material-symbols-outlined">shopping_cart</span> My Cart <span class="cart-badge"><?= $cart_count ?></span></a></li>
                <li><a href="view_order_history.php" class="active"><span class="material-symbols-outlined">history</span> Order History</a></li>
                <li><a href="logout.php" class="logout-btn"><span class="material-symbols-outlined">logout</span> Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="header">
                <div style="display:flex; align-items:center;">
                    <button class="menu-toggle" onclick="toggleSidebar()"><span class="material-symbols-outlined">menu</span></button>
                    <h1>Order History</h1>
                </div>
                <a href="browse_products.php" class="back-btn">Continue Shopping</a>
            </div>

            <div class="filters">
                <button class="filter-btn <?= $status_filter == '' ? 'active' : '' ?>" onclick="filterOrders('')">All Orders</button>
                <button class="filter-btn <?= $status_filter == 'Pending Payment' ? 'active' : '' ?>" onclick="filterOrders('Pending Payment')">Pending Payment</button>
                <button class="filter-btn <?= $status_filter == 'Pending Approval' ? 'active' : '' ?>" onclick="filterOrders('Pending Approval')">Pending Approval</button>
                <button class="filter-btn <?= $status_filter == 'Approved' ? 'active' : '' ?>" onclick="filterOrders('Approved')">Approved</button>
                <button class="filter-btn <?= $status_filter == 'Processed' ? 'active' : '' ?>" onclick="filterOrders('Processed')">Processed</button>
                <button class="filter-btn <?= $status_filter == 'Shipped' ? 'active' : '' ?>" onclick="filterOrders('Shipped')">Shipped</button>
                <button class="filter-btn <?= $status_filter == 'Delivered' ? 'active' : '' ?>" onclick="filterOrders('Delivered')">Delivered</button>
                <button class="filter-btn <?= $status_filter == 'Cancelled' ? 'active' : '' ?>" onclick="filterOrders('Cancelled')">Cancelled</button>
            </div>

            <?php if (mysqli_num_rows($orders) > 0): ?>
                <div class="orders-grid">
                    <?php while ($order = mysqli_fetch_assoc($orders)): ?>
                        <?php
                        $status_class = strtolower(str_replace(' ', '', $order['status']));
                        ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div>
                                    <div class="order-number"><?= htmlspecialchars($order['order_number']) ?></div>
                                    <div class="order-date"><?= date('F d, Y g:i A', strtotime($order['created_at'])) ?></div>
                                </div>
                                <span class="order-status <?= $status_class ?>">
                                    <?= htmlspecialchars($order['status']) ?>
                                </span>
                            </div>

                            <div class="order-details">
                                <div class="order-detail-item">
                                    <label>Total Amount</label>
                                    <span class="amount">₱<?= number_format($order['total_amount'], 2) ?></span>
                                </div>
                                <div class="order-detail-item">
                                    <label>Payment Method</label>
                                    <span><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $order['payment_method']))) ?></span>
                                </div>
                                <div class="order-detail-item">
                                    <label>Delivery Address</label>
                                    <span style="font-size:13px;"><?= htmlspecialchars(substr($order['delivery_address'], 0, 50)) ?>...</span>
                                </div>
                                <?php if ($order['tracking_number']): ?>
                                    <div class="order-detail-item">
                                        <label>Tracking Number</label>
                                        <span><?= htmlspecialchars($order['tracking_number']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="order-actions">
                                <a href="track_order.php?order_id=<?= $order['id'] ?>" class="btn btn-secondary" style="display:inline-flex; align-items:center;">
                                    <span class="material-symbols-outlined" style="font-size:16px; margin-right:5px;">location_on</span>
                                    Track Order
                                </a>
                                <button onclick="printReceipt(<?= $order['id'] ?>)" class="btn btn-primary" style="display:inline-flex; align-items:center;">
                                    <span class="material-symbols-outlined" style="font-size:16px; margin-right:5px;">print</span>
                                    Print Receipt
                                </button>
                                <?php if ($order['status'] == 'Draft' || $order['status'] == 'Pending Payment'): ?>
                                    <a href="make_payment.php?order_id=<?= $order['id'] ?>" class="btn btn-secondary" style="display:inline-flex; align-items:center;">Make Payment</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-orders">
                    <span class="material-symbols-outlined">inbox</span>
                    <p>No orders found</p>
                    <a href="browse_products.php" class="back-btn">Start Shopping</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function filterOrders(status) {
            window.location.href = `view_order_history.php?status=${encodeURIComponent(status)}`;
        }

        function printReceipt(orderId) {
            window.open(`print_receipt.php?order_id=${orderId}`, '_blank');
        }
    </script>
    
<script src="../assets/sidebar.js"></script>
</body>
</html>
