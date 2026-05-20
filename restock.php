<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include '../connection.php';

$message = '';
$messageType = '';

// Handle restock confirmation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $cost_price = floatval($_POST['cost_price']);
    $supplier = isset($_POST['supplier']) ? mysqli_real_escape_string($connection, $_POST['supplier']) : '';
    $notes = mysqli_real_escape_string($connection, $_POST['notes']);
    
    // Get current product info
    $product_query = "SELECT * FROM inventory_items WHERE id = ?";
    $stmt = mysqli_prepare($connection, $product_query);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    
    if ($product) {
        if (empty($supplier)) {
            $supplier = $product['supplier'];
        }
        
        // Update inventory quantity
        $new_quantity = $product['current_quantity'] + $quantity;
        $update_query = "UPDATE inventory_items SET current_quantity = ?, selling_price = ?, supplier = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($connection, $update_query);
        mysqli_stmt_bind_param($update_stmt, "idsi", $new_quantity, $cost_price, $supplier, $product_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            // Log stock movement
            $reference = "Restock from " . $supplier . ($notes ? " - " . $notes : "");
            $log_query = "INSERT INTO stock_movements (product_id, product_name, movement_type, quantity, reference, user_name) 
                         VALUES (?, ?, 'in', ?, ?, ?)";
            $log_stmt = mysqli_prepare($connection, $log_query);
            mysqli_stmt_bind_param($log_stmt, "issss", $product_id, $product['name'], $quantity, $reference, $_SESSION['name']);
            mysqli_stmt_execute($log_stmt);
            
            $message = "Restock confirmed successfully!";
            $messageType = "success";
        } else {
            $message = "Error confirming restock: " . mysqli_error($connection);
            $messageType = "error";
        }
    }
}

// Get low stock items
$low_stock_query = "SELECT * FROM inventory_items WHERE current_quantity <= min_stock ORDER BY current_quantity ASC";
$low_stock_result = mysqli_query($connection, $low_stock_query);

// Get all products for restock selection
$all_products = mysqli_query($connection, "SELECT * FROM inventory_items ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Supply / Restock - Admin</title>
    <!-- Google Font -->
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<!-- Google Material Symbols -->
<link rel="stylesheet"
href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />

<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Outfit',sans-serif;
}

:root{
    --bg-dark:#070b17;
    --bg-card:rgba(255,255,255,0.06);
    --border:rgba(255,255,255,0.12);
    --blue:#3b82f6;
    --blue-hover:#5a95ff;
    --text:#ffffff;
    --text-secondary:#a9b5d1;
    --danger:#ff5c7a;
    --success:#00d68f;
    --shadow:0 8px 32px rgba(0,0,0,0.4);
    --blur:blur(18px);
}

