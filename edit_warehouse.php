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
$message = '';

// Get warehouse ID from URL
$warehouseId = $_GET['id'] ?? 0;

// Fetch warehouse data
$stmt = $conn->prepare("SELECT * FROM warehouses WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $warehouseId, $userId);
$stmt->execute();
$result = $stmt->get_result();
$warehouse = $result->fetch_assoc();
$stmt->close();

if (!$warehouse) {
    header("Location: warehouses.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("UPDATE warehouses SET name=?, location=?, capacity=?, contact_info=?, warehouse_type=?, last_inspection_date=? WHERE id=? AND user_id=?");
    $stmt->bind_param("ssisssii", 
        $_POST['name'],
        $_POST['location'],
        $_POST['capacity'],
        $_POST['contact_info'],
        $_POST['warehouse_type'],
        $_POST['last_inspection_date'],
        $warehouseId,
        $userId
    );
    
    if ($stmt->execute()) {
        $message = '<div class="agri-alert agri-alert-success">Warehouse updated successfully!</div>';
        // Refresh warehouse data
        $stmt = $conn->prepare("SELECT * FROM warehouses WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $warehouseId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $warehouse = $result->fetch_assoc();
        $stmt->close();
    } else {
        $message = '<div class="agri-alert agri-alert-danger">Error updating warehouse: ' . $conn->error . '</div>';
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Warehouse - AGROMATI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/warehouses.css">
</head>
<body>
    <div class="agri-container">
        <!-- Sidebar Navigation -->
        <aside class="agri-sidebar">
            <div class="agri-sidebar-header">
                <h2>AGROMATI</h2>
                <p>Farmer Portal</p>
            </div>
            <nav class="agri-sidebar-nav">
                <ul>
                    <li class="agri-nav-item"><a href="dashboard.php" class="agri-nav-link"><i class="fas fa-tachometer-alt agri-nav-icon"></i> Dashboard</a></li>
                    <li class="agri-nav-item"><a href="warehouses.php" class="agri-nav-link"><i class="fas fa-warehouse agri-nav-icon"></i> Warehouses</a></li>
                    <!-- Other menu items -->
                    <li class="agri-nav-item"><a href="logout.php" class="agri-nav-link"><i class="fas fa-sign-out-alt agri-nav-icon"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="agri-main-content">
            <div class="agri-warehouse-container">
                <h1 class="agri-main-title"><i class="fas fa-warehouse"></i> Edit Warehouse</h1>
                
                <?php echo $message; ?>
                
                <div class="agri-form-card">
                    <form method="POST">
                        <div class="agri-form-group">
                            <label><i class="fas fa-warehouse"></i> Warehouse Name</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($warehouse['name']) ?>" required>
                        </div>
                        
                        <div class="agri-form-group">
                            <label><i class="fas fa-map-marker-alt"></i> Location</label>
                            <input type="text" name="location" value="<?= htmlspecialchars($warehouse['location']) ?>" required>
                        </div>
                        
                        <div class="agri-form-group">
                            <label><i class="fas fa-ruler-combined"></i> Capacity (sq ft)</label>
                            <input type="number" name="capacity" value="<?= $warehouse['capacity'] ?>" required>
                        </div>
                        
                        <div class="agri-form-group">
                            <label><i class="fas fa-phone"></i> Contact Info</label>
                            <input type="text" name="contact_info" value="<?= htmlspecialchars($warehouse['contact_info']) ?>">
                        </div>
                        
                        <div class="agri-form-group">
                            <label><i class="fas fa-tag"></i> Warehouse Type</label>
                            <select name="warehouse_type" class="agri-form-select">
                                <option value="">Select Type</option>
                                <option value="Dry Storage" <?= $warehouse['warehouse_type'] == 'Dry Storage' ? 'selected' : '' ?>>Dry Storage</option>
                                <option value="Refrigerated" <?= $warehouse['warehouse_type'] == 'Refrigerated' ? 'selected' : '' ?>>Refrigerated</option>
                                <option value="Frozen" <?= $warehouse['warehouse_type'] == 'Frozen' ? 'selected' : '' ?>>Frozen</option>
                                <option value="Controlled Atmosphere" <?= $warehouse['warehouse_type'] == 'Controlled Atmosphere' ? 'selected' : '' ?>>Controlled Atmosphere</option>
                            </select>
                        </div>
                        
                        <div class="agri-form-group">
                            <label><i class="fas fa-calendar-check"></i> Last Inspection Date</label>
                            <input type="date" name="last_inspection_date" value="<?= $warehouse['last_inspection_date'] ?>">
                        </div>
                        
                        <button type="submit" class="agri-btn agri-btn-success">
                            <i class="fas fa-save"></i> Update Warehouse
                        </button>
                        <a href="warehouses.php" class="agri-btn agri-btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="js/script.js"></script>
</body>
</html>