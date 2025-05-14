<?php
require_once 'db_connect.php';

// Process form submission for adding/updating rent settings
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] == 'update_regular') {
        $amount = $_POST['regular_amount'];
        $sql = "UPDATE rent_settings SET amount = ? WHERE day_type = 'regular'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("d", $amount);
        $stmt->execute();
    } 
    else if ($_POST['action'] == 'update_friday') {
        $amount = $_POST['friday_amount'];
        $sql = "UPDATE rent_settings SET amount = ? WHERE day_type = 'friday'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("d", $amount);
        $stmt->execute();
    }
    else if ($_POST['action'] == 'add_special') {
        $date = $_POST['special_date'];
        $name = $_POST['special_name'];
        $amount = $_POST['special_amount'];
        
        // Check if special date already exists
        $checkSql = "SELECT id FROM rent_settings WHERE day_type = 'special' AND special_date = ?";
        $stmt = $conn->prepare($checkSql);
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing special date
            $row = $result->fetch_assoc();
            $sql = "UPDATE rent_settings SET special_name = ?, amount = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sdi", $name, $amount, $row['id']);
        } else {
            // Insert new special date
            $sql = "INSERT INTO rent_settings (day_type, special_date, special_name, amount) VALUES ('special', ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssd", $date, $name, $amount);
        }
        $stmt->execute();
    }
    else if ($_POST['action'] == 'delete_special' && isset($_POST['special_id'])) {
        $id = $_POST['special_id'];
        $sql = "DELETE FROM rent_settings WHERE id = ? AND day_type = 'special'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
    
    // Redirect to avoid form resubmission
    header("Location: rent_settings.php");
    exit();
}

// Get regular rent setting
$regularSql = "SELECT amount FROM rent_settings WHERE day_type = 'regular'";
$result = $conn->query($regularSql);
$regularAmount = ($result->num_rows > 0) ? $result->fetch_assoc()['amount'] : 150.00;

// Get Friday rent setting
$fridaySql = "SELECT amount FROM rent_settings WHERE day_type = 'friday'";
$result = $conn->query($fridaySql);
$fridayAmount = ($result->num_rows > 0) ? $result->fetch_assoc()['amount'] : 200.00;

// Get special day settings
$specialSql = "SELECT id, special_date, special_name, amount FROM rent_settings 
               WHERE day_type = 'special' ORDER BY special_date DESC";
$specialResult = $conn->query($specialSql);
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ভাড়া সেটিংস</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'SolaimanLipi', Arial, sans-serif;
            background-color: #fff5f5;
        }
        .card {
            border-color: #dc3545;
            box-shadow: 0 2px 5px rgba(220, 53, 69, 0.2);
        }
        .btn-primary {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .btn-primary:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
        .btn-success {
            background-color: #e05d6f;
            border-color: #e05d6f;
        }
        .btn-success:hover {
            background-color: #d54d5f;
            border-color: #d54d5f;
        }
        .btn-info {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        .btn-info:hover {
            background-color: #f5c6cb;
            border-color: #f1b0b7;
            color: #721c24;
        }
        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }
        .card-header {
            font-weight: bold;
        }
        .bg-primary {
            background-color: #dc3545 !important;
        }
        .bg-success {
            background-color: #e05d6f !important;
        }
        .bg-info {
            background-color: #f8d7da !important;
            color: #721c24 !important;
        }
        .text-white {
            color: white !important;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1 class="text-center mb-4 text-danger">ভাড়া সেটিংস</h1>
        
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">নিয়মিত দিনের ভাড়া</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <input type="hidden" name="action" value="update_regular">
                            <div class="mb-3">
                                <label for="regular_amount" class="form-label">ভাড়া (টাকা)</label>
                                <input type="number" step="0.01" class="form-control" id="regular_amount" name="regular_amount" value="<?php echo $regularAmount; ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary">আপডেট করুন</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">শুক্রবারের ভাড়া</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <input type="hidden" name="action" value="update_friday">
                            <div class="mb-3">
                                <label for="friday_amount" class="form-label">ভাড়া (টাকা)</label>
                                <input type="number" step="0.01" class="form-control" id="friday_amount" name="friday_amount" value="<?php echo $fridayAmount; ?>" required>
                            </div>
                            <button type="submit" class="btn btn-success">আপডেট করুন</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-info">
                <h5 class="mb-0">বিশেষ দিনের ভাড়া</h5>
            </div>
            <div class="card-body">
                <form method="post" action="" class="mb-4">
                    <input type="hidden" name="action" value="add_special">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="special_date" class="form-label">তারিখ</label>
                            <input type="date" class="form-control" id="special_date" name="special_date" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="special_name" class="form-label">বিশেষ দিনের নাম</label>
                            <input type="text" class="form-control" id="special_name" name="special_name" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="special_amount" class="form-label">ভাড়া (টাকা)</label>
                            <input type="number" step="0.01" class="form-control" id="special_amount" name="special_amount" required>
                        </div>
                        <div class="col-md-2 mb-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-info w-100">যোগ করুন</button>
                        </div>
                    </div>
                </form>
                
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="table-danger">
                            <tr>
                                <th>তারিখ</th>
                                <th>বিশেষ দিনের নাম</th>
                                <th>ভাড়া (টাকা)</th>
                                <th>অ্যাকশন</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($specialResult->num_rows > 0) {
                                while($row = $specialResult->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . date('Y-m-d', strtotime($row['special_date'])) . "</td>";
                                    echo "<td>" . $row['special_name'] . "</td>";
                                    echo "<td>" . $row['amount'] . "</td>";
                                    echo "<td>
                                            <form method='post' action='' style='display:inline;'>
                                                <input type='hidden' name='action' value='delete_special'>
                                                <input type='hidden' name='special_id' value='" . $row['id'] . "'>
                                                <button type='submit' class='btn btn-sm btn-danger' onclick='return confirm(\"আপনি কি নিশ্চিত?\");'>মুছুন</button>
                                            </form>
                                          </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4' class='text-center'>কোন বিশেষ দিন যোগ করা হয়নি</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="text-center mb-4">
            <a href="index.php" class="btn btn-secondary">ড্যাশবোর্ডে ফিরে যান</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

