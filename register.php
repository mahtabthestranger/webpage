<?php
session_start();
require_once 'db_connect.php';

// Check if user is already logged in
if(isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

// Process registration form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    if(empty($username) || empty($password) || empty($confirm_password)) {
        $error = "সব ঘর পূরণ করুন";
    } elseif($password != $confirm_password) {
        $error = "পাসওয়ার্ড মিলছে না";
    } else {
        // Check if username already exists
        $sql = "SELECT id FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            $error = "ইউজারনেম ইতিমধ্যে ব্যবহৃত হয়েছে";
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $username, $hashed_password);
            
            if($stmt->execute()) {
                $success = "রেজিস্ট্রেশন সফল হয়েছে। এখন লগইন করুন।";
            } else {
                $error = "রেজিস্ট্রেশন ব্যর্থ হয়েছে: " . $stmt->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>রেজিস্ট্রেশন - প্রচেষ্টা অটো রিকশা</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'SolaimanLipi', Arial, sans-serif;
            background-color: #fff5f5;
        }
        .register-container {
            max-width: 400px;
            margin: 80px auto;
        }
        .card {
            border-color: #dc3545;
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.2);
        }
        .card-header {
            background-color: #dc3545;
            color: white;
            font-weight: bold;
            padding: 15px;
        }
        .btn-primary {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .btn-primary:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
        .btn-link {
            color: #dc3545;
        }
        .btn-link:hover {
            color: #c82333;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #dc3545;
            font-size: 0.9rem;
        }
        .app-title {
            font-size: 1.8rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <div class="card">
                <div class="card-header text-center">
                    <span class="app-title">প্রচেষ্টা অটো রিকশা</span>
                </div>
                <div class="card-body p-4">
                    <h4 class="text-center mb-4">রেজিস্ট্রেশন করুন</h4>
                    
                    <?php if(!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if(!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label">ইউজারনেম</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">পাসওয়ার্ড</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">পাসওয়ার্ড নিশ্চিত করুন</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">রেজিস্ট্রেশন</button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-3">
                        <a href="login.php" class="btn btn-link">ইতিমধ্যে অ্যাকাউন্ট আছে? লগইন করুন</a>
                    </div>
                </div>
            </div>
            
            <div class="footer">
                <p>Developed by Mahtab Uddin Ahmed</p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>