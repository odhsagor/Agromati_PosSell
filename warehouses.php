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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add warehouse
    if (isset($_POST['add_warehouse'])) {
        $stmt = $conn->prepare("INSERT INTO warehouses (user_id, name, location, capacity, contact_info, warehouse_type, last_inspection_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ississs", 
            $userId,
            $_POST['name'],
            $_POST['location'],
            $_POST['capacity'],
            $_POST['contact_info'],
            $_POST['warehouse_type'],
            $_POST['last_inspection_date']
        );
        
        if ($stmt->execute()) {
            $message = '<div class="agri-alert agri-alert-success">Warehouse added successfully!</div>';
        } else {
            $message = '<div class="agri-alert agri-alert-danger">Error adding warehouse: ' . $conn->error . '</div>';
        }
        $stmt->close();
    }
    // Delete 
    elseif (isset($_POST['delete_warehouse'])) {
        $stmt = $conn->prepare("DELETE FROM warehouses WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $_POST['warehouse_id'], $userId);
        
        if ($stmt->execute()) {
            $message = '<div class="agri-alert agri-alert-success">Warehouse deleted successfully!</div>';
        } else {
            $message = '<div class="agri-alert agri-alert-danger">Error deleting warehouse: ' . $conn->error . '</div>';
        }
        $stmt->close();
    }
}


$stmt = $conn->prepare("SELECT * FROM warehouses WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$warehouses = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warehouse Management - AGROMATI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/warehouses.css">
</head>
<body>
    <div class="agri-container">
        <aside class="agri-sidebar">
            <div class="agri-sidebar-header">
                <h2>AGROMATI</h2>
                <p>Farmer Portal</p>
            </div>
            <nav class="agri-sidebar-nav">
                <ul>
                    <li class="agri-nav-item" data-page="dashboard">
                        <a href="dashboard.php" class="agri-nav-link">
                            <i class="fas fa-tachometer-alt agri-nav-icon"></i> 
                            Dashboard
                        </a>
                    </li>
                    <li class="agri-nav-item" data-page="harvests">
                        <a href="harvests.php" class="agri-nav-link">
                            <i class="fas fa-seedling agri-nav-icon"></i> 
                            My Harvests
                        </a>
                    </li>
                    <li class="agri-nav-item " data-page="products">
                        <a href="products.php" class="agri-nav-link">
                            <i class="fas fa-box agri-nav-icon"></i> 
                            My Products
                        </a>
                    </li>
                    <li class="agri-nav-item active" data-page="wholesaler">
                        <a href="warehouses.php" class="agri-nav-link">
                            <i class="fas fa-warehouse agri-nav-icon"></i> 
                            wholesaler
                        </a>
                    </li>
                    <li class="agri-nav-item" data-page="profile">
                        <a href="#" class="agri-nav-link">
                            <i class="fas fa-user agri-nav-icon"></i> 
                            Profile
                        </a>
                    </li>
                    <li class="agri-nav-item" data-page="orders">
                        <a href="order.php" class="agri-nav-link">
                            <i class="fas fa-clipboard-list agri-nav-icon"></i> 
                            Orders
                        </a>
                    </li>
                    <li class="agri-nav-item" data-page="weather">
                        <a href="#" class="agri-nav-link">
                            <i class="fas fa-cloud-sun agri-nav-icon"></i> 
                            Weather
                        </a>
                    </li>
                    <li class="agri-nav-item">
                        <a href="logout.php" class="agri-nav-link">
                            <i class="fas fa-sign-out-alt agri-nav-icon"></i> 
                            Logout
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="agri-main-content">
            <div class="agri-warehouse-container">
                <h1 class="agri-main-title"><i class="fas fa-warehouse"></i> Warehouse Management</h1>
                
                <?php echo $message; ?>
                
                <div class="agri-warehouse-row">
                    <div class="agri-form-card">
                        <h2><i class="fas fa-plus-circle"></i> Add New Warehouse</h2>
                        <form method="POST">
                            <input type="hidden" name="add_warehouse" value="1">
                            
                            <div class="agri-form-group">
                                <label><i class="fas fa-warehouse"></i> Warehouse Name</label>
                                <input type="text" name="name" required>
                            </div>
                            
                            <div class="agri-form-group">
                                <label><i class="fas fa-map-marker-alt"></i> Location</label>
                                <input type="text" name="location" required>
                            </div>
                            
                            <div class="agri-form-group">
                                <label><i class="fas fa-ruler-combined"></i> Capacity (sq ft)</label>
                                <input type="number" name="capacity" required>
                            </div>
                            
                            <div class="agri-form-group">
                                <label><i class="fas fa-phone"></i> Contact Info</label>
                                <input type="text" name="contact_info">
                            </div>
                            
                            <div class="agri-form-group">
                                <label><i class="fas fa-tag"></i> Warehouse Type</label>
                                <select name="warehouse_type" class="agri-form-select">
                                    <option value="">Select Type</option>
                                    <option value="Dry Storage">Dry Storage</option>
                                    <option value="Refrigerated">Refrigerated</option>
                                    <option value="Frozen">Frozen</option>
                                    <option value="Controlled Atmosphere">Controlled Atmosphere</option>
                                </select>
                            </div>
                            
                            <div class="agri-form-group">
                                <label><i class="fas fa-calendar-check"></i> Last Inspection Date</label>
                                <input type="date" name="last_inspection_date">
                            </div>
                            
                            <button type="submit" class="agri-btn agri-btn-success">
                                <i class="fas fa-save"></i> Save Warehouse
                            </button>
                        </form>
                    </div>


                    <div class="agri-table-card">
                        <h2><i class="fas fa-table"></i> Existing Warehouses</h2>
                        
                        <?php if (count($warehouses) > 0): ?>
                        <div class="agri-table-responsive">
                            <table class="agri-warehouse-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Location</th>
                                        <th>Capacity</th>
                                        <th>Type</th>
                                        <th>Last Inspected</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($warehouses as $warehouse): ?>
                                    <tr>
                                        <td><?= $warehouse['id'] ?></td>
                                        <td><?= htmlspecialchars($warehouse['name']) ?></td>
                                        <td><?= htmlspecialchars($warehouse['location']) ?></td>
                                        <td><?= number_format($warehouse['capacity']) ?> sq ft</td>
                                        <td><?= htmlspecialchars($warehouse['warehouse_type']) ?></td>
                                        <td><?= $warehouse['last_inspection_date'] ? date('M d, Y', strtotime($warehouse['last_inspection_date'])) : 'N/A' ?></td>
                                        <td class="agri-actions">
                                            <a href="edit_warehouse.php?id=<?= $warehouse['id'] ?>" class="agri-btn agri-btn-sm agri-btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="warehouse_id" value="<?= $warehouse['id'] ?>">
                                                <input type="hidden" name="delete_warehouse" value="1">
                                                <button type="submit" class="agri-btn agri-btn-sm agri-btn-danger" onclick="return confirm('Are you sure you want to delete this warehouse?');">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="agri-empty-state">
                            <i class="fas fa-warehouse"></i>
                            <p>No warehouses found. Add your first warehouse!</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="js/script.js"></script>
</body>
</html>