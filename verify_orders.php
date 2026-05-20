<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include '../connection.php';
include 'helpers.php';

// Handle order verification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $order_id = $_POST['order_id'];
    
    if ($_POST['action'] == 'approve') {
        $old_status = getOrderStatus($connection, $order_id);
        $query = "UPDATE orders SET status = 'Approved', created_by = ? WHERE id = ?";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "ii", $_SESSION['admin_id'], $order_id);
        mysqli_stmt_execute($stmt);
        logOrderStatusChange($connection, $order_id, $old_status, 'Approved', $_SESSION['admin_id']);
        $message = "Order approved successfully!";
    } elseif ($_POST['action'] == 'reject') {
        $old_status = getOrderStatus($connection, $order_id);
        $query = "UPDATE orders SET status = 'Cancelled', created_by = ? WHERE id = ?";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "ii", $_SESSION['admin_id'], $order_id);
        mysqli_stmt_execute($stmt);
        logOrderStatusChange($connection, $order_id, $old_status, 'Cancelled', $_SESSION['admin_id']);
        $message = "Order rejected successfully!";
    }
}

// Get pending orders for verification
$query = "SELECT o.*, c.fullname, c.email, c.phone 
          FROM orders o 
          JOIN customers c ON o.customer_id = c.id 
          WHERE o.status IN ('Draft', 'Pending Payment', 'Pending Approval')
          ORDER BY o.created_at DESC";
$result = mysqli_query($connection, $query);

$order_ids = [];
$pending_orders_list = [];
while ($row = mysqli_fetch_assoc($result)) {
    $order_ids[] = $row['id'];
    $pending_orders_list[] = $row;
}

