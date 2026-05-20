<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include '../connection.php';

// Handle status filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query based on filter
$query = "SELECT o.*, c.fullname, c.email, c.phone 
          FROM orders o 
          JOIN customers c ON o.customer_id = c.id";

if ($status_filter) {
    $query .= " WHERE o.status = '" . mysqli_real_escape_string($connection, $status_filter) . "'";
}

$query .= " ORDER BY o.created_at DESC";
$result = mysqli_query($connection, $query);

// Get order statistics
$status_stats = [];
$statuses = ['Draft', 'Pending Payment', 'Pending Approval', 'Approved', 'Processed', 'Shipped', 'Delivered', 'Cancelled'];
foreach ($statuses as $status) {
    $count = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as count FROM orders WHERE status = '$status'"))['count'];
    $status_stats[$status] = $count;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Orders - Admin</title>
    <style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined');

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Outfit',sans-serif;
}

:root{
    --bg-dark:#060b16;
    --bg-card:rgba(15,23,42,0.72);
    --glass-border:rgba(255,255,255,0.08);
    --accent:#3b82f6;
    --accent-hover:#5c9cff;
    --text:#ffffff;
    --text-secondary:#b9c7da;
    --shadow:0 8px 32px rgba(0,0,0,.45);
}

body{
    background:
        radial-gradient(circle at top left, rgba(59,130,246,.18), transparent 25%),
        radial-gradient(circle at bottom right, rgba(0,140,255,.15), transparent 30%),
        linear-gradient(135deg,#090e1a,#0d111d,#111827);
    background-attachment:fixed;
    color:var(--text);
    min-height:100vh;
    overflow-x:hidden;
}

/* SCROLLBAR */
::-webkit-scrollbar{
    width:8px;
}
::-webkit-scrollbar-track{
    background:#0a0f1e;
}
::-webkit-scrollbar-thumb{
    background:rgba(59,130,246,.5);
    border-radius:10px;
}

/* CONTAINER */
.container{
    display:flex;
    min-height:100vh;
}

/* ======================
   SIDEBAR
====================== */
.sidebar{
    width:280px;
    background:rgba(15,23,42,0.8);
    backdrop-filter:blur(18px);
    -webkit-backdrop-filter:blur(18px);
    border-right:1px solid rgba(255,255,255,0.08);
    box-shadow:0 0 30px rgba(59,130,246,.15);
    color:white;
    padding:25px 20px;
    position:fixed;
    top:0;
    left:0;
    height:100vh;
    overflow-y:auto;
    z-index:9999;
}

.sidebar.collapsed{
    transform:translateX(-100%);
}

.sidebar h2{
    font-size:26px;
    font-weight:700;
    text-align:center;
    margin-bottom:30px;
    color:white;
    text-shadow:0 0 15px rgba(59,130,246,.4);
}

.sidebar-menu{
    list-style:none;
}

.sidebar-menu li{
    margin-bottom:8px;
}

.sidebar-menu a{
    display:flex;
    align-items:center;
    gap:12px;
    text-decoration:none;
    color:#d9e2f1;
    padding:14px 18px;
    border-radius:18px;
    font-size:14px;
    font-weight:500;
    position:relative;
    overflow:hidden;
}

.sidebar-menu a::before{
    content:'';
    position:absolute;
    top:0;
    left:-100%;
    width:100%;
    height:100%;
    background:linear-gradient(
        90deg,
        transparent,
        rgba(255,255,255,0.08),
        transparent
    );
    transition:.6s;
}

.sidebar-menu a:hover::before{
    left:100%;
}

.sidebar-menu a:hover,
.sidebar-menu a.active{
    background:rgba(59,130,246,.18);
    border:1px solid rgba(59,130,246,.25);
    box-shadow:0 0 18px rgba(59,130,246,.25);
    transform:translateX(5px);
    color:white;
}

.section-title{
    color:#7f92b0;
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:1px;
    margin:20px 0 10px;
    padding-left:15px;
    font-weight:600;
}

/* MAIN */
.main-content{
    flex:1;
    margin-left:280px;
    padding:35px;
    transition:.4s ease;
}

.main-content.expanded{
    margin-left:0;
}

/* HEADER */
.header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
}

.header h1{
    font-size:32px;
    color:#fff;
    font-weight:700;
    text-shadow:0 0 20px rgba(59,130,246,.25);
}

