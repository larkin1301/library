<?php
session_start();
require_once 'config/db.php';

//check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

//fetch current user details
$stmt = $pdo->prepare("SELECT username, role FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

//update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newUsername = trim($_POST['username']);
    $newPassword = trim($_POST['password']);

    if (!empty($newUsername)) {
        $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
        $stmt->execute([$newUsername, $userId]);
        $_SESSION['username'] = $newUsername; //update session value
        $successMessage = "Username updated successfully!";
    }

    if (!empty($newPassword)) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $userId]);
        $successMessage = "Password updated successfully!";
    }

    header("Location: profile.php");
    exit();
}
?>

<h2>My Profile</h2>
<a href="dashboard.php">Return to Dashboard</a>
<p><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
<p><strong>Role:</strong> <?= htmlspecialchars($user['role']) ?></p>

<?php if (!empty($successMessage)): ?>
    <p style="color: green;"><?= htmlspecialchars($successMessage) ?></p>
<?php endif; ?>

<h3>Update Account</h3>
<form method="POST">
    <label>New Username:</label><br>
    <input type="text" name="username" placeholder="New Username"><br><br>

    <label>New Password:</label><br>
    <input type="password" name="password" placeholder="New Password"><br><br>

    <button type="submit">Update</button>
</form>

<br>

