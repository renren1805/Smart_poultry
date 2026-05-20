<?php
session_start();
require('../connection.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['user_id'];

if (!isset($_GET['order_id'])) {
    header("Location: view_order_history.php");
    exit();
}

$order_id = intval($_GET['order_id']);

// Get order details
$query = "SELECT * FROM orders WHERE id = ? AND customer_id = ?";
$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $customer_id);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$order) {
    header("Location: view_order_history.php");
    exit();
}

// Map the DB status to our tracking stages
$db_status = $order['status'];
$stages = [
    'Processing' => false,
    'Shipped / Shipped Out' => false,
    'In Transit' => false,
    'Out for Delivery' => false,
    'Successfully Delivered' => false
];

$active_stage = -1;

if (in_array($db_status, ['Approved', 'Processed', 'Shipped', 'Delivered'])) {
    $stages['Processing'] = true;
    $active_stage = 0;
}
if (in_array($db_status, ['Shipped', 'Delivered'])) {
    $stages['Shipped / Shipped Out'] = true;
    $active_stage = 1;
    
    // Simulate In Transit if Shipped
    if ($db_status == 'Shipped') {
        $stages['In Transit'] = true;
        $active_stage = 2;
        
        // Simulate Out for Delivery if it's been a day since shipped
        // For demonstration, we'll randomly decide or check tracking_number
        if (!empty($order['tracking_number'])) {
            $stages['Out for Delivery'] = true;
            $active_stage = 3;
        }
    }
}
if ($db_status == 'Delivered') {
    $stages['Processing'] = true;
    $stages['Shipped / Shipped Out'] = true;
    $stages['In Transit'] = true;
    $stages['Out for Delivery'] = true;
    $stages['Successfully Delivered'] = true;
    $active_stage = 4;
}

// Handle Cancelled / Pending cases
$is_cancelled = ($db_status == 'Cancelled');
$is_pending = in_array($db_status, ['Draft', 'Pending Payment', 'Pending Approval']);

?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order - <?= htmlspecialchars($order['order_number']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        :root {
            --primary: #f59e0b;
            --secondary: #a83434;
            --dark: #0f172a;
            --light: #f8fafc;
            --blue-glow: 0 0 30px rgba(245, 158, 11, 0.4);
            --success: #10b981;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #090e1a 0%, #0f172a 50%, #090e1a 100%);
            background-attachment: fixed;
            color: white;
            padding: 40px 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding: 20px 30px;
            background: rgba(255,255,255,.05);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,.1);
        }

        .header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #fff;
        }

        .back-btn {
            background: rgba(245, 158, 11, 0.15);
            color: white;
            border: 1px solid rgba(245, 158, 11, 0.2);
            padding: 10px 20px;
            text-decoration: none;
            backdrop-filter: blur(12px);
            border-radius: 12px;
            font-weight: 600;
            transition: .3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .back-btn:hover {
            background: var(--primary);
            transform: translateY(-2px);
            box-shadow: var(--blue-glow);
        }

        .tracking-card {
            background: rgba(255,255,255,.05);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 40px;
            border: 1px solid rgba(255,255,255,.1);
            margin-bottom: 30px;
        }

        .order-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 1px solid rgba(255,255,255,.1);
        }

        .meta-item label {
            display: block;
            color: #94a3b8;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .meta-item span {
            font-size: 18px;
            font-weight: 600;
            color: #fff;
        }

        /* Timeline Styles */
        .timeline {
            position: relative;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px 0;
        }

        .timeline::before {
            content: '';
            position: absolute;
            top: 0;
            left: 20px;
            height: 100%;
            width: 4px;
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 40px;
            padding-left: 60px;
        }

        .timeline-item:last-child {
            margin-bottom: 0;
        }

        .timeline-icon {
            position: absolute;
            left: 0;
            top: 0;
            width: 44px;
            height: 44px;
            background: #1e293b;
            border: 4px solid #0f172a;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            z-index: 2;
            transition: .3s ease;
        }

        .timeline-icon .material-symbols-outlined {
            font-size: 20px;
        }

        .timeline-content {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.05);
            padding: 20px;
            border-radius: 16px;
            transition: .3s ease;
        }

        .timeline-content h3 {
            font-size: 18px;
            color: #94a3b8;
            margin-bottom: 5px;
        }

        .timeline-content p {
            color: #64748b;
            font-size: 14px;
        }

        /* Active/Completed States */
        .timeline-item.completed .timeline-icon {
            background: var(--primary);
            color: #fff;
            box-shadow: 0 0 15px rgba(245, 158, 11, 0.4);
        }
        
        .timeline-item.delivered .timeline-icon {
            background: var(--success);
            color: #fff;
            box-shadow: 0 0 15px rgba(16, 185, 129, 0.4);
        }

        .timeline-item.completed .timeline-content h3 {
            color: #fff;
        }
        
        .timeline-item.active .timeline-content {
            border-color: var(--primary);
            background: rgba(245, 158, 11, 0.05);
            box-shadow: 0 0 20px rgba(245, 158, 11, 0.1);
            transform: translateX(10px);
        }

        .timeline-item.active .timeline-content h3 {
            color: var(--primary);
            font-weight: 700;
        }

        /* Connecting Line Fill */
        .timeline-progress {
            position: absolute;
            top: 0;
            left: 20px;
            width: 4px;
            background: var(--primary);
            border-radius: 4px;
            z-index: 1;
            transition: height 1s ease;
        }

        .cancelled-msg, .pending-msg {
            text-align: center;
            padding: 40px;
            background: rgba(234, 67, 53, 0.1);
            border: 1px solid rgba(234, 67, 53, 0.2);
            border-radius: 16px;
            color: #fca5a5;
        }

        .pending-msg {
            background: rgba(251, 188, 5, 0.1);
            border-color: rgba(251, 188, 5, 0.2);
            color: #fde047;
        }

        @media (max-width: 1024px) {
            .tracking-card {
                padding: 25px;
            }
            .header {
                flex-direction: column;
                gap: 20px;
                align-items: flex-start;
            }
        }
    </style>
