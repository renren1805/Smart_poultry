<?php
session_start();
require('../connection.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: view_order_history.php");
    exit();
}

$customer_id = $_SESSION['user_id'];

if (!isset($_GET['order_id'])) {
    header("Location: view_order_history.php");
    exit();
}

$order_id = intval($_GET['order_id']);

/* GET ORDER */
$order_query = "SELECT o.*, c.fullname, c.email, c.phone 
                FROM orders o
                JOIN customers c ON o.customer_id = c.id
                WHERE o.id = ? AND o.customer_id = ?";

$stmt = mysqli_prepare($connection, $order_query);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $customer_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($result);

if (!$order) {
    header("Location: view_order_history.php");
    exit();
}

/* GET ITEMS (FIXED ARRAY VERSION) */
$items_query = "SELECT oi.*, ii.name as product_name 
                FROM order_items oi 
                JOIN inventory_items ii ON oi.product_id = ii.id 
                WHERE oi.order_id = ?";
$items_stmt = mysqli_prepare($connection, $items_query);
mysqli_stmt_bind_param($items_stmt, "i", $order_id);
mysqli_stmt_execute($items_stmt);

$result_items = mysqli_stmt_get_result($items_stmt);

$order_items = [];
while ($row = mysqli_fetch_assoc($result_items)) {
    $order_items[] = $row;
}

