<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include '../connection.php';
include 'helpers.php';

$message = "";

// Handle order actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {

    $order_id = intval($_POST['order_id']);
    $action = $_POST['action'];

    // SHIP ORDER
    if ($action == 'ship') {

        // Prevent undefined array key error
        $tracking_number = isset($_POST['tracking_number'])
            ? trim($_POST['tracking_number'])
            : '';

        if (!empty($tracking_number)) {

            $old_status = getOrderStatus($connection, $order_id);
            $query = "UPDATE orders 
                      SET status = 'Shipped',
                          tracking_number = ?,
                          shipping_date = NOW(),
                          created_by = ?
                      WHERE id = ?";

            $stmt = mysqli_prepare($connection, $query);

            mysqli_stmt_bind_param(
                $stmt,
                "sii",
                $tracking_number,
                $_SESSION['admin_id'],
                $order_id
            );

            if (mysqli_stmt_execute($stmt)) {
                logOrderStatusChange($connection, $order_id, $old_status, 'Shipped', $_SESSION['admin_id'], "Tracking: $tracking_number");
                $message = "Order shipped successfully!";
            } else {
                $message = "Failed to ship order.";
            }
        } else {
            $message = "Tracking number is required.";
        }
    }

    // MARK DELIVERED
    elseif ($action == 'deliver') {

        $query = "UPDATE orders 
                  SET status = 'Delivered',
                      actual_delivery = NOW(),
                      created_by = ?
                  WHERE id = ?";

        $stmt = mysqli_prepare($connection, $query);

        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $_SESSION['admin_id'],
            $order_id
        );

        if (mysqli_stmt_execute($stmt)) {
            $message = "Order marked as delivered!";
        } else {
            $message = "Failed to update order.";
        }
    }
}

// Get processed & shipped orders
$query = "SELECT 
            o.*,
            c.fullname,
            c.email,
            c.phone,
            c.address
          FROM orders o
          JOIN customers c
            ON o.customer_id = c.id
          WHERE o.status IN ('Processed', 'Shipped')
          ORDER BY o.created_at DESC";

$result = mysqli_query($connection, $query);

if (!$result) {
    die("Query Failed: " . mysqli_error($connection));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ship Out Orders - Admin</title>
   <style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined');

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Outfit',sans-serif;
}

