<?php
include '../connection.php';

echo "<h2>Starting FIFO Batches Database Migration...</h2>";

// 1. Create stock_batches table
$create_table_query = "CREATE TABLE IF NOT EXISTS `stock_batches` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `quantity_added` INT NOT NULL,
    `remaining_quantity` INT NOT NULL,
    `cost_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `supplier` VARCHAR(255) NULL,
    `expiry_date` DATE NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `inventory_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (mysqli_query($connection, $create_table_query)) {
    echo "<p style='color: green;'>✔ Table 'stock_batches' checked/created successfully.</p>";
} else {
    die("<p style='color: red;'>❌ Error creating table 'stock_batches': " . mysqli_error($connection) . "</p>");
}

// 2. Check if we need to seed the table
$check_empty = mysqli_query($connection, "SELECT COUNT(*) as count FROM stock_batches");
$count = mysqli_fetch_assoc($check_empty)['count'];

if ($count == 0) {
    echo "<p>Migrating existing active inventory items into initial stock batches...</p>";
    
    $select_items = mysqli_query($connection, "SELECT id, current_quantity, selling_price, supplier, expiry_date, created_at FROM inventory_items WHERE current_quantity > 0");
    
    $seeded = 0;
    while ($row = mysqli_fetch_assoc($select_items)) {
        $product_id = intval($row['id']);
        $qty = intval($row['current_quantity']);
        $price = floatval($row['selling_price']);
        $supplier = $row['supplier'] ? "'" . mysqli_real_escape_string($connection, $row['supplier']) . "'" : "NULL";
        $expiry = $row['expiry_date'] ? "'" . mysqli_real_escape_string($connection, $row['expiry_date']) . "'" : "NULL";
        $created = $row['created_at'] ? "'" . mysqli_real_escape_string($connection, $row['created_at']) . "'" : "NOW()";
        
        $insert_batch = "INSERT INTO stock_batches (product_id, quantity_added, remaining_quantity, cost_price, supplier, expiry_date, created_at)
                         VALUES ($product_id, $qty, $qty, $price, $supplier, $expiry, $created)";
        
        if (mysqli_query($connection, $insert_batch)) {
            $seeded++;
        } else {
            echo "<p style='color: red;'>❌ Failed to seed batch for Product ID $product_id: " . mysqli_error($connection) . "</p>";
        }
    }
    
    echo "<p style='color: green;'>✔ Successfully migrated $seeded inventory items into active stock batches.</p>";
} else {
    echo "<p style='color: orange;'>⚠ Table 'stock_batches' already contains records. Skipping migration seeding.</p>";
}

echo "<h3 style='color: green;'>FIFO Database Migration Complete!</h3>";
?>
