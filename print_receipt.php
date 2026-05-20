<?php
session_start();
require('../connection.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

/* GET ORDER */
$order_q = "SELECT * FROM orders WHERE id = ? AND customer_id = ?";
$stmt = mysqli_prepare($connection, $order_q);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $customer_id);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$order) {
    die("Order not found.");
}

/* GET ITEMS */
$items_q = "SELECT oi.*, ii.name as product_name 
            FROM order_items oi 
            JOIN inventory_items ii ON oi.product_id = ii.id 
            WHERE oi.order_id = ?";
$stmt = mysqli_prepare($connection, $items_q);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$items = mysqli_stmt_get_result($stmt);

/* GET CUSTOMER */
$cust_q = "SELECT * FROM customers WHERE id = ?";
$stmt = mysqli_prepare($connection, $cust_q);
mysqli_stmt_bind_param($stmt, "i", $customer_id);
mysqli_stmt_execute($stmt);
$customer = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt</title>

    <style>
    * {
        box-sizing: border-box;
    }

    body {
        font-family: Arial;
        margin: 0;
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        background: linear-gradient(135deg, #8b0f0f 0%, #0f172a 50%, #090e1a 100%);
        padding: 20px;
    }

    /* RECEIPT CARD */
    .receipt {
        width: 100%;
        max-width: 700px;
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

    /* HEADER */
    .header {
        text-align: center;
        border-bottom: 2px solid #eee;
        padding-bottom: 15px;
    }

    .header h2 {
        margin: 0;
    }

    /* INFO */
    .info {
        margin-top: 15px;
        font-size: 14px;
        line-height: 1.5;
    }

    /* TABLE */
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        font-size: 14px;
    }

    th, td {
        padding: 10px;
        border-bottom: 1px solid #eee;
        text-align: left;
        word-wrap: break-word;
    }

    /* TOTAL */
    .total {
        text-align: right;
        font-size: 18px;
        font-weight: bold;
        margin-top: 20px;
    }

    /* BUTTON */
    .btn {
        margin-top: 20px;
        padding: 10px 15px;
        background: #f63b3b;
        color: white;
        border: none;
        cursor: pointer;
        border-radius: 8px;
        width: 100%;
    }

    /* TABLET */
    @media (max-width: 1024px) {
        .receipt {
            padding: 18px;
        }

        table, th, td {
            font-size: 13px;
        }
    }

    /* MOBILE */
    @media (max-width: 480px) {
        body {
            padding: 10px;
        }

        .receipt {
            padding: 15px;
            border-radius: 8px;
        }

        .header h2 {
            font-size: 18px;
        }

        .total {
            font-size: 16px;
        }

        .btn {
            font-size: 14px;
            padding: 12px;
        }

        th, td {
            padding: 8px;
            font-size: 12px;
        }
    }

    @media print {
        .btn { display:none; }
        body { background: white; }
    }
    </style>

    <link rel="stylesheet" href="../assets/responsive.css">
</head>

<body>

<div class="receipt">

    <div class="header">
        <h2>POULTRY SHOP RECEIPT</h2>
        <p>Order #: <?= htmlspecialchars($order['order_number']) ?></p>
    </div>

    <div class="info">
        <p><strong>Customer:</strong> <?= htmlspecialchars($customer['fullname']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($customer['email']) ?></p>
        <p><strong>Delivery Address:</strong> <?= htmlspecialchars($order['delivery_address']) ?></p>
        <p><strong>Payment Method:</strong> <?= htmlspecialchars(strtoupper($order['payment_method'])) ?></p>
        <p><strong>Status:</strong> <?= htmlspecialchars($order['status']) ?></p>
        <p><strong>Date:</strong> <?= date('F d, Y h:i A', strtotime($order['created_at'])) ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
        </thead>

        <tbody>
            <?php while ($item = mysqli_fetch_assoc($items)): ?>
            <tr>
                <td><?= htmlspecialchars($item['product_name']) ?></td>
                <td><?= $item['quantity'] ?></td>
                <td>₱<?= number_format($item['unit_price'], 2) ?></td>
                <td>₱<?= number_format($item['total_price'], 2) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="total">
        TOTAL: ₱<?= number_format($order['total_amount'], 2) ?>
    </div>

    <div class="btn-container" data-html2canvas-ignore style="display:flex; gap:10px; margin-top:20px;">
        <a href="view_order_history.php" class="btn" style="text-align:center; text-decoration:none; background:#666; margin-top:0;">Back to Orders</a>
        <button class="btn" onclick="window.print()" style="margin-top:0;">Print Receipt</button>
        <button class="btn" onclick="downloadReceipt()" style="margin-top:0; background:#f59e0b;">Download Receipt</button>
    </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
function downloadReceipt() {
    const element = document.querySelector('.receipt');
    
    // Temporarily hide shadow for a cleaner image output
    element.style.boxShadow = 'none';
    
    html2canvas(element, {
        scale: 3, // Higher scale for high-quality, crisp text
        useCORS: true,
        backgroundColor: '#ffffff',
        logging: false
    }).then(canvas => {
        // Restore box shadow
        element.style.boxShadow = '';
        
        const link = document.createElement('a');
        link.download = 'Receipt_' + '<?= htmlspecialchars($order['order_number']) ?>' + '.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    }).catch(err => {
        element.style.boxShadow = '';
        console.error('Error generating image:', err);
    });
}
</script>
</body>
</html>