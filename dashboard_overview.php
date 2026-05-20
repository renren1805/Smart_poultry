<?php
require('../connection.php');

// =====================
// ANALYTICS DATA
// =====================
$inventory_total = 0;
$low_stock_items = 0;
$out_of_stock_items = 0;
$inventory_value = 0;

try {

    // total inventory items
    $inventory_total = $connection->query("
        SELECT COUNT(*) as c FROM inventory_items
    ")->fetch_assoc()['c'] ?? 0;

    // low stock
    $low_stock_items = $connection->query("
        SELECT COUNT(*) as c 
        FROM inventory_items 
        WHERE current_quantity <= reorder_point AND current_quantity > 0
    ")->fetch_assoc()['c'] ?? 0;

    // out of stock
    $out_of_stock_items = $connection->query("
        SELECT COUNT(*) as c 
        FROM inventory_items 
        WHERE current_quantity = 0
    ")->fetch_assoc()['c'] ?? 0;

    // inventory value (estimated worth)
    $inventory_value = $connection->query("
        SELECT SUM(current_quantity * price) as total 
        FROM inventory_items
    ")->fetch_assoc()['total'] ?? 0;

} catch(Exception $e) {}
// =====================
// PAYMENT HANDLER
// =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_payment'])) {

    $order_number = trim($_POST['payment_order_number']);
    $payment_reference = trim($_POST['payment_reference']);
    $payment_amount = floatval($_POST['payment_amount']);

    $stmt = $connection->prepare("SELECT * FROM orders WHERE order_number=?");
    $stmt->bind_param("s", $order_number);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if ($order) {

        $stmt = $connection->prepare("SELECT SUM(total_price) as total FROM order_items WHERE order_id=?");
        $stmt->bind_param("i", $order['id']);
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

        // =====================
        // FAKE vs REAL DETECTION
        // =====================
        $is_suspicious = false;
        $reasons = [];

        if ($payment_amount <= 0) {
            $is_suspicious = true;
            $reasons[] = "Invalid amount";
        }

        if (abs($payment_amount - $total) > 0.01) {
            $is_suspicious = true;
            $reasons[] = "Amount mismatch";
        }

        if (strlen($payment_reference) < 6) {
            $is_suspicious = true;
            $reasons[] = "Weak reference";
        }

        if (!$is_suspicious) {
            $stmt = $connection->prepare("UPDATE orders SET status='Paid', payment_reference=?, payment_amount=?, payment_date=NOW() WHERE id=?");
            $stmt->bind_param("sdi", $payment_reference, $payment_amount, $order['id']);
            $stmt->execute();

            $success_message = "Payment verified successfully!";
        } else {
            $error_message = "⚠ Suspicious payment detected: " . implode(", ", $reasons);
        }
    } else {
        $error_message = "Order not found.";
    }
}
?>

<!-- ===================== STYLE ===================== -->
<style>
body {
    background: #050b1a;
    font-family: Arial;
    color: #fff;
}

.glass {
    background: rgba(255,255,255,0.05);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(0,140,255,0.2);
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 0 20px rgba(0,140,255,0.15);
}

.grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
}

.card {
    padding: 15px;
    border-radius: 12px;
    background: rgba(0,140,255,0.08);
    transition: 0.3s;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0 25px rgba(0,140,255,0.4);
}

input, button {
    padding: 10px;
    border-radius: 8px;
    border: none;
}

input {
    width: 100%;
    background: rgba(255,255,255,0.08);
    color: white;
}

button {
    background: #007bff;
    color: white;
    cursor: pointer;
    transition: 0.3s;
}

button:hover {
    background: #00a2ff;
}

.alert {
    padding: 10px;
    border-radius: 10px;
    margin-top: 10px;
}

.success { background: rgba(0,255,100,0.15); }
.error { background: rgba(255,0,80,0.15); }
</style>

<script>
// AUTO-FILL SIMULATION (real-time lookup)
document.getElementById("orderInput")?.addEventListener("blur", function() {
    let val = this.value;

    if(val.length > 5){
        fetch("?lookup_order="+val)
        .then(r=>r.json())
        .then(data=>{
            if(data.amount){
                document.querySelector("input[name='payment_amount']").value = data.amount;
            }
        });
    }
});

// PRINT RECEIPT
function printReceipt(order){
    let w = window.open();
    w.document.write(`
        <h2>Receipt</h2>
        <p>Order: ${order}</p>
        <p>Status: Paid</p>
        <button onclick="window.print()">Print</button>
    `);
}

// REAL-TIME ALERTS (simple polling)
setInterval(()=>{
    fetch("?alerts=1")
    .then(r=>r.json())
    .then(data=>{
        if(data.new_alert){
            alert("New Payment Update: " + data.new_alert);
        }
    });
},5000);
</script>

<?php
// AJAX ENDPOINTS (same file)
if(isset($_GET['lookup_order'])){
    $order_number = $_GET['lookup_order'];

    $stmt = $connection->prepare("
        SELECT SUM(total_price) as total 
        FROM order_items oi
        JOIN orders o ON o.id=oi.order_id
        WHERE o.order_number=?
    ");
    $stmt->bind_param("s",$order_number);
    $stmt->execute();

    echo json_encode($stmt->get_result()->fetch_assoc());
    exit;
}

if(isset($_GET['alerts'])){
    echo json_encode(["new_alert"=>""]);
    exit;
}
?>