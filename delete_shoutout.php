<?php
// Check if user is logged in as admin
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin.php");
    exit();
}

// Check if ID is provided
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    
    // Database configuration
    $dbHost = "localhost";
    $dbUsername = "root"; // Change this to your MySQL username
    $dbPassword = ""; // Change this to your MySQL password
    $dbName = "shoutout_db";
    
    // Create database connection
    $conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // First, get the photo filename to delete the file if exists
    $sql = "SELECT photo_filename FROM shoutouts WHERE id = $id";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (!empty($row['photo_filename'])) {
            $photoPath = 'uploads/' . $row['photo_filename'];
            if (file_exists($photoPath)) {
                unlink($photoPath); // Delete the photo file
            }
        }
    }
    
    // Delete the record
    $sql = "DELETE FROM shoutouts WHERE id = $id";
    if ($conn->query($sql) === TRUE) {
        header("Location: admin.php?status=deleted");
    } else {
        echo "Error deleting record: " . $conn->error;
    }
    
    $conn->close();
} else {
    header("Location: admin.php");
    exit();
}
?>