/* ======================
   BUTTONS
====================== */
.back-btn,
.btn,
.menu-toggle{
    border:none;
    outline:none;
    cursor:pointer;
    border-radius:14px;
    font-weight:600;
    transition:.3s ease;
}

.back-btn{
    background:rgba(59,130,246,.15);
    color:white;
    border:1px solid rgba(59,130,246,.2);
    padding:12px 20px;
    text-decoration:none;
    backdrop-filter:blur(12px);
}

.back-btn:hover{
    background:var(--primary);
    transform:translateY(-2px);
    box-shadow:0 0 20px rgba(59,130,246,.35);
}

.menu-toggle{
    background:rgba(59,130,246,.15);
    color:white;
    padding:12px 16px;
    margin-right:15px;
    border:1px solid rgba(255,255,255,.1);
}

.menu-toggle:hover{
    background:var(--primary);
}
.menu-toggle:hover{
    background:rgba(59,130,246,.28);
    transform:scale(1.05);
}

/* BUTTON */
.back-btn,
.btn{
    border:none;
    text-decoration:none;
    cursor:pointer;
    color:#fff;
    font-weight:500;
    transition:.3s ease;
    background:rgba(59,130,246,.18);
    border:1px solid rgba(59,130,246,.25);
    backdrop-filter:blur(20px);
    box-shadow:0 0 20px rgba(59,130,246,.18);
}

.back-btn{
    padding:12px 22px;
    border-radius:16px;
}

.back-btn:hover,
.btn:hover{
    background:var(--accent);
    transform:translateY(-2px);
    box-shadow:0 0 30px rgba(59,130,246,.45);
}

.btn{
    padding:10px 14px;
    border-radius:12px;
    font-size:12px;
}


/* STATS */
.stats-bar{
    display:flex;
    flex-wrap:wrap;
    gap:12px;
    margin-bottom:25px;
}

.stat-badge{
    padding:10px 18px;
    border-radius:30px;
    text-decoration:none;
    color:#fff;
    font-size:13px;
    font-weight:500;
    backdrop-filter:blur(18px);
    background:rgba(255,255,255,.06);
    border:1px solid rgba(255,255,255,.08);
    transition:.3s ease;
}

.stat-badge:hover{
    transform:translateY(-3px);
    box-shadow:0 0 20px rgba(59,130,246,.25);
}

.stat-badge.active{
    background:rgba(59,130,246,.2);
    border:1px solid rgba(59,130,246,.4);
    box-shadow:0 0 20px rgba(59,130,246,.35);
}

.stat-badge.draft{background:rgba(108,117,125,.18);}
.stat-badge.pending{background:rgba(255,193,7,.15);}
.stat-badge.approved{background:rgba(23,162,184,.18);}
.stat-badge.processed{background:rgba(40,167,69,.18);}
.stat-badge.shipped{background:rgba(0,123,255,.18);}
.stat-badge.delivered{background:rgba(25,135,84,.18);}
.stat-badge.cancelled{background:rgba(220,53,69,.18);}

/* TABLE GLASS */
.orders-table{
    background:rgba(15,23,42,.72);
    backdrop-filter:blur(25px);
    -webkit-backdrop-filter:blur(25px);
    border:1px solid rgba(255,255,255,.08);
    border-radius:28px;
    overflow:hidden;
    box-shadow:0 8px 40px rgba(0,0,0,.35);
    animation:fadeIn .5s ease;
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:rgba(255,255,255,.04);
    color:#b5c8ff;
    padding:18px;
    text-transform:uppercase;
    font-size:12px;
    border-bottom:1px solid rgba(255,255,255,.08);
}

td{
    padding:18px;
    color:#d7e3ff;
    border-bottom:1px solid rgba(255,255,255,.05);
}

tr{
    transition:.3s ease;
}

tr:hover{
    background:rgba(59,130,246,.08);
    transform:scale(1.003);
}

/* STATUS BADGES */
.status-badge{
    padding:8px 14px;
    border-radius:30px;
    font-size:12px;
    font-weight:600;
    display:inline-block;
    backdrop-filter:blur(15px);
}

.status-draft{
    background:rgba(108,117,125,.2);
}
.status-pending{
    background:rgba(255,193,7,.2);
    color:#ffd95b;
}
.status-approved{
    background:rgba(23,162,184,.2);
}
.status-processed{
    background:rgba(40,167,69,.2);
}
.status-shipped{
    background:rgba(0,123,255,.2);
}
.status-delivered{
    background:rgba(25,135,84,.2);
}
.status-cancelled{
    background:rgba(220,53,69,.2);
}