// Fetch all order items for these orders
$order_items = [];
if (!empty($order_ids)) {
    $ids_string = implode(',', $order_ids);
    $items_query = "SELECT oi.*, p.name as product_name, p.image_path 
                    FROM order_items oi 
                    JOIN inventory_items p ON oi.product_id = p.id 
                    WHERE oi.order_id IN ($ids_string)";
    $items_result = mysqli_query($connection, $items_query);
    while ($item = mysqli_fetch_assoc($items_result)) {
        $order_items[$item['order_id']][] = $item;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Orders - Admin</title>
    <style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined');

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Outfit', sans-serif;
}

body{
    background:
    radial-gradient(circle at top left, rgba(59,130,246,.15), transparent 30%),
    radial-gradient(circle at bottom right, rgba(59,130,246,.08), transparent 25%),
    linear-gradient(135deg,#090e1a,#0b1220,#0f172a);
    background-attachment:fixed;
    min-height:100vh;
    color:#fff;
    overflow-x:hidden;
}

/* Smooth animation */
*{
    transition: all .3s ease;
}

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
/* ======================
   MAIN CONTENT
====================== */
.main-content{
    margin-left:280px;
    flex:1;
    padding:35px;
}

.main-content.expanded{
    margin-left:0;
}

.header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
}

.header h1{
    font-size:32px;
    font-weight:700;
    color:#fff;
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
/* ======================
   SUCCESS MESSAGE
====================== */
.message{
    background:rgba(34,197,94,.12);
    border:1px solid rgba(34,197,94,.25);
    color:#bbf7d0;
    padding:18px;
    border-radius:18px;
    margin-bottom:20px;
    backdrop-filter:blur(12px);
    animation:fadeIn .5s ease;
}

/* ======================
   GLASS TABLE
====================== */
.orders-table{
    background:rgba(255,255,255,.05);
    border:1px solid rgba(255,255,255,.08);
    backdrop-filter:blur(18px);
    border-radius:24px;
    overflow:hidden;
    box-shadow:
    0 0 40px rgba(59,130,246,.12),
    inset 0 0 0 1px rgba(255,255,255,.03);
    animation:fadeUp .5s ease;
}

table{
    width:100%;
    border-collapse:collapse;
}

th, td{
    padding:18px;
    text-align:left;
}

th{
    background:rgba(255,255,255,.04);
    color:#60a5fa;
    font-size:13px;
    text-transform:uppercase;
    letter-spacing:.5px;
    border-bottom:1px solid rgba(255,255,255,.08);
}

td{
    color:#e5e7eb;
    border-bottom:1px solid rgba(255,255,255,.05);
}

tr{
    transition:.3s ease;
}

tr:hover{
    background:rgba(59,130,246,.08);
    transform:scale(1.003);
}

/* ======================
   STATUS BADGES
====================== */
.status-badge{
    padding:8px 14px;
    border-radius:50px;
    font-size:12px;
    font-weight:600;
    display:inline-block;
}

.status-draft{
    background:rgba(251,191,36,.18);
    color:#fde68a;
    border:1px solid rgba(251,191,36,.3);
}

.status-pending{
    background:rgba(59,130,246,.18);
    color:#93c5fd;
    border:1px solid rgba(59,130,246,.3);
}

/* ======================
   ACTION BUTTONS
====================== */
.action-buttons{
    display:flex;
    gap:10px;
}

.btn-approve{
    background:rgba(34,197,94,.18);
    border:1px solid rgba(34,197,94,.3);
}

.btn-approve:hover{
    background:rgba(34,197,94,.35);
}

.btn-reject{
    background:rgba(239,68,68,.18);
    border:1px solid rgba(239,68,68,.3);
}

.btn-reject:hover{
    background:rgba(239,68,68,.35);
}

.btn-view{
    background:rgba(59,130,246,.18);
}

/* ======================
   EMPTY STATE
====================== */
.empty-state{
    text-align:center;
    padding:60px 20px;
    color:#94a3b8;
}

.empty-state h3{
    color:#fff;
    margin-bottom:10px;
    font-size:22px;
}

/* ======================
   SCROLLBAR
====================== */
::-webkit-scrollbar{
    width:8px;
}

::-webkit-scrollbar-thumb{
    background:rgba(59,130,246,.3);
    border-radius:20px;
}

/* ======================
   ANIMATIONS
====================== */
@keyframes fadeUp{
    from{
        opacity:0;
        transform:translateY(20px);
    }
    to{
        opacity:1;
        transform:translateY(0);
    }
}

@keyframes fadeIn{
    from{
        opacity:0;
    }
    to{
        opacity:1;
    }
}

/* ======================
   RESPONSIVE DESIGN
====================== */

/* DESKTOP / LARGE LAPTOP */
@media screen and (max-width: 1400px){
    .sidebar{
        width:260px;
    }

    .main-content{
        margin-left:260px;
        padding:30px;
    }
}

/* LAPTOP */
@media screen and (max-width: 1200px){
    .main-content{
        padding:25px;
    }

    .header h1{
        font-size:28px;
    }

    th, td{
        padding:14px;
    }
}

/* TABLET */
@media screen and (max-width: 992px){

    .sidebar{
        width:260px;
        transform:translateX(-100%);
        position:fixed;
        left:0;
        top:0;
        transition:.3s ease;
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
        gap:15px;
    }

    .header h1{
        font-size:26px;
    }

    .orders-table{
        overflow-x:auto;
        border-radius:18px;
    }

    table{
        min-width:850px;
    }

    .action-buttons{
        flex-direction:column;
    }

    .action-buttons button{
        width:100%;
    }

    .menu-toggle{
        display:block;
    }
}

/* MOBILE */
@media (max-width: 1024px){

    body{
        overflow-x:hidden;
    }

    .main-content{
        padding:16px;
    }

    .header{
        flex-direction:column;
        align-items:flex-start;
        gap:15px;
    }

    .header h1{
        font-size:22px;
    }

    .back-btn{
        width:100%;
        text-align:center;
    }

    .orders-table{
        overflow-x:auto;
        width:100%;
    }

    table{
        min-width:750px;
        font-size:13px;
    }

    th, td{
        padding:10px;
        white-space:nowrap;
    }

    .menu-toggle{
        display:block;
    }

    .action-buttons{
        flex-direction:column;
        width:100%;
    }

    .btn{
        width:100%;
    }
}

/* SMALL MOBILE */
@media screen and (max-width: 480px){

    .main-content{
        padding:12px;
    }

    .header h1{
        font-size:20px;
    }

    .sidebar{
        width:240px;
    }

    table{
        min-width:680px;
        font-size:12px;
    }

    th, td{
        padding:8px;
    }

    .status-badge{
        font-size:11px;
        padding:6px 10px;
    }

    .back-btn{
        padding:10px;
        font-size:14px;
    }
}
/* SMALL MOBILE */
@media(max-width:480px){
    .main-content{
        padding:15px;
    }

    .header{
        padding:12px 15px;
    }

    .header h1{
        font-size:20px;
    }

    .sidebar{
        width:260px;
    }

    table{
        font-size:11px;
        min-width:700px;
    }

    th, td{
        padding:6px;
    }
}

/* Custom Order Verification Premium Styles */
.filter-bar {
    display: flex;
    gap: 20px;
    margin-bottom: 25px;
    background: rgba(255, 255, 255, 0.04);
    padding: 20px;
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.06);
    backdrop-filter: blur(15px);
}

.search-wrapper {
    position: relative;
    flex: 1;
}

.search-wrapper span {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #8ea1bb;
    font-size: 20px;
}

.search-wrapper input {
    width: 100%;
    padding: 12px 15px 12px 45px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 14px;
    color: white;
    outline: none;
    transition: all 0.3s ease;
}

.search-wrapper input:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 15px rgba(66, 133, 244, 0.2);
}

