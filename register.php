<?php
session_start();
require_once 'config/db.php';

//form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'user'; // all public registrations are normal users

    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");

    try {
        $stmt->execute([$username, $password, $role]);
        echo "User registered! You can now <a href='login.php'>login</a>.";
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo "Error: That username is already taken. Please choose another.";
        } else {
            echo "Error: " . $e->getMessage();
        }
    }
}
?>

<h2>Register New Account</h2>
<form method="POST">
    <input type="text" name="username" placeholder="Username" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button type="submit">Register</button>
</form>
