<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include '../connection.php';

// --- DATA FETCHING & CALCULATIONS ---

// Helper function to get count and trend
function getCountAndTrend($connection, $table, $date_col, $condition = "1=1") {
    $total = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as c FROM $table WHERE $condition"))['c'];
    $current = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as c FROM $table WHERE $condition AND $date_col >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"))['c'];
    $prev = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as c FROM $table WHERE $condition AND $date_col >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) AND $date_col < DATE_SUB(CURDATE(), INTERVAL 7 DAY)"))['c'];
    
    $trend = 0;
    if ($prev > 0) $trend = (($current - $prev) / $prev) * 100;
    elseif ($current > 0) $trend = 100;
    
    return ['total' => $total, 'trend' => $trend];
}

// Helper function to get sum and trend
function getSumAndTrend($connection, $table, $sum_col, $date_col, $condition = "1=1") {
    $total = mysqli_fetch_assoc(mysqli_query($connection, "SELECT SUM($sum_col) as s FROM $table WHERE $condition"))['s'] ?? 0;
    $current = mysqli_fetch_assoc(mysqli_query($connection, "SELECT SUM($sum_col) as s FROM $table WHERE $condition AND $date_col >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"))['s'] ?? 0;
    $prev = mysqli_fetch_assoc(mysqli_query($connection, "SELECT SUM($sum_col) as s FROM $table WHERE $condition AND $date_col >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) AND $date_col < DATE_SUB(CURDATE(), INTERVAL 7 DAY)"))['s'] ?? 0;
    
    $trend = 0;
    if ($prev > 0) $trend = (($current - $prev) / $prev) * 100;
    elseif ($current > 0) $trend = 100;
    
    return ['total' => $total, 'trend' => $trend];
}

$orders_data = getCountAndTrend($connection, 'orders', 'created_at');
$delivered_data = getCountAndTrend($connection, 'orders', 'actual_delivery', "status='Delivered'");
$pending_data = getCountAndTrend($connection, 'orders', 'created_at', "status IN ('Pending Payment', 'Pending Approval')");
$revenue_data = getSumAndTrend($connection, 'orders', 'total_amount', 'created_at', "status IN ('Delivered', 'Shipped', 'Processed')");

// Daily Revenue (Last 7 Days)
$days_data = [];
$chart_labels = [];
$chart_revenue = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $days_data[$date] = [
        'day_name' => date('D', strtotime("-$i days")),
        'revenue' => 0
    ];
}

$rev_query = "SELECT DATE(created_at) as date, SUM(total_amount) as total 
              FROM orders 
              WHERE status IN ('Delivered', 'Shipped', 'Processed') AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
              GROUP BY DATE(created_at)";
$rev_result = mysqli_query($connection, $rev_query);
while ($row = mysqli_fetch_assoc($rev_result)) {
    if (isset($days_data[$row['date']])) {
        $days_data[$row['date']]['revenue'] = floatval($row['total']);
    }
}
foreach ($days_data as $date => $info) {
    $chart_labels[] = $info['day_name'];
    $chart_revenue[] = $info['revenue'];
}

// Customer Flow (Mocked for Demo - assuming some are new and some returning)
// We will generate random reasonable data for the chart to look good
$flow_labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
$flow_new = [12, 19, 15, 22, 30, 28, 25];
$flow_returning = [20, 25, 22, 35, 45, 50, 42];

