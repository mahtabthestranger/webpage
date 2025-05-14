<?php
/**
 * Database Connection Configuration
 * 
 * This file establishes a connection to the MySQL database for the Procheshtha Rickshaw application.
 * It creates the database if it doesn't exist and sets up proper character encoding for Bangla support.
 */

// Database configuration
$servername = "localhost";  // Database server
$username = "root";         // Database username
$password = "";             // Database password
$dbname = "procheshtha_rickshaw"; // Database name

// Error reporting - comment this out in production
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Function to handle connection errors
function handleConnectionError($connection, $message) {
    $error = $connection->connect_error;
    error_log("Database connection error: " . $error);
    die("কানেকশন ব্যর্থ হয়েছে: " . $error);
}

// Function to handle query errors
function handleQueryError($connection, $message) {
    $error = $connection->error;
    error_log("Database query error: " . $error);
    die($message . ": " . $error);
}

// Step 1: Create connection without database first
try {
    $conn_temp = new mysqli($servername, $username, $password);
    
    // Check connection
    if ($conn_temp->connect_error) {
        handleConnectionError($conn_temp, "Initial connection failed");
    }
    
    // Create database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if ($conn_temp->query($sql) !== TRUE) {
        handleQueryError($conn_temp, "ডাটাবেস তৈরি করতে ব্যর্থ হয়েছে");
    }
    
    $conn_temp->close();
} catch (Exception $e) {
    error_log("Exception during database creation: " . $e->getMessage());
    die("ডাটাবেস সংযোগে সমস্যা হয়েছে। অনুগ্রহ করে পরে আবার চেষ্টা করুন।");
}

// Step 2: Connect to the database
try {
    // Now connect with the database
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        handleConnectionError($conn, "Database connection failed");
    }
    
    // Set charset to utf8mb4 for Bangla support
    if (!$conn->set_charset("utf8mb4")) {
        handleQueryError($conn, "ক্যারেক্টার সেট সেট করতে ব্যর্থ হয়েছে");
    }
    
    // Set SQL mode to strict
    $conn->query("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
    
} catch (Exception $e) {
    error_log("Exception during database connection: " . $e->getMessage());
    die("ডাটাবেস সংযোগে সমস্যা হয়েছে। অনুগ্রহ করে পরে আবার চেষ্টা করুন।");
}

// Connection successful - $conn is now available for use in other files
?>


