<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: retailer_login.php");
    exit();
}

$db = new mysqli('localhost', 'root', '', 'agromatiDB');
if ($db->connect_error) {
    die("DB connection failed: " . $db->connect_error);
}

// Get retailer info
$stmt = $db->prepare("SELECT id, name, created_at FROM retailers WHERE retailer_id = ? LIMIT 1");
$stmt->bind_param("s", $_SESSION['retailer_id']);
$stmt->execute();
$retailer = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$retailer) {
    die("Retailer not found.");
}
$retailer_id = (int)$retailer['id'];

// Get POS sales summary
$sales_sql = "SELECT 
                COUNT(id) as transaction_count,
                SUM(total_items) as total_items_sold,
                SUM(total_amount) as total_sales_amount
            FROM retailer_sales 
            WHERE retailer_id = ?";
$stmt = $db->prepare($sales_sql);
$stmt->bind_param("i", $retailer_id);
$stmt->execute();
$sales_summary = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get stock movement summary
$stock_sql = "SELECT 
                SUM(CASE WHEN direction = 'in' THEN quantity ELSE 0 END) as total_stock_in,
                SUM(CASE WHEN direction = 'in' THEN quantity * unit_price ELSE 0 END) as total_stock_in_value,
                SUM(CASE WHEN direction = 'out' THEN quantity ELSE 0 END) as total_stock_out,
                SUM(CASE WHEN direction = 'out' THEN quantity * unit_price ELSE 0 END) as total_stock_out_value,
                COUNT(id) as total_transactions
            FROM retailer_stock_moves 
            WHERE retailer_id = ?";
$stmt = $db->prepare($stock_sql);
$stmt->bind_param("i", $retailer_id);
$stmt->execute();
$stock_summary = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Calculate net values
$net_movement = ($stock_summary['total_stock_in'] ?? 0) - ($stock_summary['total_stock_out'] ?? 0);
$net_value = ($stock_summary['total_stock_in_value'] ?? 0) - ($stock_summary['total_stock_out_value'] ?? 0);

// Get POS cart count
$cartCount = 0;
if (isset($_SESSION['pos_cart'])) {
    foreach ($_SESSION['pos_cart'] as $line) {
        $cartCount += (int)$line['qty'];
    }
}
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
    <style>
        .summary-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        .summary-card:hover {
            transform: translateY(-5px);
        }
        .summary-card-in {
            border-left: 4px solid #198754;
        }
        .summary-card-out {
            border-left: 4px solid #dc3545;
        }
        .summary-card-net {
            border-left: 4px solid #0d6efd;
        }
        .summary-card-sales {
            border-left: 4px solid #6f42c1;
        }
        .display-6 {
            font-size: 1.8rem;
            font-weight: 600;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
    </style>
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
                    <li class="agri-nav-item" data-page="pos_sales">
                        <a href="retailer_posSell.php" class="agri-nav-link">
                            <i class="fas fa-history agri-nav-icon"></i> 
                            POS Sales History
                        </a>
                    </li>
                    <li class="agri-nav-item" data-page="stock">
                        <a href="retailer_stock.php" class="agri-nav-link">
                            <i class="fas fa-warehouse agri-nav-icon"></i> 
                            Stock Movement
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
                <!-- Welcome Card -->
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

                <!-- POS Sales Summary -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h4 class="mb-3">POS Sales Summary</h4>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card summary-card summary-card-sales h-100">
                            <div class="card-body text-center">
                                <h5 class="card-title">Total Sales</h5>
                                <p class="card-text display-6">৳<?= number_format($sales_summary['total_sales_amount'] ?? 0, 2) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card summary-card summary-card-sales h-100">
                            <div class="card-body text-center">
                                <h5 class="card-title">Transactions</h5>
                                <p class="card-text display-6"><?= number_format($sales_summary['transaction_count'] ?? 0) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card summary-card summary-card-sales h-100">
                            <div class="card-body text-center">
                                <h5 class="card-title">Items Sold</h5>
                                <p class="card-text display-6"><?= number_format($sales_summary['total_items_sold'] ?? 0) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stock Movement Summary -->
                <div class="row">
                    <div class="col-md-12">
                        <h4 class="mb-3">Stock Movement Summary</h4>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card summary-card summary-card-in h-100">
                            <div class="card-body text-center">
                                <h5 class="card-title">Stock In</h5>
                                <p class="card-text display-6"><?= number_format($stock_summary['total_stock_in'] ?? 0) ?></p>
                                <small class="text-muted">Value: ৳<?= number_format($stock_summary['total_stock_in_value'] ?? 0, 2) ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card summary-card summary-card-out h-100">
                            <div class="card-body text-center">
                                <h5 class="card-title">Stock Out</h5>
                                <p class="card-text display-6"><?= number_format($stock_summary['total_stock_out'] ?? 0) ?></p>
                                <small class="text-muted">Value: ৳<?= number_format($stock_summary['total_stock_out_value'] ?? 0, 2) ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card summary-card summary-card-net h-100">
                            <div class="card-body text-center">
                                <h5 class="card-title">Net Movement</h5>
                                <p class="card-text display-6"><?= number_format($net_movement) ?></p>
                                <small class="text-muted">Net Value: ৳<?= number_format($net_value, 2) ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card summary-card h-100">
                            <div class="card-body text-center">
                                <h5 class="card-title">Transactions</h5>
                                <p class="card-text display-6"><?= number_format($stock_summary['total_transactions'] ?? 0) ?></p>
                                <small class="text-muted">Total movements recorded</small>
                            </div>
                        </div>
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