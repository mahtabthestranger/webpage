<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get all rickshaws with driver info
$sql = "SELECT r.id, r.rickshaw_number, r.status, 
               d.id as driver_id, d.name, d.phone, d.nid, d.rent, d.join_date 
        FROM rickshaws r 
        LEFT JOIN drivers d ON r.id = d.rickshaw_id 
        ORDER BY r.rickshaw_number";
$result = $conn->query($sql);

// Get current month and year
$currentMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
$currentYear = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Calculate total earnings for the month
$totalEarningsSql = "SELECT SUM(rent) as total_earnings 
                     FROM attendance 
                     WHERE MONTH(date) = ? AND YEAR(date) = ?";
$stmt = $conn->prepare($totalEarningsSql);
$stmt->bind_param("ii", $currentMonth, $currentYear);
$stmt->execute();
$earningsResult = $stmt->get_result();
$totalEarnings = 0;
if ($earningsResult->num_rows > 0) {
    $totalEarnings = $earningsResult->fetch_assoc()['total_earnings'] ?: 0;
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>প্রচেষ্টা অটো রিকশা ড্যাশবোর্ড</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --red-50: #fef2f2;
            --red-100: #fee2e2;
            --red-200: #fecaca;
            --red-300: #fca5a5;
            --red-400: #f87171;
            --red-500: #ef4444;
            --red-600: #dc2626;
            --red-700: #b91c1c;
            --red-800: #991b1b;
            --red-900: #7f1d1d;
            --red-950: #450a0a;
            
            --green-500: #22c55e;
            --green-600: #16a34a;
            --green-700: #15803d;
            
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --gray-950: #030712;
        }
        
        body {
            font-family: 'Noto Sans Bengali', 'SolaimanLipi', Arial, sans-serif;
            background-color: var(--red-50);
            color: var(--gray-800);
            padding-top: 70px;
        }
        
        /* Main Navbar - Fixed */
        .main-navbar {
            background-color: var(--red-600);
            box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.1), 0 2px 4px -1px rgba(220, 38, 38, 0.06);
            padding: 0.75rem 0;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1030;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: white;
        }
        
        .navbar-brand:hover {
            color: var(--red-100);
        }
        
        .user-welcome {
            color: white;
            font-weight: 600;
        }
        
        .logout-btn {
            background-color: white;
            color: var(--red-600);
            border: none;
            font-weight: 600;
            transition: all 0.2s;
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
        }
        
        .logout-btn:hover {
            background-color: var(--red-100);
            color: var(--red-700);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        /* Rickshaw Navbar - Sticky */
        .rickshaw-navbar {
            background-color: var(--red-500);
            padding: 0.75rem 0;
            margin-bottom: 2rem;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.1), 0 2px 4px -1px rgba(239, 68, 68, 0.06);
            overflow-x: auto;
            white-space: nowrap;
            scrollbar-width: thin;
            scrollbar-color: var(--red-400) var(--red-200);
            position: sticky;
            top: 70px;
            z-index: 1020;
        }
        
        .rickshaw-navbar::-webkit-scrollbar {
            height: 6px;
        }
        
        .rickshaw-navbar::-webkit-scrollbar-track {
            background: var(--red-200);
            border-radius: 0.5rem;
        }
        
        .rickshaw-navbar::-webkit-scrollbar-thumb {
            background-color: var(--red-400);
            border-radius: 0.5rem;
        }
        
        .rickshaw-nav {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .rickshaw-nav-item {
            margin: 0 0.25rem;
        }
        
        .rickshaw-nav-link {
            display: inline-block;
            padding: 0.5rem 1rem;
            color: white;
            text-decoration: none;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .rickshaw-nav-link:hover, .rickshaw-nav-link.active {
            background-color: white;
            color: var(--red-600);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        /* Page Header */
        .page-header {
            padding: 1.5rem 0;
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .page-header h1 {
            font-weight: 700;
            color: var(--red-600);
            margin-bottom: 0.5rem;
        }
        
        .page-header p {
            color: var(--gray-500);
            font-size: 1.1rem;
        }
        
        /* Action Buttons */
        .action-buttons {
            margin-bottom: 1.5rem;
        }
        
        .btn {
            border-radius: 0.5rem;
            padding: 0.625rem 1.25rem;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .btn-primary {
            background-color: var(--red-600);
            border-color: var(--red-600);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--red-700);
            border-color: var(--red-700);
        }
        
        .btn-success {
            background-color: var(--red-500);
            border-color: var(--red-500);
            color: white;
        }
        
        .btn-success:hover {
            background-color: var(--red-600);
            border-color: var(--red-600);
        }
        
        .btn-info {
            background-color: var(--red-100);
            border-color: var(--red-200);
            color: var(--red-700);
        }
        
        .btn-info:hover {
            background-color: var(--red-200);
            border-color: var(--red-300);
            color: var(--red-800);
        }
        
        .btn-secondary {
            background-color: var(--gray-500);
            border-color: var(--gray-500);
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: var(--gray-600);
            border-color: var(--gray-600);
        }
        
        .btn-warning {
            background-color: var(--red-300);
            border-color: var(--red-300);
            color: var(--red-900);
        }
        
        .btn-warning:hover {
            background-color: var(--red-400);
            border-color: var(--red-400);
        }
        
        .btn-danger {
            background-color: var(--red-600);
            border-color: var(--red-600);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: var(--red-700);
            border-color: var(--red-700);
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.2s;
            height: 100%;
            background-color: white;
            margin-bottom: 1.5rem;
        }
        
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .card-header {
            padding: 1rem 1.25rem;
            border: none;
            font-weight: 600;
        }
        
        .card-header-red {
            background-color: var(--red-600);
            color: white;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .card-body p {
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
        }
        
        .card-body p strong {
            min-width: 120px;
            display: inline-block;
            color: var(--red-700);
            font-weight: 600;
        }
        
        .btn-group {
            margin-top: 1rem;
            display: flex;
            gap: 0.5rem;
        }
        
        /* Attendance Calendar */
        .attendance-calendar {
            background-color: white;
            border-radius: 0.75rem;
            padding: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            margin-top: 1.25rem;
        }
        
        .attendance-calendar h6 {
            color: var(--red-600);
            font-weight: 600;
            margin-bottom: 0.75rem;
        }
        
        .attendance-btn {
            width: 35px;
            height: 35px;
            padding: 0;
            line-height: 35px;
            text-align: center;
            margin: 2px;
            font-size: 0.875rem;
            border-radius: 0.375rem;
            transition: all 0.2s;
        }
        
        .attendance-btn:hover {
            transform: scale(1.1);
        }
        
        .present {
            background-color: var(--green-600);
            color: white;
            border: none;
        }
        
        .absent {
            background-color: var(--red-600);
            color: white;
            border: none;
        }
        
        .no-record {
            background-color: var(--gray-100);
            color: var(--gray-500);
            border: 1px dashed var(--gray-300);
        }
        
        .rent-display {
            font-size: 0.75rem;
            display: block;
            margin-top: 2px;
            color: var(--red-600);
            font-weight: 600;
        }
        
        /* Month Selector */
        .month-selector {
            background-color: white;
            border-radius: 0.75rem;
            padding: 1.25rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            margin-bottom: 1.5rem;
        }
        
        .form-select {
            border-radius: 0.5rem;
            padding: 0.625rem 1rem;
            border: 1px solid var(--gray-300);
            font-weight: 500;
        }
        
        .form-select:focus {
            box-shadow: 0 0 0 0.25rem rgba(220, 38, 38, 0.25);
            border-color: var(--red-500);
        }
        
        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 0.75rem;
            margin-left: 0.5rem;
        }
        
        .status-active {
            background-color: var(--red-100);
            color: var(--red-800);
        }
        
        .status-inactive {
            background-color: var(--gray-200);
            color: var(--gray-700);
        }
        
        /* Icon Boxes */
        .icon-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background-color: var(--red-100);
            color: var(--red-600);
            border-radius: 9999px;
            margin-right: 0.75rem;
            font-size: 1rem;
        }
        
        /* Alert Boxes */
        .alert-warning {
            background-color: var(--red-100);
            border-color: var(--red-300);
            color: var(--red-800);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        /* Footer */
        .footer {
            background-color: var(--red-100);
            color: var(--red-800);
            padding: 1.5rem 0;
            text-align: center;
            margin-top: 3rem;
            border-top: 1px solid var(--red-200);
        }
        
        .footer p {
            margin-bottom: 0;
            font-weight: 500;
        }
        
        .developer-credit {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: var(--red-600);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .card-body {
                padding: 1rem;
            }
            
            .attendance-btn {
                width: 30px;
                height: 30px;
                line-height: 30px;
                font-size: 0.75rem;
            }
            
            .btn {
                padding: 0.5rem 1rem;
            }
            
            .btn-sm {
                padding: 0.25rem 0.75rem;
                font-size: 0.75rem;
            }
            
            .page-header {
                padding: 1.25rem 0;
            }
            
            .page-header h1 {
                font-size: 1.75rem;
            }
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.3s ease-out forwards;
        }
        
        /* Scroll to top button */
        .scroll-top {
            position: fixed;
            bottom: 1.25rem;
            right: 1.25rem;
            width: 40px;
            height: 40px;
            background-color: var(--red-600);
            color: white;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            transition: all 0.2s;
            z-index: 1000;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .scroll-top.show {
            opacity: 1;
        }
        
        .scroll-top:hover {
            background-color: var(--red-700);
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body>
    <!-- Main Navbar -->
    <nav class="navbar main-navbar">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                প্রচেষ্টা অটো রিকশা
            </a>
            <div class="ms-auto d-flex align-items-center">
                <span class="user-welcome me-3">স্বাগতম, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php" class="btn logout-btn btn-sm">
                    <i class="bi bi-box-arrow-right me-1"></i>
                    লগআউট
                </a>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">
        <!-- Page Header -->
        <div class="page-header fade-in">
            <h1>
                <i class="bi bi-speedometer2 me-2"></i>
                ড্যাশবোর্ড
            </h1>
            <p>রিকশা এবং ড্রাইভার ম্যানেজমেন্ট সিস্টেম</p>
        </div>
        
        <!-- Total Earnings Card -->
        <div class="card mb-3 fade-in">
            <div class="card-header card-header-red py-2">
                <h6 class="mb-0">
                    <i class="bi bi-cash-coin me-1"></i>
                    মাসিক মোট আয় (<?php echo date('F Y', mktime(0, 0, 0, $currentMonth, 1, $currentYear)); ?>)
                </h6>
            </div>
            <div class="card-body py-2">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="text-center text-success mb-0"><?php echo number_format($totalEarnings, 0); ?> টাকা</h5>
                    </div>
                    <div class="col-md-6">
                        <p class="text-muted mb-0 text-center text-md-end small">সকল রিকশা থেকে মোট আয়</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Rickshaw Navigation -->
        <div class="rickshaw-navbar fade-in">
            <div class="container">
                <ul class="rickshaw-nav">
                    <li class="rickshaw-nav-item">
                        <a href="#all" class="rickshaw-nav-link active">
                            <i class="bi bi-grid-3x3-gap-fill me-1"></i> সব রিকশা
                        </a>
                    </li>
                    <?php
                    // Get all rickshaw numbers for navigation
                    $rickshawSql = "SELECT id, rickshaw_number FROM rickshaws ORDER BY rickshaw_number";
                    $rickshawResult = $conn->query($rickshawSql);
                    
                    if ($rickshawResult->num_rows > 0) {
                        while($rickshaw = $rickshawResult->fetch_assoc()) {
                            echo '<li class="rickshaw-nav-item">';
                            echo '<a href="#rickshaw-' . $rickshaw["id"] . '" class="rickshaw-nav-link" onclick="scrollToRickshaw(' . $rickshaw["id"] . ')">';
                            echo '<i class="bi bi-truck me-1"></i> রিকশা #' . $rickshaw["rickshaw_number"];
                            echo '</a>';
                            echo '</li>';
                        }
                    }
                    ?>
                </ul>
            </div>
        </div>
        
        <!-- Action Buttons & Month Selector -->
        <div class="row mb-4 fade-in">
            <div class="col-md-6 mb-3 mb-md-0">
                <div class="action-buttons d-flex flex-wrap gap-2">
                    <a href="add_driver.php" class="btn btn-primary">
                        <i class="bi bi-person-plus-fill me-1"></i>
                        নতুন ড্রাইভার যোগ করুন
                    </a>
                    <a href="rent_settings.php" class="btn btn-success">
                        <i class="bi bi-gear-fill me-1"></i>
                        ভাড়া সেটিংস
                    </a>
                </div>
            </div>
            <div class="col-md-6">
                <form class="month-selector" method="get">
                    <div class="row g-2">
                        <div class="col-5">
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
                        <div class="col-4">
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
                        <div class="col-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search me-1"></i>
                                দেখুন
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Rickshaw Cards -->
        <div class="row fade-in">
            <?php
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
            ?>
                <div class="col-lg-4 col-md-6 mb-4" id="rickshaw-<?php echo $row["id"]; ?>">
                    <div class="card">
                        <div class="card-header card-header-red d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bi bi-truck me-2"></i>
                                রিকশা #<?php echo $row["rickshaw_number"]; ?>
                            </h5>
                            <span class="status-badge <?php echo ($row["status"] == 'Active') ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $row["status"]; ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <?php if($row["driver_id"]) { ?>
                                <p>
                                    <span class="icon-box"><i class="bi bi-person-fill"></i></span>
                                    <strong>ড্রাইভারের নাম:</strong> <?php echo $row["name"]; ?>
                                </p>
                                <p>
                                    <span class="icon-box"><i class="bi bi-telephone-fill"></i></span>
                                    <strong>ফোন নম্বর:</strong> <?php echo $row["phone"]; ?>
                                </p>
                                <p>
                                    <span class="icon-box"><i class="bi bi-card-text"></i></span>
                                    <strong>এনআইডি:</strong> <?php echo $row["nid"]; ?>
                                </p>
                                <p>
                                    <span class="icon-box"><i class="bi bi-cash"></i></span>
                                    <strong>ভাড়া:</strong> <?php echo $row["rent"]; ?> টাকা
                                </p>
                                <p>
                                    <span class="icon-box"><i class="bi bi-calendar-check"></i></span>
                                    <strong>যোগদানের তারিখ:</strong> <?php echo $row["join_date"]; ?>
                                </p>
                                
                                <div class="btn-group">
                                    <a href="edit_driver.php?id=<?php echo $row["driver_id"]; ?>" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil-fill me-1"></i> এডিট
                                    </a>
                                    <a href="remove_driver.php?id=<?php echo $row["driver_id"]; ?>" class="btn btn-sm btn-danger" onclick="return confirm('আপনি কি নিশ্চিত?');">
                                        <i class="bi bi-trash-fill me-1"></i> অপসারণ
                                    </a>
                                    <a href="attendance.php?driver_id=<?php echo $row["driver_id"]; ?>&month=<?php echo $currentMonth; ?>&year=<?php echo $currentYear; ?>" class="btn btn-sm btn-info">
                                        <i class="bi bi-calendar-week me-1"></i> উপস্থিতি
                                    </a>
                                </div>
                                
                                <!-- Attendance Calendar -->
                                <div class="attendance-calendar">
                                    <h6>
                                        <i class="bi bi-calendar3 me-2"></i>
                                        উপস্থিতি (<?php echo date('F Y', mktime(0, 0, 0, $currentMonth, 1, $currentYear)); ?>)
                                    </h6>
                                    <div class="d-flex flex-wrap justify-content-center">
                                        <?php
                                        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);
                                        
                                        // Get attendance data for this driver for the selected month
                                        $attendanceSql = "SELECT date, status, rent FROM attendance 
                                                         WHERE driver_id = ? AND MONTH(date) = ? AND YEAR(date) = ?";
                                        $stmt = $conn->prepare($attendanceSql);
                                        $stmt->bind_param("iii", $row["driver_id"], $currentMonth, $currentYear);
                                        $stmt->execute();
                                        $attendanceResult = $stmt->get_result();
                                        
                                        $attendanceData = [];
                                        while($attendance = $attendanceResult->fetch_assoc()) {
                                            $day = date('j', strtotime($attendance['date']));
                                            $attendanceData[$day] = [
                                                'status' => $attendance['status'],
                                                'rent' => $attendance['rent']
                                            ];
                                        }
                                        
                                        for($day = 1; $day <= $daysInMonth; $day++) {
                                            $btnClass = 'no-record';
                                            $btnText = $day;
                                            $rentDisplay = '';
                                            
                                            if(isset($attendanceData[$day])) {
                                                if($attendanceData[$day]['status'] == 'উপস্থিত') {
                                                    $btnClass = 'present';
                                                    $rentDisplay = "<span class='rent-display'>{$attendanceData[$day]['rent']} ৳</span>";
                                                } else {
                                                    $btnClass = 'absent';
                                                }
                                            }
                                            
                                            echo "<div class='text-center'>";
                                            echo "<a href='mark_attendance.php?driver_id={$row["driver_id"]}&date=$currentYear-$currentMonth-$day&redirect=index' 
                                                    class='btn attendance-btn $btnClass'>$btnText</a>";
                                            echo $rentDisplay;
                                            echo "</div>";
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php } else { ?>
                                <div class="text-center py-4">
                                    <div class="icon-box mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                                        <i class="bi bi-person-x-fill"></i>
                                    </div>
                                    <h5 class="text-gray-600 mb-3">কোন ড্রাইভার নিযুক্ত নেই</h5>
                                    <!-- Removed the "ড্রাইভার নিযুক্ত করুন" button -->
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            <?php
                }
            } else {
                echo "<div class='col-12'><div class='alert alert-warning'>কোন রিকশা পাওয়া যায়নি।</div></div>";
            }
            ?>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>© <?php echo date('Y'); ?> প্রচেষ্টা অটো রিকশা। সর্বস্বত্ব সংরক্ষিত।</p>
            <p class="developer-credit"> ©মাহতাব উদ্দিন আহমেদ।</p>
        </div>
    </footer>
    
    <!-- Scroll to top button -->
    <div class="scroll-top" id="scrollTop">
        <i class="bi bi-arrow-up"></i>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show/hide scroll to top button
        window.addEventListener('scroll', function() {
            const scrollTopBtn = document.getElementById('scrollTop');
            if (window.pageYOffset > 300) {
                scrollTopBtn.classList.add('show');
            } else {
                scrollTopBtn.classList.remove('show');
            }
        });

        // Scroll to top functionality
        document.getElementById('scrollTop').addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    </script>
</body>
</html>







