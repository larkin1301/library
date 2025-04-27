<?php
session_start();
require_once 'config/db.php';

//check for login and role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !=='admin') {
    echo "<h2>Access Denied</h2>";
    echo "<p>You must be an admin to access this page, redirecting to login...</p>";
        
    //wait 3 seconds and redirect
    header("refresh:3;url=login.php");
    exit;
}

//form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");

    try {
        $stmt->execute([$username, $password, $role]);
        echo "User registered!";
    } catch (PDOException $e){
        echo "Error: " . $e->getMessage();
    }
}
?>

<h2>Register User</h2>
<form method="POST">
    <input type="text" name="username" placeholder="Username" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <select name="role">
        <option value="user">User</option>
        <option value="admin">Admin</option>
    </select><br>
    <button type="submit">Register</button>
</form>