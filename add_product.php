<?php
session_start();

include '../connection.php';

$message = '';
$messageType = '';

// Handle product addition
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($connection, $_POST['name']);
    $description = mysqli_real_escape_string($connection, $_POST['description']);
    $category = mysqli_real_escape_string($connection, $_POST['category']);
    $current_quantity = intval($_POST['current_quantity']);
    $min_stock = intval($_POST['min_stock']);
    $selling_price = floatval($_POST['selling_price']);
    $unit = mysqli_real_escape_string($connection, $_POST['unit']);
    $expiry_date = $_POST['expiry_date'] ? $_POST['expiry_date'] : NULL;
    $barcode = mysqli_real_escape_string($connection, $_POST['barcode']);
    
    // Handle image upload
    $image_path = NULL;
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $upload_dir = '../uploads/inventory/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
            $image_path = 'uploads/inventory/' . $file_name;
        }
    }
    
    $query = "INSERT INTO inventory_items (name, description, category, current_quantity, min_stock, selling_price, unit, expiry_date, barcode, image_path, status) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
    
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "sssiidssss", $name, $description, $category, $current_quantity, $min_stock, $selling_price, $unit, $expiry_date, $barcode, $image_path);
    
    if (mysqli_stmt_execute($stmt)) {
        $message = "Product added successfully!";
        $messageType = "success";
        
        // Log stock movement
        $movement_query = "INSERT INTO stock_movements (product_id, product_name, movement_type, quantity, reference, user_name) 
                          VALUES (LAST_INSERT_ID(), ?, 'in', ?, 'Initial Stock', ?)";
        $movement_stmt = mysqli_prepare($connection, $movement_query);
        mysqli_stmt_bind_param($movement_stmt, "sis", $name, $current_quantity, $_SESSION['name']);
        mysqli_stmt_execute($movement_stmt);
    } else {
        $message = "Error adding product: " . mysqli_error($connection);
        $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Admin</title>
    <!-- Google Font -->
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<!-- Material Symbols -->
<link rel="stylesheet"
href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Outfit',sans-serif;
}

:root{
    --primary:#3b82f6;
    --primary-hover:#2563eb;
    --bg-dark:#090e1a;
    --bg-secondary:#0d1324;
    --glass:rgba(255,255,255,0.08);
    --glass-border:rgba(255,255,255,0.12);
    --text:#f5f7ff;
    --text-muted:#9ca3af;
    --shadow:0 8px 32px rgba(0,0,0,.4);
    --blue-glow:0 0 30px rgba(59,130,246,.25);
}