body{
    background:
        radial-gradient(circle at top left,#1f4fff33,transparent 35%),
        radial-gradient(circle at bottom right,#009dff22,transparent 30%),
        linear-gradient(135deg,#090e1a,#0b1020,#000814);
    background-attachment:fixed;
    color:#fff;
    min-height:100vh;
    overflow-x:hidden;
}

/* Scrollbar */
::-webkit-scrollbar{
    width:8px;
}
::-webkit-scrollbar-track{
    background:#0b1120;
}
::-webkit-scrollbar-thumb{
    background:#3b82f6;
    border-radius:10px;
}

/* Layout */
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

/* MAIN CONTENT */
.main-content{
    margin-left:280px;
    flex:1;
    padding:35px;
    transition:all .35s ease;
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
    font-size:30px;
    font-weight:700;
    color:#fff;
    text-shadow:0 0 25px rgba(59,130,246,0.35);
}

.back-btn{
    background:rgba(59,130,246,0.15);
    border:1px solid rgba(59,130,246,0.3);
    color:#fff;
    padding:12px 20px;
    border-radius:15px;
    text-decoration:none;
    transition:.3s ease;
    backdrop-filter:blur(12px);
}

.back-btn:hover{
    transform:translateY(-3px);
    background:#2563eb;
    box-shadow:0 10px 30px rgba(37,99,235,.4);
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
/* SUCCESS MESSAGE */
.message{
    background:rgba(34,197,94,.12);
    border:1px solid rgba(34,197,94,.3);
    color:#86efac;
    padding:18px;
    border-radius:18px;
    margin-bottom:25px;
    backdrop-filter:blur(18px);
    animation:fadeIn .5s ease;
}

/* TABLE CARD */
.orders-table{
    background:rgba(255,255,255,0.06);
    border:1px solid rgba(255,255,255,0.08);
    border-radius:28px;
    overflow:hidden;
    backdrop-filter:blur(18px);
    box-shadow:
        0 8px 32px rgba(0,0,0,.4),
        0 0 40px rgba(59,130,246,.08);
    animation:fadeUp .5s ease;
}

/* TABLE */
table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:rgba(255,255,255,.05);
    color:#8cb4ff;
    font-size:12px;
    text-transform:uppercase;
    letter-spacing:1px;
    padding:20px;
}

td{
    padding:20px;
    border-bottom:1px solid rgba(255,255,255,.05);
    color:#e2e8f0;
}

tr{
    transition:.3s ease;
}

tr:hover{
    background:rgba(59,130,246,.08);
}

/* STATUS */
.status-badge{
    padding:8px 14px;
    border-radius:999px;
    font-size:12px;
    font-weight:600;
}

.status-processed{
    background:rgba(255,193,7,.15);
    color:#ffd54f;
    border:1px solid rgba(255,193,7,.25);
}

.status-shipped{
    background:rgba(59,130,246,.18);
    color:#60a5fa;
    border:1px solid rgba(59,130,246,.3);
}

/* BUTTONS */
.action-buttons{
    display:flex;
    gap:10px;
}

.btn{
    border:none;
    cursor:pointer;
    padding:11px 18px;
    border-radius:14px;
    color:white;
    font-size:13px;
    transition:.3s ease;
    text-decoration:none;
    backdrop-filter:blur(15px);
}

.btn:hover{
    transform:translateY(-3px);
}

.btn-ship{
    background:linear-gradient(135deg,#2563eb,#3b82f6);
    box-shadow:0 6px 20px rgba(59,130,246,.3);
}

.btn-ship:hover{
    box-shadow:0 12px 30px rgba(59,130,246,.45);
}

.btn-deliver{
    background:linear-gradient(135deg,#0284c7,#0ea5e9);
    box-shadow:0 6px 20px rgba(14,165,233,.3);
}

.btn-deliver:hover{
    box-shadow:0 12px 30px rgba(14,165,233,.45);
}

.btn-cancel{
    background:rgba(255,255,255,.08);
    border:1px solid rgba(255,255,255,.1);
}

/* EMPTY STATE */
.empty-state{
    text-align:center;
    padding:70px;
    color:#94a3b8;
}

.empty-state h3{
    color:#fff;
    margin-bottom:10px;
}

/* MODAL */
.modal{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.7);
    backdrop-filter:blur(10px);
    z-index:9999;
}

.modal-content{
    width:500px;
    max-width:90%;
    margin:8% auto;
    background:rgba(15,23,42,.7);
    border:1px solid rgba(255,255,255,.08);
    border-radius:28px;
    padding:35px;
    backdrop-filter:blur(25px);
    box-shadow:
        0 20px 60px rgba(0,0,0,.5),
        0 0 40px rgba(59,130,246,.15);
    animation:zoomIn .35s ease;
}

.modal-content h2{
    margin-bottom:20px;
    color:white;
}

/* FORM */
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
    padding:15px 18px;
    border-radius:16px;
    border:1px solid rgba(255,255,255,.08);
    background:rgba(255,255,255,.06);
    color:white;
    outline:none;
    transition:.3s ease;
    backdrop-filter:blur(12px);
}

.form-group input:focus{
    border-color:#3b82f6;
    box-shadow:0 0 20px rgba(59,130,246,.3);
    transform:translateY(-2px);
}

.form-group input::placeholder{
    color:#94a3b8;
}

.modal-buttons{
    display:flex;
    gap:12px;
    margin-top:25px;
}

/* ANIMATIONS */
@keyframes fadeIn{
    from{
        opacity:0;
        transform:translateY(-10px);
    }
    to{
        opacity:1;
        transform:translateY(0);
    }
}

@keyframes fadeUp{
    from{
        opacity:0;
        transform:translateY(25px);
    }
    to{
        opacity:1;
        transform:translateY(0);
    }
}

@keyframes zoomIn{
    from{
        opacity:0;
        transform:scale(.9);
    }
    to{
        opacity:1;
        transform:scale(1);
    }
}

/* =========================
   RESPONSIVE DESIGN
========================= */

/* LAPTOPS & SMALL DESKTOP */
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

    table{
        font-size:14px;
    }
}


/* TABLETS */
@media (max-width: 992px){

    .sidebar{
        width:240px;
    }

    .main-content{
        margin-left:240px;
        padding:20px;
    }

    .header{
        flex-wrap:wrap;
        gap:15px;
    }

    .header h1{
        font-size:24px;
    }

    .orders-table{
        overflow-x:auto;
        border-radius:22px;
    }

    table{
        min-width:850px;
    }

    th,
    td{
        padding:14px;
        font-size:13px;
    }

    .btn{
        padding:10px 14px;
        font-size:12px;
    }
}


/* MOBILE */
@media (max-width: 1024px){

    body{
        overflow-x:hidden;
    }

    .container{
        flex-direction:column;
    }

    /* SIDEBAR */
    .sidebar{
        position:fixed;
        top:0;
        left:-100%;
        width:260px;
        height:100vh;
        transition:.35s ease;
        z-index:9999;
    }

    .sidebar.active{
        left:0;
    }

    /* MAIN */
    .main-content{
        margin-left:0;
        width:100%;
        padding:18px;
    }

    .header{
        display:flex;
        flex-direction:column;
        align-items:flex-start;
        gap:12px;
        margin-bottom:20px;
    }

    .header h1{
        font-size:22px;
    }

    .menu-toggle{
        display:block;
    }

    .back-btn{
        width:100%;
        text-align:center;
    }

    /* TABLE */
    .orders-table{
        overflow-x:auto;
        border-radius:20px;
    }

    table{
        min-width:700px;
    }

    th,
    td{
        padding:12px;
        font-size:12px;
    }

    .action-buttons{
        flex-direction:column;
        width:100%;
    }

    .btn{
        width:100%;
        text-align:center;
    }

    /* MODAL */
    .modal-content{
        width:95%;
        padding:22px;
        margin:20% auto;
        border-radius:22px;
    }

    .modal-buttons{
        flex-direction:column;
    }

    .modal-buttons .btn{
        width:100%;
    }
}


/* SMALL PHONES */
@media (max-width: 480px){

    .main-content{
        padding:14px;
    }

    .header h1{
        font-size:20px;
    }

    .sidebar{
        width:230px;
    }

    th,
    td{
        padding:10px;
        font-size:11px;
    }

    .status-badge{
        font-size:10px;
        padding:6px 10px;
    }

    .modal-content{
        padding:18px;
        margin:30% auto;
    }

    .form-group input{
        padding:12px 14px;
    }
}


/* LARGE MONITORS */
@media (min-width: 1400px){

    .sidebar{
        width:300px;
    }

    .main-content{
        margin-left:300px;
        padding:40px;
    }

    table{
        font-size:15px;
    }

    th,
    td{
        padding:22px;
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
                    <h1>Ship Out Orders</h1>
                </div>
                <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
            </div>
            
           <?php if (!empty($message)): ?>
                <div class="message"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <div class="orders-table">
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Address</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Tracking #</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['order_number']) ?></td>
                                    <td><?= htmlspecialchars($row['fullname']) ?></td>
                                    <td><?= htmlspecialchars(substr($row['address'], 0, 30)) ?>...</td>
                                    <td>₱<?= number_format($row['total_amount'], 2) ?></td>
                                    <td>
                                        <span class="status-badge <?= $row['status'] == 'Processed' ? 'status-processed' : 'status-shipped' ?>">
                                            <?= htmlspecialchars($row['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= !empty($row['tracking_number']) ? htmlspecialchars($row['tracking_number']) : '-' ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($row['status'] == 'Processed'): ?>
                                                <button onclick="openShipModal(<?= $row['id'] ?>)" class="btn btn-ship">Ship</button>
                                            <?php elseif ($row['status'] == 'Shipped'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                                    <input type="hidden" name="action" value="deliver">
                                                    <button type="submit" class="btn btn-deliver">Mark Delivered</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>No Orders to Ship</h3>
                        <p>There are no processed orders waiting to be shipped.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div id="shipModal" class="modal">
        <div class="modal-content">
            <h2>Ship Order</h2>
            <form method="POST">
                <input type="hidden" name="order_id" id="modalOrderId">
                <input type="hidden" name="action" value="ship">
                
                <div class="form-group">
                    <label>Tracking Number:</label>
                    <input type="text" name="tracking_number" placeholder="Enter tracking number" required>
                </div>
                
                <div class="modal-buttons">
                    <button type="submit" class="btn btn-ship">Ship Order</button>
                    <button type="button" onclick="closeShipModal()" class="btn btn-cancel">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openShipModal(orderId) {
            document.getElementById('modalOrderId').value = orderId;
            document.getElementById('shipModal').style.display = 'block';
        }

        function closeShipModal() {
            document.getElementById('shipModal').style.display = 'none';
        }

        /* Close sidebar when clicking outside */
        </script>
<script src="../assets/sidebar.js"></script>
</body>
</html>