/* EMPTY STATE */
.empty-state{
    text-align:center;
    padding:70px 20px;
    color:#8ea1c5;
}

.empty-state h3{
    color:#fff;
    margin-bottom:10px;
}

/* ANIMATIONS */
@keyframes fadeIn{
    from{
        opacity:0;
        transform:translateY(20px);
    }
    to{
        opacity:1;
        transform:translateY(0);
    }
}

/* ==========================
   RESPONSIVE DESIGN
========================== */

/* LAPTOPS */
@media (max-width: 1200px){
    .sidebar{
        width:260px;
    }

    .main-content{
        margin-left:260px;
        padding:25px;
    }

    .header h1{
        font-size:28px;
    }
}

/* TABLETS */
@media (max-width: 992px){

    .sidebar{
        transform:translateX(-100%);
        transition:0.3s ease;
    }

    .sidebar.active{
        transform:translateX(0);
    }

    .main-content{
        margin-left:0;
        width:100%;
        padding:20px;
    }

    .header{
        flex-wrap:wrap;
        gap:12px;
    }

    .header h1{
        font-size:24px;
    }

    .stats-bar{
        display:grid;
        grid-template-columns:repeat(auto-fit, minmax(150px, 1fr));
        gap:10px;
    }

    .stat-badge{
        text-align:center;
    }

    .orders-table{
        overflow-x:auto;
        border-radius:20px;
    }

    table{
        min-width:900px;
    }

    th, td{
        padding:12px;
        font-size:13px;
        white-space:nowrap;
    }
}

/* PHONES */
@media (max-width: 1024px){

    body{
        overflow-x:hidden;
    }

    .sidebar{
        width:280px;
        position:fixed;
        left:0;
        top:0;
        height:100%;
        z-index:2000;
    }

    .main-content{
        margin-left:0;
        padding:15px;
    }

    .header{
        display:flex;
        flex-direction:column;
        align-items:flex-start;
        gap:12px;
    }

    .header > div{
        width:100%;
        justify-content:space-between;
    }

    .header h1{
        font-size:22px;
    }

    .back-btn{
        width:100%;
        text-align:center;
    }

    .stats-bar{
        grid-template-columns:repeat(2, 1fr);
    }

    .stat-badge{
        width:100%;
        font-size:11px;
        padding:10px;
    }

    .orders-table{
        overflow-x:auto;
    }

    table{
        min-width:850px;
    }

    th{
        font-size:11px;
    }

    td{
        font-size:12px;
    }

    .btn{
        padding:8px 12px;
        font-size:11px;
    }
}

/* SMALL PHONES */
@media (max-width: 480px){

    .main-content{
        padding:12px;
    }

    .header h1{
        font-size:20px;
    }

    .stats-bar{
        grid-template-columns:1fr;
    }

    .menu-toggle{
        padding:10px 14px;
        font-size:14px;
    }

    .back-btn{
        font-size:13px;
        padding:10px 15px;
    }

    table{
        min-width:800px;
    }

    td, th{
        padding:10px;
    }
}

