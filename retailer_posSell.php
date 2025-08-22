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
$stmt = $db->prepare("SELECT id, name FROM retailers WHERE retailer_id = ? LIMIT 1");
$stmt->bind_param("s", $_SESSION['retailer_id']);
$stmt->execute();
$retRow = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$retRow) {
    die("Retailer not found.");
}
$retailer_id = (int)$retRow['id'];
$retailer_name = $retRow['name'];

// Handle search and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Build query for sales history
$sql = "SELECT 
            rs.id, 
            rs.customer_name, 
            rs.customer_phone, 
            rs.total_items, 
            rs.total_amount, 
            rs.created_at,
            COUNT(rsi.id) as item_count
        FROM retailer_sales rs
        LEFT JOIN retailer_sale_items rsi ON rs.id = rsi.sale_id
        WHERE rs.retailer_id = ?";

$params = [];
$types = "i";
$params[] = &$retailer_id;

if (!empty($search)) {
    $sql .= " AND (rs.id = ? OR rs.customer_name LIKE ? OR rs.customer_phone LIKE ?)";
    $types .= "iss";
    $search_param = is_numeric($search) ? (int)$search : $search;
    $params[] = &$search_param;
    $like_search = "%$search%";
    $params[] = &$like_search;
    $params[] = &$like_search;
}

if (!empty($start_date)) {
    $sql .= " AND DATE(rs.created_at) >= ?";
    $types .= "s";
    $params[] = &$start_date;
}

if (!empty($end_date)) {
    $sql .= " AND DATE(rs.created_at) <= ?";
    $types .= "s";
    $params[] = &$end_date;
}

$sql .= " GROUP BY rs.id ORDER BY rs.created_at DESC, rs.id DESC";

// Prepare and execute query
$stmt = $db->prepare($sql);

// Bind parameters dynamically
if (!empty($params)) {
    $bind_params = [$types];
    for ($i = 0; $i < count($params); $i++) {
        $bind_params[] = &$params[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_params);
}

$stmt->execute();
$result = $stmt->get_result();
$sales = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate totals for display
$total_sales = 0;
$total_items = 0;
foreach ($sales as $sale) {
    $total_sales += (float)$sale['total_amount'];
    $total_items += (int)$sale['total_items'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>POS Sales History - AGROMATI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/retailer_styles.css">
    <style>
        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.075);
        }
        .badge-pill {
            border-radius: 10rem;
        }
        .summary-card {
            border-left: 4px solid #0d6efd;
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
                <li class="agri-nav-item" data-page="dashboard">
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
                    </a>
                </li>
                <li class="agri-nav-item active" data-page="pos_sales">
                    <a href="retailer_posSell.php" class="agri-nav-link active">
                        <i class="fas fa-history agri-nav-icon"></i> POS Sales History
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>POS Sales History</h2>
                <a href="retailer_pos.php" class="btn btn-primary">
                    <i class="fas fa-cash-register me-1"></i> New Sale
                </a>
            </div>

            <?php if (isset($_SESSION['pos_sales_msg'])): ?>
                <div class="alert alert-success"><?= $_SESSION['pos_sales_msg'];
                unset($_SESSION['pos_sales_msg']); ?></div>
            <?php endif; ?>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card summary-card">
                        <div class="card-body">
                            <h5 class="card-title">Total Sales</h5>
                            <p class="card-text display-6">৳<?= number_format($total_sales, 2) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card summary-card">
                        <div class="card-body">
                            <h5 class="card-title">Transactions</h5>
                            <p class="card-text display-6"><?= count($sales) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card summary-card">
                        <div class="card-body">
                            <h5 class="card-title">Items Sold</h5>
                            <p class="card-text display-6"><?= $total_items ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Form -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Search & Filter</h5>
                </div>
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Sale ID, Customer Name or Phone" value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $start_date ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $end_date ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">Search</button>
                            <a href="retailer_posSell.php" class="btn btn-outline-secondary">Clear</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sales Table -->
            <div class="card">
                <div class="card-body">
                    <?php if (empty($sales)): ?>
                        <div class="alert alert-info text-center">
                            No sales records found.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Sale ID</th>
                                        <th>Customer</th>
                                        <th>Phone</th>
                                        <th class="text-center">Items</th>
                                        <th class="text-end">Amount</th>
                                        <th>Date</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sales as $sale): ?>
                                        <tr>
                                            <td>#<?= $sale['id'] ?></td>
                                            <td><?= !empty($sale['customer_name']) ? htmlspecialchars($sale['customer_name']) : '<span class="text-muted">Walk-in</span>' ?></td>
                                            <td><?= !empty($sale['customer_phone']) ? htmlspecialchars($sale['customer_phone']) : '<span class="text-muted">N/A</span>' ?></td>
                                            <td class="text-center"><?= $sale['total_items'] ?></td>
                                            <td class="text-end">৳<?= number_format($sale['total_amount'], 2) ?></td>
                                            <td><?= date('M j, Y g:i A', strtotime($sale['created_at'])) ?></td>
                                            <td class="text-center">
                                                <a href="retailer_sales_receipt.php?sale_id=<?= $sale['id'] ?>" 
                                                   class="btn btn-sm btn-outline-primary" title="View Receipt">
                                                    <i class="fas fa-receipt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="3" class="text-end">Total:</th>
                                        <th class="text-center"><?= $total_items ?></th>
                                        <th class="text-end">৳<?= number_format($total_sales, 2) ?></th>
                                        <th colspan="2"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Set max date for end_date to today
    document.addEventListener('DOMContentLoaded', function() {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('end_date').max = today;
        
        // If start_date is set, set min for end_date to start_date
        const startDate = document.getElementById('start_date');
        const endDate = document.getElementById('end_date');
        
        startDate.addEventListener('change', function() {
            endDate.min = this.value;
        });
    });
</script>
</body>
</html>