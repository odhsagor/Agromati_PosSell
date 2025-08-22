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
    if (isset($_POST['add_harvest'])) {
        $stmt = $conn->prepare("INSERT INTO harvests (user_id, harvest_date, quantity, harvest_type, production_cost, land_acreage, seed_requirement, harvest_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssss", $userId, $_POST['harvest_date'], $_POST['quantity'], $_POST['harvest_type'], $_POST['production_cost'], $_POST['land_acreage'], $_POST['seed_requirement'], $_POST['harvest_time']);
        if ($stmt->execute()) {
            $message = '<div class="agri-alert agri-alert-success">Harvest added successfully!</div>';
        } else {
            $message = '<div class="agri-alert agri-alert-danger">Error adding harvest: ' . $conn->error . '</div>';
        }
        $stmt->close();
    }
    elseif (isset($_POST['update_harvest'])) {
        $stmt = $conn->prepare("UPDATE harvests SET harvest_date=?, quantity=?, harvest_type=?, production_cost=?, land_acreage=?, seed_requirement=?, harvest_time=? WHERE id=? AND user_id=?");
        $stmt->bind_param("sssssssii", $_POST['harvest_date'], $_POST['quantity'], $_POST['harvest_type'], $_POST['production_cost'], $_POST['land_acreage'], $_POST['seed_requirement'], $_POST['harvest_time'], $_POST['harvest_id'], $userId);
        if ($stmt->execute()) {
            $message = '<div class="agri-alert agri-alert-success">Harvest updated successfully!</div>';
        } else {
            $message = '<div class="agri-alert agri-alert-danger">Error updating harvest: ' . $conn->error . '</div>';
        }
        $stmt->close();
    }

    elseif (isset($_POST['delete_harvest'])) {
        $stmt = $conn->prepare("DELETE FROM harvests WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $_POST['harvest_id'], $userId);
        if ($stmt->execute()) {
            $message = '<div class="agri-alert agri-alert-success">Harvest deleted successfully!</div>';
        } else {
            $message = '<div class="agri-alert agri-alert-danger">Error deleting harvest: ' . $conn->error . '</div>';
        }
        $stmt->close();
    }
}

