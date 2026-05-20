<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include '../connection.php';

$message = '';
$messageType = '';

// Handle product update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update') {
    $product_id = intval($_POST['product_id']);
    $name = mysqli_real_escape_string($connection, $_POST['name']);
    $description = mysqli_real_escape_string($connection, $_POST['description']);
    $category = mysqli_real_escape_string($connection, $_POST['category']);
    $min_stock = intval($_POST['min_stock']);
    $selling_price = floatval($_POST['selling_price']);
    $unit = mysqli_real_escape_string($connection, $_POST['unit']);
    $expiry_date = $_POST['expiry_date'] ? $_POST['expiry_date'] : NULL;
    $barcode = mysqli_real_escape_string($connection, $_POST['barcode']);
    $status = mysqli_real_escape_string($connection, $_POST['status']);
    
    // Handle image upload
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
            $query = "UPDATE inventory_items SET name=?, description=?, category=?, min_stock=?, price=?, selling_price=?, unit=?, expiry_date=?, supplier=?, barcode=?, image_path=?, status=? WHERE id=?";
            $stmt = mysqli_prepare($connection, $query);
            mysqli_stmt_bind_param($stmt, "sssiiddssssis", $name, $description, $category, $min_stock, $price, $selling_price, $unit, $expiry_date, $supplier, $barcode, $image_path, $status, $product_id);
        }
    } else {
        $query = "UPDATE inventory_items SET name=?, description=?, category=?, min_stock=?, price=?, selling_price=?, unit=?, expiry_date=?, supplier=?, barcode=?, status=? WHERE id=?";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "sssiiddsssis", $name, $description, $category, $min_stock, $price, $selling_price, $unit, $expiry_date, $supplier, $barcode, $status, $product_id);
    }
    
    if (mysqli_stmt_execute($stmt)) {
        $message = "Product updated successfully!";
        $messageType = "success";
    } else {
        $message = "Error updating product: " . mysqli_error($connection);
        $messageType = "error";
    }
}

// Get product to edit
$product = null;
if (isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    $query = "SELECT * FROM inventory_items WHERE id = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);
}

// Get all products for selection
$all_products = mysqli_query($connection, "SELECT id, name, category FROM inventory_items ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Product - Admin</title>
    <!-- Material Symbols -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');

:root{
    --bg:#070B1A;
    --panel:rgba(255,255,255,0.06);
    --glass:rgba(255,255,255,0.08);
    --border:rgba(255,255,255,0.12);
    --text:#EAF2FF;
    --muted:rgba(234,242,255,0.6);
    --blue:#3B82F6;
    --blue2:#1D4ED8;
    --glow:0 0 20px rgba(59,130,246,0.4);
}

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Outfit',sans-serif;
}

body{
    background: radial-gradient(circle at top, #0B1228, #090e1a 60%, #03040C);
    background-attachment:fixed;
    color:var(--text);
    overflow-x:hidden;
}

/* ===== LAYOUT ===== */
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

/* ===== MAIN ===== */
.main-content{
    margin-left:280px;
    flex:1;
    padding:30px;
    transition:0.4s ease;
}

.main-content.expanded{
    margin-left:0;
}

/* ===== HEADER ===== */
.header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
}

.header h1{
    font-size:26px;
    background:linear-gradient(90deg,var(--blue),#A5B4FC);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
}

/* ===== BUTTONS ===== */
.back-btn{
    background:linear-gradient(135deg,var(--blue),var(--blue2));
    padding:10px 18px;
    border-radius:10px;
    color:white;
    text-decoration:none;
    box-shadow:var(--glow);
    transition:0.3s;
}

.back-btn:hover{
    transform:translateY(-2px);
}

/* ===== GLASS CARDS ===== */
.product-selector,
.form-container{
    background:var(--panel);
    backdrop-filter:blur(18px);
    border:1px solid var(--border);
    border-radius:18px;
    padding:20px;
    box-shadow:0 10px 30px rgba(0,0,0,0.4);
    margin-bottom:25px;
    animation:fadeUp 0.6s ease;
}

/* ===== SELECT ===== */
select, input, textarea{
    width:100%;
    padding:12px;
    border-radius:12px;
    border:1px solid var(--border);
    background:rgba(255,255,255,0.05);
    color:var(--text);
    outline:none;
    transition:0.3s ease;
}

select:focus,
input:focus,
textarea:focus{
    border-color:var(--blue);
    box-shadow:0 0 12px rgba(59,130,246,0.4);
    transform:scale(1.01);
}

/* ===== FORM GRID ===== */
.form-grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:18px;
}

.form-group.full-width{
    grid-column:span 2;
}

label{
    font-size:13px;
    margin-bottom:6px;
    display:block;
    color:var(--muted);
}

/* ===== BUTTON ===== */
.submit-btn{
    background:linear-gradient(135deg,var(--blue),var(--blue2));
    border:none;
    padding:12px 25px;
    border-radius:12px;
    color:white;
    font-weight:600;
    cursor:pointer;
    transition:0.3s ease;
    box-shadow:var(--glow);
}

