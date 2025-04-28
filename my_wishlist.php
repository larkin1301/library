<?php
session_start();
require 'db.php';

//check for login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

//fetch wishlist
$stmt = $pdo->prepare("
    SELECT resources.*
    FROM wishlists
    INNER JOIN resources ON wishlists.resource_id = resources.id
    WHERE wishlists.user_id = ?
");
$stmt->execute([$userId]);
$wishlistItems = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Wishlist</title>
</head>
<body>
    <h1>My Wishlist</h1>

    <?php if (count($wishlistItems) > 0): ?>
        <table border="1" cellpadding="5">
            <tr>
                <th>Title</th>
                <th>Format</th>
                <th>Type</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($wishlistItems as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['title']) ?></td>
                    <td><?= htmlspecialchars($item['format']) ?></td>
                    <td><?= htmlspecialchars($item['type']) ?></td>
                    <td><?= $item['available'] ? 'Available' : 'Checked Out' ?></td>
                    <td>
                        <!-- remove from wishlist -->
                        <a href="wishlist.php?action=remove&id=<?= $item['id'] ?>">❌ Remove</a>

                        <!-- borrow if available -->
                        <?php if ($item['type'] === 'virtual' && $item['file_path']): ?>
                            <br><a href="uploads/files/<?= htmlspecialchars($item['file_path']) ?>" download>Download</a>
                        <?php elseif ($item['type'] === 'physical' && $item['available']): ?>
                            <br><a href="resources.php?checkout=<?= $item['id'] ?>">Checkout</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>You have no items in your wishlist yet.</p>
    <?php endif; ?>

    <br>
    <a href="search.php">← Back to Search</a>
</body>
</html>
