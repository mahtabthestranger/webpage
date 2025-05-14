<?php
require_once 'db_connect.php';

if (!isset($_GET['driver_id'])) {
    header("Location: index.php");
    exit();
}

$driver_id = $_GET['driver_id'];
$currentMonth = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$currentYear = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Validate month and year
if ($currentMonth < 1 || $currentMonth > 12) {
    $currentMonth = intval(date('m'));
}
if ($currentYear < 2023 || $currentYear > 2100) {
    $currentYear = intval(date('Y'));
}

// Get driver details
$sql = "SELECT d.*, r.rickshaw_number 
        FROM drivers d 
        JOIN rickshaws r ON d.rickshaw_id = r.id 
        WHERE d.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: index.php");
    exit();
}

$driver = $result->fetch_assoc();

// Get attendance data for this driver for the selected month
$attendanceSql = "SELECT date, status, rent FROM attendance 
                 WHERE driver_id = ? AND MONTH(date) = ? AND YEAR(date) = ?
                 ORDER BY date";
$stmt = $conn->prepare($attendanceSql);
$stmt->bind_param("iii", $driver_id, $currentMonth, $currentYear);
$stmt->execute();
$attendanceResult = $stmt->get_result();

$attendanceData = [];
$presentDays = 0;
$absentDays = 0;
$totalRent = 0;

while($row = $attendanceResult->fetch_assoc()) {
    $day = date('j', strtotime($row['date']));
    $attendanceData[$day] = [
        'status' => $row['status'],
        'rent' => $row['rent']
    ];
    
    if($row['status'] == 'উপস্থিত') {
        $presentDays++;
        $totalRent += $row['rent'];
    } else {
        $absentDays++;
    }
}

$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);
$totalRecordedDays = $presentDays + $absentDays;
$noRecordDays = $daysInMonth - $totalRecordedDays;

// Process form submission for updating rent
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_rent'])) {
    $day = $_POST['day'];
    $rent = $_POST['rent'];
    $date = "$currentYear-$currentMonth-$day";
    
    $updateSql = "UPDATE attendance SET rent = ? WHERE driver_id = ? AND date = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("dis", $rent, $driver_id, $date);
    
    if ($stmt->execute()) {
        // Refresh the page to show updated data
        header("Location: attendance.php?driver_id=$driver_id&month=$currentMonth&year=$currentYear");
        exit();
    }
}

// Get rent settings for reference
$regularSql = "SELECT amount FROM rent_settings WHERE day_type = 'regular'";
$result = $conn->query($regularSql);
$regularAmount = ($result->num_rows > 0) ? $result->fetch_assoc()['amount'] : 150.00;

$fridaySql = "SELECT amount FROM rent_settings WHERE day_type = 'friday'";
$result = $conn->query($fridaySql);
$fridayAmount = ($result->num_rows > 0) ? $result->fetch_assoc()['amount'] : 200.00;
?>

