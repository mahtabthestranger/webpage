<?php
require_once 'db_connect.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$driver_id = $_GET['id'];

// Delete driver
$sql = "DELETE FROM drivers WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $driver_id);

if ($stmt->execute()) {
    header("Location: index.php");
    exit();
} else {
    echo "Error: " . $stmt->error;
}
$stmt->close();
$conn->close();
?>