<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include '../connection.php';

$message = '';
$messageType = '';

// Handle inventory adjustments
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $movement_type = $_POST['movement_type'];
    $reference = mysqli_real_escape_string($connection, $_POST['reference']);
    
    // Handle image upload
    $image_path = '';
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $target_dir = '../uploads/inventory/';
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $image_name = time() . '_' . basename($_FILES['product_image']['name']);
        $target_file = $target_dir . $image_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($imageFileType, $allowed_types)) {
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_file)) {
                $image_path = 'uploads/inventory/' . $image_name;
                // Update product image
               $image_update = "UPDATE inventory_items SET image_path = ? WHERE id = ?";
                $img_stmt = mysqli_prepare($connection, $image_update);
                mysqli_stmt_bind_param($img_stmt, "si", $image_path, $product_id);
                mysqli_stmt_execute($img_stmt);
            }
        }
    }
    
    // Get current product info
    $product_query = "SELECT * FROM inventory_items WHERE id = ?";
    $stmt = mysqli_prepare($connection, $product_query);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    
    if ($product) {
        if ($movement_type == 'in') {
            $new_quantity = $product['current_quantity'] + $quantity;
        } elseif ($movement_type == 'out') {
            $new_quantity = $product['current_quantity'] - $quantity;
            if ($new_quantity < 0) {
                $new_quantity = 0;
            }
        } else {
            $new_quantity = $quantity;
        }
        
        // Update inventory
        $update_query = "UPDATE inventory_items SET current_quantity = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($connection, $update_query);
        mysqli_stmt_bind_param($update_stmt, "ii", $new_quantity, $product_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            // Log stock movement
            $log_query = "INSERT INTO stock_movements (product_id, product_name, movement_type, quantity, reference, user_name) 
                         VALUES (?, ?, ?, ?, ?, ?)";
            $log_stmt = mysqli_prepare($connection, $log_query);
            mysqli_stmt_bind_param(
            $log_stmt,
            "ississ",
            $product_id,
            $product['name'],
            $movement_type,
            $quantity,
            $reference,
            $_SESSION['name']
        );
            mysqli_stmt_execute($log_stmt);
            
            $message = "Inventory updated successfully!";
            $messageType = "success";
        } else {
            $message = "Error updating inventory: " . mysqli_error($connection);
            $messageType = "error";
        }
    }
}

// Get all distinct categories for the tabs
$cat_query = "SELECT DISTINCT category FROM inventory_items WHERE category IS NOT NULL AND category != '' ORDER BY category ASC";
$cat_result = mysqli_query($connection, $cat_query);
$categories = [];
while ($cat_row = mysqli_fetch_assoc($cat_result)) {
    $categories[] = $cat_row['category'];
}

// Get filter category
$selected_category = isset($_GET['category']) ? $_GET['category'] : '';

// Base query condition
$where_clause = "";
if ($selected_category !== "") {
    $escaped_cat = mysqli_real_escape_string($connection, $selected_category);
    $where_clause = " WHERE category = '$escaped_cat'";
}

$query = "SELECT * FROM inventory_items" . $where_clause . " ORDER BY name ASC";
$result = mysqli_query($connection, $query);

// AJAX Request Handler
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    ob_start();
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $stock_status = 'in';
            if ($row['current_quantity'] == 0) {
                $stock_status = 'out';
            } elseif ($row['current_quantity'] <= $row['min_stock']) {
                $stock_status = 'low';
            }
            ?>
            <tr>
                <td>
                    <?php if (!empty($row['image_path'])): ?>
                        <img src="../<?= htmlspecialchars($row['image_path']) ?>"
                            alt="<?= htmlspecialchars($row['name']) ?>"
                            class="product-img">
                    <?php else: ?>
                        <span style="color: #6b7280; font-size: 12px;">No Image</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['category']) ?></td>
                <td><?= number_format($row['current_quantity']) ?></td>
                <td><?= htmlspecialchars($row['unit']) ?></td>
                <td>
                    <?php if ($stock_status == 'out'): ?>
                        <span class="stock-status stock-out">Out of Stock</span>
                    <?php elseif ($stock_status == 'low'): ?>
                        <span class="stock-status stock-low">Low Stock</span>
                    <?php else: ?>
                        <span class="stock-status stock-in">In Stock</span>
                    <?php endif; ?>
                </td>
                <td>
                    <button onclick="openAdjustModal(<?= $row['id'] ?>, '<?= htmlspecialchars($row['name']) ?>', <?= $row['current_quantity'] ?>)" class="btn btn-adjust">Adjust</button>
                </td>
            </tr>
            <?php
        }
    } else {
        echo "<tr><td colspan='7' style='text-align:center;'>No products found in this category.</td></tr>";
    }
    $table_html = ob_get_clean();

    header('Content-Type: application/json');
    echo json_encode([
        'table' => $table_html
    ]);
    exit();
}

