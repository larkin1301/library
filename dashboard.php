<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>

<h2>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</h2>
<p>You are logged in as: <?= htmlspecialchars($_SESSION['role']) ?></p>

<a href="logout.php">Logout</a>

<hr>

<h3>Navigation</h3>
<ul>
    <li><a href="profile.php">My Profile</a></li>
    <li><a href="resources.php">Browse Resources</a></li>

    <?php if ($_SESSION['role'] === 'admin'): ?>
        <li><a href="add_resource.php">Add New Resource</a></li>
        <li><a href="delete_resource.php">Delete Resource</a></li>
    <?php endif; ?>
</ul>

<hr>

<h3>Your Wishlist</h3>

<?php
$userId = $_SESSION['user_id'];

//fetch wishlist items
$stmt = $pdo->prepare("
    SELECT resources.title, resources.author 
    FROM wishlists
    JOIN resources ON wishlists.resource_id = resources.id
    WHERE wishlists.user_id = ?
    ORDER BY wishlists.created_at DESC
");
$stmt->execute([$userId]);
$wishlistItems = $stmt->fetchAll();

if (empty($wishlistItems)): ?>
    <p>You have no items in your wishlist.</p>
<?php else: ?>
    <ul>
    <?php foreach ($wishlistItems as $item): ?>
        <li><?= htmlspecialchars($item['title']) ?> by <?= htmlspecialchars($item['author']) ?></li>
    <?php endforeach; ?>
    </ul>
<?php endif; ?>
