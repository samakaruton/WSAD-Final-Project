<?php
// Database connection file
$dbserver = "localhost";
$user = "root";
$pass = "";
$dbas = "church_cmis";

try {
    $conn = mysqli_connect($dbserver, $user, $pass, $dbas);
    
    if (!$conn) {
        throw new mysqli_sql_exception("Connection failed: " . mysqli_connect_error());
    }
    
    // Set charset to utf8mb4 for better character support
    mysqli_set_charset($conn, "utf8mb4");
    
} catch (mysqli_sql_exception $e) {
    die('Could not Connect to Database: ' . $e->getMessage());
}

// Helper function to sanitize input
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data);
}

// Helper function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

// Helper function to check user role
function has_role($allowed_roles) {
    if (!is_logged_in()) {
        return false;
    }
    return in_array($_SESSION['role_name'], $allowed_roles);
}

// Helper function to redirect
function redirect($url) {
    header("Location: $url");
    exit();
}
?>