<!DOCTYPE html>
<html lang="bn" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ড্রাইভার উপস্থিতি - <?php echo $driver['name']; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #3f51b5;
            --secondary-color: #f50057;
            --success-color: #4caf50;
            --danger-color: #f44336;
            --warning-color: #ff9800;
            --info-color: #2196f3;
            --light-color: #f5f5f5;
            --dark-color: #212121;
        }
        
        body {
            font-family: 'SolaimanLipi', Arial, sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }
        
        .navbar {
            background-color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }
        
        .card-header {
            border-radius: 10px 10px 0 0 !important;
            font-weight: 600;
            padding: 15px 20px;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .attendance-btn {
            width: 40px;
            height: 40px;
            padding: 0;
            line-height: 40px;
            text-align: center;
            margin: 3px;
            font-size: 0.9rem;
            border-radius: 8px;
            transition: all 0.2s;
        }
        
        .attendance-btn:hover {
            transform: scale(1.1);
        }
        
        .present {
            background-color: var(--success-color);
            color: white;
            border: none;
        }
        
        .absent {
            background-color: var(--danger-color);
            color: white;
            border: none;
        }
        
        .no-record {
            background-color: var(--light-color);
            color: var(--dark-color);
            border: 1px dashed #ccc;
        }
        
        .summary-box {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        
        .summary-box:hover {
            transform: translateY(-3px);
        }
        
        .rent-display {
            font-size: 0.8rem;
            display: block;
            margin-top: 4px;
            font-weight: 600;
        }
        
        .table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .table thead th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            border: none;
        }
        
        .badge {
            padding: 6px 10px;
            font-weight: 500;
        }
        
        .btn-action {
            border-radius: 50px;
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .month-selector {
            background-color: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .form-select {
            border-radius: 8px;
            padding: 10px;
            border: 1px solid #ddd;
        }
        
        .form-select:focus {
            box-shadow: 0 0 0 0.25rem rgba(63, 81, 181, 0.25);
            border-color: var(--primary-color);
        }
        
        .modal-content {
            border-radius: 15px;
            border: none;
        }
        
        .modal-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 15px 15px 0 0;
        }
        
        .modal-footer {
            border-top: none;
        }
        
        /* Dark mode toggle */
        .theme-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            transition: all 0.3s;
        }
        
        .theme-toggle:hover {
            transform: scale(1.1);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .attendance-btn {
                width: 35px;
                height: 35px;
                line-height: 35px;
                font-size: 0.8rem;
                margin: 2px;
            }
            
            .card-body {
                padding: 15px;
            }
            
            .summary-box {
                padding: 15px 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-calendar-check me-2"></i>
                প্রচেষ্টা অটো রিকশা
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-house-door me-1"></i> ড্যাশবোর্ড
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="rent_settings.php">
                            <i class="bi bi-gear me-1"></i> ভাড়া সেটিংস
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        <!-- Page Title -->
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="text-center fw-bold">
                    <i class="bi bi-person-badge me-2"></i>
                    ড্রাইভার উপস্থিতি
                </h2>
                <p class="text-center text-muted">
                    <?php echo date('F Y', mktime(0, 0, 0, $currentMonth, 1, $currentYear)); ?>
                </p>
            </div>
        </div>
        
        <!-- Driver Info and Summary -->
        <div class="row mb-4">
            <!-- Driver Info -->
            <div class="col-md-6 mb-4 mb-md-0">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <i class="bi bi-person-circle me-2"></i>
                        ড্রাইভার তথ্য
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-4">
                            <div class="bg-light rounded-circle p-3 me-3">
                                <i class="bi bi-person-fill fs-1 text-primary"></i>
                            </div>
                            <div>
                                <h4 class="mb-0"><?php echo $driver['name']; ?></h4>
                                <p class="text-muted mb-0">রিকশা #<?php echo $driver['rickshaw_number']; ?></p>
                            </div>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-telephone-fill text-primary me-2"></i>
                                    <div>
                                        <small class="text-muted d-block">ফোন নম্বর</small>
                                        <span><?php echo $driver['phone']; ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-credit-card-2-front-fill text-primary me-2"></i>
                                    <div>
                                        <small class="text-muted d-block">এনআইডি</small>
                                        <span><?php echo $driver['nid']; ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-cash-stack text-primary me-2"></i>
                                    <div>
                                        <small class="text-muted d-block">ভাড়া</small>
                                        <span><?php echo $driver['rent']; ?> টাকা</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-calendar-date-fill text-primary me-2"></i>
                                    <div>
                                        <small class="text-muted d-block">যোগদানের তারিখ</small>
                                        <span><?php echo $driver['join_date']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Attendance Summary -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-info text-white">
                        <i class="bi bi-bar-chart-fill me-2"></i>
                        উপস্থিতি সারাংশ
                    </div>
                    <div class="card-body">
                        <div class="row g-3 mb-4">
                            <div class="col-6 col-md-3">
                                <div class="summary-box bg-success text-white">
                                    <h3><?php echo $presentDays; ?></h3>
                                    <p class="mb-0">উপস্থিত</p>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="summary-box bg-danger text-white">
                                    <h3><?php echo $absentDays; ?></h3>
                                    <p class="mb-0">অনুপস্থিত</p>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="summary-box bg-light">
                                    <h3><?php echo $noRecordDays; ?></h3>
                                    <p class="mb-0">রেকর্ড নেই</p>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="summary-box bg-warning">
                                    <h3><?php echo number_format($totalRent, 0); ?></h3>
                                    <p class="mb-0">মোট ভাড়া</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Month Selector -->
                        <form class="month-selector" method="get">
                            <input type="hidden" name="driver_id" value="<?php echo $driver_id; ?>">
                            <div class="row g-2">
                                <div class="col-md-5">
                                    <select name="month" class="form-select">
                                        <?php
                                        for($m=1; $m<=12; $m++) {
                                            $selected = ($m == $currentMonth) ? 'selected' : '';
                                            $monthName = date('F', mktime(0, 0, 0, $m, 1));
                                            echo "<option value='$m' $selected>$monthName</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <select name="year" class="form-select">
                                        <?php
                                        $startYear = 2023;
                                        $endYear = 2100;
                                        for($y=$startYear; $y<=$endYear; $y++) {
                                            $selected = ($y == $currentYear) ? 'selected' : '';
                                            echo "<option value='$y' $selected>$y</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-search me-1"></i> দেখুন
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Attendance Calendar -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-calendar-month me-2"></i>
                মাসিক উপস্থিতি ক্যালেন্ডার
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <div class="d-flex flex-wrap justify-content-center">
                        <?php
                        for($day = 1; $day <= $daysInMonth; $day++) {
                            $date = "$currentYear-$currentMonth-$day";
                            $btnClass = 'no-record';
                            $btnText = $day;
                            $status = '';
                            $rentDisplay = '';
                            
                            if(isset($attendanceData[$day])) {
                                if($attendanceData[$day]['status'] == 'উপস্থিত') {
                                    $btnClass = 'present';
                                    $status = 'উপস্থিত';
                                    $rentDisplay = "<span class='rent-display'>{$attendanceData[$day]['rent']} ৳</span>";
                                } else {
                                    $btnClass = 'absent';
                                    $status = 'অনুপস্থিত';
                                }
                            }
                            
                            echo "<div class='text-center m-1'>";
                            echo "<a href='mark_attendance.php?driver_id=$driver_id&date=$date&month=$currentMonth&year=$currentYear' 
                                    class='btn attendance-btn $btnClass' 
                                    title='$day - $status'>$btnText</a>";
                            
                            if(!empty($rentDisplay)) {
                                echo $rentDisplay;
                                echo "<a href='#' data-bs-toggle='modal' data-bs-target='#editRentModal' 
                                      data-day='$day' data-rent='{$attendanceData[$day]['rent']}' 
                                      class='badge bg-secondary mt-1 d-block'>Edit</a>";
                            }
                            echo "</div>";
                        }
                        ?>
                    </div>
                </div>
                
                <div class="mt-4">
                    <div class="d-flex justify-content-center align-items-center mb-3">
                        <span class="btn attendance-btn present me-2"></span>
                        <span class="me-3">উপস্থিত</span>
                        <span class="btn attendance-btn absent me-2"></span>
                        <span class="me-3">অনুপস্থিত</span>
                        <span class="btn attendance-btn no-record me-2"></span>
                        <span>রেকর্ড নেই</span>
                    </div>
                    <p class="text-center text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        উপস্থিতি পরিবর্তন করতে তারিখে ক্লিক করুন
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Daily Rent Table -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <i class="bi bi-cash-coin me-2"></i>
                দৈনিক ভাড়া বিবরণ
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>তারিখ</th>
                                <th>উপস্থিতি</th>
                                <th>ভাড়া (টাকা)</th>
                                <th>অ্যাকশন</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $monthlyTotal = 0;
                            
                            // Sort by date
                            ksort($attendanceData);
                            
                            foreach($attendanceData as $day => $data) {
                                $date = date('Y-m-d', mktime(0, 0, 0, $currentMonth, $day, $currentYear));
                                $formattedDate = date('d F Y', mktime(0, 0, 0, $currentMonth, $day, $currentYear));
                                $status = $data['status'];
                                $rent = $data['rent'];
                                
                                if($status == 'উপস্থিত') {
                                    $monthlyTotal += $rent;
                                }
                                
                                echo "<tr>";
                                echo "<td>$formattedDate</td>";
                                echo "<td>";
                                if($status == 'উপস্থিত'):
                                    echo "<span class='badge bg-success'><i class='bi bi-check-circle-fill me-1'></i> উপস্থিত</span>";
                                else:
                                    echo "<span class='badge bg-danger'><i class='bi bi-x-circle-fill me-1'></i> অনুপস্থিত</span>";
                                endif;
                                echo "</td>";
                                echo "<td>" . ($status == 'উপস্থিত' ? $rent . ' ৳' : '-') . "</td>";
                                echo "<td>";
                                if($status == 'উপস্থিত') {
                                    echo "<button type='button' class='btn btn-sm btn-warning edit-rent-btn' 
                                           data-bs-toggle='modal' data-bs-target='#editRentModal'
                                           data-day='$day' data-rent='$rent'>
                                           Edit Rent
                                          </button>";
                                }
                                echo "</td>";
                                echo "</tr>";
                            }
                            
                            if(count($attendanceData) == 0) {
                                echo "<tr><td colspan='4' class='text-center'>কোন রেকর্ড পাওয়া যায়নি</td></tr>";
                            } else {
                                echo "<tr class='table-info'>";
                                echo "<td colspan='2'><strong>মোট ভাড়া</strong></td>";
                                echo "<td colspan='2'><strong>$monthlyTotal ৳</strong></td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="index.php?month=<?php echo $currentMonth; ?>&year=<?php echo $currentYear; ?>" class="btn btn-secondary">ড্যাশবোর্ডে ফিরে যান</a>
            <a href="rent_settings.php" class="btn btn-primary">ভাড়া সেটিংস</a>
        </div>
    </div>
    
    <!-- Edit Rent Modal -->
    <div class="modal fade" id="editRentModal" tabindex="-1" aria-labelledby="editRentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editRentModalLabel">Edit Rent</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" name="update_rent" value="1">
                        <input type="hidden" id="edit_day" name="day" value="">
                        <div class="mb-3">
                            <label for="edit_rent" class="form-label">ভাড়া (টাকা)</label>
                            <input type="number" step="0.01" class="form-control" id="edit_rent" name="rent" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বাতিল</button>
                        <button type="submit" class="btn btn-primary">আপডেট করুন</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set up edit rent modal
        document.addEventListener('DOMContentLoaded', function() {
            const editRentModal = document.getElementById('editRentModal');
            if (editRentModal) {
                editRentModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const day = button.getAttribute('data-day');
                    const rent = button.getAttribute('data-rent');
                    
                    const dayInput = document.getElementById('edit_day');
                    const rentInput = document.getElementById('edit_rent');
                    
                    dayInput.value = day;
                    rentInput.value = rent;
                });
            }
        });
    </script>
</body>
</html>