.submit-btn:hover{
    transform:translateY(-3px) scale(1.02);
    box-shadow:0 0 25px rgba(59,130,246,0.6);
}

/* ===== MESSAGE ===== */
.message{
    padding:12px;
    border-radius:12px;
    margin-bottom:15px;
    backdrop-filter:blur(10px);
}

.message.success{
    background:rgba(34,197,94,0.15);
    border:1px solid rgba(34,197,94,0.3);
}

.message.error{
    background:rgba(239,68,68,0.15);
    border:1px solid rgba(239,68,68,0.3);
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

/* ===== ANIMATIONS ===== */
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

/* ===== IMAGE ===== */
img{
    border-radius:10px;
    border:1px solid var(--border);
}

/* ===== MOBILE ===== */
@media (max-width: 1024px){
    .menu-toggle{display:block;}
    .sidebar{transform:translateX(-100%);}
    .main-content{margin-left:0;}
}

/* ===== SCROLLBAR ===== */
::-webkit-scrollbar{
    width:6px;
}

::-webkit-scrollbar-thumb{
    background:var(--blue);
    border-radius:10px;
}
</style>
<link rel="stylesheet" href="../assets/responsive.css">
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
    <div class="container">
        <div class="sidebar">
            <h2 style="display:flex; align-items:center; justify-content:center; gap:10px;">
                <span class="material-symbols-outlined" style="font-size:28px;">storefront</span>
                Smart Poultry
            </h2>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><span class="material-symbols-outlined">dashboard</span> Overview</a></li>
            
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
            <li><a href="update_product.php" class="active"><span class="material-symbols-outlined">edit_square</span> Update Product</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="header">
                <div style="display: flex; align-items: center;">
                    <button class="menu-toggle" onclick="toggleSidebar()"><span class="material-symbols-outlined">menu</span></button>
                    <h1> Update Product Info</h1>
                </div>
                <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
            </div>
            
            <?php if ($message): ?>
                <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <div class="product-selector">
                <label style="display: block; margin-bottom: 10px; color: #333; font-weight: 600;">Select Product to Edit:</label>
                <select onchange="window.location.href='update_product.php?id='+this.value">
                    <option value="">-- Select a Product --</option>
                    <?php while ($row = mysqli_fetch_assoc($all_products)): ?>
                        <option value="<?= $row['id'] ?>" <?= $product && $product['id'] == $row['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['name']) ?> (<?= htmlspecialchars($row['category']) ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <?php if ($product): ?>
                <div class="form-container">
                    <h2>Edit Product: <?= htmlspecialchars($product['name']) ?></h2>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <input type="hidden" name="action" value="update">
                        
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label>Product Name *</label>
                                <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
                            </div>
                            
                            <div class="form-group full-width">
                                <label>Description</label>
                                <textarea name="description"><?= htmlspecialchars($product['description']) ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Category *</label>
                                <select name="category" required>
                                    <option value="Feeds" <?= $product['category'] == 'Feeds' ? 'selected' : '' ?>>Feeds</option>
                                    <option value="Supplements/Medicines" <?= $product['category'] == 'Supplements/Medicines' ? 'selected' : '' ?>>Supplements/Medicines</option>
                                    <option value="Equipments/Feeding tools" <?= $product['category'] == 'Equipments/Feeding tools' ? 'selected' : '' ?>>Equipments/Feeding tools</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Unit *</label>
                                <input type="text" name="unit" value="<?= htmlspecialchars($product['unit']) ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Minimum Stock Level *</label>
                                <input type="number" name="min_stock" min="0" value="<?= $product['min_stock'] ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Price *</label>
                                <input type="number" name="selling_price" step="0.01" min="0" value="<?= $product['selling_price'] ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Expiry Date</label>
                                <input type="date" name="expiry_date" value="<?= $product['expiry_date'] ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Barcode</label>
                                <input type="text" name="barcode" value="<?= htmlspecialchars($product['barcode']) ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Status *</label>
                                <select name="status" required>
                                    <option value="active" <?= $product['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $product['status'] == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    <option value="discontinued" <?= $product['status'] == 'discontinued' ? 'selected' : '' ?>>Discontinued</option>
                                </select>
                            </div>
                            
                            <div class="form-group full-width">
                                <label>Product Image</label>
                                <input type="file" name="product_image" accept="image/*">
                                <?php if ($product['image_path']): ?>
                                    <div style="margin-top: 10px;">
                                        <small>Current image:</small><br>
                                        <img src="../<?= htmlspecialchars($product['image_path']) ?>" alt="Product Image" style="max-width: 100px; margin-top: 5px;">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <button type="submit" class="submit-btn">Update Product</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="no-product">
                    <h3>No Product Selected</h3>
                    <p>Please select a product from the dropdown above to edit.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
<script src="../assets/sidebar.js"></script>
</body>
</html>