$stmt = $conn->prepare("SELECT * FROM harvests WHERE user_id = ? ORDER BY harvest_date DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$harvests = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Harvest Management - AGROMATI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/harvests.css">
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
                    <li class="agri-nav-item active" data-page="harvests">
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
                    <li class="agri-nav-item" data-page="wholesaler">
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
            <div class="agri-harvest-container">
                <h1 class="agri-main-title"><i class="fas fa-seedling"></i> Harvest Management</h1>
                
                <?php echo $message; ?>
                
                <div class="agri-harvest-row">
                    <div class="agri-form-card">
                        <h2><i class="fas fa-plus-circle"></i> 
                            <?= isset($_GET['edit']) ? 'Edit Harvest Record' : 'Add New Harvest Record' ?>
                        </h2>
                        <form method="POST">
                            <?php if (isset($_GET['edit'])): 
                                $editId = $_GET['edit'];
                                $editHarvest = array_filter($harvests, function($h) use ($editId) { return $h['id'] == $editId; });
                                if (!empty($editHarvest)) {
                                    $editHarvest = reset($editHarvest);
                                }
                            ?>
                                <input type="hidden" name="harvest_id" value="<?= $editId ?>">
                                <input type="hidden" name="update_harvest" value="1">
                            <?php else: ?>
                                <input type="hidden" name="add_harvest" value="1">
                            <?php endif; ?>
                            
                            <div class="agri-form-group">
                                <label><i class="fas fa-calendar-alt"></i> Harvest Date</label>
                                <input type="date" name="harvest_date" value="<?= isset($editHarvest) ? $editHarvest['harvest_date'] : '' ?>" required>
                            </div>
                            
                            <div class="agri-form-group">
                                <label><i class="fas fa-weight-hanging"></i> Quantity Harvested</label>
                                <input type="text" name="quantity" placeholder="e.g., 100 kg" value="<?= isset($editHarvest) ? htmlspecialchars($editHarvest['quantity']) : '' ?>" required>
                            </div>
                            
                            <div class="agri-form-group">
                                <label><i class="fas fa-leaf"></i> Harvest Type</label>
                                <input type="text" name="harvest_type" placeholder="e.g., Organic" value="<?= isset($editHarvest) ? htmlspecialchars($editHarvest['harvest_type']) : '' ?>" required>
                            </div>
                            
                            <div class="agri-form-group">
                                <label><i class="fas fa-dollar-sign"></i> Production Cost</label>
                                <input type="text" name="production_cost" placeholder="e.g., $500" value="<?= isset($editHarvest) ? htmlspecialchars($editHarvest['production_cost']) : '' ?>" required>
                            </div>
                            
                            <div class="agri-form-group">
                                <label><i class="fas fa-tractor"></i> Land Acreage</label>
                                <input type="text" name="land_acreage" placeholder="e.g., 5 acres" value="<?= isset($editHarvest) ? htmlspecialchars($editHarvest['land_acreage']) : '' ?>" required>
                            </div>
                            
                            <div class="agri-form-group">
                                <label><i class="fas fa-seedling"></i> Per Acre Seed Requirement</label>
                                <input type="text" name="seed_requirement" placeholder="e.g., 10 kg/acre" value="<?= isset($editHarvest) ? htmlspecialchars($editHarvest['seed_requirement']) : '' ?>" required>
                            </div>
                            
                            <div class="agri-form-group">
                                <label><i class="fas fa-clock"></i> Time of Harvest</label>
                                <select name="harvest_time" class="agri-form-select" required>
                                    <option value="">Select Time</option>
                                    <option value="Morning" <?= (isset($editHarvest) && $editHarvest['harvest_time'] == 'Morning') ? 'selected' : '' ?>>Morning</option>
                                    <option value="Afternoon" <?= (isset($editHarvest) && $editHarvest['harvest_time'] == 'Afternoon') ? 'selected' : '' ?>>Afternoon</option>
                                    <option value="Evening" <?= (isset($editHarvest) && $editHarvest['harvest_time'] == 'Evening') ? 'selected' : '' ?>>Evening</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="agri-btn agri-btn-success">
                                <i class="fas fa-save"></i> <?= isset($_GET['edit']) ? 'Update Record' : 'Save Record' ?>
                            </button>
                            
                            <?php if (isset($_GET['edit'])): ?>
                                <a href="harvests.php" class="agri-btn agri-btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <!-- Harvest Records Table -->
                    <div class="agri-table-card">
                        <h2><i class="fas fa-table"></i> Harvest Records</h2>
                        
                        <?php if (count($harvests) > 0): ?>
                        <div class="agri-table-responsive">
                            <table class="agri-harvest-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Quantity</th>
                                        <th>Type</th>
                                        <th>Cost</th>
                                        <th>Land</th>
                                        <th>Seeds</th>
                                        <th>Time</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($harvests as $harvest): ?>
                                    <tr>
                                        <td><?= date('M d, Y', strtotime($harvest['harvest_date'])) ?></td>
                                        <td><?= htmlspecialchars($harvest['quantity']) ?></td>
                                        <td><?= htmlspecialchars($harvest['harvest_type']) ?></td>
                                        <td><?= htmlspecialchars($harvest['production_cost']) ?></td>
                                        <td><?= htmlspecialchars($harvest['land_acreage']) ?></td>
                                        <td><?= htmlspecialchars($harvest['seed_requirement']) ?></td>
                                        <td><?= htmlspecialchars($harvest['harvest_time']) ?></td>
                                        <td class="agri-actions">
                                            <a href="harvests.php?edit=<?= $harvest['id'] ?>" class="agri-btn agri-btn-sm agri-btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="harvest_id" value="<?= $harvest['id'] ?>">
                                                <input type="hidden" name="delete_harvest" value="1">
                                                <button type="submit" class="agri-btn agri-btn-sm agri-btn-danger" onclick="return confirm('Are you sure you want to delete this harvest record?');">
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
                            <i class="fas fa-inbox"></i>
                            <p>No harvest records found. Add your first harvest!</p>
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