<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include '../connection.php';
include 'helpers.php';

// Handle payment verification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $order_id = $_POST['order_id'];
    $admin_notes = isset($_POST['admin_notes']) ? mysqli_real_escape_string($connection, $_POST['admin_notes']) : '';
    
    if ($_POST['action'] == 'approve') {
        $old_status = getOrderStatus($connection, $order_id);
        $query = "UPDATE orders SET status = 'Approved', payment_date = NOW(), created_by = ? WHERE id = ?";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "ii", $_SESSION['admin_id'], $order_id);
        mysqli_stmt_execute($stmt);
        logOrderStatusChange($connection, $order_id, $old_status, 'Approved', $_SESSION['admin_id']);
        
        if (!empty($admin_notes)) {
            $update_notes = "UPDATE orders SET notes = CONCAT(IFNULL(notes, ''), '\nAdmin Note: ', ?) WHERE id = ?";
            $note_stmt = mysqli_prepare($connection, $update_notes);
            mysqli_stmt_bind_param($note_stmt, "si", $admin_notes, $order_id);
            mysqli_stmt_execute($note_stmt);
        }
        
        $message = "Payment verified and approved successfully!";
    } elseif ($_POST['action'] == 'reject') {
        $old_status = getOrderStatus($connection, $order_id);
        $query = "UPDATE orders SET status = 'Cancelled', created_by = ? WHERE id = ?";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "ii", $_SESSION['admin_id'], $order_id);
        mysqli_stmt_execute($stmt);
        logOrderStatusChange($connection, $order_id, $old_status, 'Cancelled', $_SESSION['admin_id']);
        
        if (!empty($admin_notes)) {
            $update_notes = "UPDATE orders SET notes = CONCAT(IFNULL(notes, ''), '\nAdmin Note (Rejected): ', ?) WHERE id = ?";
            $note_stmt = mysqli_prepare($connection, $update_notes);
            mysqli_stmt_bind_param($note_stmt, "si", $admin_notes, $order_id);
            mysqli_stmt_execute($note_stmt);
        }
        
        $message = "Payment rejected successfully!";
    }
}

// Get orders with pending payment
$query = "SELECT o.*, c.fullname, c.email, c.phone 
          FROM orders o 
          JOIN customers c ON o.customer_id = c.id 
          WHERE o.status = 'Pending Payment'
          ORDER BY o.created_at DESC";
$result = mysqli_query($connection, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Payment - Admin</title>

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
    --bg:#090e1a;
    --card:rgba(15,23,42,.65);
    --border:rgba(255,255,255,.08);
    --accent:#3b82f6;
    --accent2:#6ea8fe;
    --text:#ffffff;
    --muted:#9ca3af;
    --danger:#ff4d6d;
    --success:#22c55e;
    --shadow:0 8px 32px rgba(0,0,0,.35);
}