.select-wrapper {
    width: 250px;
    position: relative;
}

.select-wrapper select {
    width: 100%;
    padding: 12px 15px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 14px;
    color: white;
    outline: none;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml;utf8,<svg fill='white' height='24' viewBox='0 0 24 24' width='24' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/><path d='M0 0h24v24H0z' fill='none'/></svg>");
    background-repeat: no-repeat;
    background-position: right 12px center;
}

.select-wrapper select option {
    background: #0f172a;
    color: white;
}

/* Table detail collapse transitions */
.order-row {
    cursor: pointer;
    transition: all 0.3s ease;
}

.order-row:hover {
    background: rgba(66, 133, 244, 0.1) !important;
}

.order-row.expanded {
    background: rgba(66, 133, 244, 0.08) !important;
}

.detail-row {
    background: rgba(17, 25, 40, 0.5) !important;
}

.detail-row td {
    padding: 0 !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.details-collapse-wrapper {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.details-collapse-wrapper.show {
    max-height: 1200px;
}

.order-details-pane {
    padding: 25px;
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
}

.items-details-box {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 18px;
    padding: 20px;
}

.items-details-box h4 {
    margin-bottom: 15px;
    font-size: 15px;
    color: #8cb4ff;
    display: flex;
    align-items: center;
    gap: 8px;
}

.info-details-box {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 18px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.info-details-box h4 {
    font-size: 15px;
    color: #8cb4ff;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 5px;
}

.info-item {
    font-size: 13px;
}

.info-item label {
    display: block;
    color: #8ea1bb;
    font-size: 11px;
    text-transform: uppercase;
    margin-bottom: 4px;
}

.info-item span {
    color: white;
    font-weight: 500;
}

.product-thumb {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    object-fit: cover;
    background: rgba(255,255,255,0.05);
}

.details-table {
    width: 100%;
    margin-top: 10px;
}

.details-table th {
    padding: 10px !important;
    background: transparent !important;
    border-bottom: 1px solid rgba(255,255,255,0.08) !important;
    font-size: 11px !important;
}

.details-table td {
    padding: 10px !important;
    border-bottom: none !important;
    font-size: 12px !important;
}

.btn-toggle-icon {
    font-size: 18px;
    transition: transform 0.3s ease;
}

.order-row.expanded .btn-toggle-icon {
    transform: rotate(180deg);
}

/* RESPONSIVE LAYOUT FIXES FOR DETAIL VIEW */
@media screen and (max-width: 992px) {
    .order-details-pane {
        grid-template-columns: 1fr;
        gap: 20px;
        padding: 15px;
    }
    .filter-bar {
        flex-direction: column;
        gap: 12px;
    }
    .select-wrapper {
        width: 100%;
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
                    <h1>Verify Orders</h1>
                </div>
                <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
            </div>
            
            <?php if (isset($message)): ?>
                <div class="message"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <!-- Search and Select Box filters -->
            <div class="filter-bar">
                <div class="search-wrapper">
                    <span class="material-symbols-outlined">search</span>
                    <input type="text" id="orderSearchInput" placeholder="Search orders by customer name, email, or order number..." onkeyup="filterOrders()">
                </div>
                <div class="select-wrapper">
                    <select id="orderSelectFilter" onchange="selectOrderFilter()">
                        <option value="">All Pending Orders</option>
                        <?php foreach ($pending_orders_list as $row): ?>
                            <option value="<?= htmlspecialchars($row['order_number']) ?>"><?= htmlspecialchars($row['order_number']) ?> (<?= htmlspecialchars($row['fullname']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="orders-table">
                <?php if (count($pending_orders_list) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 50px;"></th>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_orders_list as $row): 
                                $oid = $row['id'];
                                $items = isset($order_items[$oid]) ? $order_items[$oid] : [];
                            ?>
                                <!-- Main Row -->
                                <tr class="order-row" id="order-row-<?= $oid ?>" onclick="toggleOrderDetails(<?= $oid ?>)" data-orderno="<?= htmlspecialchars($row['order_number']) ?>" data-customer="<?= htmlspecialchars(strtolower($row['fullname'])) ?>" data-email="<?= htmlspecialchars(strtolower($row['email'])) ?>">
                                    <td>
                                        <span class="material-symbols-outlined btn-toggle-icon">expand_more</span>
                                    </td>
                                    <td><strong><?= htmlspecialchars($row['order_number']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['fullname']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= htmlspecialchars($row['phone']) ?></td>
                                    <td style="color: #60a5fa; font-weight: 600;">₱<?= number_format($row['total_amount'], 2) ?></td>
                                    <td>
                                        <span class="status-badge <?= $row['status'] == 'Draft' ? 'status-draft' : 'status-pending' ?>">
                                            <?= htmlspecialchars($row['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                    <td>
                                        <div class="action-buttons" onclick="event.stopPropagation();">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn btn-approve">Approve</button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn btn-reject">Reject</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                
                                <!-- Detailed Row -->
                                <tr class="detail-row" id="detail-row-<?= $oid ?>" data-orderno="<?= htmlspecialchars($row['order_number']) ?>" data-customer="<?= htmlspecialchars(strtolower($row['fullname'])) ?>" data-email="<?= htmlspecialchars(strtolower($row['email'])) ?>">
                                    <td colspan="9">
                                        <div class="details-collapse-wrapper" id="collapse-<?= $oid ?>">
                                            <div class="order-details-pane">
                                                <!-- Items Purchased -->
                                                <div class="items-details-box">
                                                    <h4>
                                                        <span class="material-symbols-outlined" style="font-size: 20px;">shopping_bag</span>
                                                        Ordered Items Detail
                                                    </h4>
                                                    <?php if (count($items) > 0): ?>
                                                        <table class="details-table">
                                                            <thead>
                                                                <tr>
                                                                    <th>Image</th>
                                                                    <th>Product Name</th>
                                                                    <th>Unit Price</th>
                                                                    <th>Quantity</th>
                                                                    <th>Total Price</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($items as $item): ?>
                                                                    <tr>
                                                                        <td>
                                                                            <img src="../<?= htmlspecialchars($item['image_path'] ? $item['image_path'] : 'uploads/inventory/default.png') ?>" class="product-thumb" alt="Product">
                                                                        </td>
                                                                        <td><strong><?= htmlspecialchars($item['product_name']) ?></strong></td>
                                                                        <td>₱<?= number_format($item['unit_price'], 2) ?></td>
                                                                        <td>× <?= intval($item['quantity']) ?></td>
                                                                        <td style="color: #60a5fa; font-weight: 500;">₱<?= number_format($item['total_price'], 2) ?></td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    <?php else: ?>
                                                        <p style="color: #8ea1bb; font-size: 13px;">No items found for this order.</p>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <!-- Delivery Information -->
                                                <div class="info-details-box">
                                                    <h4>
                                                        <span class="material-symbols-outlined" style="font-size: 20px;">info</span>
                                                        Customer & Delivery Info
                                                    </h4>
                                                    
                                                    <div class="info-item">
                                                        <label>Recipient Name</label>
                                                        <span><?= htmlspecialchars($row['fullname']) ?></span>
                                                    </div>
                                                    
                                                    <div class="info-item">
                                                        <label>Delivery Address</label>
                                                        <span style="display: block; font-size: 12px; line-height: 1.5; color: #cbd5e1; margin-top: 2px;">
                                                            <?= nl2br(htmlspecialchars($row['delivery_address'] ? $row['delivery_address'] : 'No address provided')) ?>
                                                        </span>
                                                    </div>
                                                    
                                                    <div class="info-item">
                                                        <label>Payment details</label>
                                                        <span style="text-transform: uppercase; font-size: 12px; color: #ffc107;">
                                                            <?= htmlspecialchars(str_replace('_', ' ', $row['payment_method'])) ?>
                                                        </span>
                                                    </div>
                                                    
                                                    <div class="info-item">
                                                        <label>Customer Notes</label>
                                                        <span style="font-size: 12px; color: #94a3b8; font-style: italic;">
                                                            <?= htmlspecialchars($row['notes'] ? $row['notes'] : 'No special instructions') ?>
                                                        </span>
                                                    </div>
                                                    
                                                    <!-- Actions Inside detail box -->
                                                    <div class="action-buttons" style="margin-top: 15px; justify-content: flex-end;">
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                                            <input type="hidden" name="action" value="approve">
                                                            <button type="submit" class="btn btn-approve" style="padding: 12px 20px;">Approve Order</button>
                                                        </form>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                                            <input type="hidden" name="action" value="reject">
                                                            <button type="submit" class="btn btn-reject" style="padding: 12px 20px;">Reject Order</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>No Pending Orders</h3>
                        <p>There are no orders waiting for verification.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>

        // Collapsible Details Logic
        function toggleOrderDetails(orderId) {
            const row = document.getElementById(`order-row-${orderId}`);
            const collapseWrapper = document.getElementById(`collapse-${orderId}`);
            
            // Check if currently expanded
            const isExpanded = row.classList.contains('expanded');
            
            // Collapse all rows first for clean layout
            document.querySelectorAll('.order-row').forEach(r => r.classList.remove('expanded'));
            document.querySelectorAll('.details-collapse-wrapper').forEach(w => w.classList.remove('show'));
            
            // If it wasn't expanded, expand it now
            if (!isExpanded) {
                row.classList.add('expanded');
                collapseWrapper.classList.add('show');
            }
        }

        // Search orders dynamically
        function filterOrders() {
            const query = document.getElementById('orderSearchInput').value.toLowerCase().trim();
            const selectFilter = document.getElementById('orderSelectFilter');
            
            // Clear select dropdown value when typing search
            if (query !== "") {
                selectFilter.value = "";
            }
            
            const rows = document.querySelectorAll('.order-row');
            rows.forEach(row => {
                const orderId = row.id.replace('order-row-', '');
                const detailRow = document.getElementById(`detail-row-${orderId}`);
                
                const orderNo = row.getAttribute('data-orderno').toLowerCase();
                const customer = row.getAttribute('data-customer').toLowerCase();
                const email = row.getAttribute('data-email').toLowerCase();
                
                const matches = orderNo.includes(query) || customer.includes(query) || email.includes(query);
                
                if (matches) {
                    row.style.display = "";
                    detailRow.style.display = "";
                } else {
                    row.style.display = "none";
                    detailRow.style.display = "none";
                    // Collapse if it was open
                    row.classList.remove('expanded');
                    document.getElementById(`collapse-${orderId}`).classList.remove('show');
                }
            });
        }

        // Select order dropdown filter
        function selectOrderFilter() {
            const selectedOrderNo = document.getElementById('orderSelectFilter').value.toLowerCase().trim();
            const searchInput = document.getElementById('orderSearchInput');
            
            // Clear search input when choosing dropdown
            if (selectedOrderNo !== "") {
                searchInput.value = "";
            }
            
            const rows = document.querySelectorAll('.order-row');
            rows.forEach(row => {
                const orderId = row.id.replace('order-row-', '');
                const detailRow = document.getElementById(`detail-row-${orderId}`);
                
                const orderNo = row.getAttribute('data-orderno').toLowerCase();
                
                if (selectedOrderNo === "" || orderNo === selectedOrderNo) {
                    row.style.display = "";
                    detailRow.style.display = "";
                    // Auto expand selected order
                    if (selectedOrderNo !== "") {
                        row.classList.add('expanded');
                        document.getElementById(`collapse-${orderId}`).classList.add('show');
                    } else {
                        row.classList.remove('expanded');
                        document.getElementById(`collapse-${orderId}`).classList.remove('show');
                    }
                } else {
                    row.style.display = "none";
                    detailRow.style.display = "none";
                    row.classList.remove('expanded');
                    document.getElementById(`collapse-${orderId}`).classList.remove('show');
                }
            });
        }
</script>
<script src="../assets/sidebar.js"></script>
</body>
</html>
