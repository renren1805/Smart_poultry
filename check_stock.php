<?php
session_start();

include '../connection.php';

// Get filter category
$selected_category = isset($_GET['category']) ? $_GET['category'] : '';

// Get all distinct categories for the dropdown filter
$cat_query = "SELECT DISTINCT category FROM inventory_items WHERE category IS NOT NULL AND category != '' ORDER BY category ASC";
$cat_result = mysqli_query($connection, $cat_query);

// Pagination settings
$limit = 6; // Set a small limit like 6 to show pagination clearly
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Base condition
$where_clause = "";
if ($selected_category !== "") {
    $escaped_cat = mysqli_real_escape_string($connection, $selected_category);
    $where_clause = " WHERE category = '$escaped_cat'";
}

// Get filtered total items for pagination
$total_filtered_query = "SELECT COUNT(*) as count FROM inventory_items" . $where_clause;
$total_filtered_result = mysqli_query($connection, $total_filtered_query);
$total_filtered = mysqli_fetch_assoc($total_filtered_result)['count'];
$total_pages = ceil($total_filtered / $limit);

// Ensure page doesn't exceed total pages if total pages > 0
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
}

// Query for items
$query = "SELECT * FROM inventory_items" . $where_clause . " ORDER BY current_quantity ASC LIMIT $limit OFFSET $offset";
$result = mysqli_query($connection, $query);

// Get stock statistics
$total_items = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as count FROM inventory_items" . $where_clause))['count'];
$low_stock = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as count FROM inventory_items WHERE current_quantity <= min_stock" . ($selected_category !== "" ? " AND category = '" . mysqli_real_escape_string($connection, $selected_category) . "'" : "")))['count'];
$out_of_stock = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as count FROM inventory_items WHERE current_quantity = 0" . ($selected_category !== "" ? " AND category = '" . mysqli_real_escape_string($connection, $selected_category) . "'" : "")))['count'];

// AJAX Request Handler
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    ob_start();
    ?>
    <div class="stat-card">
        <h3>Total Items</h3>
        <div class="value"><?= number_format($total_items) ?></div>
    </div>
    <div class="stat-card warning">
        <h3>Low Stock</h3>
        <div class="value"><?= number_format($low_stock) ?></div>
    </div>
    <div class="stat-card danger">
        <h3>Out of Stock</h3>
        <div class="value"><?= number_format($out_of_stock) ?></div>
    </div>
    <?php
    $stats_html = ob_get_clean();

    ob_start();
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $stock_status = 'in';
            $quantity_class = '';
            if ($row['current_quantity'] == 0) {
                $stock_status = 'out';
                $quantity_class = 'out';
            } elseif ($row['current_quantity'] <= $row['min_stock']) {
                $stock_status = 'low';
                $quantity_class = 'low';
            }
            ?>
            <tr>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['category']) ?></td>
                <td class="quantity <?= $quantity_class ?>"><?= number_format($row['current_quantity']) ?></td>
                <td><?= number_format($row['min_stock']) ?></td>
                <td><?= htmlspecialchars($row['unit']) ?></td>
                <td>₱<?= number_format($row['selling_price'], 2) ?></td>
                <td>
                    <?php if ($stock_status == 'out'): ?>
                        <span class="stock-status stock-out">Out of Stock</span>
                    <?php elseif ($stock_status == 'low'): ?>
                        <span class="stock-status stock-low">Low Stock</span>
                    <?php else: ?>
                        <span class="stock-status stock-in">In Stock</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php
        }
    } else {
        echo "<tr><td colspan='7' style='text-align:center;'>No products found in this category.</td></tr>";
    }
    $table_html = ob_get_clean();

    ob_start();
    if ($total_pages > 1) {
        ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="check_stock.php?page=1&category=<?= urlencode($selected_category) ?>" class="page-link" data-page="1">&laquo; First</a>
                <a href="check_stock.php?page=<?= $page - 1 ?>&category=<?= urlencode($selected_category) ?>" class="page-link" data-page="<?= $page - 1 ?>">&lsaquo; Prev</a>
            <?php endif; ?>

            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);

            for ($i = $start_page; $i <= $end_page; $i++):
                $active_class = ($i === $page) ? 'active' : '';
            ?>
                <a href="check_stock.php?page=<?= $i ?>&category=<?= urlencode($selected_category) ?>" class="page-link <?= $active_class ?>" data-page="<?= $i ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="check_stock.php?page=<?= $page + 1 ?>&category=<?= urlencode($selected_category) ?>" class="page-link" data-page="<?= $page + 1 ?>">Next &rsaquo;</a>
                <a href="check_stock.php?page=<?= $total_pages ?>&category=<?= urlencode($selected_category) ?>" class="page-link" data-page="<?= $total_pages ?>">Last &raquo;</a>
            <?php endif; ?>
        </div>
        <?php
    }
    $pagination_html = ob_get_clean();

    header('Content-Type: application/json');
    echo json_encode([
        'stats' => $stats_html,
        'table' => $table_html,
        'pagination' => $pagination_html
    ]);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Stock - Admin</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Outfit', sans-serif;
}

