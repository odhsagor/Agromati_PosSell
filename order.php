<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
  header("Location: login.php"); 
  exit();
}

$farmer_id = (int)$_SESSION['user_id'];

$db = new mysqli('localhost','root','','agromatiDB');
if ($db->connect_error) {
  die("DB connection failed: " . $db->connect_error);
}
$sql = "SELECT 
          o.id AS order_id,
          o.created_at,
          r.name AS retailer_name,
          p.name AS product_name,
          oi.quantity,
          oi.status AS item_status,
          oi.subtotal
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id
        JOIN products p ON p.id = oi.product_id
        JOIN retailers r ON r.id = o.retailer_id
        WHERE oi.farmer_id = ?
        ORDER BY o.created_at DESC
        LIMIT 20"; // Limit to recent orders

$stmt = $db->prepare($sql);
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$res = $stmt->get_result();
$orders = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Orders - Farmer</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="css/order.css">
</head>
<body>
<div class="container">

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
                        <a href="#" class="agri-nav-link">
                            <i class="fas fa-user agri-nav-icon"></i> 
                            Profile
                        </a>
                    </li>
                    <li class="agri-nav-item active" data-page="orders">
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

  <main class="main-content">
    <div class="page-header">
      <h2>Incoming Orders</h2>
      <a href="dashboard.php" class="btn btn-outline">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
      </a>
    </div>

    <?php if(!empty($_SESSION['order_msg'])): ?>
      <div class="alert alert-success"><?=$_SESSION['order_msg']; unset($_SESSION['order_msg']);?></div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
      <div class="order-card">
        <p>No orders found.</p>
      </div>
    <?php else: ?>
      <?php foreach ($orders as $order): ?>
        <div class="order-card">
          <div class="order-header">
            <div>
              <span class="order-id">Order #<?= htmlspecialchars($order['order_id']) ?></span>
              <span class="order-date"><?= htmlspecialchars($order['created_at']) ?></span>
            </div>
            <span class="order-status status-<?= htmlspecialchars($order['item_status']) ?>">
              <?= htmlspecialchars($order['item_status']) ?>
            </span>
          </div>
          
          <div class="order-details">
            <div>
              <strong>Retailer:</strong> <?= htmlspecialchars($order['retailer_name']) ?><br>
              <strong>Product:</strong> <?= htmlspecialchars($order['product_name']) ?>
            </div>
            <div>
              <strong>Quantity:</strong> <?= number_format($order['quantity']) ?><br>
              <strong>Subtotal:</strong> ৳<?= number_format($order['subtotal'], 2) ?>
            </div>
          </div>
          
          <div class="order-actions">
            <form method="post" action="farmer_update_order_status.php" class="status-form">
              <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
              <select name="new_status" class="status-select" required>
                <option value="pending" <?= $order['item_status']=='pending'?'selected':'' ?>>Pending</option>
                <option value="confirmed" <?= $order['item_status']=='confirmed'?'selected':'' ?>>Confirmed</option>
                <option value="shipped" <?= $order['item_status']=='shipped'?'selected':'' ?>>Shipped</option>
                <option value="cancelled" <?= $order['item_status']=='cancelled'?'selected':'' ?>>Cancelled</option>
              </select>
              <button type="submit" class="btn btn-outline">Update</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </main>
</div>
</body>
</html>