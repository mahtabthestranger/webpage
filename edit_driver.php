<?php
require_once 'db_connect.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$driver_id = $_GET['id'];

// Get driver details
$sql = "SELECT * FROM drivers WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: index.php");
    exit();
}

$driver = $result->fetch_assoc();

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $nid = $_POST['nid'];
    $rent = $_POST['rent'];
    
    $sql = "UPDATE drivers SET name = ?, phone = ?, nid = ?, rent = ? WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssdi", $name, $phone, $nid, $rent, $driver_id);
    
    if ($stmt->execute()) {
        header("Location: index.php");
        exit();
    } else {
        $error = "Error: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Driver</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <h1 class="text-center mb-4">Edit Driver</h1>
        
        <?php if(isset($error)) { ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php } ?>
        
        <form method="post" action="">
            <div class="mb-3">
                <label for="name" class="form-label">নাম</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo $driver['name']; ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="phone" class="form-label">ফোন নম্বর</label>
                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo $driver['phone']; ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="nid" class="form-label">এনআইডি নম্বর</label>
                <input type="text" class="form-control" id="nid" name="nid" value="<?php echo $driver['nid']; ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="rent" class="form-label">ভাড়া (টাকা)</label>
                <input type="number" class="form-control" id="rent" name="rent" value="<?php echo $driver['rent']; ?>" required>
            </div>
            
            <button type="submit" class="btn btn-primary">আপডেট করুন</button>
            <a href="index.php" class="btn btn-secondary">বাতিল করুন</a>
        </form>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