body{
    background:
    radial-gradient(circle at top left, rgba(59,130,246,.15), transparent 35%),
    radial-gradient(circle at bottom right, rgba(0,153,255,.12), transparent 30%),
    linear-gradient(135deg,#090e1a,#0b1120,#000000);
    background-attachment:fixed;
    min-height:100vh;
    color:var(--text);
    overflow-x:hidden;
}

/* Smooth animation */
*{
    transition:all .3s ease;
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

/* Main */
.main-content{
    margin-left:280px;
    flex:1;
    padding:35px;
}

.main-content.expanded{
    margin-left:0;
}

/* Header */
.header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:30px;
}

.header h1{
    font-size:30px;
    color:white;
    font-weight:700;
}

/* Buttons */
.back-btn,
.submit-btn,
.btn,
.menu-toggle{
    border:none;
    outline:none;
    cursor:pointer;
    border-radius:18px;
    transition:.3s ease;
}

.back-btn{
    background:linear-gradient(135deg,#3b82f6,#246BEB);
    color:white;
    padding:12px 22px;
    text-decoration:none;
    font-weight:600;
    box-shadow:0 0 20px rgba(59,130,246,.3);
}

.back-btn:hover{
    transform:translateY(-3px);
    box-shadow:0 0 30px rgba(59,130,246,.45);
}

.menu-toggle{
    background:rgba(255,255,255,.08);
    color:white;
    width:50px;
    height:50px;
    margin-right:12px;
    backdrop-filter:blur(10px);
}

.menu-toggle:hover{
    background:rgba(59,130,246,.25);
    transform:scale(1.08);
}

/* Cards */
.alert-box,
.low-stock-table,
.restock-form{
    background:rgba(255,255,255,0.06);
    border:1px solid rgba(255,255,255,0.08);
    backdrop-filter:blur(18px);
    -webkit-backdrop-filter:blur(18px);
    border-radius:28px;
    padding:30px;
    box-shadow:0 8px 32px rgba(0,0,0,.35);
    animation:fadeUp .5s ease;
}

.alert-box{
    margin-bottom:25px;
    border-left:4px solid #fbbc05;
}

.alert-box h3{
    color:#fbbc05;
    margin-bottom:10px;
}

.low-stock-table{
    margin-bottom:30px;
}

.low-stock-table h2,
.restock-form h2{
    color:white;
    margin-bottom:22px;
}

/* Table */
table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:rgba(255,255,255,.05);
    color:#8fb5ff;
    font-size:12px;
    text-transform:uppercase;
    padding:15px;
}

td{
    padding:16px;
    border-bottom:1px solid rgba(255,255,255,.05);
    color:#d7e3ff;
}

tr:hover{
    background:rgba(59,130,246,.08);
}

/* Restock button */
.btn-restock{
    background:linear-gradient(135deg,#3b82f6,#246BEB);
    color:white;
    padding:10px 18px;
    border-radius:14px;
    font-weight:600;
}

.btn-restock:hover{
    transform:translateY(-3px);
    box-shadow:0 0 25px rgba(59,130,246,.35);
}

/* Messages */
.message{
    padding:18px;
    border-radius:18px;
    margin-bottom:25px;
    animation:fadeUp .5s ease;
}

.message.success{
    background:rgba(0,214,143,.12);
    border:1px solid rgba(0,214,143,.2);
    color:#8effca;
}

.message.error{
    background:rgba(255,92,122,.12);
    border:1px solid rgba(255,92,122,.2);
    color:#ff9db0;
}

/* Form */
.form-group{
    margin-bottom:22px;
}

.form-group label{
    display:block;
    margin-bottom:10px;
    font-size:14px;
    font-weight:500;
    color:#c8d4f0;
}

.form-group input,
.form-group select,
.form-group textarea{
    width:100%;
    padding:16px;
    background:rgba(255,255,255,.05);
    border:1px solid rgba(255,255,255,.08);
    border-radius:18px;
    color:white;
    font-size:14px;
    backdrop-filter:blur(10px);
}

.form-group select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: url("data:image/svg+xml;utf8,<svg fill='white' height='24' viewBox='0 0 24 24' width='24' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/><path d='M0 0h24v24H0z' fill='none'/></svg>");
    background-repeat: no-repeat;
    background-position: right 16px center;
    padding-right: 45px;
    cursor: pointer;
}

.form-group select option {
    background: #0f172a;
    color: white;
}

.form-group input::placeholder,
.form-group textarea::placeholder{
    color:#8c98b5;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus{
    border-color:#3b82f6;
    box-shadow:
    0 0 0 4px rgba(59,130,246,.12),
    0 0 30px rgba(59,130,246,.18);
    transform:translateY(-2px);
}

.submit-btn{
    width:100%;
    background:linear-gradient(135deg,#3b82f6,#246BEB);
    color:white;
    padding:16px;
    font-size:16px;
    font-weight:700;
    border-radius:18px;
    box-shadow:0 0 25px rgba(59,130,246,.25);
}

.submit-btn:hover{
    transform:translateY(-4px);
    box-shadow:0 0 35px rgba(59,130,246,.45);
}

/* Stock status */
.stock-status{
    padding:7px 14px;
    border-radius:20px;
    font-size:12px;
    font-weight:600;
}

.stock-low{
    background:rgba(251,188,5,.12);
    color:#fbbc05;
}

.stock-out{
    background:rgba(255,92,122,.12);
    color:#ff7b94;
}

/* Animations */
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

/* =========================
   RESPONSIVE DESIGN
========================= */

/* Large Laptop / Small Desktop */
@media (max-width: 1200px) {
    .main-content {
        padding: 25px;
    }

    .header h1 {
        font-size: 26px;
    }

    .sidebar {
        width: 250px;
    }

    .main-content {
        margin-left: 250px;
    }
}

/* Tablet */
@media (max-width: 992px) {

    .sidebar {
        width: 240px;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }

    .sidebar.active {
        transform: translateX(0);
    }

    .main-content {
        margin-left: 0;
        width: 100%;
        padding: 20px;
    }

    .header {
        flex-wrap: wrap;
        gap: 15px;
    }

    .header h1 {
        font-size: 24px;
    }

    .alert-box,
    .low-stock-table,
    .restock-form {
        padding: 22px;
        border-radius: 22px;
    }

    table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
    }

    th,
    td {
        padding: 14px;
        font-size: 14px;
    }
}

/* Mobile */
@media (max-width: 1024px) {

    body {
        overflow-x: hidden;
    }

    .container {
        flex-direction: column;
    }

    .sidebar {
        width: 260px;
        position: fixed;
        left: 0;
        top: 0;
        height: 100vh;
        transform: translateX(-100%);
        z-index: 9999;
    }

    .sidebar.active {
        transform: translateX(0);
    }

    .main-content {
        margin-left: 0;
        width: 100%;
        padding: 16px;
    }

    .header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    .header > div {
        width: 100%;
        justify-content: flex-start;
    }

    .header h1 {
        font-size: 22px;
        line-height: 1.3;
    }

    .menu-toggle {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .back-btn {
        width: 100%;
        text-align: center;
    }

    .alert-box,
    .low-stock-table,
    .restock-form {
        padding: 18px;
        border-radius: 20px;
    }

    /* Responsive table */
    table {
        display: block;
        width: 100%;
        overflow-x: auto;
        white-space: nowrap;
        border-radius: 12px;
    }

    th,
    td {
        padding: 12px;
        font-size: 13px;
    }

    /* Forms */
    .form-group input,
    .form-group select,
    .form-group textarea {
        padding: 14px;
        font-size: 14px;
    }

    .submit-btn {
        font-size: 15px;
        padding: 14px;
    }

    .btn-restock {
        padding: 8px 14px;
        font-size: 13px;
    }
}

/* Small Phones */
@media (max-width: 480px) {

    .main-content {
        padding: 12px;
    }

    .header h1 {
        font-size: 18px;
    }

    .sidebar {
        width: 230px;
    }

    .alert-box h3,
    .low-stock-table h2,
    .restock-form h2 {
        font-size: 18px;
    }

    th,
    td {
        font-size: 12px;
        padding: 10px;
    }

    .submit-btn,
    .back-btn {
        padding: 12px;
        font-size: 14px;
    }

    .form-group label {
        font-size: 13px;
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
                    <h1> Confirm Supply / Restock</h1>
                </div>
                <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
            </div>
            
            <?php if ($message): ?>
                <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <?php if (mysqli_num_rows($low_stock_result) > 0): ?>
                <div class="alert-box">
                    <h3>Low Stock Alert</h3>
                    <p>The following items need restocking:</p>
                </div>
                
                <div class="low-stock-table">
                    <h2>Items Needing Restock</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Current Stock</th>
                                <th>Min Stock</th>
                                <th>Unit</th>
                                <th>Supplier</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($low_stock_result)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                    <td style="color: #dc3545; font-weight: bold;"><?= number_format($row['current_quantity']) ?></td>
                                    <td><?= number_format($row['min_stock']) ?></td>
                                    <td><?= htmlspecialchars($row['unit']) ?></td>
                                    <td><?= htmlspecialchars($row['supplier'] ?: 'N/A') ?></td>
                                    <td>
                                        <button onclick="openRestockModal(<?= $row['id'] ?>, '<?= htmlspecialchars($row['name']) ?>', <?= $row['current_quantity'] ?>, '<?= htmlspecialchars($row['supplier'] ?: '') ?>', <?= floatval($row['selling_price'] ?? 0) ?>)" class="btn btn-restock">Restock</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <div class="restock-form">
                <h2>Restock Any Product</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Select Product:</label>
                        <select name="product_id" id="restockProductSelect" required>
                            <option value="">-- Select a Product --</option>
                            <?php mysqli_data_seek($all_products, 0); ?>
                            <?php while ($row = mysqli_fetch_assoc($all_products)): ?>
                                <option value="<?= $row['id'] ?>">
                                    <?= htmlspecialchars($row['name']) ?> (Current: <?= number_format($row['current_quantity']) ?> <?= htmlspecialchars($row['unit']) ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Supplier (Optional):</label>
                        <input type="text" name="supplier" id="restockSupplier" placeholder="Supplier name">
                    </div>

                    <div class="form-group">
                        <label>Quantity to Add:</label>
                        <input type="number" name="quantity" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Price per Unit:</label>
                        <input type="number" name="cost_price" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Notes (Optional):</label>
                        <textarea name="notes" placeholder="Any additional notes about this restock"></textarea>
                    </div>
                    
                    <button type="submit" name="action" value="restock" class="submit-btn">Confirm Restock</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function openRestockModal(productId, productName, currentStock, supplier, costPrice) {
            const select = document.getElementById('restockProductSelect');
            select.value = productId;
            
            const supplierInput = document.getElementById('restockSupplier');
            if (supplierInput) {
                supplierInput.value = supplier || '';
            }

            const costInput = document.querySelector('input[name="cost_price"]');
            costInput.value = costPrice || '';
            
            document.querySelector('.restock-form').scrollIntoView({ behavior: 'smooth' });
        }
    </script>
    
<script src="../assets/sidebar.js"></script>
</body>
</html>
