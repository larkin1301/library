<?php
session_start();
require 'db.php'; // database connection

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check action and media id
if (isset($_GET['action']) && isset($_GET['id'])) {
    $userId = $_SESSION['user_id'];
    $resourceId = (int)$_GET['id'];

    if ($_GET['action'] === 'add') {
        // Check if already in wishlist
        $stmt = $pdo->prepare("SELECT * FROM wishlists WHERE user_id = ? AND resource_id = ?");
        $stmt->execute([$userId, $resourceId]);
        if ($stmt->rowCount() == 0) {
            // Not in wishlist, add it
            $stmt = $pdo->prepare("INSERT INTO wishlists (user_id, resource_id) VALUES (?, ?)");
            $stmt->execute([$userId, $resourceId]);
        }
    } elseif ($_GET['action'] === 'remove') {
        // Remove from wishlist
        $stmt = $pdo->prepare("DELETE FROM wishlists WHERE user_id = ? AND resource_id = ?");
        $stmt->execute([$userId, $resourceId]);
    }
}

// Redirect back
header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>
