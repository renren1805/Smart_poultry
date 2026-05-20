<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include '../connection.php';
include 'helpers.php';

// Handle order packing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $order_id = $_POST['order_id'];
    
    if ($_POST['action'] == 'pack') {
        $old_status = getOrderStatus($connection, $order_id);
        $query = "UPDATE orders SET status = 'Processed', created_by = ? WHERE id = ?";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "ii", $_SESSION['admin_id'], $order_id);
        mysqli_stmt_execute($stmt);
        logOrderStatusChange($connection, $order_id, $old_status, 'Processed', $_SESSION['admin_id']);
        $message = "Order packed successfully!";
    }
}

// Get approved orders ready for packing
$query = "SELECT o.*, c.fullname, c.email, c.phone 
          FROM orders o 
          JOIN customers c ON o.customer_id = c.id 
          WHERE o.status = 'Approved'
          ORDER BY o.created_at DESC";
$result = mysqli_query($connection, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pack Order - Admin</title>
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
    --bg-dark:#090e1a;
    --bg-card:rgba(255,255,255,0.08);
    --glass-border:rgba(255,255,255,0.12);
    --primary:#3b82f6;
    --primary-hover:#2563eb;
    --text:#ffffff;
    --text-secondary:#bfc9d9;
    --shadow:0 8px 32px rgba(0,0,0,0.4);
}

