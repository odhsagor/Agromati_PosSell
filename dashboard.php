<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AGROMATI Farmer Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
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
                    <li class="agri-nav-item active" data-page="dashboard">
                        <a href="#" class="agri-nav-link">
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
                    <li class="agri-nav-item" data-page="products">
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
                        <a href="profile.php" class="agri-nav-link">
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
                        <a href="weather.php" class="agri-nav-link">
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
            <h1 class="agri-main-title">Dashboard Overview</h1>
            
            <section class="agri-card">
                <h2 class="agri-card-title">Welcome to AGROMATI Farmer Portal!</h2>
                <p class="agri-card-text">This dashboard provides a quick overview of your farming operations.</p>
                <div class="agri-user-greeting">
                    Welcome back, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>!
                    <small>(<?php echo htmlspecialchars($_SESSION['user_email']); ?>)</small>
                </div>
                
                <div class="agri-dashboard-grid">
                    <div class="agri-dashboard-card">
                        <h3 class="agri-card-subtitle">Total Harvests</h3>
                        <p class="agri-card-value">24</p>
                    </div>
                    <div class="agri-dashboard-card">
                        <h3 class="agri-card-subtitle">Products Listed</h3>
                        <p class="agri-card-value">15</p>
                    </div>
                    <div class="agri-dashboard-card">
                        <h3 class="agri-card-subtitle">Active Warehouses</h3>
                        <p class="agri-card-value">3</p>
                    </div>
                    <div class="agri-dashboard-card">
                        <h3 class="agri-card-subtitle">Pending Orders</h3>
                        <p class="agri-card-value">7</p>
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>
</html>