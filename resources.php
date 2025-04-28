<?php
session_start();
require_once 'config/db.php';

//check for login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

//add to wishlist
if (isset($_GET['add_to_wishlist'])) {
    $resourceId = (int)$_GET['add_to_wishlist'];
    $stmt = $pdo->prepare("INSERT IGNORE INTO wishlists (user_id, resource_id, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$userId, $resourceId]);

    $_SESSION['success_message'] = "Added to wishlist!";
    header('Location: resources.php');
    exit();
}

//remove from wishlist
if (isset($_GET['remove_from_wishlist'])) {
    $resourceId = (int)$_GET['remove_from_wishlist'];
    $stmt = $pdo->prepare("DELETE FROM wishlists WHERE user_id = ? AND resource_id = ?");
    $stmt->execute([$userId, $resourceId]);

    $_SESSION['success_message'] = "Removed from wishlist!";
    header('Location: resources.php');
    exit();
}

//checkout
if (isset($_GET['checkout'])) {
    $resourceId = (int)$_GET['checkout'];

    $stmt = $pdo->prepare("SELECT * FROM resources WHERE id = ? AND available = 1");
    $stmt->execute([$resourceId]);
    $resource = $stmt->fetch();

    if ($resource) {
        $update = $pdo->prepare("UPDATE resources SET available = 0 WHERE id = ?");
        $update->execute([$resourceId]);

        $loan = $pdo->prepare("INSERT INTO loans (user_id, resource_id, checkout_date, return_date) VALUES (?, ?, NOW(), NULL)");
        $loan->execute([$userId, $resourceId]);

        $_SESSION['success_message'] = "You have successfully borrowed: " . htmlspecialchars($resource['title']);
    } else {
        $_SESSION['error_message'] = "Sorry, this media is not available.";
    }

    header("Location: resources.php");
    exit();
}

//return
if (isset($_GET['return'])) {
    $resource_id = (int)$_GET['return'];

    $stmt = $pdo->prepare("SELECT id FROM loans WHERE resource_id = ? AND return_date IS NULL ORDER BY checkout_date DESC LIMIT 1");
    $stmt->execute([$resource_id]);
    $loan = $stmt->fetch();

    if ($loan) {
        $stmt = $pdo->prepare("UPDATE loans SET return_date = NOW() WHERE id = ?");
        $stmt->execute([$loan['id']]);

        $stmt = $pdo->prepare("UPDATE resources SET available = 1 WHERE id = ?");
        $stmt->execute([$resource_id]);
    }

    header('Location: resources.php');
    exit;
}

//transfer
if (isset($_GET['transfer']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $resource_id = (int)$_GET['transfer'];

    $stmt = $pdo->prepare("SELECT * FROM resources WHERE id = ?");
    $stmt->execute([$resource_id]);
    $resource = $stmt->fetch();

    if (!$resource) {
        $_SESSION['error_message'] = "Resource not found.";
        header('Location: resources.php');
        exit();
    }

    if ($resource['type'] === 'virtual') {
        $_SESSION['error_message'] = "Cannot transfer virtual media.";
        header('Location: resources.php');
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_branch_id'])) {
        $newBranchId = (int)$_POST['new_branch_id'];

        $stmt = $pdo->prepare("UPDATE resources SET branch_id = ? WHERE id = ?");
        $stmt->execute([$newBranchId, $resource_id]);

        $_SESSION['success_message'] = "Resource transferred successfully!";
        header('Location: resources.php');
        exit();
    }

    $branchesStmt = $pdo->query("SELECT id, name FROM branches ORDER BY name ASC");
    $branches = $branchesStmt->fetchAll();
    ?>

    <h2>Transfer Resource: <?= htmlspecialchars($resource['title']) ?></h2>

    <form method="post" action="resources.php?transfer=<?= $resource_id ?>">
        <label>New Branch:</label>
        <select name="new_branch_id" required>
            <?php foreach ($branches as $branch): ?>
                <option value="<?= $branch['id'] ?>" <?= ($branch['id'] == $resource['branch_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($branch['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Transfer</button>
    </form>

    <br>
    <a href="resources.php">Cancel and return</a>

    <?php
    exit();
}

//search
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($searchTerm !== '') {
    $stmt = $pdo->prepare("
        SELECT resources.*, branches.name AS branch_name
        FROM resources
        LEFT JOIN branches ON resources.branch_id = branches.id
        WHERE resources.title LIKE ? OR resources.author LIKE ?
        ORDER BY resources.created_at DESC
    ");
    $stmt->execute(['%' . $searchTerm . '%', '%' . $searchTerm . '%']);
} else {
    $stmt = $pdo->query("
        SELECT resources.*, branches.name AS branch_name
        FROM resources
        LEFT JOIN branches ON resources.branch_id = branches.id
        ORDER BY resources.created_at DESC
    ");
}

$resources = $stmt->fetchAll();

//fetch user's wishlist
$stmt = $pdo->prepare("SELECT resource_id FROM wishlists WHERE user_id = ?");
$stmt->execute([$userId]);
$userWishlist = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<h2>Library Resources</h2>
<a href="dashboard.php">Return to Dashboard</a>

<form method="get" action="resources.php">
    <input type="text" name="search" placeholder="Search by Title or Author" value="<?= htmlspecialchars($searchTerm) ?>">
    <button type="submit">Search</button>
</form>
<br>

<?php if (!empty($_SESSION['success_message'])): ?>
    <p style="color: green;"><?= htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></p>
<?php endif; ?>

<?php if (!empty($_SESSION['error_message'])): ?>
    <p style="color: red;"><?= htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></p>
<?php endif; ?>

<table border="1" cellpadding="10" cellspacing="0">
    <thead>
        <tr>
            <th>Cover</th>
            <th>Title</th>
            <th>Author</th>
            <th>Format</th>
            <th>Type</th>
            <th>Branch</th>
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
                    <?php if ($resource['type'] === 'physical'): ?>
                        <?= htmlspecialchars($resource['branch_name'] ?? 'Unknown Branch') ?>
                    <?php else: ?>
                        Virtual Media
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($resource['type'] === 'virtual' && $resource['file_path']): ?>
                        <a href="uploads/files/<?= htmlspecialchars($resource['file_path']) ?>" download>Download</a><br>
                    <?php elseif ($resource['type'] === 'physical'): ?>
                        <?php if ($resource['available']): ?>
                            <a href="?checkout=<?= $resource['id'] ?>">Checkout</a><br>
                        <?php else: ?>
                            <a href="?return=<?= $resource['id'] ?>">Return</a><br>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if (in_array($resource['id'], $userWishlist)): ?>
                        <a href="?remove_from_wishlist=<?= $resource['id'] ?>">Remove from Wishlist</a><br>
                    <?php else: ?>
                        <a href="?add_to_wishlist=<?= $resource['id'] ?>">Add to Wishlist</a><br>
                    <?php endif; ?>

                    <?php if ($resource['type'] === 'physical' && isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="?transfer=<?= $resource['id'] ?>">Transfer Branch</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
