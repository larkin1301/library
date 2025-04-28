<?php
session_start();
require_once 'config/db.php';

//check for admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<h2>Access Denied</h2>";
    exit();
}

//check if delete request is made
if (isset($_GET['delete'])) {
    $resource_id = (int)$_GET['delete'];

    //check if resource exists and is available
    $stmt = $pdo->prepare("SELECT * FROM resources WHERE id = ?");
    $stmt->execute([$resource_id]);
    $resource = $stmt->fetch();

    if (!$resource) {
        $message = "Resource not found.";
    } elseif ($resource['available'] != 1) {
        $message = "Cannot delete: The resource is currently checked out.";
    } else {
        //delete resource
        $stmt = $pdo->prepare("DELETE FROM resources WHERE id = ?");
        if ($stmt->execute([$resource_id])) {
            $message = "Resource deleted successfully.";
        } else {
            $message = "Error deleting resource.";
        }
    }
}

//fetch all resources
$stmt = $pdo->query("SELECT * FROM resources ORDER BY created_at DESC");
$resources = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Resources</title>
</head>
<body>

<h2>Delete Available Resources</h2>

<?php if (!empty($message)): ?>
    <p style="color: red;"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<table border="1" cellpadding="10" cellspacing="0">
    <thead>
        <tr>
            <th>Title</th>
            <th>Author</th>
            <th>Type</th>
            <th>Format</th>
            <th>Available</th>
            <th>Branch ID</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($resources as $resource): ?>
        <tr>
            <td><?= htmlspecialchars($resource['title']) ?></td>
            <td><?= htmlspecialchars($resource['author']) ?></td>
            <td><?= htmlspecialchars($resource['type']) ?></td>
            <td><?= htmlspecialchars($resource['format']) ?></td>
            <td><?= $resource['available'] ? 'Yes' : 'No' ?></td>
            <td><?= htmlspecialchars($resource['branch_id']) ?></td>
            <td>
                <?php if ($resource['available'] == 1): ?>
                    <a href="delete_resource.php?delete=<?= $resource['id'] ?>" onclick="return confirm('Are you sure you want to delete this resource?');">Delete</a>
                <?php else: ?>
                    Not Available
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<br>
<a href="dashboard.php">Return to Dashboard</a>

</body>
</html>