:root{
    --primary:#3b82f6;
    --primary-hover:#5a95ff;
    --glass:rgba(255,255,255,0.08);
    --border:rgba(255,255,255,0.12);
    --shadow:rgba(59,130,246,0.25);
    --text:#ffffff;
    --text-muted:#b8c4d9;
    --danger:#ff4d6d;
    --warning:#ffc107;
    --success:#2ecc71;
}

body{
    min-height:100vh;
    background:
        radial-gradient(circle at top left,#0f4c81 0%, transparent 30%),
        radial-gradient(circle at bottom right,#1b1f3a 0%, transparent 30%),
        linear-gradient(135deg,#090e1a,#0b1120,#000814);
    background-attachment:fixed;
    color:var(--text);
    overflow-x:hidden;
}

/* SCROLLBAR */
::-webkit-scrollbar{
    width:8px;
}

::-webkit-scrollbar-thumb{
    background:rgba(255,255,255,.15);
    border-radius:20px;
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

/* MENU */
.sidebar-menu{
    list-style:none;
}

.sidebar-menu li{
    margin-bottom:8px;
}

.sidebar-menu a{
    display:flex;
    align-items:center;
    gap:10px;
    color:#d6dfff;
    text-decoration:none;
    padding:14px 16px;
    border-radius:16px;
    transition:.35s ease;
    font-size:14px;
    position:relative;
    overflow:hidden;
}

.sidebar-menu a::before{
    content:'';
    position:absolute;
    left:-100%;
    top:0;
    width:100%;
    height:100%;
    background:linear-gradient(
        90deg,
        transparent,
        rgba(255,255,255,.08),
        transparent
    );
    transition:.6s;
}

.sidebar-menu a:hover::before{
    left:100%;
}

.sidebar-menu a:hover,
.sidebar-menu a.active{
    background:rgba(59,130,246,.15);
    border:1px solid rgba(59,130,246,.35);
    transform:translateX(5px);
    box-shadow:0 0 20px rgba(59,130,246,.18);
    color:#fff;
}

.sidebar-menu .section-title{
    color:#7d8db3;
    font-size:12px;
    text-transform:uppercase;
    margin:25px 0 12px;
    padding-left:10px;
    font-weight:600;
    letter-spacing:1px;
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

/* MAIN CONTENT */
.main-content{
    margin-left:280px;
    flex:1;
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
    margin-bottom:35px;
}

.header h1{
    font-size:32px;
    font-weight:700;
    color:#fff;
}

/* BUTTONS */
.back-btn,
.menu-toggle{
    background:rgba(59,130,246,.18);
    border:1px solid rgba(59,130,246,.35);
    backdrop-filter:blur(10px);
    color:white;
    padding:12px 20px;
    border-radius:14px;
    cursor:pointer;
    text-decoration:none;
    transition:.3s ease;
    box-shadow:0 0 20px rgba(59,130,246,.12);
}

.back-btn:hover,
.menu-toggle:hover{
    background:var(--primary);
    transform:translateY(-2px);
    box-shadow:0 0 25px rgba(59,130,246,.4);
}

/* STATS */
.stats-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(230px,1fr));
    gap:25px;
    margin-bottom:35px;
}

.stat-card{
    background:rgba(255,255,255,.06);
    border:1px solid rgba(255,255,255,.1);
    backdrop-filter:blur(18px);
    -webkit-backdrop-filter:blur(18px);
    padding:25px;
    border-radius:24px;
    transition:.35s ease;
    box-shadow:0 8px 32px rgba(0,0,0,.2);
    animation:fadeUp .6s ease;
}

.stat-card:hover{
    transform:translateY(-6px);
    border-color:rgba(59,130,246,.35);
    box-shadow:0 0 35px rgba(59,130,246,.18);
}

.stat-card h3{
    color:var(--text-muted);
    font-size:13px;
    text-transform:uppercase;
    margin-bottom:10px;
}

.stat-card .value{
    font-size:34px;
    font-weight:700;
    color:#fff;
}

.stat-card.warning .value{
    color:var(--warning);
}

.stat-card.danger .value{
    color:var(--danger);
}

/* TABLE */
.stock-table{
    background:rgba(255,255,255,.06);
    backdrop-filter:blur(20px);
    border:1px solid rgba(255,255,255,.08);
    border-radius:28px;
    overflow:hidden;
    box-shadow:0 8px 32px rgba(0,0,0,.25);
    animation:fadeUp .8s ease;
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:rgba(255,255,255,.05);
    color:#d8e2ff;
    padding:20px;
    text-transform:uppercase;
    font-size:12px;
    letter-spacing:.8px;
}

td{
    padding:18px 20px;
    color:#fff;
    border-bottom:1px solid rgba(255,255,255,.05);
}

tr{
    transition:.25s ease;
}

tr:hover{
    background:rgba(59,130,246,.08);
    transform:scale(1.005);
}

/* STOCK STATUS */
.stock-status{
    padding:8px 14px;
    border-radius:30px;
    font-size:12px;
    font-weight:600;
    display:inline-block;
    transition:.3s ease;
}

.stock-in{
    background:rgba(46,204,113,.18);
    color:#2ecc71;
    border:1px solid rgba(46,204,113,.25);
}

.stock-low{
    background:rgba(255,193,7,.15);
    color:#ffc107;
    border:1px solid rgba(255,193,7,.25);
}

.stock-out{
    background:rgba(255,77,109,.18);
    color:#ff4d6d;
    border:1px solid rgba(255,77,109,.25);
}

.quantity{
    font-weight:700;
    font-size:17px;
}

.quantity.low{
    color:#ffc107;
}

.quantity.out{
    color:#ff4d6d;
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
   RESPONSIVE DESIGN
====================== */

/* DESKTOP LARGE */
@media screen and (min-width: 1400px) {
    .sidebar {
        width: 300px;
    }

    .main-content {
        margin-left: 300px;
        padding: 40px;
    }

    .stats-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

/* LAPTOP / SMALL DESKTOP */
@media screen and (max-width: 1200px) {
    .main-content {
        padding: 25px;
    }

    .header h1 {
        font-size: 28px;
    }
}

/* TABLET */
@media screen and (max-width: 992px) {

    .sidebar {
        width: 260px;
        transform: translateX(-100%);
        transition: 0.3s ease;
    }

    .sidebar.active {
        transform: translateX(0);
    }

    .main-content {
        margin-left: 0;
        width: 100%;
        padding: 20px;
    }

    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .header {
        flex-wrap: wrap;
        gap: 15px;
    }

    .header h1 {
        font-size: 26px;
    }

    .stock-table {
        overflow-x: auto;
        border-radius: 20px;
    }

    table {
        min-width: 700px;
    }
}

/* MOBILE */
@media (max-width: 1024px) {

    .container {
        display: block;
    }

    .sidebar {
        position: fixed;
        width: 260px;
        left: 0;
        top: 0;
        height: 100vh;
        transform: translateX(-100%);
        z-index: 2000;
    }

    .sidebar.active {
        transform: translateX(0);
    }

    .main-content {
        margin-left: 0;
        width: 100%;
        padding: 18px;
    }

    .header {
        flex-direction: column;
        align-items: stretch;
        gap: 15px;
    }

    .header div {
        width: 100%;
    }

    .header h1 {
        font-size: 22px;
    }

    .back-btn {
        width: 100%;
        text-align: center;
    }

    .menu-toggle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .stats-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }

    .stat-card {
        padding: 20px;
    }

    .stock-table {
        overflow-x: auto;
        border-radius: 18px;
    }

    table {
        min-width: 650px;
    }

    th,
    td {
        padding: 12px;
        font-size: 12px;
        white-space: nowrap;
    }
}

/* SMALL MOBILE */
@media screen and (max-width: 480px) {

    .main-content {
        padding: 15px;
    }

    .header h1 {
        font-size: 20px;
    }

    .menu-toggle,
    .back-btn {
        width: 100%;
    }

    table {
        min-width: 600px;
    }

    th,
    td {
        padding: 10px;
        font-size: 11px;
    }

    .stat-card .value {
        font-size: 28px;
    }
}
/* FILTER SECTION */
.filter-section {
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(15px);
    border-radius: 24px;
    padding: 20px;
    margin-bottom: 30px;
    animation: fadeUp 0.7s ease;
}

.filter-form {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 12px;
}

.filter-group label {
    font-size: 14px;
    font-weight: 500;
    color: var(--text-muted);
}

.filter-group select {
    padding: 10px 20px;
    background: rgba(0, 0, 0, 0.4);
    border: 1px solid rgba(66, 133, 244, 0.3);
    border-radius: 12px;
    color: var(--text);
    outline: none;
    font-size: 14px;
    transition: 0.3s ease;
    cursor: pointer;
}

.filter-group select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 12px var(--shadow);
}

/* PAGINATION */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    margin-top: 30px;
    margin-bottom: 10px;
    flex-wrap: wrap;
    animation: fadeUp 0.9s ease;
}

.page-link {
    padding: 10px 16px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    color: var(--text-muted);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: 0.3s ease;
}

.page-link:hover {
    background: rgba(66, 133, 244, 0.15);
    border-color: rgba(66, 133, 244, 0.3);
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(66, 133, 244, 0.2);
}

.page-link.active {
    background: var(--primary);
    border-color: var(--primary);
    color: #fff;
    box-shadow: 0 0 15px var(--shadow);
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
                    <h1>Check Stock</h1>
                </div>
                <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" action="check_stock.php" class="filter-form">
                    <div class="filter-group">
                        <label for="category-select">Filter by Category:</label>
                        <select name="category" id="category-select" onchange="loadStockData(1, this.value)">
                            <option value="">All Categories</option>
                            <?php while ($cat_row = mysqli_fetch_assoc($cat_result)): ?>
                                <option value="<?= htmlspecialchars($cat_row['category']) ?>" <?= $selected_category === $cat_row['category'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat_row['category']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </form>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Items</h3>
                    <div class="value"><?= number_format($total_items) ?></div>
                </div>
                <div class="stat-card warning">
                    <h3>Low Stock</h3>
                    <div class="value"><?= number_format($low_stock) ?></div>
                </div>
                <div class="stat-card danger">
                    <h3>Out of Stock</h3>
                    <div class="value"><?= number_format($out_of_stock) ?></div>
                </div>
            </div>
            
            <div class="stock-table">
                <table>
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Current Stock</th>
                            <th>Min Stock</th>
                            <th>Unit</th>
                            <th>Price</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <?php
                            $stock_status = 'in';
                            $quantity_class = '';
                            if ($row['current_quantity'] == 0) {
                                $stock_status = 'out';
                                $quantity_class = 'out';
                            } elseif ($row['current_quantity'] <= $row['min_stock']) {
                                $stock_status = 'low';
                                $quantity_class = 'low';
                            }
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['category']) ?></td>
                                <td class="quantity <?= $quantity_class ?>"><?= number_format($row['current_quantity']) ?></td>
                                <td><?= number_format($row['min_stock']) ?></td>
                                <td><?= htmlspecialchars($row['unit']) ?></td>
                                <td>₱<?= number_format($row['selling_price'], 2) ?></td>
                                <td>
                                    <?php if ($stock_status == 'out'): ?>
                                        <span class="stock-status stock-out">Out of Stock</span>
                                    <?php elseif ($stock_status == 'low'): ?>
                                        <span class="stock-status stock-low">Low Stock</span>
                                    <?php else: ?>
                                        <span class="stock-status stock-in">In Stock</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Controls Container -->
            <div class="pagination-container">
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="check_stock.php?page=1&category=<?= urlencode($selected_category) ?>" class="page-link" data-page="1">&laquo; First</a>
                            <a href="check_stock.php?page=<?= $page - 1 ?>&category=<?= urlencode($selected_category) ?>" class="page-link" data-page="<?= $page - 1 ?>">&lsaquo; Prev</a>
                        <?php endif; ?>

                        <?php
                        // Display max 5 page links around current page
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <a href="check_stock.php?page=<?= $i ?>&category=<?= urlencode($selected_category) ?>" class="page-link <?= $i === $page ? 'active' : '' ?>" data-page="<?= $i ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="check_stock.php?page=<?= $page + 1 ?>&category=<?= urlencode($selected_category) ?>" class="page-link" data-page="<?= $page + 1 ?>">Next &rsaquo;</a>
                            <a href="check_stock.php?page=<?= $total_pages ?>&category=<?= urlencode($selected_category) ?>" class="page-link" data-page="<?= $total_pages ?>">Last &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
    
    <script>
// Function to load data via AJAX
function loadStockData(page, category) {
    const url = `check_stock.php?ajax=1&page=${page}&category=${encodeURIComponent(category)}`;
    
    // Add subtle visual transition
    const tableBody = document.querySelector('tbody');
    const statsGrid = document.querySelector('.stats-grid');
    const paginationContainer = document.querySelector('.pagination-container');
    
    if (tableBody) tableBody.style.opacity = '0.5';
    if (statsGrid) statsGrid.style.opacity = '0.7';
    if (paginationContainer) paginationContainer.style.opacity = '0.5';

    fetch(url)
        .then(response => response.json())
        .then(data => {
            // Update Stats Cards
            if (statsGrid) {
                statsGrid.innerHTML = data.stats;
                statsGrid.style.opacity = '1';
            }

            // Update Table Body
            if (tableBody) {
                tableBody.innerHTML = data.table;
                tableBody.style.opacity = '1';
            }

            // Update Pagination Container
            if (paginationContainer) {
                paginationContainer.innerHTML = data.pagination;
                paginationContainer.style.opacity = '1';
            }

            // Update URL in address bar without reloading
            const newUrl = `check_stock.php?page=${page}&category=${encodeURIComponent(category)}`;
            window.history.pushState({ page, category }, '', newUrl);
        })
        .catch(error => {
            console.error('AJAX Load Error:', error);
            if (tableBody) tableBody.style.opacity = '1';
            if (statsGrid) statsGrid.style.opacity = '1';
            if (paginationContainer) paginationContainer.style.opacity = '1';
        });
}

// Handle pagination click events dynamically
document.addEventListener('click', function(e) {
    const pageLink = e.target.closest('.page-link');
    if (pageLink) {
        e.preventDefault();
        const page = pageLink.getAttribute('data-page');
        const category = document.getElementById('category-select').value;
        loadStockData(page, category);
    }
});

// Handle browser back/forward buttons (History API Support)
window.addEventListener('popstate', function(e) {
    const state = e.state;
    if (state) {
        loadStockData(state.page || 1, state.category || '');
        document.getElementById('category-select').value = state.category || '';
    } else {
        const params = new URLSearchParams(window.location.search);
        const page = params.get('page') || 1;
        const category = params.get('category') || '';
        loadStockData(page, category);
        document.getElementById('category-select').value = category;
    }
});
</script>
<script src="../assets/sidebar.js"></script>
</body>
</html>