body{
    background:
    radial-gradient(circle at top left,#1e3a8a33,transparent 30%),
    radial-gradient(circle at bottom right,#2563eb22,transparent 25%),
    linear-gradient(135deg,#090e1a,#0b1120,#020617);
    background-attachment:fixed;
    color:var(--text);
    overflow-x:hidden;
}

/* Background Animation */
body::before{
    content:'';
    position:fixed;
    width:100%;
    height:100%;
    background:
    radial-gradient(circle,rgba(59,130,246,.06) 1px,transparent 1px);
    background-size:35px 35px;
    animation: moveBg 25s linear infinite;
    z-index:-1;
}

@keyframes moveBg{
    from{transform:translateY(0);}
    to{transform:translateY(-60px);}
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

/* ================= MAIN ================= */
.main-content{
    margin-left:280px;
    flex:1;
    padding:35px;
    transition:.3s ease;
}

.main-content.expanded{
    margin-left:0;
}

.header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:30px;
    animation:fadeDown .7s ease;
}

@keyframes fadeDown{
    from{
        opacity:0;
        transform:translateY(-20px);
    }
    to{
        opacity:1;
        transform:translateY(0);
    }
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

/* ================= GLASS CARD ================= */
.form-container{
    background:rgba(255,255,255,.06);
    border:1px solid rgba(255,255,255,.08);
    backdrop-filter:blur(25px);
    -webkit-backdrop-filter:blur(25px);
    border-radius:30px;
    padding:35px;
    box-shadow:var(--shadow);
    animation:fadeUp .8s ease;
    position:relative;
    overflow:hidden;
}

.form-container::before{
    content:'';
    position:absolute;
    top:-50%;
    left:-50%;
    width:200%;
    height:200%;
    background:radial-gradient(circle,
    rgba(59,130,246,.07),
    transparent 40%);
    animation:rotateGlow 15s linear infinite;
}

@keyframes rotateGlow{
    from{transform:rotate(0deg);}
    to{transform:rotate(360deg);}
}

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

.form-container h2{
    margin-bottom:25px;
    font-size:24px;
    position:relative;
    z-index:2;
}

/* ================= FORM ================= */
.form-grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:20px;
    position:relative;
    z-index:2;
}

.form-group{
    margin-bottom:5px;
}

.form-group.full-width{
    grid-column:span 2;
}

.form-group label{
    display:block;
    margin-bottom:10px;
    color:#dbeafe;
    font-size:14px;
    font-weight:500;
}

/* Inputs */
.form-group input,
.form-group select,
.form-group textarea{
    width:100%;
    background:rgba(255,255,255,.05);
    border:1px solid rgba(255,255,255,.08);
    color:white;
    padding:15px 18px;
    border-radius:18px;
    outline:none;
    transition:.3s ease;
    font-size:14px;
}

.form-group textarea{
    min-height:120px;
    resize:vertical;
}

.form-group input::placeholder,
.form-group textarea::placeholder{
    color: #fff;;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus{
    border-color:#3b82f6;
    box-shadow:
    0 0 0 4px rgba(59,130,246,.15),
    0 0 20px rgba(59,130,246,.2);
    transform:translateY(-2px);
}

.form-group input:hover,
.form-group textarea:hover,
.form-group select:hover{
    border-color:rgba(59,130,246,.4);
}

/* Submit Button */
.submit-btn{
    margin-top:25px;
    background:linear-gradient(
    135deg,
    #3b82f6,
    #2563eb);
    color:white;
    padding:15px 30px;
    border-radius:18px;
    font-size:15px;
    position:relative;
    z-index:2;
}

.submit-btn:hover{
    transform:translateY(-4px) scale(1.02);
    box-shadow:
    0 10px 25px rgba(59,130,246,.35);
}

/* ================= ALERTS ================= */
.message{
    padding:16px 20px;
    border-radius:20px;
    margin-bottom:20px;
    backdrop-filter:blur(15px);
    animation:fadeDown .5s ease;
}

.message.success{
    background:rgba(34,197,94,.12);
    border:1px solid rgba(34,197,94,.25);
    color:#bbf7d0;
}

.message.error{
    background:rgba(239,68,68,.12);
    border:1px solid rgba(239,68,68,.25);
    color:#fecaca;
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
                    <h1>Add Product</h1>
                </div>
                <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
            </div>
            
            <?php if ($message): ?>
                <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <div class="form-container">
                <h2>Add New Product</h2>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label>Product Name *</label>
                            <input type="text" name="name" required>
                        </div>
                        
                        <div class="form-group full-width">
                            <label>Description</label>
                            <textarea name="description" placeholder="Enter product description"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Category *</label>
                            <select name="category" required>
                                <option value="">Select Category</option>
                                <option value="Feeds">Feeds</option>
                                <option value="Supplements/Medicines">Supplements/Medicines</option>
                                <option value="Equipment/Feeding Tools">Equipment/Feeding Tools</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Unit *</label>
                            <input type="text" name="unit" placeholder="e.g., kg, pcs, dozen" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Current Quantity *</label>
                            <input type="number" name="current_quantity" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Minimum Stock Level *</label>
                            <input type="number" name="min_stock" min="0" value="10" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Price *</label>
                            <input type="number" name="selling_price" step="0.01" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Expiry Date</label>
                            <input type="date" name="expiry_date">
                        </div>
                        
                        <div class="form-group">
                            <label>Barcode</label>
                            <input type="text" name="barcode" placeholder="Barcode number">
                        </div>
                        
                        <div class="form-group full-width">
                            <label>Product Image</label>
                            <input type="file" name="product_image" accept="image/*">
                        </div>
                    </div>
                    
                    <button type="submit" class="submit-btn">Add Product</button>
                </form>
            </div>
        </div>
    </div>
    
<script src="../assets/sidebar.js"></script>
</body>
</html>
