<?php
require_once 'db_connect.php';

// Get all available rickshaws
$sql = "SELECT r.id, r.rickshaw_number 
        FROM rickshaws r 
        LEFT JOIN drivers d ON r.id = d.rickshaw_id 
        WHERE d.id IS NULL";
$result = $conn->query($sql);

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $nid = $_POST['nid'];
    $rickshaw_id = $_POST['rickshaw_id'];
    $rent = $_POST['rent'];
    $join_date = $_POST['join_date'];
    
    $sql = "INSERT INTO drivers (name, phone, nid, rickshaw_id, rent, join_date) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssids", $name, $phone, $nid, $rickshaw_id, $rent, $join_date);
    
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
    <title>নতুন ড্রাইভার যোগ করুন</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <h1 class="text-center mb-4">নতুন ড্রাইভার যোগ করুন</h1>
        
        <?php if(isset($error)) { ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php } ?>
        
        <form method="post" action="">
            <div class="mb-3">
                <label for="name" class="form-label">নাম</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            
            <div class="mb-3">
                <label for="phone" class="form-label">ফোন নম্বর</label>
                <input type="text" class="form-control" id="phone" name="phone" required>
            </div>
            
            <div class="mb-3">
                <label for="nid" class="form-label">এনআইডি নম্বর</label>
                <input type="text" class="form-control" id="nid" name="nid" required>
            </div>
            
            <div class="mb-3">
                <label for="rickshaw_id" class="form-label">রিকশা নম্বর</label>
                <select class="form-control" id="rickshaw_id" name="rickshaw_id" required>
                    <option value="">রিকশা নির্বাচন করুন</option>
                    <?php
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $selected = "";
                            if(isset($_GET['rickshaw_id']) && $_GET['rickshaw_id'] == $row['id']) {
                                $selected = "selected";
                            }
                            echo "<option value='".$row["id"]."' ".$selected.">রিকশা #".$row["rickshaw_number"]."</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="rent" class="form-label">ভাড়া (টাকা)</label>
                <input type="number" class="form-control" id="rent" name="rent" required>
            </div>
            
            <div class="mb-3">
                <label for="join_date" class="form-label">যোগদানের তারিখ</label>
                <input type="date" class="form-control" id="join_date" name="join_date" required>
            </div>
            
            <button type="submit" class="btn btn-primary">সংরক্ষণ করুন</button>
            <a href="index.php" class="btn btn-secondary">বাতিল করুন</a>
        </form>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>