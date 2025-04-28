<?php
session_start();
require_once 'config/db.php';

//check for login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

//checkout
if (isset($_GET['checkout'])) {
    $resourceId = (int)$_GET['checkout'];
    $userId = $_SESSION['user_id'];

    // Check if media is available
    $stmt = $pdo->prepare("SELECT * FROM resources WHERE id = ? AND available = 1");
    $stmt->execute([$resourceId]);
    $resource = $stmt->fetch();

    if ($resource) {
        // Mark media as checked out (available = 0)
        $update = $pdo->prepare("UPDATE resources SET available = 0 WHERE id = ?");
        $update->execute([$resourceId]);

        // Record loan in a loans table
        $loan = $pdo->prepare("INSERT INTO loans (user_id, resource_id, checkout_date, return_date) VALUES (?, ?, NOW(), DATE_ADD(NOW(), NULL))");
        $loan->execute([$userId, $resourceId]);

        // (Optional) send an email notification here...

        $_SESSION['success_message'] = "You have successfully borrowed: " . htmlspecialchars($resource['title']);
    } else {
        $_SESSION['error_message'] = "Sorry, this media is not available.";
    }

    // Redirect back to search or wishlist page
    header("Location: search.php");
    exit();
}

//return
if (isset($_GET['return'])) {
    $resource_id = (int)$_GET['return'];

    //latest loan
    $stmt = $pdo->prepare("SELECT id FROM loans WHERE resource_id = ? AND return_date IS NULL ORDER BY checkout_date DESC LIMIT 1");
    $stmt->execute([$resource_id]);
    $loan = $stmt->fetch();

    if ($loan) {
        //mark loan as returned
        $stmt = $pdo->prepare("UPDATE loans SET return_date = NOW() WHERE id = ?");
        $stmt->execute([$loan['id']]);

        //resource marked as available
        $stmt = $pdo->prepare("UPDATE resources SET available = 1 WHERE id = ?");
        $stmt->execute([$resource_id]);
    }

    header('Location: resources.php');
    exit;
}

//fetch all
$stmt = $pdo->query("SELECT * FROM resources ORDER BY created_at DESC");
$resources = $stmt->fetchAll();
?>

<h2>Library Resources</h2>
<a href="dashboard.php">Return to Dashboard</a>

<table border="1" cellpadding="10" cellspacing="0">
    <thead>
        <tr>
            <th>Cover</th>
            <th>Title</th>
            <th>Author</th>
            <th>Format</th>
            <th>Type</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($resources as $resource): ?>
            <tr>
                <td>
                    <?php if ($resource['cover_image']): ?>
                        <img src="uploads/covers/<?= htmlspecialchars($resource['cover_image']) ?>" alt="Cover Image" width="60" height="90">
                    <?php else: ?>
                        No Cover Found
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($resource['title']) ?></td>
                <td><?= htmlspecialchars($resource['author']) ?></td>
                <td><?= htmlspecialchars($resource['format']) ?></td>
                <td><?= htmlspecialchars(ucfirst($resource['type'])) ?></td>
                <td>
                    <?php if ($resource['type'] === 'virtual' && $resource['file_path']): ?>
                        <a href="uploads/files/<?= htmlspecialchars($resource['file_path']) ?>" download>Download</a>
                    <?php elseif ($resource['type'] === 'physical'): ?>
                        <?php if ($resource['available']): ?>
                            <a href="?checkout=<?= $resource['id'] ?>">Checkout</a>
                        <?php else: ?>
                            <a href="?return=<?= $resource['id'] ?>">Return</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>