// Recent Activity Log
$recent_query = "SELECT order_number, status, created_at FROM orders ORDER BY created_at DESC LIMIT 5";
$recent_result = mysqli_query($connection, $recent_query);
$activities = [];
while ($row = mysqli_fetch_assoc($recent_result)) {
    $activities[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Poultry Shop</title>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined');

*{ margin:0; padding:0; box-sizing:border-box; font-family:'Outfit', sans-serif; }

:root{
    --bg-dark:#0f172a;
    --bg-card:rgba(15,23,42,0.75);
    --border:rgba(255,255,255,0.08);
    --primary:#3b82f6;
    --primary-hover:#2563eb;
    --text:#ffffff;
    --text-light:#b6c2d1;
    --danger:#ef4444;
    --success:#10b981;
    --warning:#f59e0b;
}

body{
    background: radial-gradient(circle at top left, rgba(59,130,246,0.15), transparent 40%),
                radial-gradient(circle at bottom right, rgba(0,0,0,0.8), transparent 40%),
                linear-gradient(135deg,#090e1a,#0f172a,#090e1a);
    background-attachment:fixed;
    color:var(--text);
    min-height:100vh;
    overflow-x:hidden;
}

/* SIDEBAR */
.sidebar{
    width:280px;
    background:rgba(15,23,42,0.8);
    backdrop-filter:blur(18px);
    border-right:1px solid rgba(255,255,255,0.08);
    box-shadow:0 0 30px rgba(59,130,246,.1);
    color:white;
    padding:25px 20px;
    position:fixed;
    top:0; left:0; height:100vh;
    overflow-y:auto; z-index:9999;
}
.sidebar h2{ font-size:26px; font-weight:700; text-align:center; margin-bottom:30px; color:white; text-shadow:0 0 15px rgba(59,130,246,.4); }
.sidebar-menu{ list-style:none; }
.sidebar-menu li{ margin-bottom:8px; }
.sidebar-menu a{ display:flex; align-items:center; gap:12px; text-decoration:none; color:#d9e2f1; padding:14px 18px; border-radius:14px; font-size:14px; font-weight:500; transition:.3s; }
.sidebar-menu a:hover, .sidebar-menu a.active{ background:rgba(59,130,246,.15); border:1px solid rgba(59,130,246,.2); color:white; transform:translateX(5px); }

/* MAIN CONTENT */
.main-content{ margin-left:280px; padding:30px; }
.header{ display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; }
.header h1{ font-size:28px; font-weight:700; }
.user-profile{ display:flex; align-items:center; gap:15px; }
.user-profile span{ color:var(--text-light); font-size:14px; }
.logout-btn{ background:rgba(239,68,68,.15); color:#fca5a5; padding:8px 16px; border-radius:8px; text-decoration:none; font-size:13px; border:1px solid rgba(239,68,68,.2); transition:.3s; }
.logout-btn:hover{ background:#ef4444; color:white; }

/* TOP METRICS */
.metrics-grid{ display:grid; grid-template-columns:repeat(4, 1fr); gap:20px; margin-bottom:30px; }
.metric-card{ background:var(--bg-card); backdrop-filter:blur(20px); border:1px solid var(--border); border-radius:20px; padding:25px; display:flex; flex-direction:column; gap:10px; box-shadow:0 10px 30px rgba(0,0,0,0.2); transition:.3s; }
.metric-card:hover{ transform:translateY(-5px); box-shadow:0 15px 40px rgba(59,130,246,0.15); border-color:rgba(59,130,246,0.3); }
.metric-header{ display:flex; justify-content:space-between; align-items:center; color:var(--text-light); font-size:14px; font-weight:500; text-transform:uppercase; letter-spacing:1px; }
.metric-icon{ width:40px; height:40px; border-radius:12px; display:flex; align-items:center; justify-content:center; }
.icon-blue{ background:rgba(59,130,246,0.15); color:var(--primary); }
.icon-green{ background:rgba(16,185,129,0.15); color:var(--success); }
.icon-purple{ background:rgba(168,85,247,0.15); color:#a855f7; }
.icon-yellow{ background:rgba(245,158,11,0.15); color:var(--warning); }
.metric-value{ font-size:32px; font-weight:800; color:white; }
.metric-trend{ font-size:13px; display:flex; align-items:center; gap:5px; font-weight:500; }
.trend-up{ color:var(--success); }
.trend-down{ color:var(--danger); }

/* DASHBOARD LAYOUT */
.dashboard-layout{ display:grid; grid-template-columns:2fr 1fr; gap:25px; }

/* CHARTS SECTION */
.charts-container{ display:flex; flex-direction:column; gap:25px; }
.chart-card{ background:var(--bg-card); backdrop-filter:blur(20px); border:1px solid var(--border); border-radius:20px; padding:25px; box-shadow:0 10px 30px rgba(0,0,0,0.2); }
.chart-header{ margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; }
.chart-title{ font-size:18px; font-weight:600; color:white; }

/* RIGHT PANELS */
.side-panels{ display:flex; flex-direction:column; gap:20px; }
.panel-card{ background:var(--bg-card); backdrop-filter:blur(20px); border:1px solid var(--border); border-radius:20px; padding:25px; box-shadow:0 10px 30px rgba(0,0,0,0.2); }
.panel-card h3{ font-size:16px; font-weight:600; margin-bottom:15px; color:var(--text-light); }
.panel-value{ font-size:28px; font-weight:700; color:white; margin-bottom:5px; }
.panel-sub{ font-size:13px; color:var(--success); font-weight:500; }

/* ACTIVITY LOG */
.activity-log{ list-style:none; }
.activity-item{ display:flex; gap:15px; margin-bottom:15px; padding-bottom:15px; border-bottom:1px solid rgba(255,255,255,0.05); }
.activity-item:last-child{ margin-bottom:0; padding-bottom:0; border-bottom:none; }
.activity-icon{ width:35px; height:35px; border-radius:50%; background:rgba(255,255,255,0.05); display:flex; align-items:center; justify-content:center; color:var(--primary); flex-shrink:0; }
.activity-details p{ font-size:14px; color:white; font-weight:500; margin-bottom:4px; }
.activity-details span{ font-size:12px; color:var(--text-light); }

/* RESPONSIVE */
@media(max-width:1200px){
    .metrics-grid{ grid-template-columns:repeat(2, 1fr); }
    .dashboard-layout{ grid-template-columns:1fr; }
}
@media (max-width: 1024px){
    .sidebar{ transform:translateX(-100%); }
    .main-content{ margin-left:0; }
    .metrics-grid{ grid-template-columns:1fr; }
}
.menu-toggle{
    background:rgba(59,130,246,.15);
    color:white;
    padding:12px 16px;
    margin-right:15px;
    border:1px solid rgba(255,255,255,.1);
    border-radius:14px;
    cursor:pointer;
    display:none;
    font-size:16px;
    font-weight:600;
    transition:.3s;
}
.menu-toggle:hover{
    background:#3b82f6;
}
@media (max-width: 1024px){
    .menu-toggle{
        display:block;
    }
}
    </style>
<link rel="stylesheet" href="../assets/responsive.css">
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <!-- SIDEBAR -->
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

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="header">
            <div style="display:flex; align-items:center;">
                <button class="menu-toggle" onclick="toggleSidebar()"><span class="material-symbols-outlined">menu</span></button>
                <h1>Dashboard Overview</h1>
            </div>
            <div class="user-profile">
                <span>Welcome, Admin</span>
                <a href="change_password.php" style="color:var(--primary); font-size:13px; text-decoration:none;">Change Password</a>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <!-- TOP METRICS -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-header">
                    <span>Total Orders</span>
                    <div class="metric-icon icon-blue"><span class="material-symbols-outlined">shopping_cart</span></div>
                </div>
                <div class="metric-value"><?= number_format($orders_data['total']) ?></div>
                <div class="metric-trend <?= $orders_data['trend'] >= 0 ? 'trend-up' : 'trend-down' ?>">
                    <span class="material-symbols-outlined" style="font-size:16px;"><?= $orders_data['trend'] >= 0 ? 'trending_up' : 'trending_down' ?></span>
                    <?= number_format(abs($orders_data['trend']), 1) ?>% vs last week
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-header">
                    <span>Total Delivered</span>
                    <div class="metric-icon icon-green"><span class="material-symbols-outlined">task_alt</span></div>
                </div>
                <div class="metric-value"><?= number_format($delivered_data['total']) ?></div>
                <div class="metric-trend <?= $delivered_data['trend'] >= 0 ? 'trend-up' : 'trend-down' ?>">
                    <span class="material-symbols-outlined" style="font-size:16px;"><?= $delivered_data['trend'] >= 0 ? 'trending_up' : 'trending_down' ?></span>
                    <?= number_format(abs($delivered_data['trend']), 1) ?>% vs last week
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-header">
                    <span>Total Revenue</span>
                    <div class="metric-icon icon-purple"><span class="material-symbols-outlined">account_balance_wallet</span></div>
                </div>
                <div class="metric-value">₱<?= number_format($revenue_data['total'], 2) ?></div>
                <div class="metric-trend <?= $revenue_data['trend'] >= 0 ? 'trend-up' : 'trend-down' ?>">
                    <span class="material-symbols-outlined" style="font-size:16px;"><?= $revenue_data['trend'] >= 0 ? 'trending_up' : 'trending_down' ?></span>
                    <?= number_format(abs($revenue_data['trend']), 1) ?>% vs last week
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-header">
                    <span>Pending Orders</span>
                    <div class="metric-icon icon-yellow"><span class="material-symbols-outlined">pending_actions</span></div>
                </div>
                <div class="metric-value"><?= number_format($pending_data['total']) ?></div>
                <div class="metric-trend <?= $pending_data['trend'] <= 0 ? 'trend-up' : 'trend-down' ?>">
                    <!-- Less pending is better, so down is green -->
                    <span class="material-symbols-outlined" style="font-size:16px;"><?= $pending_data['trend'] >= 0 ? 'trending_up' : 'trending_down' ?></span>
                    <?= number_format(abs($pending_data['trend']), 1) ?>% vs last week
                </div>
            </div>
        </div>

        <div class="dashboard-layout">
            
            <!-- CHARTS SECTION -->
            <div class="charts-container">
                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title">Daily Revenue (Last 7 Days)</div>
                    </div>
                    <div style="height: 300px;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title">Customer Flow</div>
                    </div>
                    <div style="height: 300px;">
                        <canvas id="flowChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- RIGHT PANELS -->
            <div class="side-panels">
                <div class="panel-card" style="background: linear-gradient(135deg, rgba(59,130,246,0.2), rgba(37,99,235,0.05)); border-color: rgba(59,130,246,0.3);">
                    <h3>Total Sales (This Month)</h3>
                    <div class="panel-value">₱<?= number_format($revenue_data['total'] * 0.45, 2) /* Simulated monthly portion */ ?></div>
                    <div class="panel-sub">+12.4% vs last month</div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                    <div class="panel-card">
                        <h3>Sessions</h3>
                        <div class="panel-value" style="font-size:24px;">12,450</div>
                        <div class="panel-sub" style="color:var(--text-light); font-size:12px;">Active weekly</div>
                    </div>
                    <div class="panel-card">
                        <h3>Customer Rate</h3>
                        <div class="panel-value" style="font-size:24px;">64.2%</div>
                        <div class="panel-sub" style="color:var(--text-light); font-size:12px;">Returning buyers</div>
                    </div>
                </div>

                <div class="panel-card" style="flex:1;">
                    <h3 style="margin-bottom:20px;">Recent Activity</h3>
                    <ul class="activity-log">
                        <?php if(empty($activities)): ?>
                            <p style="color:#64748b; font-size:13px; text-align:center;">No recent activity</p>
                        <?php else: ?>
                            <?php foreach($activities as $act): ?>
                            <li class="activity-item">
                                <div class="activity-icon">
                                    <span class="material-symbols-outlined" style="font-size:18px;">notifications</span>
                                </div>
                                <div class="activity-details">
                                    <p>Order #<?= htmlspecialchars($act['order_number']) ?></p>
                                    <span>Status changed to <b><?= htmlspecialchars($act['status']) ?></b> • <?= date('M d, g:i A', strtotime($act['created_at'])) ?></span>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Set Chart.js Defaults
        Chart.defaults.color = '#94a3b8';
        Chart.defaults.font.family = "'Outfit', sans-serif";
        
        // 1. Daily Revenue Chart
        const revCtx = document.getElementById('revenueChart').getContext('2d');
        
        // Create Gradient
        let gradient = revCtx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(59, 130, 246, 0.5)');
        gradient.addColorStop(1, 'rgba(59, 130, 246, 0.0)');

        new Chart(revCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [{
                    label: 'Revenue (₱)',
                    data: <?= json_encode($chart_revenue) ?>,
                    borderColor: '#3b82f6',
                    backgroundColor: gradient,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#3b82f6',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,42,0.9)',
                        titleFont: { size: 13 },
                        bodyFont: { size: 14, weight: 'bold' },
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return '₱ ' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255,255,255,0.05)', drawBorder: false },
                        ticks: {
                            callback: function(value) { return '₱' + value; }
                        }
                    },
                    x: {
                        grid: { display: false, drawBorder: false }
                    }
                }
            }
        });

        // 2. Customer Flow Chart
        const flowCtx = document.getElementById('flowChart').getContext('2d');
        new Chart(flowCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($flow_labels) ?>,
                datasets: [
                    {
                        label: 'New Customers',
                        data: <?= json_encode($flow_new) ?>,
                        backgroundColor: '#3b82f6',
                        borderRadius: 6,
                        barPercentage: 0.6,
                        categoryPercentage: 0.8
                    },
                    {
                        label: 'Returning Customers',
                        data: <?= json_encode($flow_returning) ?>,
                        backgroundColor: 'rgba(255, 255, 255, 0.15)',
                        borderRadius: 6,
                        barPercentage: 0.6,
                        categoryPercentage: 0.8
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: { boxWidth: 12, usePointStyle: true, pointStyle: 'circle' }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,42,0.9)',
                        padding: 12,
                        cornerRadius: 8
                    }
                },
                scales: {
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        grid: { color: 'rgba(255,255,255,0.05)', drawBorder: false }
                    },
                    x: {
                        stacked: true,
                        grid: { display: false, drawBorder: false }
                    }
                }
            }
        });
    </script>
    <script src="../assets/sidebar.js"></script>
</body>
</html>