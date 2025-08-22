<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
  header("Location: retailer_login.php"); exit();
}

if (!isset($_GET['order_id'])) { header("Location: retailer_orders.php"); exit(); }
$order_id = (int)$_GET['order_id'];

$db = new mysqli('localhost','root','','agromatiDB');
if ($db->connect_error) { die("DB error: ".$db->connect_error); }
$stmt = $db->prepare("SELECT id, name FROM retailers WHERE retailer_id = ? LIMIT 1");
$stmt->bind_param("s", $_SESSION['retailer_id']);
$stmt->execute(); $retRow = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$retRow) { die("Retailer not found."); }
$retailer_id = (int)$retRow['id'];
$stmt = $db->prepare("SELECT id, total_amount, status, created_at FROM orders WHERE id=? AND retailer_id=? LIMIT 1");
$stmt->bind_param("ii", $order_id, $retailer_id);
$stmt->execute(); $order = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$order) { die("Order not found."); }

$sql = "SELECT 
          oi.id AS order_item_id,
          oi.quantity, oi.unit_price, oi.subtotal, oi.status AS item_status,
          p.name AS product_name, p.unit_of_measure,
          f.id AS farmer_id, f.name AS farmer_name, f.phone AS farmer_phone
        FROM order_items oi
        JOIN products p ON p.id = oi.product_id
        JOIN users f ON f.id = oi.farmer_id
        WHERE oi.order_id = ?
        ORDER BY oi.id ASC";
$stmt = $db->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute(); $res = $stmt->get_result();
$items = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function itemProgress($s) {
  if ($s==='shipped') return 100;
  if ($s==='confirmed') return 50;
  return 0; 
}
$progress = 0;
$activeCount = 0;
foreach ($items as $it) {
  if ($it['item_status'] !== 'cancelled') {
    $progress += itemProgress($it['item_status']);
    $activeCount++;
  }
}
$progress = $activeCount ? round($progress / $activeCount) : 0;

$badge = $order['status']==='pending'   ? 'bg-secondary' :
         ($order['status']==='confirmed' ? 'bg-info' :
         ($order['status']==='shipped'   ? 'bg-success' : 'bg-danger'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Order #<?= (int)$order['id'] ?> - Retailer Tracking</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/retailer_styles.css">
</head>
<body>
  <div class="agri-container">
        <aside class="agri-sidebar">
            <div class="agri-sidebar-header">
                <h2>AGROMATI</h2>
                <p>Retailer Portal</p>
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
                    <li class="agri-nav-item active" data-page="My orders">
                        <a href="retailer_orders.php" class="agri-nav-link">
                            <i class="fas fa-clipboard-list agri-nav-icon"></i> 
                            Orders
                        </a>
                    </li>
                    
                    <li class="agri-nav-item" data-page="Order view">
                        <a href="retailer_order_view.php" class="agri-nav-link">
                            <i class="fas fa-user agri-nav-icon"></i> 
                            Order view
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

<main class="flex-grow-1 p-4">
  <div class="page-shell">

    <div class="card-panel card-panel--compact rounded-xl">
      <div class="page-header">
        <h2>Your Cart</h2>
        <div>
          <a class="btn btn-outline-secondary me-2" href="shop.php">
            <i class="fas fa-store me-1"></i> Continue Shopping
          </a>
          <a class="btn btn-outline-primary" href="retailer_dashboard.php">
            <i class="fas fa-home me-1"></i> Dashboard
          </a>
        </div>
      </div>

  <div class="card mb-3">
    <div class="card-body">
      <div class="row">
        <div class="col-md-3"><strong>Placed:</strong><br><?= htmlspecialchars($order['created_at']) ?></div>
        <div class="col-md-3"><strong>Status:</strong><br><span class="badge <?= $badge ?>"><?= htmlspecialchars($order['status']) ?></span></div>
        <div class="col-md-3 text-md-end"><strong>Total:</strong><br>৳<?= number_format((float)$order['total_amount'], 2) ?></div>
        <div class="col-md-3">
          <strong>Progress</strong>
          <div class="progress mt-2">
            <div class="progress-bar" role="progressbar" style="width: <?= $progress ?>%;" aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100"></div>
          </div>
          <small class="text-muted"><?= $progress ?>% based on item statuses</small>
        </div>
      </div>
    </div>
  </div>

  <?php if (empty($items)): ?>
    <div class="alert alert-info">No items found for this order.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead class="table-light">
          <tr>
            <th>Product</th>
            <th>Farmer</th>
            <th>Contact</th>
            <th class="text-end">Qty</th>
            <th>Unit</th>
            <th class="text-end">Unit Price</th>
            <th class="text-end">Subtotal</th>
            <th>Item Status</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $it): 
          $iBadge = $it['item_status']==='pending'   ? 'bg-secondary' :
                    ($it['item_status']==='confirmed' ? 'bg-info' :
                    ($it['item_status']==='shipped'   ? 'bg-success' : 'bg-danger'));
        ?>
          <tr>
            <td><?= htmlspecialchars($it['product_name']) ?></td>
            <td><?= htmlspecialchars($it['farmer_name']) ?></td>
            <td><?= htmlspecialchars($it['farmer_phone']) ?></td>
            <td class="text-end"><?= number_format((int)$it['quantity']) ?></td>
            <td><?= htmlspecialchars($it['unit_of_measure']) ?></td>
            <td class="text-end">৳<?= number_format((float)$it['unit_price'], 2) ?></td>
            <td class="text-end">৳<?= number_format((float)$it['subtotal'], 2) ?></td>
            <td><span class="badge <?= $iBadge ?>"><?= htmlspecialchars($it['item_status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <?php if ($order['status'] === 'pending'): ?>
    <form class="mt-3 text-end" method="post" action="retailer_cancel_order.php" onsubmit="return confirm('Cancel this entire order?');">
      <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
      <button class="btn btn-outline-danger"><i class="fas fa-ban me-1"></i>Cancel Order</button>
    </form>
  <?php endif; ?>


     </div>

  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