<link rel="stylesheet" href="../assets/responsive.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Track Your Order</h1>
            <a href="view_order_history.php" class="back-btn">
                <span class="material-symbols-outlined">arrow_back</span>
                Back to Orders
            </a>
        </div>

        <div class="tracking-card">
            <div class="order-meta">
                <div class="meta-item">
                    <label>Order Number</label>
                    <span><?= htmlspecialchars($order['order_number']) ?></span>
                </div>
                <div class="meta-item">
                    <label>Date Ordered</label>
                    <span><?= date('M d, Y', strtotime($order['created_at'])) ?></span>
                </div>
                <?php if ($order['tracking_number']): ?>
                <div class="meta-item">
                    <label>Courier Tracking No.</label>
                    <span style="color: var(--primary);"><?= htmlspecialchars($order['tracking_number']) ?></span>
                </div>
                <?php endif; ?>
                <div class="meta-item">
                    <label>Status</label>
                    <span style="text-transform: capitalize;"><?= htmlspecialchars($order['status']) ?></span>
                </div>
            </div>

            <?php if ($is_cancelled): ?>
                <div class="cancelled-msg">
                    <span class="material-symbols-outlined" style="font-size: 48px; margin-bottom: 10px;">cancel</span>
                    <h2>Order Cancelled</h2>
                    <p>This order has been cancelled and cannot be tracked.</p>
                </div>
            <?php elseif ($is_pending): ?>
                <div class="pending-msg">
                    <span class="material-symbols-outlined" style="font-size: 48px; margin-bottom: 10px;">pending_actions</span>
                    <h2>Order Pending</h2>
                    <p>Your order is currently waiting for payment or admin approval before processing begins.</p>
                </div>
            <?php else: ?>
                
                <?php 
                    // Calculate progress line height based on active stage
                    $progress_height = 0;
                    if ($active_stage == 0) $progress_height = 10;
                    elseif ($active_stage == 1) $progress_height = 33;
                    elseif ($active_stage == 2) $progress_height = 66;
                    elseif ($active_stage == 3) $progress_height = 85;
                    elseif ($active_stage == 4) $progress_height = 100;
                ?>

                <div class="timeline">
                    <div class="timeline-progress" style="height: <?= $progress_height ?>%;"></div>

                    <!-- Processing -->
                    <div class="timeline-item <?= $stages['Processing'] ? 'completed' : '' ?> <?= $active_stage == 0 ? 'active' : '' ?>">
                        <div class="timeline-icon">
                            <span class="material-symbols-outlined">inventory_2</span>
                        </div>
                        <div class="timeline-content">
                            <h3>Processing</h3>
                            <p>Your order is being prepared and packed in our facility.</p>
                        </div>
                    </div>

                    <!-- Shipped -->
                    <div class="timeline-item <?= $stages['Shipped / Shipped Out'] ? 'completed' : '' ?> <?= $active_stage == 1 ? 'active' : '' ?>">
                        <div class="timeline-icon">
                            <span class="material-symbols-outlined">local_shipping</span>
                        </div>
                        <div class="timeline-content">
                            <h3>Shipped / Shipped Out</h3>
                            <p>Your package has been handed over to our delivery partner.</p>
                        </div>
                    </div>

                    <!-- In Transit -->
                    <div class="timeline-item <?= $stages['In Transit'] ? 'completed' : '' ?> <?= $active_stage == 2 ? 'active' : '' ?>">
                        <div class="timeline-icon">
                            <span class="material-symbols-outlined">route</span>
                        </div>
                        <div class="timeline-content">
                            <h3>In Transit</h3>
                            <p>Your package is moving through the logistics network towards your destination.</p>
                        </div>
                    </div>

                    <!-- Out for Delivery -->
                    <div class="timeline-item <?= $stages['Out for Delivery'] ? 'completed' : '' ?> <?= $active_stage == 3 ? 'active' : '' ?>">
                        <div class="timeline-icon">
                            <span class="material-symbols-outlined">directions_car</span>
                        </div>
                        <div class="timeline-content">
                            <h3>Out for Delivery</h3>
                            <p>The rider is in your area. Expect delivery today!</p>
                        </div>
                    </div>

                    <!-- Delivered -->
                    <div class="timeline-item <?= $stages['Successfully Delivered'] ? 'delivered' : '' ?> <?= $active_stage == 4 ? 'active' : '' ?>">
                        <div class="timeline-icon">
                            <span class="material-symbols-outlined">task_alt</span>
                        </div>
                        <div class="timeline-content">
                            <h3>Successfully Delivered</h3>
                            <p>The package has been successfully handed over to the customer.</p>
                            <?php if ($stages['Successfully Delivered'] && $order['actual_delivery']): ?>
                                <p style="margin-top: 5px; color: var(--success); font-weight: 600;">
                                    Delivered on: <?= date('M d, Y g:i A', strtotime($order['actual_delivery'])) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