/* DESKTOP LARGE SCREENS */
@media (min-width: 1400px){

    .sidebar{
        width:300px;
    }

    .main-content{
        margin-left:300px;
        padding:40px;
    }

    .header h1{
        font-size:36px;
    }
}
</style>
<link rel="stylesheet" href="../assets/responsive.css">
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
    <div class="container">
        <div class="sidebar">
            <h2>Smart Poultry</h2>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active"><span class="material-symbols-outlined">dashboard</span> Overview</a></li>
            
            <div style="color:#64748b; font-size:11px; text-transform:uppercase; margin:20px 0 10px 15px; font-weight:600; letter-spacing:1px;">Orders</div>
            <li><a href="verify_orders.php"><span class="material-symbols-outlined">fact_check</span> Verify Order</a></li>
            <li><a href="check_payment.php"><span class="material-symbols-outlined">payments</span> Check Payment</a></li>
            <li><a href="pack_order.php"><span class="material-symbols-outlined">inventory_2</span> Pack Order</a></li>
            <li><a href="ship_out_orders.php"><span class="material-symbols-outlined">local_shipping</span> Ship Out</a></li>
            <li><a href="view_orders.php"><span class="material-symbols-outlined">receipt_long</span> View Orders</a></li>
            
            <div style="color:#64748b; font-size:11px; text-transform:uppercase; margin:20px 0 10px 15px; font-weight:600; letter-spacing:1px;">Inventory</div>
            <li><a href="check_stock.php"><span class="material-symbols-outlined">analytics</span> Check Stock</a></li>
            <li><a href="manage_inventory.php"><span class="material-symbols-outlined">category</span> Manage Inventory</a></li>
            <li><a href="restock.php"><span class="material-symbols-outlined">add_business</span> Restock</a></li>
            
            <div style="color:#64748b; font-size:11px; text-transform:uppercase; margin:20px 0 10px 15px; font-weight:600; letter-spacing:1px;">Products</div>
            <li><a href="add_product.php"><span class="material-symbols-outlined">add_circle</span> Add Product</a></li>
            <li><a href="update_product.php"><span class="material-symbols-outlined">edit_square</span> Update Product</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="header">
                <div style="display: flex; align-items: center;">
                    <button class="menu-toggle" onclick="toggleSidebar()"><span class="material-symbols-outlined">menu</span></button>
                    <h1>View Orders</h1>
                </div>
                <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
            </div>
            
            <div class="stats-bar">
                <a href="view_orders.php" class="stat-badge <?= $status_filter == '' ? 'active' : '' ?>">All (<?= array_sum($status_stats) ?>)</a>
                <a href="view_orders.php?status=Draft" class="stat-badge draft <?= $status_filter == 'Draft' ? 'active' : '' ?>">Draft (<?= $status_stats['Draft'] ?>)</a>
                <a href="view_orders.php?status=Pending Payment" class="stat-badge pending <?= $status_filter == 'Pending Payment' ? 'active' : '' ?>">Pending Payment (<?= $status_stats['Pending Payment'] ?>)</a>
                <a href="view_orders.php?status=Pending Approval" class="stat-badge pending <?= $status_filter == 'Pending Approval' ? 'active' : '' ?>">Pending Approval (<?= $status_stats['Pending Approval'] ?>)</a>
                <a href="view_orders.php?status=Approved" class="stat-badge approved <?= $status_filter == 'Approved' ? 'active' : '' ?>">Approved (<?= $status_stats['Approved'] ?>)</a>
                <a href="view_orders.php?status=Processed" class="stat-badge processed <?= $status_filter == 'Processed' ? 'active' : '' ?>">Processed (<?= $status_stats['Processed'] ?>)</a>
                <a href="view_orders.php?status=Shipped" class="stat-badge shipped <?= $status_filter == 'Shipped' ? 'active' : '' ?>">Shipped (<?= $status_stats['Shipped'] ?>)</a>
                <a href="view_orders.php?status=Delivered" class="stat-badge delivered <?= $status_filter == 'Delivered' ? 'active' : '' ?>">Delivered (<?= $status_stats['Delivered'] ?>)</a>
                <a href="view_orders.php?status=Cancelled" class="stat-badge cancelled <?= $status_filter == 'Cancelled' ? 'active' : '' ?>">Cancelled (<?= $status_stats['Cancelled'] ?>)</a>
            </div>
            
            <div class="orders-table">
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Payment Method</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <?php
                                $status_class = '';
                                switch($row['status']) {
                                    case 'Draft': $status_class = 'status-draft'; break;
                                    case 'Pending Payment':
                                    case 'Pending Approval': $status_class = 'status-pending'; break;
                                    case 'Approved': $status_class = 'status-approved'; break;
                                    case 'Processed': $status_class = 'status-processed'; break;
                                    case 'Shipped': $status_class = 'status-shipped'; break;
                                    case 'Delivered': $status_class = 'status-delivered'; break;
                                    case 'Cancelled': $status_class = 'status-cancelled'; break;
                                }
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['order_number']) ?></td>
                                    <td><?= htmlspecialchars($row['fullname']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= htmlspecialchars($row['phone']) ?></td>
                                    <td>₱<?= number_format($row['total_amount'], 2) ?></td>
                                    <td><span class="status-badge <?= $status_class ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                                    <td><?= htmlspecialchars($row['payment_method']) ?></td>
                                    <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                    <td>
                                        <button onclick="viewOrderDetails(<?= $row['id'] ?>)" class="btn btn-view">View Details</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>No Orders Found</h3>
                        <p>There are no orders with the selected status.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function viewOrderDetails(orderId) {
            alert('Order details view would open here for Order ID: ' + orderId);
            // You can implement a modal or redirect to a detailed order view page
        }
    </script>
    
    <script>
       /* Close sidebar when clicking outside on mobile */
</script>
<script src="../assets/sidebar.js"></script>
</body>
</html>