// Get recent stock movements
$movement_query = "SELECT sm.* FROM stock_movements sm ORDER BY sm.created_at DESC LIMIT 10";
$movements = mysqli_query($connection, $movement_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Inventory - Admin</title>
    <!-- Google Font -->
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<!-- Material Symbols -->
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
    --primary:#1a73e8;
    --secondary:#4285f4;
    --dark:#0f172a;
    --dark2:#0f172a;
    --card:rgba(255,255,255,0.07);
    --border:rgba(255,255,255,0.12);
    --text:#e5e7eb;
    --muted:#9ca3af;
    --success:#22c55e;
    --warning:#f59e0b;
    --danger:#ef4444;
    --shadow:0 8px 30px rgba(0,0,0,0.35);
    --glow:0 0 25px rgba(26,115,232,.25);
}

body{
    background:
    radial-gradient(circle at top left,#1a73e82b,transparent 30%),
    radial-gradient(circle at bottom right,#4285f42b,transparent 25%),
    linear-gradient(135deg,#090e1a,#0b1220,#111827);
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

/* Main Content */
.main-content{
    margin-left:280px;
    flex:1;
    padding:35px;
    transition:.35s ease;
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
    font-weight:700;
    color:#fff;
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
/* Message */
.message{
    padding:16px;
    border-radius:16px;
    margin-bottom:20px;
    backdrop-filter:blur(15px);
    border:1px solid rgba(255,255,255,.08);
    animation:fadeIn .4s ease;
}

.message.success{
    background:rgba(34,197,94,.12);
    color:#86efac;
}

.message.error{
    background:rgba(239,68,68,.12);
    color:#fca5a5;
}

/* Grid */
.content-grid{
    display:grid;
    grid-template-columns:2fr 1fr;
    gap:25px;
}

/* Glass Cards */
.inventory-table,
.movements-panel,
.modal-content{
    background:rgba(255,255,255,.06);
    backdrop-filter:blur(20px);
    border:1px solid rgba(255,255,255,.08);
    border-radius:24px;
    padding:25px;
    box-shadow:var(--shadow);
    animation:fadeUp .5s ease;
}

.inventory-table h2,
.movements-panel h2{
    margin-bottom:20px;
    color:#fff;
}

/* Table */
table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:rgba(255,255,255,.05);
    color:#93c5fd;
    padding:15px;
    border-bottom:1px solid rgba(255,255,255,.08);
    font-size:13px;
}

td{
    padding:15px;
    border-bottom:1px solid rgba(255,255,255,.06);
    color:#d1d5db;
}

tr{
    transition:.3s ease;
}

tr:hover{
    background:rgba(255,255,255,.04);
}

/* Status */
.stock-status{
    padding:8px 14px;
    border-radius:50px;
    font-size:12px;
    font-weight:600;
}

.stock-in{
    background:rgba(255, 255, 255, 0);
    color:#4ade80;
}

.stock-low{
    background:rgba(245,158,11,.15);
    color:#fbbf24;
}

.stock-out{
    background:rgba(239,68,68,.15);
    color:#f87171;
}

.btn-adjust{
    border-radius:12px;
    padding:10px 15px;
}

/* Movement Panel */
.movement-item{
    padding:15px 0;
    border-bottom:1px solid rgba(255,255,255,.08);
}

.movement-item:last-child{
    border:none;
}

.movement-type{
    font-weight:600;
}

.movement-type.in{
    color:#4ade80;
}

.movement-type.out{
    color:#f87171;
}

.movement-type.adjustment{
    color:#facc15;
}

.movement-details{
    color:#9ca3af;
    font-size:13px;
    margin-top:5px;
}

/* Modal */
.modal{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.7);
    backdrop-filter:blur(10px);
    z-index:10000;
}

.modal-content{
    width:500px;
    max-width:90%;
    margin:5% auto;
}

/* Forms */
.form-group{
    margin-bottom:18px;
}

.form-group label{
    display:block;
    margin-bottom:8px;
    color:#d1d5db;
    font-weight:500;
}

.form-group input,
.form-group select,
.form-group textarea{
    width:100%;
    padding:14px;
    border-radius:14px;
    border:1px solid rgba(255,255,255,.1);
    background:rgba(255,255,255,.05);
    color:#fff;
    outline:none;
    transition:.3s ease;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus{
    border-color:var(--primary);
    box-shadow:0 0 15px rgba(26,115,232,.35);
    transform:scale(1.01);
}

/* Modal Buttons */
.modal-buttons{
    display:flex;
    gap:10px;
}

.btn-submit{
    flex:1;
}

.btn-cancel{
    background:rgba(255,255,255,.08);
    color:#fff;
    border:1px solid rgba(255,255,255,.1);
}

.product-img{
    width: 80px;
    height: 80px;
    object-fit: contain;
    background: rgba(255, 255, 255, 0.05);
    border-radius:12px;
    border:2px solid rgba(255,255,255,.1);
    transition:.3s ease;
    cursor:pointer;
}

.product-img:hover{
    transform:scale(1.08);
    box-shadow:0 0 20px rgba(59,130,246,.35);
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

@keyframes fadeIn{
    from{
        opacity:0;
    }
    to{
        opacity:1;
    }
}

/* Responsive */
/* ======================
   RESPONSIVE DESIGN
====================== */

@media (max-width: 1024px){

    .content-grid{
        grid-template-columns:1fr;
    }

    .movements-panel{
        order:-1;
    }

    .main-content{
        padding:25px;
    }
}

/* ======================
   RESPONSIVE DESIGN
====================== */

/* DESKTOP LARGE */
@media (min-width: 1400px){
    .sidebar{
        width:300px;
    }

    .main-content{
        margin-left:300px;
        padding:40px;
    }

    .content-grid{
        grid-template-columns:2fr 400px;
    }
}

/* LAPTOP */
@media (max-width:1200px){

    .main-content{
        padding:25px;
    }

    .content-grid{
        grid-template-columns:1fr;
        gap:20px;
    }

    .movements-panel{
        order:-1;
    }

    .product-img{
        width:90px;
        height:90px;
    }
}

/* TABLET */
@media (max-width:992px){

    .sidebar{
        width:250px;
    }

    .main-content{
        margin-left:250px;
        padding:20px;
    }

    .header h1{
        font-size:26px;
    }

    .inventory-table,
    .movements-panel{
        padding:20px;
        border-radius:20px;
    }

    .product-img{
        width:75px;
        height:75px;
    }

    table{
        font-size:14px;
    }

    th,
    td{
        padding:12px;
    }
}

/* MOBILE + TABLET */
@media (max-width: 1024px){

    body{
        overflow-x:hidden;
    }

    .sidebar{
        position:fixed;
        left:-280px;
        top:0;
        width:260px;
        height:100vh;
        transition:.35s ease;
        z-index:2000;
    }

    .sidebar.active{
        left:0;
    }

    .main-content{
        margin-left:0 !important;
        width:100%;
        padding:15px;
    }

    .header{
        flex-direction:column;
        align-items:flex-start;
        gap:15px;
    }

    .header h1{
        font-size:22px;
    }

    .menu-toggle{
        display:block;
    }

    .content-grid{
        grid-template-columns:1fr;
        gap:18px;
    }

    .inventory-table,
    .movements-panel{
        width:100%;
        overflow:hidden;
        padding:18px;
    }

    /* TABLE FIX */
    .inventory-table{
        overflow-x:auto;
        -webkit-overflow-scrolling:touch;
    }

    table{
        min-width:700px;
        width:100%;
    }

    th,
    td{
        padding:10px;
        font-size:12px;
        white-space:nowrap;
    }

    .product-img{
        width:60px;
        height:60px;
    }

    .btn-adjust{
        padding:8px 12px;
        font-size:12px;
    }

    .back-btn{
        width:100%;
        text-align:center;
    }

    /* MODAL FIX */
    .modal-content{
        width:95%;
        margin:10% auto;
        padding:20px;
        border-radius:18px;
    }

    .modal-buttons{
        flex-direction:column;
    }

    .btn-submit,
    .btn-cancel{
        width:100%;
    }
}

/* SMALL PHONES */
@media (max-width:480px){

    .main-content{
        padding:12px;
    }

    .header h1{
        font-size:20px;
    }

    .header{
        gap:12px;
    }

    .inventory-table,
    .movements-panel{
        padding:15px;
        border-radius:16px;
    }

    table{
        min-width:600px;
    }

    th,
    td{
        font-size:11px;
        padding:8px;
    }

    .product-img{
        width:50px;
        height:50px;
    }

    .btn-adjust{
        padding:7px 10px;
        font-size:11px;
    }

    .modal-content{
        width:95%;
        margin:20% auto;
        padding:15px;
    }

    .form-group input,
    .form-group select,
    .form-group textarea{
        padding:12px;
        font-size:14px;
    }

    .menu-toggle{
        padding:10px 14px;
    }
}

/* CATEGORY TABS */
.category-tabs {
    display: flex;
    gap: 12px;
    margin-bottom: 25px;
    flex-wrap: wrap;
    animation: fadeUp 0.6s ease;
}

.tab-btn {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.08);
    color: var(--muted);
    padding: 12px 22px;
    border-radius: 16px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.tab-btn:hover {
    background: rgba(66, 133, 244, 0.15);
    border-color: rgba(66, 133, 244, 0.3);
    color: #fff;
    transform: translateY(-2px);
}

.tab-btn.active {
    background: var(--primary);
    border-color: var(--primary);
    color: #fff;
    box-shadow: 0 0 15px rgba(26, 115, 232, 0.4);
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
                    <h1>Manage Inventory</h1>
                </div>
                <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
            </div>
            
            <?php if ($message): ?>
                <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <div class="content-grid">
                <div class="inventory-table">
                    <h2>Inventory Items</h2>
                    
                    <!-- Category Tabs -->
                    <div class="category-tabs">
                        <button class="tab-btn <?= $selected_category === '' ? 'active' : '' ?>" onclick="filterByCategory('')">
                            All Products
                        </button>
                        <?php foreach ($categories as $cat): ?>
                            <button class="tab-btn <?= $selected_category === $cat ? 'active' : '' ?>" onclick="filterByCategory('<?= htmlspecialchars($cat) ?>')">
                                <?= htmlspecialchars($cat) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Product Description</th>
                                <th>Category</th>
                                <th>Stock</th>
                                <th>Unit</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <?php
                                $stock_status = 'in';
                                if ($row['current_quantity'] == 0) {
                                    $stock_status = 'out';
                                } elseif ($row['current_quantity'] <= $row['min_stock']) {
                                    $stock_status = 'low';
                                }
                                ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($row['image_path'])): ?>
                                            <img src="../<?= htmlspecialchars($row['image_path']) ?>"
                                                alt="<?= htmlspecialchars($row['name']) ?>"
                                                class="product-img">
                                        <?php else: ?>
                                            <span style="color: #6b7280; font-size: 12px;">No Image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                    <td><?= htmlspecialchars($row['category']) ?></td>
                                    <td><?= number_format($row['current_quantity']) ?></td>
                                    <td><?= htmlspecialchars($row['unit']) ?></td>
                                    <td>
                                        <?php if ($stock_status == 'out'): ?>
                                            <span class="stock-status stock-out">Out of Stock</span>
                                        <?php elseif ($stock_status == 'low'): ?>
                                            <span class="stock-status stock-low">Low Stock</span>
                                        <?php else: ?>
                                            <span class="stock-status stock-in">In Stock</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button onclick="openAdjustModal(<?= $row['id'] ?>, '<?= htmlspecialchars($row['name']) ?>', <?= $row['current_quantity'] ?>)" class="btn btn-adjust">Adjust</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="movements-panel">
                    <h2>Recent Movements</h2>
                    <?php while ($movement = mysqli_fetch_assoc($movements)): ?>
                        <div class="movement-item">
                            <div class="movement-type <?= $movement['movement_type'] ?>">
                                <?= strtoupper($movement['movement_type']) ?>: <?= htmlspecialchars($movement['product_name']) ?>
                            </div>
                            <div class="movement-details">
                                Qty: <?= number_format($movement['quantity']) ?> | 
                                Ref: <?= htmlspecialchars($movement['reference']) ?> | 
                                <?= date('M d, H:i', strtotime($movement['created_at'])) ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div id="adjustModal" class="modal">
        <div class="modal-content">
            <h2>Adjust Inventory</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="manage_inventory">
                <input type="hidden" name="product_id" id="modalProductId">
                
                <div class="form-group">
                    <label>Product:</label>
                    <input type="text" id="modalProductName" readonly>
                </div>
                
                <div class="form-group">
                    <label>Current Stock:</label>
                    <input type="text" id="modalCurrentStock" readonly>
                </div>
                
                <div class="form-group">
                    <label>Movement Type:</label>
                    <select name="movement_type" required>
                        <option value="in">Stock In</option>
                        <option value="out">Stock Out</option>
                        <option value="adjustment">Adjustment</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Quantity:</label>
                    <input type="number" name="quantity" min="0" required>
                </div>
                
                <div class="form-group">
                    <label>Reference/Notes:</label>
                    <input type="text" name="reference" placeholder="Reason for adjustment" required>
                </div>
                
                <div class="form-group">
                    <label>Product Image (Optional):</label>
                    <input type="file" name="product_image" accept="image/*">
                    <small style="color: #9ca3af; margin-top: 5px; display: block;">Upload a new image for this product</small>
                </div>
                
                <div class="modal-buttons">
                    <button type="submit" class="btn btn-submit">Update Inventory</button>
                    <button type="button" onclick="closeAdjustModal()" class="btn btn-cancel">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openAdjustModal(productId, productName, currentStock) {
            document.getElementById('modalProductId').value = productId;
            document.getElementById('modalProductName').value = productName;
            document.getElementById('modalCurrentStock').value = currentStock;
            document.getElementById('adjustModal').style.display = 'block';
        }
        
        function closeAdjustModal() {
            document.getElementById('adjustModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target == document.getElementById('adjustModal')) {
                closeAdjustModal();
            }
        }
    </script>
    
    <script>
// AJAX Filtering by Category Tabs
function filterByCategory(category) {
    // Update active tab class
    const tabs = document.querySelectorAll('.tab-btn');
    tabs.forEach(tab => {
        const tabText = tab.innerText.trim();
        if (tabText === 'All Products' && category === '') {
            tab.classList.add('active');
        } else if (tabText === category) {
            tab.classList.add('active');
        } else {
            tab.classList.remove('active');
        }
    });

    const url = `manage_inventory.php?ajax=1&category=${encodeURIComponent(category)}`;
    const tableBody = document.querySelector('tbody');
    if (tableBody) tableBody.style.opacity = '0.5';

    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (tableBody) {
                tableBody.innerHTML = data.table;
                tableBody.style.opacity = '1';
            }
            // Update URL without reloading
            const newUrl = category === '' ? 'manage_inventory.php' : `manage_inventory.php?category=${encodeURIComponent(category)}`;
            window.history.pushState({ category }, '', newUrl);
        })
        .catch(error => {
            console.error('AJAX Category Filter Error:', error);
            if (tableBody) tableBody.style.opacity = '1';
        });
}

// History back/forward support
window.addEventListener('popstate', function(e) {
    const category = (e.state && e.state.category) ? e.state.category : '';
    filterByCategory(category);
});
</script>
<script src="../assets/sidebar.js"></script>
</body>
</html>
