<?php
require_once 'db_connect.php';

if (!isset($_GET['driver_id']) || !isset($_GET['date'])) {
    header("Location: index.php");
    exit();
}

$driver_id = $_GET['driver_id'];
$date = $_GET['date'];

// Validate date format
if (!preg_match("/^\d{4}-\d{1,2}-\d{1,2}$/", $date)) {
    header("Location: index.php");
    exit();
}

// Check if driver exists
$checkSql = "SELECT id FROM drivers WHERE id = ?";
$stmt = $conn->prepare($checkSql);
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: index.php");
    exit();
}

// Get rent amount based on date
function getRentForDate($conn, $date) {
    // Check if it's a special day
    $specialSql = "SELECT amount FROM rent_settings WHERE day_type = 'special' AND special_date = ?";
    $stmt = $conn->prepare($specialSql);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['amount'];
    }
    
    // Check if it's Friday
    $dayOfWeek = date('w', strtotime($date));
    if ($dayOfWeek == 5) { // 5 is Friday
        $fridaySql = "SELECT amount FROM rent_settings WHERE day_type = 'friday'";
        $result = $conn->query($fridaySql);
        if ($result->num_rows > 0) {
            return $result->fetch_assoc()['amount'];
        }
    }
    
    // Regular day
    $regularSql = "SELECT amount FROM rent_settings WHERE day_type = 'regular'";
    $result = $conn->query($regularSql);
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['amount'];
    }
    
    // Default if no settings found
    return 150.00;
}

// Check if attendance record already exists
$checkAttSql = "SELECT id, status FROM attendance WHERE driver_id = ? AND date = ?";
$stmt = $conn->prepare($checkAttSql);
$stmt->bind_param("is", $driver_id, $date);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Record exists, toggle status
    $row = $result->fetch_assoc();
    $newStatus = ($row['status'] == 'উপস্থিত') ? 'অনুপস্থিত' : 'উপস্থিত';
    
    // Calculate rent based on new status
    $rent = ($newStatus == 'উপস্থিত') ? getRentForDate($conn, $date) : 0;
    
    $updateSql = "UPDATE attendance SET status = ?, rent = ? WHERE id = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("sdi", $newStatus, $rent, $row['id']);
    $stmt->execute();
} else {
    // Record doesn't exist, create new with default 'উপস্থিত'
    $rent = getRentForDate($conn, $date);
    
    $insertSql = "INSERT INTO attendance (driver_id, date, status, rent) VALUES (?, ?, 'উপস্থিত', ?)";
    $stmt = $conn->prepare($insertSql);
    $stmt->bind_param("isd", $driver_id, $date, $rent);
    $stmt->execute();
}

// Redirect back to previous page
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
header("Location: $referer");
exit();
?>

