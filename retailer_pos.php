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
$stmt = $db->prepare("SELECT id, name FROM retailers WHERE retailer_id = ? LIMIT 1");
$stmt->bind_param("s", $_SESSION['retailer_id']);
$stmt->execute();
$retRow = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$retRow) { die("Retailer not found."); }
$retailer_id   = (int)$retRow['id'];
$retailer_name = $retRow['name'];
$db->begin_transaction();
try {
    $sql = "SELECT oi.id AS oi_id, oi.product_id, oi.quantity, oi.unit_price
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            WHERE o.retailer_id = ? AND oi.status = 'shipped' AND oi.stock_added = 0";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $retailer_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $toAdd = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (!empty($toAdd)) {
        $upsert = $db->prepare("
            INSERT INTO retailer_inventory (retailer_id, product_id, qty_available, default_retail_price)
            VALUES (?,?,?,?)
            ON DUPLICATE KEY UPDATE
              qty_available = qty_available + VALUES(qty_available),
              default_retail_price = COALESCE(VALUES(default_retail_price), default_retail_price)
        ");
        $move = $db->prepare("
            INSERT INTO retailer_stock_moves (retailer_id, product_id, direction, quantity, unit_price, source_type, source_id)
            VALUES (?,?, 'in', ?, ?, 'order_item', ?)
        ");
        $mark = $db->prepare("UPDATE order_items SET stock_added = 1 WHERE id = ?");

        foreach ($toAdd as $row) {
            $pid   = (int)$row['product_id'];
            $qty   = (int)$row['quantity'];
            $price = (float)$row['unit_price'];

            $upsert->bind_param("iiid", $retailer_id, $pid, $qty, $price);
            $upsert->execute();

            $move->bind_param("iiidi", $retailer_id, $pid, $qty, $price, $row['oi_id']);
            $move->execute();

            $mark->bind_param("i", $row['oi_id']);
            $mark->execute();
        }
        $upsert->close();
        $move->close();
        $mark->close();
    }

    $db->commit();
} catch (Exception $e) {
    $db->rollback();
    $_SESSION['pos_err'] = "Inventory sync skipped.";
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$sql = "SELECT 
            ri.product_id, ri.qty_available, ri.default_retail_price,
            p.name, p.type, p.unit_of_measure, p.per_unit_price
        FROM retailer_inventory ri
        JOIN products p ON p.id = ri.product_id
        WHERE ri.retailer_id = ? AND ri.qty_available > 0";
if ($q !== '') {
    $sql .= " AND (p.name LIKE ? OR p.type LIKE ?)";
}
$sql .= " ORDER BY p.name ASC";

if ($q !== '') {
    $stmt = $db->prepare($sql);
    $like = "%{$q}%";
    $stmt->bind_param("iss", $retailer_id, $like, $like);
} else {
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $retailer_id);
}
$stmt->execute();
$res = $stmt->get_result();
$items = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$posCart = $_SESSION['pos_cart'] ?? [];
$cartCount = 0;
foreach ($posCart as $line) { $cartCount += (int)$line['qty']; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sell to Customer (POS) - AGROMATI</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/retailer_styles.css">
    <style>
        .pos-sticky-actions { position: sticky; top: 0; z-index: 10; background: #fff; padding: .75rem 0; }
        .badge-pill { border-radius: 999px; }
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
                    <li class="agri-nav-item active" data-page="pos">
                        <a href="retailer_pos.php" class="agri-nav-link active">
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
                        <a href="retailer_logout.php" class="agri-nav-link">
                            <i class="fas fa-sign-out-alt agri-nav-icon"></i> 
                            Logout
                        </a>
                    </li>
                </ul>
        </nav>
    </aside>

    <!-- Main -->
    <main class="agri-main-content">
        <div class="container py-4">
            <div class="pos-sticky-actions">
                <div class="d-flex flex-wrap justify-content-between align-items-center">
                    <h3 class="mb-2 mb-md-0">Sell to Customer (POS)</h3>
                    <div class="d-flex gap-2">
                        <a class="btn btn-outline-secondary" href="retailer_orders.php">
                            <i class="fas fa-list me-1"></i> My Orders
                        </a>
                        <a class="btn btn-success" href="pos_cart.php">
                            <i class="fas fa-shopping-cart me-1"></i> POS Cart
                            <?php if ($cartCount > 0): ?>
                              <span class="badge bg-light text-dark ms-1"><?= $cartCount ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>

                <form class="row g-2 mt-3" method="get">
                    <div class="col-md-6">
                        <input name="q" class="form-control" placeholder="Search product name or type..."
                               value="<?= htmlspecialchars($q) ?>">
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-outline-secondary"><i class="fas fa-search me-1"></i>Search</button>
                    </div>
                    <div class="col-auto">
                        <a class="btn btn-outline-dark" href="retailer_pos.php"><i class="fas fa-undo me-1"></i>Clear</a>
                    </div>
                </form>
            </div>
            <?php if (!empty($_SESSION['pos_msg'])): ?>
                <div class="alert alert-success mt-3"><?= $_SESSION['pos_msg']; unset($_SESSION['pos_msg']); ?></div>
            <?php endif; ?>
            <?php if (!empty($_SESSION['pos_err'])): ?>
                <div class="alert alert-danger mt-3"><?= $_SESSION['pos_err']; unset($_SESSION['pos_err']); ?></div>
            <?php endif; ?>
            <div class="row mt-3">
                <?php foreach ($items as $it): 
                    $suggested = $it['default_retail_price'] !== null
                        ? number_format((float)$it['default_retail_price'], 2, '.', '')
                        : number_format((float)$it['per_unit_price'], 2, '.', '');
                ?>
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title mb-1"><?= htmlspecialchars($it['name']) ?></h5>
                                <small class="text-muted mb-2"><?= htmlspecialchars($it['type']) ?></small>

                                <div class="mb-1"><strong>Available:</strong>
                                    <?= number_format($it['qty_available']) ?> 
                                </div>
                                <div class="mb-3"><strong>Suggested Price:</strong> ৳<?= number_format((float)$suggested, 2) ?></div>

                                <form class="mt-auto" method="post" action="pos_add_to_cart.php">
                                    <input type="hidden" name="product_id" value="<?= (int)$it['product_id'] ?>">
                                    <div class="mb-2">
                                        <label class="form-label">Quantity (max <?= (int)$it['qty_available'] ?>)</label>
                                        <input type="number" name="quantity" class="form-control" min="1"
                                               max="<?= (int)$it['qty_available'] ?>" value="1" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">
                                            Sell Price (৳ per <?= htmlspecialchars($it['unit_of_measure']) ?>)
                                        </label>
                                        <input type="number" step="0.01" min="0.01" name="unit_price"
                                               class="form-control" value="<?= $suggested ?>" required>
                                    </div>
                                    <button class="btn btn-success w-100">
                                        <i class="fas fa-cart-plus me-1"></i> Add to POS Cart
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($items)): ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            No in-stock items. When farmers mark your order items as <strong>shipped</strong>,
                            they’ll sync here automatically.
                        </div>
                    </div>
                <?php endif; ?>
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
