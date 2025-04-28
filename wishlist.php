<?php
session_start();
require 'config/db.php';

//check for login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $userId = $_SESSION['user_id'];
    $resourceId = (int)$_GET['id'];

    if ($_GET['action'] === 'add') {
        //check if already in wishlist
        $stmt = $pdo->prepare("SELECT * FROM wishlists WHERE user_id = ? AND resource_id = ?");
        $stmt->execute([$userId, $resourceId]);
        if ($stmt->rowCount() == 0) {
            //if not in wishlist, add it
            $stmt = $pdo->prepare("INSERT INTO wishlists (user_id, resource_id) VALUES (?, ?)");
            $stmt->execute([$userId, $resourceId]);
        }
    } elseif ($_GET['action'] === 'remove') {
        //remove from wishlist
        $stmt = $pdo->prepare("DELETE FROM wishlists WHERE user_id = ? AND resource_id = ?");
        $stmt->execute([$userId, $resourceId]);
    }
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>
