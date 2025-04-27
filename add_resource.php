<?php
session_start();
require_once 'config/db.php';

//check role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !=='admin') {
    echo "<h2>Access Denied</h2>";
    echo "<p>You must be an admin to access this page, redirecting to login...</p>";
        
    //wait 3 seconds and redirect
    header("refresh:3;url=login.php");
    exit;
}

//form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $type = $_POST['type'];
    $format = trim($POST['format']);

    $cover_image = null;
    $file_path = null;

    //upload for cover image
    if (!empty($_FILES['cover_image']['name'])) {
        $cover_dir = 'uploads/covers/';
        $cover_image = time() . '_' . basename($_FILES['cover_image']['name']);
        move_uploaded_file($_FILES['cover_image']['tmp_name'], $cover_dir . $cover_image);
    }

    //upload for virtual media, if resource is audio book, ebook etc.
    if ($type === 'virtual' && !empty($_FILES['file']['name'])) {
        $file_dir = 'uploads/files/';
        $file_path = time() . '_' . basename($_FILES['file']['name']);
        move_uploaded_file($_FILES['file']['tmp_name'], $file_dir . $file_path);
    }

    $stmt = $pdo->prepare("INSERT INTO resources (title, author, type, format, cover_image, file_path) VALUES (?, ?, ?, ?, ?, ?)");

    try {
        $stmt->execute([$title, $author, $type, $format, $cover_image, $file_path]);
        echo "Resource sucessfully added!";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>

<h2>Add New Resource</h2>
<form method="POST" enctype="multipart/form-data">
    <input type="text" name="title" placeholder="Title" required><br>
    <input type="text" name="author" placeholder="Author" required><br>

    <label>Type:</label>
    <select name="type" required>
        <option value="physical">Physical</option>
        <option value="virtual">Virtual</option>
    </select><br>

    <input type="text" name="format" placeholder="Format (e.g., Book, DVD, EBook)" required><br>

    <label>Cover Image (Optional):</label>
    <input type="file" name="cover_image" accept="image/*"><br>

    <label>File Upload (for virtual media only):</label>
    <input type="file" name="file" accept=".pdf,.mp3,.mp4,.epub"><br>

    <button type="submit">Add Resource</button>
</form>