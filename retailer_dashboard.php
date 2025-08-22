<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: retailer_login.php");
    exit();
}

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'agromatiDB';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$stmt = $conn->prepare("SELECT name, email, phone, created_at FROM retailers WHERE retailer_id = ?");
$stmt->bind_param("s", $_SESSION['retailer_id']);
$stmt->execute();
$result = $stmt->get_result();
$retailer = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retailer Dashboard - AGROMATI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/retailer_styles.css">
</head>
<body>
    <div class="agri-container">
        <aside class="agri-sidebar">
            <div class="agri-sidebar-header">
                <img src="Image/logo.png" alt="AGROMATI Logo" class="logo-image">
            </div>
            <nav class="agri-sidebar-nav">
                <ul>
                    <li class="agri-nav-item active" data-page="dashboard">
                        <a href="retailer_dashboard.php" class="agri-nav-link">
                            <i class="fas fa-tachometer-alt agri-nav-icon"></i> 
                            Dashboard
                        </a>
                    </li>
                    <li class="agri-nav-item" data-page="Shop">
                        <a href="shop.php" class="agri-nav-link">
                            <i class="fas fa-users agri-nav-icon"></i> 
                            Shop
                        </a>
                    </li>
                    <li class="agri-nav-item" data-page="Cart">
                        <a href="cart.php" class="agri-nav-link">
                            <i class="fas fa-clipboard-list agri-nav-icon"></i> 
                            Cart
                        </a>
                    </li>
                    <li class="agri-nav-item" data-page="My orders">
                        <a href="retailer_orders.php" class="agri-nav-link">
                            <i class="fas fa-clipboard-list agri-nav-icon"></i> 
                            Orders
                        </a>
                    </li>
                    <li class="agri-nav-item" data-page="pos">
                        <a href="retailer_pos.php" class="agri-nav-link">
                            <i class="fas fa-cash-register agri-nav-icon"></i> POS (Sell)
                        </a>
                    </li>
                    <li class="agri-nav-item" data-page="pos_cart">
                        <a href="pos_cart.php" class="agri-nav-link">
                            <i class="fas fa-shopping-cart agri-nav-icon"></i> POS Cart
                            <?php if ($cartCount > 0): ?>
                            <span class="badge bg-success badge-pill ms-2"><?= $cartCount ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="agri-nav-item">
                        <a href="retailer_posSell.php" class="agri-nav-link">
                            <i class="fas fa-sign-out-alt agri-nav-icon"></i> 
                            Pos Sell
                        </a>
                    </li>
                    <li class="agri-nav-item">
                        <a href="retailer_logout.php" class="agri-nav-link">
                            <i class="fas fa-sign-out-alt agri-nav-icon"></i> 
                            Logout
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="agri-main-content">
            <div class="container py-4">
                <div class="agri-card mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3>Welcome, <?php echo htmlspecialchars($retailer['name']); ?></h3>
                            <p>Retailer ID: <?php echo htmlspecialchars($_SESSION['retailer_id']); ?></p>
                        </div>
                        <div class="text-end">
                            <p class="mb-1"><small>Member since: <?= date('F j, Y', strtotime($retailer['created_at'])) ?></small></p>
                            <span class="badge bg-success">Active</span>
                        </div>
                    </div>
                </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.agri-nav-item').forEach(item => {
            if (item.classList.contains('active')) {
                item.querySelector('.agri-nav-link').classList.add('active');
            }
        });
    </script>
</body>
</html>