/* HANDLE PAYMENT */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $payment_method = mysqli_real_escape_string($connection, $_POST['payment_method'] ?? $order['payment_method']);
    
    // Require reference number and payment amount only if the payment method is GCash and Bank Transfer
    if ($payment_method === 'gcash' || $payment_method === 'bank_transfer') {
        $payment_amount = floatval($_POST['payment_amount'] ?? 0);
        $payment_reference = mysqli_real_escape_string($connection, $_POST['payment_reference'] ?? '');
        $status = 'Pending Payment';
        $payment_date = date('Y-m-d H:i:s');
    } else {
        $payment_amount = 0;
        $payment_reference = '';
        $status = 'Pending Approval';
        $payment_date = null;
    }

    if (($payment_method === 'gcash' || $payment_method === 'bank_transfer') && $payment_amount < $order['total_amount']) {
        $error = "Payment must be at least ₱" . number_format($order['total_amount'], 2);
    } else {
        $update = "UPDATE orders SET 
                    payment_method = ?,
                    payment_amount = ?, 
                    payment_reference = ?, 
                    payment_date = ?, 
                    status = ? 
                    WHERE id = ?";

        $stmt = mysqli_prepare($connection, $update);
        mysqli_stmt_bind_param($stmt, "sdsssi", $payment_method, $payment_amount, $payment_reference, $payment_date, $status, $order_id);
        mysqli_stmt_execute($stmt);

        header("Location: print_receipt.php?order_id=$order_id");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Payment</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:'Outfit', sans-serif;
        }

        :root{
            --primary: #f59e0b;
            --secondary: #a83434;
            --accent: #d97706;
            --warning: #FBBC05;
            --dark: #0f172a;
            --light: #f8fafc;
            --blue-glow: 0 0 30px rgba(245, 158, 11, 0.4);
        }

        body{
            min-height:100vh;
            background:linear-gradient(135deg, #090e1a 0%, #0f172a 50%, #090e1a 100%);
            background-attachment:fixed;
            color:white;
            overflow-x:hidden;
        }

        .container{
            display:flex;
            min-height:100vh;
        }

        /* SIDEBAR */
        .sidebar{
            width:280px;
            background:rgba(15, 23, 42, 0.8);
            backdrop-filter:blur(20px);
            border-right:1px solid rgba(255,255,255,.1);
            padding:30px 20px;
            position:fixed;
            top:0;
            left:0;
            height:100vh;
            overflow-y:auto;
            transition:.3s ease;
            z-index:9999;
        }

        .sidebar h2{
            font-size:28px;
            font-weight:700;
            color:#fff;
            margin-bottom:40px;
            text-shadow:0 0 20px rgba(245, 158, 11, 0.5);
            display:flex;
            align-items:center;
            gap:10px;
        }

        .sidebar-menu{
            list-style:none;
        }

        .sidebar-menu li{
            margin-bottom:10px;
        }

        .sidebar-menu a{
            display:flex;
            align-items:center;
            gap:12px;
            padding:14px 18px;
            color:#94a3b8;
            text-decoration:none;
            border-radius:12px;
            transition:.3s ease;
            font-weight:500;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active{
            background:rgba(245, 158, 11, 0.15);
            color:#fff;
            border:1px solid rgba(245, 158, 11, 0.25);
        }

        .sidebar-menu .material-symbols-outlined{
            font-size:22px;
        }

        /* MAIN CONTENT */
        .main-content{
            margin-left:280px;
            flex:1;
            padding:30px;
        }

        .header{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:30px;
            padding:20px 30px;
            background:rgba(255, 255, 255, 0.05);
            backdrop-filter:blur(15px);
            border-radius:20px;
            border:1px solid rgba(255,255,255,.1);
        }

        .header h1{
            font-size:32px;
            font-weight:700;
            color:#fff;
            text-shadow:0 0 20px rgba(66, 244, 75, 0.3);
        }

        /* PAYMENT CONTENT */
        .payment-content{
            display:grid;
            grid-template-columns:1.5fr 1fr;
            gap:30px;
        }

        .payment-form{
            background:rgba(255,255,255,.05);
            backdrop-filter:blur(20px);
            border-radius:20px;
            padding:30px;
            border:1px solid rgba(255,255,255,.1);
        }

        .payment-form h2{
            font-size:24px;
            font-weight:600;
            color:#fff;
            margin-bottom:25px;
        }

        .order-info{
            background:rgba(255,255,255,.03);
            padding:20px;
            border-radius:14px;
            margin-bottom:25px;
            border:1px solid rgba(255,255,255,.05);
        }

        .order-info-item{
            display:flex;
            justify-content:space-between;
            margin-bottom:12px;
            padding-bottom:12px;
            border-bottom:1px solid rgba(255,255,255,.05);
        }

        .order-info-item:last-child{
            border-bottom:none;
            margin-bottom:0;
            padding-bottom:0;
        }

        .order-info-label{
            color:#94a3b8;
            font-size:14px;
        }

        .order-info-value{
            color:#fff;
            font-weight:600;
            font-size:15px;
        }

        .form-group{
            margin-bottom:20px;
        }

        .form-group label{
            display:block;
            margin-bottom:8px;
            color:#94a3b8;
            font-size:14px;
            font-weight:500;
        }

        .form-group input,
        .form-group select{
            width:100%;
            padding:14px 18px;
            border:1px solid rgba(255,255,255,.1);
            border-radius:12px;
            background:rgba(255,255,255,.05);
            backdrop-filter:blur(15px);
            color:white;
            font-size:14px;
            outline:none;
            transition:.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus{
            border-color:var(--primary);
            box-shadow:var(--blue-glow);
        }

        .form-group select option{
            background:#1e293b;
            color:white;
        }

        .payment-info{
            background:rgba(52,168,83,.1);
            padding:20px;
            border-radius:14px;
            margin-bottom:25px;
            border:1px solid rgba(52,168,83,.2);
        }

        .payment-info h3{
            color:var(--secondary);
            font-size:18px;
            font-weight:600;
            margin-bottom:15px;
            display:flex;
            align-items:center;
            gap:8px;
        }

        .payment-info p{
            color:#94a3b8;
            font-size:14px;
            line-height:1.6;
            margin-bottom:10px;
        }

        .payment-info p:last-child{
            margin-bottom:0;
        }

        .submit-btn{
            width:100%;
            padding:18px;
            background:var(--primary);
            color:white;
            border:none;
            border-radius:14px;
            font-size:18px;
            font-weight:700;
            cursor:pointer;
            margin-top:10px;
            transition:.3s ease;
        }

        .submit-btn:hover{
            background:var(--secondary);
            transform:translateY(-2px);
            box-shadow:0 0 20px rgba(168, 52, 52, 0.35);
        }

        /* ORDER SUMMARY */
        .order-summary{
            background:rgba(255,255,255,.05);
            backdrop-filter:blur(20px);
            border-radius:20px;
            padding:30px;
            border:1px solid rgba(255,255,255,.1);
            height:fit-content;
            position:sticky;
            top:30px;
        }

        .order-summary h2{
            font-size:24px;
            font-weight:600;
            color:#fff;
            margin-bottom:25px;
        }

        .summary-item{
            display:flex;
            justify-content:space-between;
            margin-bottom:15px;
            padding-bottom:15px;
            border-bottom:1px solid rgba(255,255,255,.05);
        }

        .summary-item:last-child{
            border-bottom:none;
        }

        .summary-item-name{
            color:#94a3b8;
            font-size:14px;
        }

        .summary-item-value{
            color:#fff;
            font-weight:600;
            font-size:16px;
        }

        .summary-total{
            margin-top:20px;
            padding-top:20px;
            border-top:2px solid rgba(245, 158, 11, 0.2);
        }

        .summary-total .summary-item-value{
            font-size:28px;
            color:var(--secondary);
        }

        /* BUTTONS */
        .back-btn{
            background:rgba(245, 158, 11, 0.15);
            color:white;
            border:1px solid rgba(245, 158, 11, 0.2);
            padding:12px 20px;
            text-decoration:none;
            backdrop-filter:blur(12px);
            border-radius:14px;
            font-weight:600;
            transition:.3s ease;
        }

        .back-btn:hover{
            background:var(--primary);
            transform:translateY(-2px);
            box-shadow:0 0 20px rgba(245, 158, 11, 0.35);
        }

        .menu-toggle{
            background:rgba(245, 158, 11, 0.15);
            color:white;
            padding:12px 16px;
            margin-right:15px;
            border:1px solid rgba(255,255,255,.1);
            border-radius:14px;
            cursor:pointer;
            display:none;
        }

        .menu-toggle:hover{
            background:var(--primary);
        }

        .success{
            background:rgba(168, 52, 52, 0.15);
            color:#34A853;
            padding:15px;
            border-radius:12px;
            margin-bottom:20px;
            border:1px solid rgba(168, 52, 52, 0.25);
        }

        .error{
            background:rgba(217, 119, 6,.15);
            color:#ea4335;
            padding:15px;
            border-radius:12px;
            margin-bottom:20px;
            border:1px solid rgba(217, 119, 6,.25);
        }

        /* RESPONSIVE - TABLETS & MOBILE */
        @media(max-width:1024px){
            .payment-content{
                grid-template-columns:1fr;
                gap:20px;
            }

            .header{
                flex-direction:column;
                align-items:flex-start;
                gap:15px;
                padding:15px 20px;
            }

            .header h1{
                font-size:24px;
            }

            .order-summary{
                position:static;
            }

            .payment-form{
                padding:20px;
            }

            .order-info{
                grid-template-columns:1fr;
            }

            .form-group input,
            .form-group textarea{
                font-size:14px;
            }
        }

        /* RESPONSIVE - SMALL MOBILE */
        @media(max-width:480px){
            .header{
                padding:12px 15px;
            }

            .header h1{
                font-size:20px;
            }

            .payment-form{
                padding:15px;
            }

            .submit-btn{
                padding:12px;
                font-size:14px;
            }
        }

        /* RESPONSIVE - LARGE SCREENS */
        @media(min-width:1400px){
            .payment-content{
                grid-template-columns:1fr 400px;
            }
        }
    </style>
<link rel="stylesheet" href="../assets/responsive.css">
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
    <div class="container">
        <div class="sidebar">
            <h2>
                <span class="material-symbols-outlined">storefront</span>
                Poultry Shop
            </h2>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><span class="material-symbols-outlined">dashboard</span> Dashboard</a></li>
                <li><a href="browse_products.php"><span class="material-symbols-outlined">shopping_bag</span> Browse Products</a></li>
                <li><a href="cart.php"><span class="material-symbols-outlined">shopping_cart</span> My Cart</a></li>
                <li><a href="view_order_history.php"><span class="material-symbols-outlined">history</span> Order History</a></li>
                <li><a href="make_payment.php" class="active"><span class="material-symbols-outlined">payments</span> Make Payment</a></li>
                <li><a href="logout.php" class="logout-btn"><span class="material-symbols-outlined">logout</span> Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="header">
                <div style="display:flex; align-items:center;">
                    <button class="menu-toggle" onclick="toggleSidebar()"><span class="material-symbols-outlined">menu</span></button>
                    <h1>Make Payment</h1>
                </div>
                <a href="view_order_history.php" class="back-btn">← Back to Orders</a>
            </div>

            <?php if (isset($success)): ?>
                <div class="success">
                    <span class="material-symbols-outlined" style="vertical-align:middle; margin-right:8px;">check_circle</span>
                    <?= htmlspecialchars($success) ?>
                </div>
                <div style="text-align:center; margin-top:30px;">
                    <button onclick="printReceipt(<?= $order_id ?>)" class="submit-btn" style="display:inline-block; width:auto; padding:15px 40px;">Print Receipt</button>
                </div>
            <?php else: ?>
                <?php if (isset($error)): ?>
                    <div class="error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if ($order['status'] !== 'Draft'): ?>
                    <div class="success">
                        <span class="material-symbols-outlined" style="vertical-align:middle; margin-right:8px;">check_circle</span>
                        <?php if ($order['payment_method'] === 'cod'): ?>
                            Order confirmed and pending approval.
                        <?php else: ?>
                            Payment already submitted on <?= date('F d, Y g:i A', strtotime($order['payment_date'])) ?>
                        <?php endif; ?>
                    </div>
                    <div style="text-align:center; margin-top:30px;">
                        <button onclick="printReceipt(<?= $order_id ?>)" class="submit-btn" style="display:inline-block; width:auto; padding:15px 40px;">Print Receipt</button>
                    </div>
                <?php else: ?>
                    <form method="POST" class="payment-content">
                        <div class="payment-form">
                            <h2>Payment Information</h2>
                            
                            <div class="order-info">
                                <div class="order-info-item">
                                    <span class="order-info-label">Order Number</span>
                                    <span class="order-info-value"><?= htmlspecialchars($order['order_number']) ?></span>
                                </div>
                                <div class="order-info-item">
                                    <span class="order-info-label" style="align-self: center;">Payment Method</span>
                                    <select name="payment_method" id="paymentMethodSelect" onchange="updatePaymentView()" style="background:rgba(255,255,255,0.1); color:#fff; border:1px solid rgba(255,255,255,0.2); padding:8px 12px; border-radius:8px; outline:none;">
                                        <option value="cod" <?= $order['payment_method'] == 'cod' ? 'selected' : '' ?> style="color:#000;">Cash on Delivery (COD)</option>
                                        <option value="gcash" <?= $order['payment_method'] == 'gcash' ? 'selected' : '' ?> style="color:#000;">GCash</option>
                                        <option value="bank_transfer" <?= $order['payment_method'] == 'bank_transfer' ? 'selected' : '' ?> style="color:#000;">Bank Transfer</option>
                                    </select>
                                </div>
                                <div class="order-info-item">
                                    <span class="order-info-label">Total Amount</span>
                                    <span class="order-info-value" style="color:var(--secondary); font-size:18px;">₱<?= number_format($order['total_amount'], 2) ?></span>
                                </div>
                            </div>

                            <!-- Dynamic Payment Instructions -->
                            <div id="instructionGcash" class="payment-info" style="display: <?= $order['payment_method'] == 'gcash' ? 'block' : 'none' ?>;">
                                <h3><span class="material-symbols-outlined">payments</span> GCash Payment Instructions</h3>
                                <p>Send your payment to GCash number: <strong>09123456789</strong></p>
                                <p>Account Name: <strong>Poultry Shop Admin</strong></p>
                                <p>Please enter the reference number from your GCash transaction below.</p>
                            </div>

                            <div id="instructionBank" class="payment-info" style="display: <?= $order['payment_method'] == 'bank_transfer' ? 'block' : 'none' ?>;">
                                <h3><span class="material-symbols-outlined">account_balance</span> Bank Transfer Instructions</h3>
                                <p>Bank: <strong>BPI</strong></p>
                                <p>Account Number: <strong>1234-5678-90</strong></p>
                                <p>Account Name: <strong>Poultry Shop Admin</strong></p>
                                <p>Please enter the reference number from your bank transaction below.</p>
                            </div>

                            <div id="instructionCod" class="payment-info" style="display: <?= $order['payment_method'] == 'cod' ? 'block' : 'none' ?>;">
                                <h3><span class="material-symbols-outlined">local_shipping</span> Cash on Delivery</h3>
                                <p>You will pay the full amount upon delivery of your order.</p>
                                <p>Please click 'Submit Payment' to confirm your order.</p>
                            </div>

                            <!-- Payment Inputs (Hidden for COD) -->
                            <div id="paymentInputs" style="display: <?= ($order['payment_method'] == 'gcash' || $order['payment_method'] == 'bank_transfer') ? 'block' : 'none' ?>;">
                                <div class="form-group">
                                    <label>Payment Amount *</label>
                                    <input type="number" id="paymentAmountInput" name="payment_amount" step="0.01" min="0" value="<?= ($order['payment_method'] == 'gcash' || $order['payment_method'] == 'bank_transfer') ? number_format($order['total_amount'], 2, '.', '') : '' ?>" <?= ($order['payment_method'] == 'gcash' || $order['payment_method'] == 'bank_transfer') ? 'required' : '' ?>>
                                </div>

                                <div class="form-group">
                                    <label>Reference Number *</label>
                                    <input type="text" id="referenceInput" name="payment_reference" placeholder="Enter transaction reference number" <?= ($order['payment_method'] == 'gcash' || $order['payment_method'] == 'bank_transfer') ? 'required' : '' ?>>
                                </div>
                            </div>

                            <button type="submit" class="submit-btn">Submit Payment</button>
                        </div>

                        <div class="order-summary">
                            <h2>Order Summary</h2>
                            <?php foreach ($order_items as $item): ?>
                                <div class="summary-item">
                                    <span class="summary-item-name"><?= htmlspecialchars($item['product_name']) ?> x <?= $item['quantity'] ?></span>
                                    <span class="summary-item-value">₱<?= number_format($item['total_price'], 2) ?></span>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="summary-item">
                                <span class="summary-item-name">Subtotal</span>
                                <span class="summary-item-value">₱<?= number_format($order['total_amount'], 2) ?></span>
                            </div>
                            
                            <div class="summary-item">
                                <span class="summary-item-name">Shipping</span>
                                <span class="summary-item-value">Free</span>
                            </div>
                            
                            <div class="summary-item summary-total">
                                <span class="summary-item-name">Total</span>
                                <span class="summary-item-value">₱<?= number_format($order['total_amount'], 2) ?></span>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function printReceipt(orderId) {
            window.open(`print_receipt.php?order_id=${orderId}`, '_blank');
        }

        function updatePaymentView() {
            const method = document.getElementById('paymentMethodSelect').value;
            
            // Instructions
            document.getElementById('instructionGcash').style.display = (method === 'gcash') ? 'block' : 'none';
            document.getElementById('instructionBank').style.display = (method === 'bank_transfer') ? 'block' : 'none';
            document.getElementById('instructionCod').style.display = (method === 'cod') ? 'block' : 'none';
            
            // Inputs
            const inputsContainer = document.getElementById('paymentInputs');
            const amountInput = document.getElementById('paymentAmountInput');
            const refInput = document.getElementById('referenceInput');
            
            if (method === 'gcash' || method === 'bank_transfer') {
                inputsContainer.style.display = 'block';
                amountInput.required = true;
                refInput.required = true;
                // Set amount back to total amount
                amountInput.value = '<?= number_format($order['total_amount'], 2, '.', '') ?>';
            } else {
                inputsContainer.style.display = 'none';
                amountInput.required = false;
                refInput.required = false;
                // Clear out amount visually when hidden
                amountInput.value = ''; 
            }
        }
    </script>
<script src="../assets/sidebar.js"></script>
</body>
</html>
