<?php
// Database connection file

$servername = "localhost"; // Update with your database server
$username = "roseline.tsatsu"; // Update with your MySQL username
$password = "Ladymodesty@2004"; // Update with your MySQL password
$dbname = "webtech_fall2024_roseline_tsatsu"; // Ensure this matches your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