body{
    background:
        radial-gradient(circle at top left,#12213d 0%,transparent 30%),
        radial-gradient(circle at bottom right,#0a4abf20 0%,transparent 25%),
        linear-gradient(135deg,#020617,#0f172a,#090e1a);
    background-attachment:fixed;
    color:var(--text);
    min-height:100vh;
    overflow-x:hidden;
}

/* Scrollbar */
::-webkit-scrollbar{
    width:8px;
}
::-webkit-scrollbar-thumb{
    background:rgba(255,255,255,.15);
    border-radius:20px;
}

/* Container */
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

/* Menu */
.sidebar-menu{
    list-style:none;
}

.sidebar-menu li{
    margin-bottom:10px;
}

.sidebar-menu .section-title{
    color:#7d8db3;
    font-size:12px;
    text-transform:uppercase;
    letter-spacing:1.5px;
    margin:25px 0 10px;
    font-weight:600;
    padding-left:10px;
}

.sidebar-menu a{
    display:flex;
    align-items:center;
    gap:10px;

    text-decoration:none;
    color:#dbeafe;

    padding:14px 16px;
    border-radius:16px;

    transition:.3s ease;
    position:relative;
    overflow:hidden;
}

.sidebar-menu a::before{
    content:'';
    position:absolute;
    inset:0;
    background:linear-gradient(
        90deg,
        rgba(59,130,246,.18),
        transparent
    );
    transform:translateX(-100%);
    transition:.5s;
}

.sidebar-menu a:hover::before{
    transform:translateX(0);
}

.sidebar-menu a:hover,
.sidebar-menu a.active{
    background:rgba(59,130,246,.18);
    border:1px solid rgba(59,130,246,.25);
    transform:translateX(6px);

    box-shadow:
        0 0 25px rgba(59,130,246,.2);
}

/* MAIN CONTENT */
.main-content{
    flex:1;
    margin-left:280px;
    padding:35px;
    transition:.35s ease;
}

.main-content.expanded{
    margin-left:0;
}

/* HEADER */
.header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:30px;
}

.header h1{
    font-size:32px;
    font-weight:700;
    color:white;
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
/* MESSAGE */
.message{
    background:rgba(34,197,94,.12);
    border:1px solid rgba(34,197,94,.25);
    color:#86efac;
    padding:16px;
    border-radius:20px;
    margin-bottom:20px;

    backdrop-filter:blur(16px);
}

/* TABLE */
.orders-table{
    background:rgba(255,255,255,.05);
    border:1px solid rgba(255,255,255,.08);

    backdrop-filter:blur(22px);
    -webkit-backdrop-filter:blur(22px);

    border-radius:30px;
    overflow:hidden;

    box-shadow:
        0 8px 40px rgba(0,0,0,.35),
        0 0 30px rgba(59,130,246,.08);

    animation:fadeUp .6s ease;
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:rgba(255,255,255,.04);
    color:#90caf9;
    text-transform:uppercase;
    font-size:12px;
    letter-spacing:1px;
}

th,td{
    padding:18px;
    text-align:left;
    border-bottom:1px solid rgba(255,255,255,.05);
}

td{
    color:#dbeafe;
}

tr{
    transition:.3s ease;
}

tr:hover{
    background:rgba(59,130,246,.06);
}

/* Payment Method */
.payment-method{
    background:rgba(255,255,255,.08);
    border:1px solid rgba(255,255,255,.08);
    color:#bfdbfe;

    padding:8px 14px;
    border-radius:999px;
    font-size:12px;
    font-weight:600;
}

/* ACTION BUTTONS */
.action-buttons{
    display:flex;
    gap:10px;
}

.btn{
    padding:10px 16px;
    border-radius:14px;
    font-size:13px;
    transition:.3s ease;
}

.btn:hover{
    transform:translateY(-3px) scale(1.02);
}

.btn-confirm{
    background:rgba(34,197,94,.15);
    border:1px solid rgba(34,197,94,.25);
}

.btn-confirm:hover{
    box-shadow:0 0 25px rgba(34,197,94,.35);
}

.btn-reject{
    background:rgba(255,77,109,.12);
    border:1px solid rgba(255,77,109,.2);
}

.btn-reject:hover{
    box-shadow:0 0 25px rgba(255,77,109,.3);
}

.btn-cancel{
    background:rgba(255,255,255,.08);
}

/* EMPTY STATE */
.empty-state{
    padding:70px;
    text-align:center;
    color:#94a3b8;
}

.empty-state h3{
    color:white;
    margin-bottom:10px;
}

/* MODAL */
.modal{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.65);
    backdrop-filter:blur(8px);
    z-index:9999;
}

.modal-content{
    width:800px;
    max-width:95%;
    margin:5% auto;
    padding:35px;

    border-radius:30px;

    background:rgba(15,23,42,.95);
    border:1px solid rgba(255,255,255,.1);

    backdrop-filter:blur(30px);

    box-shadow:
        0 0 50px rgba(59,130,246,.18);

    animation:modalPop .35s ease;
    max-height: 85vh;
    overflow-y: auto;
}

.modal-content h2{
    margin-bottom:25px;
    color:white;
    font-size: 28px;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Verification Grid */
.verify-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 25px;
}

.verify-section {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 20px;
    padding: 20px;
}

.verify-section h3 {
    color: #60a5fa;
    font-size: 16px;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    padding-bottom: 10px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    font-size: 14px;
}

.info-label {
    color: #94a3b8;
}

.info-value {
    color: #f8fafc;
    font-weight: 500;
    text-align: right;
    max-width: 60%;
    word-wrap: break-word;
}

/* Items Table */
.items-table {
    width: 100%;
    margin-top: 10px;
    background: rgba(0,0,0,0.2);
    border-radius: 12px;
}
.items-table th, .items-table td {
    padding: 10px;
    font-size: 13px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}

.warning-text {
    color: var(--danger);
    font-weight: 600;
    font-size: 13px;
    margin-top: 5px;
    display: block;
}

/* INPUTS */
.form-group{
    margin-bottom:18px;
}

.form-group label{
    display:block;
    margin-bottom:8px;
    color:#cbd5e1;
    font-weight:500;
}

.form-group input{
    width:100%;
    padding:14px 16px;

    border-radius:16px;
    border:1px solid rgba(255,255,255,.08);

    background:rgba(255,255,255,.05);
    color:white;

    outline:none;
    transition:.3s ease;
}

.form-group input:focus{
    border-color:rgba(59,130,246,.55);

    box-shadow:
        0 0 0 4px rgba(59,130,246,.15),
        0 0 30px rgba(59,130,246,.15);

    transform:translateY(-2px);
}

.modal-buttons{
    display:flex;
    gap:12px;
    margin-top:20px;
}

/* ANIMATIONS */
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

@keyframes modalPop{
    from{
        opacity:0;
        transform:scale(.9);
    }
    to{
        opacity:1;
        transform:scale(1);
    }
}

/* ==========================
   RESPONSIVE DESIGN
========================== */

/* LARGE LAPTOPS / DESKTOP */
@media screen and (max-width: 1400px) {
    .sidebar{
        width:260px;
    }

    .main-content{
        margin-left:260px;
        padding:30px;
    }

    .header h1{
        font-size:28px;
    }

    th, td{
        padding:14px;
    }
}

/* TABLETS / SMALL LAPTOPS */
@media screen and (max-width: 1024px){

    .sidebar{
        width:240px;
    }

    .main-content{
        margin-left:240px;
        padding:25px;
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
        border-radius:20px;
    }

    table{
        min-width:850px;
    }

    th, td{
        padding:12px;
        font-size:13px;
    }

    .action-buttons{
        flex-direction:column;
    }

    .btn{
        width:100%;
    }
}

/* MOBILE + TABLET SIDEBAR */
@media (max-width: 1024px){

    body{
        overflow-x:hidden;
    }

    .sidebar{
        position:fixed;
        top:0;
        left:-100%;
        width:270px;
        height:100%;
        transition:0.35s ease;
        z-index:2000;
    }

    .sidebar.collapsed{
        left:-100%;
    }

    .sidebar.active{
        left:0;
    }

    .main-content{
        margin-left:0 !important;
        width:100%;
        padding:18px;
    }

    .header{
        flex-direction:column;
        align-items:flex-start;
        gap:15px;
        margin-bottom:20px;
    }

    .header h1{
        font-size:24px;
    }

    .menu-toggle{
        display:block;
    }

    .orders-table{
        width:100%;
        overflow-x:auto;
        border-radius:18px;
    }

    table{
        min-width:900px;
    }

    th, td{
        padding:10px;
        font-size:12px;
        white-space:nowrap;
    }

    .action-buttons{
        flex-direction:column;
        width:100%;
    }

    .btn{
        width:100%;
        text-align:center;
    }

    .back-btn{
        width:100%;
        text-align:center;
    }

    .modal-content{
        width:95%;
        margin:20% auto;
        padding:20px;
        border-radius:20px;
    }

    .modal-buttons{
        flex-direction:column;
    }

    .modal-buttons .btn{
        width:100%;
    }
}

/* SMALL PHONES */
@media screen and (max-width: 480px){

    .main-content{
        padding:15px;
    }

    .header h1{
        font-size:20px;
    }

    .menu-toggle{
        padding:10px 14px;
        font-size:14px;
    }

    table{
        min-width:750px;
    }

    th, td{
        padding:8px;
        font-size:11px;
    }

    .modal-content{
        width:94%;
        padding:18px;
    }

    .form-group input{
        padding:12px;
        font-size:14px;
    }

    .back-btn,
    .btn{
        font-size:13px;
        padding:10px 14px;
    }
}

/* EXTRA LARGE MONITORS */
@media screen and (min-width: 1600px){

    .sidebar{
        width:320px;
    }

    .main-content{
        margin-left:320px;
        padding:45px;
    }

    .header h1{
        font-size:36px;
    }

    table{
        font-size:15px;
    }

    th, td{
        padding:20px;
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
                    <h1>Check Payment</h1>
                </div>
                <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
            </div>
            
            <?php if (isset($message)): ?>
                <div class="message"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
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
                                <th>Payment Method</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)): 
                                $oid = $row['id'];
                                $item_query = "SELECT oi.*, ii.name as product_name, ii.category, ii.unit FROM order_items oi JOIN inventory_items ii ON oi.product_id = ii.id WHERE oi.order_id = $oid";
                                $items_res = mysqli_query($connection, $item_query);
                                $items = [];
                                while($it = mysqli_fetch_assoc($items_res)) {
                                    $items[] = $it;
                                }
                                $orderData = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                                $itemsData = htmlspecialchars(json_encode($items), ENT_QUOTES, 'UTF-8');
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['order_number']) ?></td>
                                    <td><?= htmlspecialchars($row['fullname']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= htmlspecialchars($row['phone']) ?></td>
                                    <td>₱<?= number_format($row['total_amount'], 2) ?></td>
                                    <td><span class="payment-method"><?= htmlspecialchars($row['payment_method']) ?></span></td>
                                    <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button onclick="openVerificationModal(<?= $orderData ?>, <?= $itemsData ?>)" class="btn btn-confirm">Review Payment</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>No Pending Payments</h3>
                        <p>There are no orders waiting for payment verification.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <h2><span class="material-symbols-outlined" style="font-size:30px;">fact_check</span> Payment Verification</h2>
            
            <div class="verify-grid">
                <!-- 1. Order & Customer Details -->
                <div class="verify-section">
                    <h3><span class="material-symbols-outlined">person</span> Order & Customer Details</h3>
                    <div class="info-row"><span class="info-label">Order Number:</span> <span class="info-value" id="m_order_no"></span></div>
                    <div class="info-row"><span class="info-label">Customer Name:</span> <span class="info-value" id="m_customer"></span></div>
                    <div class="info-row"><span class="info-label">Contact Number:</span> <span class="info-value" id="m_phone"></span></div>
                    <div class="info-row"><span class="info-label">Email:</span> <span class="info-value" id="m_email"></span></div>
                    <div class="info-row"><span class="info-label">Delivery Address:</span> <span class="info-value" id="m_address"></span></div>
                    <div class="info-row"><span class="info-label">Order Date:</span> <span class="info-value" id="m_date"></span></div>
                </div>

                <!-- 2. Payment Information -->
                <div class="verify-section">
                    <h3><span class="material-symbols-outlined">payments</span> Payment Information</h3>
                    <div class="info-row"><span class="info-label">Payment Method:</span> <span class="info-value" id="m_method" style="text-transform:uppercase; color:#fbbf24; font-weight:700;"></span></div>
                    <div class="info-row"><span class="info-label">Total Amount Due:</span> <span class="info-value" id="m_due" style="font-size:16px;"></span></div>
                    <div class="info-row"><span class="info-label">Declared Amount Paid:</span> <span class="info-value" id="m_paid" style="color:#4ade80; font-size:16px;"></span></div>
                    <div class="info-row" id="discrepancy_row" style="display:none;"><span class="info-label">Discrepancy:</span> <span class="info-value warning-text" id="m_discrepancy"></span></div>
                    <div class="info-row"><span class="info-label">Reference Number:</span> <span class="info-value" id="m_ref"></span></div>
                </div>

                <!-- 3. Poultry-Specific Order Details -->
                <div class="verify-section" style="grid-column: 1 / -1;">
                    <h3><span class="material-symbols-outlined">inventory_2</span> Poultry-Specific Order Details</h3>
                    <div style="font-size:13px; color:#94a3b8; margin-bottom:5px;">Handling Notes: <span id="m_notes" style="color:white; font-style:italic;"></span></div>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Quantity / Unit</th>
                                <th style="text-align:right;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="m_items_body">
                            <!-- Populated via JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 4. Audit & Verification Details -->
            <form method="POST">
                <input type="hidden" name="order_id" id="modalOrderId">
                
                <div class="verify-section" style="background: rgba(59, 130, 246, 0.05);">
                    <h3><span class="material-symbols-outlined">admin_panel_settings</span> Audit & Verification Details</h3>
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Admin Notes / Remarks (Internal Audit Trail)</label>
                        <input type="text" name="admin_notes" placeholder="Enter notes regarding this payment check (e.g. Reference confirmed, missing P100)">
                    </div>
                </div>

                <div class="modal-buttons" style="margin-top: 25px;">
                    <button type="submit" name="action" value="approve" class="btn btn-confirm" style="flex:1; padding:15px; font-size:15px;">Approve Payment</button>
                    <button type="submit" name="action" value="reject" class="btn btn-reject" style="flex:1; padding:15px; font-size:15px;" onclick="return confirm('Are you sure you want to REJECT this payment?');">Reject / Mark Invalid</button>
                    <button type="button" onclick="closeModal()" class="btn btn-cancel" style="padding:15px;">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openVerificationModal(order, items) {
            // 1. Order & Customer Details
            document.getElementById('modalOrderId').value = order.id;
            document.getElementById('m_order_no').innerText = order.order_number;
            document.getElementById('m_customer').innerText = order.fullname;
            document.getElementById('m_phone').innerText = order.phone;
            document.getElementById('m_email').innerText = order.email;
            document.getElementById('m_address').innerText = order.delivery_address || 'N/A';
            document.getElementById('m_date').innerText = new Date(order.created_at).toLocaleString();

            // 2. Payment Information
            document.getElementById('m_method').innerText = order.payment_method.replace('_', ' ');
            
            const totalDue = parseFloat(order.total_amount);
            const amountPaid = parseFloat(order.payment_amount) || 0;
            
            document.getElementById('m_due').innerText = '₱' + totalDue.toLocaleString(undefined, {minimumFractionDigits: 2});
            document.getElementById('m_paid').innerText = '₱' + amountPaid.toLocaleString(undefined, {minimumFractionDigits: 2});
            document.getElementById('m_ref').innerText = order.payment_reference || 'N/A';

            // Discrepancy Check
            const discrepancyRow = document.getElementById('discrepancy_row');
            if (amountPaid > 0 && amountPaid !== totalDue) {
                discrepancyRow.style.display = 'flex';
                document.getElementById('m_discrepancy').innerText = `Warning: Payment declared is ₱${amountPaid.toFixed(2)} vs Total ₱${totalDue.toFixed(2)}`;
            } else {
                discrepancyRow.style.display = 'none';
            }

            // 3. Poultry-Specific Details
            document.getElementById('m_notes').innerText = order.notes || 'None';
            
            let itemsHtml = '';
            items.forEach(item => {
                itemsHtml += `
                    <tr>
                        <td><strong>${item.product_name}</strong></td>
                        <td>${item.category || 'Uncategorized'}</td>
                        <td>${item.quantity} ${item.unit || 'units'}</td>
                        <td style="text-align:right;">₱${parseFloat(item.total_price).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    </tr>
                `;
            });
            document.getElementById('m_items_body').innerHTML = itemsHtml;

            // Show Modal
            document.getElementById('paymentModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('paymentModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target == document.getElementById('paymentModal')) {
                closeModal();
            }
        }
    </script>
<script src="../assets/sidebar.js"></script>
</body>
</html>
