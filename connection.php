<?php
// ============================================================
//  SMART CONNECTION — Auto-detects InfinityFree vs Localhost
// ============================================================

$is_live = ($_SERVER['HTTP_HOST'] !== 'localhost' && $_SERVER['HTTP_HOST'] !== '127.0.0.1');

if ($is_live) {
    // ---- INFINITYFREE (Production) -------------------------
    $host     = 'sql202.infinityfree.com';
    $user     = 'if0_41959273';
    $password = 'OocqPJUugjfRo';
    $database = 'if0_41959273_finals';
} else {
    // ---- LOCALHOST (Development) ---------------------------
    $host     = 'localhost';
    $user     = 'root';
    $password = '';
    $database = 'finals';
}

$connection = mysqli_connect($host, $user, $password, $database);

if (mysqli_connect_error()) {
    die("<div style='font-family:sans-serif;background:#fee2e2;color:#991b1b;padding:20px;margin:20px;border-radius:8px;'>
            <strong>Database Connection Error</strong><br>
            " . htmlspecialchars(mysqli_connect_error()) . "
         </div>");
}

// Set charset for full UTF-8 support (emojis, special chars)
mysqli_set_charset($connection, 'utf8mb4');

// GLOBALLY SET TIMEZONE FOR ENTIRE SYSTEM
// This ensures that all PHP date() functions output accurate local time
date_default_timezone_set('Asia/Manila');

// This ensures all MySQL NOW() and timestamp columns use the correct offset
mysqli_query($connection, "SET time_zone = '+08:00'");
?>