body{
    background:
    radial-gradient(circle at top left,#0a1b48 0%,transparent 40%),
    radial-gradient(circle at bottom right,#001433 0%,transparent 30%),
    #090e1a;
    background-attachment:fixed;
    min-height:100vh;
    color:var(--text);
    overflow-x:hidden;
}

/* Smooth Animation */
*{
    transition:all .3s ease;
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
    margin-bottom:30px;
}

.header h1{
    font-size:32px;
    font-weight:700;
    color:white;
    text-shadow:0 0 20px rgba(59,130,246,.2);
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
   MESSAGE
====================== */
.message{
    background:rgba(25,135,84,.12);
    border:1px solid rgba(25,135,84,.25);
    backdrop-filter:blur(12px);
    color:#90ee90;
    padding:18px;
    border-radius:20px;
    margin-bottom:20px;
    box-shadow:0 0 20px rgba(25,135,84,.15);
    animation:fadeIn .5s ease;
}

/* ======================
   TABLE CONTAINER
====================== */
.orders-table{
    background:rgba(255,255,255,.06);
    border:1px solid rgba(255,255,255,.08);
    backdrop-filter:blur(18px);
    border-radius:28px;
    overflow:hidden;
    box-shadow:0 0 35px rgba(59,130,246,.08);
    animation:slideUp .6s ease;
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:rgba(255,255,255,.04);
    color:#dce6ff;
    text-transform:uppercase;
    font-size:12px;
    letter-spacing:.5px;
}

th,td{
    padding:18px;
    text-align:left;
    border-bottom:1px solid rgba(255,255,255,.05);
}

td{
    color:#d5def0;
}

tr:hover{
    background:rgba(59,130,246,.08);
}

/* ======================
   STATUS BADGE
====================== */
.status-badge{
    padding:8px 14px;
    border-radius:999px;
    font-size:12px;
    font-weight:600;
    background:rgba(40,167,69,.15);
    border:1px solid rgba(40,167,69,.2);
    color:#5dff8d;
    box-shadow:0 0 12px rgba(40,167,69,.2);
}

/* ======================
   ACTION BUTTONS
====================== */
.action-buttons{
    display:flex;
    gap:10px;
}

.btn{
    padding:10px 18px;
    font-size:13px;
    border-radius:14px;
}

.btn-pack{
    background:rgba(59,130,246,.18);
    color:white;
    border:1px solid rgba(59,130,246,.2);
}

.btn-pack:hover{
    background:var(--primary);
    transform:translateY(-2px);
    box-shadow:0 0 20px rgba(59,130,246,.35);
}

.btn-view{
    background:rgba(255,255,255,.08);
}

.btn-view:hover{
    background:rgba(255,255,255,.18);
}

/* ======================
   EMPTY STATE
====================== */
.empty-state{
    text-align:center;
    padding:80px 20px;
    color:#aab7cf;
}

.empty-state h3{
    color:white;
    font-size:24px;
    margin-bottom:10px;
}

/* ======================
   SCROLLBAR
====================== */
::-webkit-scrollbar{
    width:8px;
}

::-webkit-scrollbar-thumb{
    background:rgba(255,255,255,.15);
    border-radius:10px;
}

/* ======================
   ANIMATIONS
====================== */
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

@keyframes slideUp{
    from{
        opacity:0;
        transform:translateY(30px);
    }
    to{
        opacity:1;
        transform:translateY(0);
    }
}

/* ======================
   RESPONSIVE DESIGN
====================== */

/* Hide menu button on desktop */
.menu-toggle{
    display:none;
}

/* ======================
   LARGE LAPTOP / DESKTOP
====================== */
@media screen and (max-width:1200px){

    .sidebar{
        width:250px;
    }

    .main-content{
        margin-left:250px;
        padding:25px;
    }

    .header h1{
        font-size:28px;
    }

    th, td{
        padding:14px;
    }
}

/* ======================
   TABLET
====================== */
@media screen and (max-width:992px){

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

    .back-btn{
        padding:10px 16px;
        font-size:14px;
    }

    th, td{
        padding:12px;
        font-size:13px;
    }

    .btn{
        padding:10px 14px;
        font-size:12px;
    }
}

/* ======================
   MOBILE / SMALL TABLETS
====================== */
@media (max-width: 1024px){

    /* show menu button */
    .menu-toggle{
        display:block;
    }

    /* sidebar hidden */
    .sidebar{
        transform:translateX(-100%);
        width:270px;
        transition:0.3s ease;
    }

    .sidebar.active{
        transform:translateX(0);
    }

    .main-content{
        margin-left:0;
        width:100%;
        padding:18px;
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

    /* make table scrollable */
    .orders-table{
        overflow-x:auto;
        border-radius:20px;
    }

    table{
        min-width:850px;
        width:max-content;
    }

    th, td{
        white-space:nowrap;
        font-size:12px;
        padding:10px;
    }

    .action-buttons{
        flex-direction:column;
        width:100%;
    }

    .btn{
        width:100%;
        text-align:center;
    }

    .empty-state{
        padding:50px 20px;
    }

    .empty-state h3{
        font-size:20px;
    }
}

/* ======================
   SMALL PHONES
====================== */
@media screen and (max-width:480px){

    body{
        overflow-x:hidden;
    }

    .main-content{
        padding:15px;
    }

    .header{
        gap:12px;
    }

    .header h1{
        font-size:20px;
    }

    .menu-toggle{
        padding:10px 14px;
        font-size:14px;
    }

    .back-btn{
        font-size:13px;
        padding:10px;
    }

    .orders-table{
        border-radius:18px;
    }

    table{
        min-width:750px;
    }

    th, td{
        padding:8px;
        font-size:11px;
    }

    .btn{
        padding:10px;
        font-size:11px;
    }

    .status-badge{
        font-size:10px;
        padding:6px 10px;
    }
}

/* ======================
   LARGE MONITORS / PC
====================== */
@media screen and (min-width:1400px){

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
                    <h1>Pack Order</h1>
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
                                <th>Order</th>
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
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['order_number']) ?></td>
                                    <td><?= htmlspecialchars($row['fullname']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= htmlspecialchars($row['phone']) ?></td>
                                    <td>₱<?= number_format($row['total_amount'], 2) ?></td>
                                    <td><span class="status-badge"><?= htmlspecialchars($row['status']) ?></span></td>
                                    <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="action" value="pack">
                                                <button type="submit" class="btn btn-pack">Mark as Packed</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>No Orders to Pack</h3>
                        <p>There are no approved orders waiting to be packed.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
// Reset sidebar on resize
window.addEventListener('resize', function(){
    const sidebar = document.querySelector('.sidebar');

    if(window.innerWidth > 768){
        sidebar.classList.remove('active');
    }
});
</script>
<script src="../assets/sidebar.js"></script>
</body>
</html>
