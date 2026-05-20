<?php
/**
 * helpers.php — Shared utility functions
 * Include this after connection.php in any admin page that changes order status.
 */

/**
 * Logs an order status change to the order_status_history table.
 * Replaces the MySQL trigger removed for InfinityFree shared hosting compatibility.
 *
 * @param mysqli $connection  Active DB connection
 * @param int    $order_id    The order ID
 * @param string $old_status  Previous status
 * @param string $new_status  New status being set
 * @param int    $admin_id    Admin performing the change
 * @param string $notes       Optional extra note
 */
function logOrderStatusChange($connection, $order_id, $old_status, $new_status, $admin_id = null, $notes = '') {
    if ($old_status === $new_status) return; // No change, skip

    $auto_note = "Status changed from $old_status to $new_status";
    if (!empty($notes)) {
        $auto_note .= " — $notes";
    }

    $stmt = mysqli_prepare($connection,
        "INSERT INTO order_status_history (order_id, status, notes, changed_by, created_at)
         VALUES (?, ?, ?, ?, NOW())"
    );
    mysqli_stmt_bind_param($stmt, "issi", $order_id, $new_status, $auto_note, $admin_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/**
 * Fetches the current status of an order.
 *
 * @param mysqli $connection
 * @param int    $order_id
 * @return string|null
 */
function getOrderStatus($connection, $order_id) {
    $stmt = mysqli_prepare($connection, "SELECT status FROM orders WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row ? $row['status'] : null;
}
?>
