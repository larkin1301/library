<?php
session_start();
require 'config/db.php'; 

$searchResults = [];

//form submission
if (isset($_GET['q'])) {
    $q = trim($_GET['q']);
    if ($q !== '') {
        $stmt = $pdo->prepare("SELECT * FROM resources WHERE title LIKE ?");
        $stmt->execute(["%$q%"]);
        $searchResults = $stmt->fetchAll();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Search Media</title>
</head>
<body>
    <h1>Search Media</h1>

    <!-- success/error messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <p style="color: green;"><?= $_SESSION['success_message'] ?></p>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <p style="color: red;"><?= $_SESSION['error_message'] ?></p>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    <!-- end of messages -->

    <form method="get" action="search.php">
        <input type="text" name="q" placeholder="Enter media title" value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>">
        <button type="submit">Search</button>
    </form>

    <?php if (isset($_GET['q'])): ?>
        <h2>Search Results for "<?= htmlspecialchars($_GET['q']) ?>"</h2>

        <?php if (count($searchResults) > 0): ?>
            <table border="1" cellpadding="5">
                <tr>
                    <th>Title</th>
                    <th>Format</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($searchResults as $resource): ?>
                    <tr>
                        <td><?= htmlspecialchars($resource['title']) ?></td>
                        <td><?= htmlspecialchars($resource['format']) ?></td>
                        <td><?= htmlspecialchars($resource['type']) ?></td>
                        <td><?= $resource['available'] ? 'Available' : 'Checked Out' ?></td>
                        <td>
                            <?php if ($resource['type'] === 'virtual' && $resource['file_path']): ?>
                                <a href="uploads/files/<?= htmlspecialchars($resource['file_path']) ?>" download>Download</a>
                            <?php elseif ($resource['type'] === 'physical'): ?>
                                <?php if ($resource['available']): ?>
                                    <a href="resources.php?checkout=<?= $resource['id'] ?>">Checkout</a>
                                <?php else: ?>
                                    Not available
                                <?php endif; ?>
                            <?php endif; ?>

                            <!-- wishlist button -->
                            <?php if (isset($_SESSION['user_id'])): ?><br>
                                <a href="resources.php?checkout=<?= $resource['id'] ?>">Add to Wishlist</a>
                            <?php else: ?><br>
                                <small>Login to wishlist</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No results found.</p>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>
