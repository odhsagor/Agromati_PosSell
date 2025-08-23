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
$direction = isset($_GET['direction']) ? $_GET['direction'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Build query for stock movements
$sql = "SELECT 
            rsm.id,
            rsm.direction,
            rsm.quantity,
            rsm.unit_price,
            rsm.source_type,
            rsm.source_id,
            rsm.created_at,
            p.name as product_name,
            p.type as product_type,
            p.unit_of_measure,
            CASE 
                WHEN rsm.source_type = 'order_item' THEN 'Purchase from Farmer'
                WHEN rsm.source_type = 'sale' THEN CONCAT('Sale #', rsm.source_id)
                ELSE rsm.source_type
            END as description
        FROM retailer_stock_moves rsm
        JOIN products p ON p.id = rsm.product_id
        WHERE rsm.retailer_id = ?";

$params = [];
$types = "i";
$params[] = &$retailer_id;

if (!empty($search)) {
    $sql .= " AND (p.name LIKE ? OR p.type LIKE ?)";
    $types .= "ss";
    $like_search = "%$search%";
    $params[] = &$like_search;
    $params[] = &$like_search;
}

if (!empty($direction) && in_array($direction, ['in', 'out'])) {
    $sql .= " AND rsm.direction = ?";
    $types .= "s";
    $params[] = &$direction;
}

if (!empty($start_date)) {
    $sql .= " AND DATE(rsm.created_at) >= ?";
    $types .= "s";
    $params[] = &$start_date;
}

if (!empty($end_date)) {
    $sql .= " AND DATE(rsm.created_at) <= ?";
    $types .= "s";
    $params[] = &$end_date;
}

$sql .= " ORDER BY rsm.created_at DESC, rsm.id DESC";

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
$movements = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate totals
$total_in = 0;
$total_out = 0;
$total_in_value = 0;
$total_out_value = 0;

foreach ($movements as $move) {
    $value = (float)$move['quantity'] * (float)$move['unit_price'];
    if ($move['direction'] === 'in') {
        $total_in += (int)$move['quantity'];
        $total_in_value += $value;
    } else {
        $total_out += (int)$move['quantity'];
        $total_out_value += $value;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Movement - AGROMATI</title>
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
        .in-badge {
            background-color: #198754;
        }
        .out-badge {
            background-color: #dc3545;
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
                <li class="agri-nav-item" data-page="pos_sales">
                    <a href="retailer_posSell.php" class="agri-nav-link">
                        <i class="fas fa-history agri-nav-icon"></i> POS Sales History
                    </a>
                </li>
                <li class="agri-nav-item active" data-page="stock">
                    <a href="retailer_stock.php" class="agri-nav-link active">
                        <i class="fas fa-warehouse agri-nav-icon"></i> Stock Movement
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
                <h2>Stock Movement History</h2>
                <a href="retailer_pos.php" class="btn btn-primary">
                    <i class="fas fa-cash-register me-1"></i> New Sale
                </a>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card summary-card-in">
                        <div class="card-body">
                            <h5 class="card-title">Stock In</h5>
                            <p class="card-text display-6"><?= number_format($total_in) ?></p>
                            <small class="text-muted">Value: ৳<?= number_format($total_in_value, 2) ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card summary-card-out">
                        <div class="card-body">
                            <h5 class="card-title">Stock Out</h5>
                            <p class="card-text display-6"><?= number_format($total_out) ?></p>
                            <small class="text-muted">Value: ৳<?= number_format($total_out_value, 2) ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card summary-card-net">
                        <div class="card-body">
                            <h5 class="card-title">Net Movement</h5>
                            <p class="card-text display-6"><?= number_format($total_in - $total_out) ?></p>
                            <small class="text-muted">Net Value: ৳<?= number_format($total_in_value - $total_out_value, 2) ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Transactions</h5>
                            <p class="card-text display-6"><?= count($movements) ?></p>
                            <small class="text-muted">Total movements recorded</small>
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
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search Products</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Product name or type" value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="direction" class="form-label">Direction</label>
                            <select class="form-select" id="direction" name="direction">
                                <option value="">All</option>
                                <option value="in" <?= $direction === 'in' ? 'selected' : '' ?>>Stock In</option>
                                <option value="out" <?= $direction === 'out' ? 'selected' : '' ?>>Stock Out</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $start_date ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $end_date ?>">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                            <a href="retailer_stock.php" class="btn btn-outline-secondary">Clear</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Stock Movements Table -->
            <div class="card">
                <div class="card-body">
                    <?php if (empty($movements)): ?>
                        <div class="alert alert-info text-center">
                            No stock movements found.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Product</th>
                                        <th>Type</th>
                                        <th>Direction</th>
                                        <th class="text-end">Quantity</th>
                                        <th class="text-end">Unit Price</th>
                                        <th class="text-end">Total Value</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($movements as $move): 
                                        $total_value = (float)$move['quantity'] * (float)$move['unit_price'];
                                    ?>
                                        <tr>
                                            <td><?= date('M j, Y g:i A', strtotime($move['created_at'])) ?></td>
                                            <td><?= htmlspecialchars($move['product_name']) ?></td>
                                            <td><?= htmlspecialchars($move['product_type']) ?></td>
                                            <td>
                                                <span class="badge <?= $move['direction'] === 'in' ? 'in-badge' : 'out-badge' ?>">
                                                    <?= strtoupper($move['direction']) ?>
                                                </span>
                                            </td>
                                            <td class="text-end"><?= number_format($move['quantity']) ?> <?= htmlspecialchars($move['unit_of_measure']) ?></td>
                                            <td class="text-end">৳<?= number_format($move['unit_price'], 2) ?></td>
                                            <td class="text-end">৳<?= number_format($total_value, 2) ?></td>
                                            <td><?= htmlspecialchars($move['description']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="4" class="text-end">Totals:</th>
                                        <th class="text-end"><?= number_format($total_in - $total_out) ?></th>
                                        <th class="text-end"></th>
                                        <th class="text-end">৳<?= number_format($total_in_value - $total_out_value, 2) ?></th>
                                        <th></th>
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