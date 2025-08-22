<?php
session_start();

$db_host = 'localhost';
$db_user = 'root'; 
$db_pass = '';
$db_name = 'agromatiDB';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = $_POST['product_id'];
    $name = $_POST['name'];
    $type = $_POST['type'];
    $unit = $_POST['unit_of_measure'];
    $seasonality = $_POST['seasonality'];
    $nutrition = $_POST['nutrition'];
    $per_unit_price = $_POST['per_unit_price'];
    $total_units = $_POST['total_units'];
    
    $stmt = $conn->prepare("UPDATE products SET name=?, type=?, unit_of_measure=?, seasonality=?, nutrition=?, per_unit_price=?, total_units=? WHERE id=? AND user_id=?");
    $stmt->bind_param("sssssddii", $name, $type, $unit, $seasonality, $nutrition, $per_unit_price, $total_units, $productId, $userId);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    
    header("Location: products.php");
    exit();
}
?>